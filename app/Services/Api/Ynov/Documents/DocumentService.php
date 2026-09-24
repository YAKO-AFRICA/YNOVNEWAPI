<?php

namespace App\Services\Api\Ynov\Documents;

use App\Models\Api\Ynov\Esouscription\Document;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\ImageManager;

/**
 * Service d'upload de documents numérisés.
 *
 * Ce service centralise toute la logique de :
 *   - Stockage physique des fichiers (sur le disque configuré)
 *   - Compression automatique des images (jpg, jpeg, webp)
 *   - Génération de noms uniques (anti-collision)
 *   - Rollback (suppression) en cas d'erreur
 *
 * Il est appelé par DocumentController et retourne toujours un tableau
 * standardisé que le controller peut utiliser directement.
 */
class DocumentService
{
    /**
     * Nom du disque de stockage (ex: 'docnumerises').
     */
    protected string $disk;

    /**
     * Sous-dossier relatif (année/mois), ex: '2026/09'.
     */
    protected string $dossierRelatif;

    /**
     * Instance d'Intervention Image (driver GD ou Imagick).
     */
    protected ImageManager $manager;

    /**
     * Constructeur.
     *
     * Initialise le disque, le dossier du mois courant,
     * et le driver d'image (Imagick si dispo, sinon GD).
     */
    public function __construct()
    {
        $this->disk           = config('documents.disk');
        $this->dossierRelatif = now()->format('Y') . '/' . now()->format('m');
        $this->manager        = $this->creerManager();
    }

    /**
     * Crée un ou plusieurs documents en BDD + stocke les fichiers physiques.
     */
    public function createDocument(array $data = [], ?UploadedFile $file = null): mixed
    {
        $createdPaths   = [];
        $documentsCrees = [];
        $files          = [];
        $labelsByFile   = [];

        // Cas 1 : un seul fichier passé directement
        if ($file instanceof UploadedFile) {
            $files[]        = $file;
            $labelsByFile[] = $data['libelle'] ?? null;
        }

        // Cas 2 : tableau de documents
        foreach ($data['documents'] ?? [] as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $uploadedFile = $entry['file'] ?? null;

            if (!$uploadedFile instanceof UploadedFile) {
                continue;
            }

            $files[]        = $uploadedFile;
            $labelsByFile[] = $entry['libelle'] ?? null;
        }

        if (empty($files)) {
            return [];
        }

        try {
            return DB::transaction(function () use ($data, $files, $labelsByFile, &$documentsCrees, &$createdPaths) {
                foreach ($files as $index => $uploadedFile) {
                    $extension = strtolower($uploadedFile->getClientOriginalExtension());
                    $allowed   = config('documents.allowed_extensions', []);

                    if (!in_array($extension, $allowed, true)) {
                        throw new \RuntimeException("Extension non autorisée : .{$extension}");
                    }

                    $resultat      = $this->traiter($uploadedFile);
                    $cheminRelatif = $resultat['chemin_relatif'];

                    if ($cheminRelatif) {
                        $createdPaths[] = $cheminRelatif;
                    }

                    $typeDocument = $this->devinerTypeDocument($resultat['extension'], $resultat['mime_type']);
                    $libelle      = $labelsByFile[$index]
                        ?? $data['libelle']
                        ?? pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);

                    $document = Document::create([
                        'uuid_document'  => (string) Str::uuid(),
                        'reference_uuid' => $data['reference_uuid'] ?? null,
                        'nom_fichier'    => $resultat['nom_stocke'],
                        'libelle'        => $libelle,
                        'source'         => $data['source'] ?? null,
                        // ⚠️ On stocke le CHEMIN RELATIF (ex: 2026/09/fichier.pdf)
                        //    ainsi que l'URL publique complète n'est PAS stockée.
                        'chemin'         => $cheminRelatif,
                        'type_document'  => $typeDocument,
                        'taille_fichier' => $resultat['taille'],
                        'mime_type'      => $resultat['mime_type'],
                        'statut'         => 'actif',
                        'created_by'     => $data['created_by'] ?? null,
                    ]);

                    $documentsCrees[] = [
                        'document'       => $document,
                        'nom_original'   => $uploadedFile->getClientOriginalName(),
                        'nom_stocke'     => $resultat['nom_stocke'],
                        'extension'      => $resultat['extension'],
                        'mime_type'      => $resultat['mime_type'],
                        'taille'         => $resultat['taille'],
                        'compresse'      => $resultat['compresse'],
                        'dimensions'     => $resultat['dimensions'],
                        'chemin_relatif' => $cheminRelatif,
                        'url_publique'   => $resultat['url_publique'],
                    ];
                }

                return count($documentsCrees) === 1 ? $documentsCrees[0] : $documentsCrees;
            });
        } catch (\Throwable $e) {
            foreach ($createdPaths as $path) {
                if (is_string($path) && $path !== '') {
                    $this->supprimer($path);
                }
            }

            throw $e;
        }
    }

    
    /**
     * Traite un fichier : décide s'il faut compresser, stocke, retourne les infos.
     */
    public function traiter(UploadedFile $file): array
    {
        $extension   = strtolower($file->getClientOriginalExtension());
        $poidsOctets = $file->getSize();
        $poidsKo     = (int) ceil($poidsOctets / 1024);

        [$largeur, $hauteur] = $this->lireDimensions($file);

        $nomUnique = $this->genererNomUnique($file);

        $disque = Storage::disk($this->disk);

        // Création du dossier année/mois si nécessaire
        if (!$disque->exists($this->dossierRelatif)) {
            $disque->makeDirectory($this->dossierRelatif, 0755, true, true);
        }

        $compresser = config('documents.image.enabled')
            && $this->estImageCompressible($extension)
            && $this->doitEtreCompresse($largeur, $hauteur, $poidsKo);

        if ($compresser) {
            return $this->compresserImage($file, $nomUnique, $largeur, $hauteur, $extension);
        }

        return $this->copieDirecte($file, $nomUnique, $extension, $largeur, $hauteur);
    }

    /**
     * Supprime un fichier du disque (rollback).
     */
    public function supprimer(string $cheminRelatif): bool
    {
        $disque = Storage::disk($this->disk);

        if ($disque->exists($cheminRelatif)) {
            return $disque->delete($cheminRelatif);
        }

        return false;
    }

    /**
     * Devine le type de document à partir de l'extension / mime.
     */
    public function devinerTypeDocument(string $extension, string $mimeType): string
    {
        $extension = strtolower($extension);

        $mapping = [
            'pdf'  => 'pdf',
            'doc'  => 'word',   'docx' => 'word',
            'xls'  => 'excel',  'xlsx' => 'excel', 'csv' => 'csv',
            'ppt'  => 'powerpoint', 'pptx' => 'powerpoint',
            'jpg'  => 'image',  'jpeg' => 'image', 'png' => 'image',
            'gif'  => 'image',  'svg'  => 'image', 'webp' => 'image',
            'zip'  => 'archive', 'rar' => 'archive',
            'txt'  => 'texte',
        ];

        if (isset($mapping[$extension])) {
            return $mapping[$extension];
        }

        if (Str::startsWith($mimeType, 'image/')) return 'image';
        if (Str::startsWith($mimeType, 'video/')) return 'video';
        if (Str::startsWith($mimeType, 'audio/')) return 'audio';
        if (Str::startsWith($mimeType, 'text/'))  return 'texte';

        return 'autre';
    }

    // ========================================================================
    // LOGIQUE DE DÉCISION
    // ========================================================================

    protected function estImageCompressible(string $extension): bool
    {
        $extensions = config('documents.image.extensions', ['jpg', 'jpeg', 'webp']);
        return in_array($extension, $extensions, true);
    }

    protected function doitEtreCompresse(int $largeur, int $hauteur, int $poidsKo): bool
    {
        $maxWidth   = (int) config('documents.image.max_width', 1920);
        $maxHeight  = (int) config('documents.image.max_height', 1920);
        $seuilPoids = (int) config('documents.image.seuil_poids', 500);

        if ($largeur > $maxWidth || $hauteur > $maxHeight) return true;
        if ($poidsKo > $seuilPoids) return true;

        return false;
    }



    // ========================================================================
    // TRAITEMENTS
    // ========================================================================

    protected function compresserImage(
        UploadedFile $file,
        string $nomUnique,
        int $largeurOriginale,
        int $hauteurOriginale,
        string $extension
    ): array {
        try {
            $maxWidth  = (int) config('documents.image.max_width', 1920);
            $maxHeight = (int) config('documents.image.max_height', 1920);
            $quality   = (int) config('documents.image.quality', 85);

            $image = $this->manager->read($file->getRealPath());
            $image->scaleDown(width: $maxWidth, height: $maxHeight);

            $encoded = match ($extension) {
                'jpg', 'jpeg' => $image->toJpeg($quality),
                'webp'        => $image->toWebp($quality),
                default       => throw new \RuntimeException("Format non supporté : {$extension}"),
            };

            $cheminRelatif = $this->dossierRelatif . '/' . $nomUnique;

            Storage::disk($this->disk)->put($cheminRelatif, (string) $encoded);

            $nouvellesDims = $this->lireDimensionsDepuisDisque($cheminRelatif);
            $tailleFinale  = Storage::disk($this->disk)->size($cheminRelatif);

            Log::info('[DocumentService] Image compressée', [
                'nom_original'   => $file->getClientOriginalName(),
                'nom_stocke'     => $nomUnique,
                'poids_avant_ko' => (int) ceil($file->getSize() / 1024),
                'poids_apres_ko' => (int) ceil($tailleFinale / 1024),
                'dimensions'     => "{$largeurOriginale}x{$hauteurOriginale} → {$nouvellesDims['width']}x{$nouvellesDims['height']}",
            ]);

            return [
                'chemin_relatif' => $cheminRelatif,
                'url_publique'   => $this->construireUrlPublique($cheminRelatif),
                'nom_stocke'     => $nomUnique,
                'taille'         => $tailleFinale,
                'mime_type'      => $this->mimeDepuisExtension($extension),
                'extension'      => $extension,
                'compresse'      => true,
                'dimensions'     => $nouvellesDims,
            ];
        } catch (\Throwable $e) {
            Log::error('[DocumentService] Échec compression → fallback', [
                'nom_original' => $file->getClientOriginalName(),
                'erreur'       => $e->getMessage(),
            ]);

            return $this->copieDirecte($file, $nomUnique, $extension, $largeurOriginale, $hauteurOriginale);
        }
    }


    protected function copieDirecte(
        UploadedFile $file,
        string $nomUnique,
        string $extension,
        int $largeur,
        int $hauteur
    ): array {
        $cheminRelatif = $file->storeAs($this->dossierRelatif, $nomUnique, $this->disk);
        // ⚠️ AJOUTE CE BLOC
        Log::info('[DocumentService] copieDirecte', [
            'disk'            => $this->disk,
            'root'            => Storage::disk($this->disk)->path(''),
            'dossierRelatif'  => $this->dossierRelatif,
            'cheminRelatif'   => $cheminRelatif,
            'file_real_path'  => $file->getRealPath(),
            'file_exists'     => file_exists($file->getRealPath()),
            'root_is_dir'     => is_dir(Storage::disk($this->disk)->path('')),
            'root_is_writable'=> is_writable(Storage::disk($this->disk)->path('')),
        ]);

        if (!$cheminRelatif) {
            throw new \RuntimeException(
                "Échec du stockage direct dans {$this->dossierRelatif} (disque: {$this->disk})"
            );
        }

        $taille = Storage::disk($this->disk)->size($cheminRelatif);

        return [
            'chemin_relatif' => $cheminRelatif,
            'url_publique'   => $this->construireUrlPublique($cheminRelatif),
            'nom_stocke'     => $nomUnique,
            'taille'         => $taille,
            'mime_type'      => $file->getMimeType() ?? 'application/octet-stream',
            'extension'      => $extension,
            'compresse'      => false,
            'dimensions'     => ['width' => $largeur, 'height' => $hauteur],
        ];
    }

  


    // ========================================================================
    // UTILITAIRES
    // ========================================================================

    protected function genererNomUnique(UploadedFile $file): string
    {
        $nomOriginal = $file->getClientOriginalName();
        $extension   = strtolower($file->getClientOriginalExtension());
        $slug        = Str::slug(pathinfo($nomOriginal, PATHINFO_FILENAME));

        if (empty($slug)) {
            $slug = 'fichier';
        }

        return $slug . '_' . now()->format('YmdHis') . '.' . $extension;
    }

    protected function lireDimensions(UploadedFile $file): array
    {
        $infos = @getimagesize($file->getRealPath());

        if ($infos === false) {
            return [0, 0];
        }

        return [(int) $infos[0], (int) $infos[1]];
    }

    protected function lireDimensionsDepuisDisque(string $cheminRelatif): array
    {
        $cheminAbsolu = Storage::disk($this->disk)->path($cheminRelatif);
        $infos        = @getimagesize($cheminAbsolu);

        if ($infos === false) {
            return ['width' => 0, 'height' => 0];
        }

        return ['width' => (int) $infos[0], 'height' => (int) $infos[1]];
    }

    /**
     * Construit l'URL publique à partir du chemin relatif.
     * Utilise APP_URL + url_prefix (déduit de DOC_PATH).
     */
    protected function construireUrlPublique(string $cheminRelatif): string
    {
        $base   = rtrim((string) url('/'), '/');
        // $base   = rtrim((string) env('APP_URL', 'http://localhost'), '/');
        $prefix = '/' . trim((string) config('documents.url_prefix', '/docnumerises/PROD'), '/');

        return $base . $prefix . '/' . ltrim($cheminRelatif, '/');
    }

    protected function mimeDepuisExtension(string $extension): string
    {
        return match (strtolower($extension)) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png'         => 'image/png',
            'gif'         => 'image/gif',
            'webp'        => 'image/webp',
            default       => 'application/octet-stream',
        };
    }

    protected function creerManager(): ImageManager
    {
        $driver = extension_loaded('imagick')
            ? new ImagickDriver()
            : new GdDriver();

        return new ImageManager($driver);
    }
}