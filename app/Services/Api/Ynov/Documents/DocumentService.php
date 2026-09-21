<?php

namespace App\Services\Api\Ynov\Documents;

use App\Models\Api\Ynov\Esouscription\Document;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
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
// class DocumentService
// {
//     /**
//      * Nom du disque de stockage (ex: 'docnumerises').
//      */
//     protected string $disk;

//     /**
//      * Sous-dossier relatif (année/mois), ex: '2026/09'.
//      */
//     protected string $dossierRelatif;

//     /**
//      * Instance d'Intervention Image (driver GD ou Imagick).
//      */
//     protected ImageManager $manager;

//     /**
//      * Constructeur.
//      *
//      * Initialise le disque, le dossier du mois courant,
//      * et le driver d'image (Imagick si dispo, sinon GD).
//      */
//     public function __construct()
//     {
//         $this->disk           = config('documents.disk');
//         $this->dossierRelatif = now()->format('Y') . '/' . now()->format('m');
//         $this->manager        = $this->creerManager();
//     }

//     /**
//      * Liste tous les documents (avec pagination et filtres).
//      */
//     public function getDocuments(array $filters = [], int $perPage = 15): array
//     {
//         try {
//             $query = Document::query();

//             // Filtres optionnels
//             if (isset($filters['statut'])) {
//                 $query->where('statut', $filters['statut']);
//             }

//             if (isset($filters['reference_uuid'])) {
//                 $query->where('reference_uuid', $filters['reference_uuid']);
//             }

//             if (isset($filters['source'])) {
//                 $query->where('source', $filters['source']);
//             }

//             // Recherche par nom ou libellé
//             if (isset($filters['search'])) {
//                 $search = $filters['search'];
//                 $query->where(function ($q) use ($search) {
//                     $q->where('nom_fichier', 'like', "%{$search}%")
//                       ->orWhere('libelle', 'like', "%{$search}%");
//                 });
//             }

//             // Inclure les documents supprimés si demandé
//             if (isset($filters['with_trashed']) && $filters['with_trashed'] === true) {
//                 $query->withTrashed();
//             }

//             // Uniquement les supprimés
//             if (isset($filters['only_trashed']) && $filters['only_trashed'] === true) {
//                 $query->onlyTrashed();
//             }

//             $perPage = $filters['per_page'] ?? 15;
//             $documents = $query->orderByDesc('created_at')->paginate($perPage);

//             return [
//                 'success' => true,
//                 'documents' => $documents->items(),
//                 'meta' => [
//                     'current_page' => $documents->currentPage(),
//                     'per_page' => $documents->perPage(),
//                     'total' => $documents->total(),
//                     'last_page' => $documents->lastPage(),
//                     'filters' => $filters,
//                 ],
//             ];
//         } catch (\Throwable $e) {
//             return [
//                 'success' => false,
//                 'message' => 'Erreur lors de la récupération des documents.',
//                 'error'   => $e->getMessage(),
//             ];
//         }
//     }

//     public function createDocument(array $data = [], ?UploadedFile $file = null): mixed
//     {
//         $createdPaths = [];
//         $documentsCrees = [];
//         $files = [];
//         $labelsByFile = [];


//         foreach ($data['documents'] as $entry) {
//             if (!is_array($entry)) {
//                 continue;
//             }

//             $uploadedFile = $entry['file'] ?? null;

//             if (!$uploadedFile instanceof UploadedFile) {
//                 continue;
//             }

//             $files[] = $uploadedFile;
//             $labelsByFile[] = $entry['libelle'] ?? null;
//         }

//         if (empty($files)) {
//             return [];
//         }

//         try {
//             return DB::transaction(function () use ($data, $files, $labelsByFile, &$documentsCrees, &$createdPaths) {
//                 foreach ($files as $index => $uploadedFile) {
//                     $extension = strtolower($uploadedFile->getClientOriginalExtension());
//                     $allowedExtensions = config('documents.allowed_extensions', []);

//                     if (!in_array($extension, $allowedExtensions, true)) {
//                         throw new \RuntimeException("Extension non autorisée : .{$extension}");
//                     }

//                     $resultat = $this->traiter($uploadedFile);
//                     $cheminRelatif = $resultat['chemin_relatif'] ?? null;

//                     if ($cheminRelatif) {
//                         $createdPaths[] = $cheminRelatif;
//                     }

//                     $typeDocument = $data['type_document']
//                         ?? $this->devinerTypeDocument($resultat['extension'], $resultat['mime_type']);

//                     $libelle = $labelsByFile[$index] ?? $data['libelle'] ?? pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);

//                     $document = Document::create([
//                         'uuid_document'  => (string) Str::uuid(),
//                         'reference_uuid' => $data['reference_uuid'] ?? null,
//                         'nom_fichier'    => $resultat['nom_stocke'] ?? $uploadedFile->getClientOriginalName(),
//                         'libelle'        => $libelle,
//                         'source'         => $data['source'] ?? null,
//                         'chemin'         => $resultat['url_publique'],
//                         'type_document'  => $typeDocument,
//                         'taille_fichier' => $resultat['taille'],
//                         'mime_type'      => $resultat['mime_type'],
//                         'statut'         => 'actif',
//                         'created_by'     => $data['created_by'] ?? null,
//                     ]);

//                     $documentsCrees[] = [
//                         'document'       => $document,
//                         'nom_original'   => $uploadedFile->getClientOriginalName(),
//                         'nom_stocke'     => $resultat['nom_stocke'],
//                         'extension'      => $resultat['extension'],
//                         'mime_type'      => $resultat['mime_type'],
//                         'taille'         => $resultat['taille'],
//                         'compresse'      => $resultat['compresse'],
//                         'dimensions'     => $resultat['dimensions'],
//                         'chemin_relatif' => $cheminRelatif,
//                         'url_publique'   => $resultat['url_publique'],
//                     ];
//                 }

//                 return count($documentsCrees) === 1 ? $documentsCrees[0] : $documentsCrees;
//             });
//         } catch (\Throwable $e) {
//             foreach ($createdPaths as $path) {
//                 if (is_string($path) && $path !== '') {
//                     $this->supprimer($path);
//                 }
//             }

//             throw $e;
//         }
//     }

//     public function findDocumentByUuid(string $uuid): ?Document
//     {
//         return Document::withTrashed()->find($uuid);
//     }

//     public function updateDocument(string $uuid, array $data): ?Document
//     {
//         $document = Document::find($uuid);

//         if (!$document) {
//             return null;
//         }

//         $document->update($data);

//         return $document->fresh();
//     }

//     public function deleteDocument(string $uuid, ?string $deletedBy = null): bool
//     {
//         $document = Document::find($uuid);

//         if (!$document) {
//             return false;
//         }

//         if ($deletedBy !== null) {
//             $document->delete_by = $deletedBy;
//             $document->save();
//         }

//         $document->delete();

//         return true;
//     }

//     public function restoreDocument(string $uuid): bool
//     {
//         $document = Document::onlyTrashed()->find($uuid);

//         if (!$document) {
//             return false;
//         }

//         $document->restore();
//         $document->delete_by = null;
//         $document->save();

//         return true;
//     }

//     public function forceDeleteDocument(string $uuid): bool
//     {
//         $document = Document::withTrashed()->find($uuid);

//         if (!$document) {
//             return false;
//         }

//         $cheminEnregistre = $document->chemin;
//         $cheminAbsolu = $this->resoudreCheminAbsolu($cheminEnregistre);

//         if ($cheminAbsolu && File::exists($cheminAbsolu)) {
//             try {
//                 File::delete($cheminAbsolu);
//                 Log::info('[forceDelete] Fichier supprimé', [
//                     'uuid'   => $uuid,
//                     'chemin' => $cheminAbsolu,
//                 ]);
//             } catch (\Throwable $e) {
//                 Log::warning('[forceDelete] Échec suppression fichier', [
//                     'uuid'   => $uuid,
//                     'chemin' => $cheminAbsolu,
//                     'erreur' => $e->getMessage(),
//                 ]);
//             }
//         }

//         $document->forceDelete();

//         return true;
//     }

//     public function getTrashedDocuments(): \Illuminate\Database\Eloquent\Collection
//     {
//         return Document::onlyTrashed()->get();
//     }

//     public function devinerTypeDocument(string $extension, string $mimeType): string
//     {
//         $extension = strtolower($extension);

//         $mapping = [
//             'pdf'  => 'pdf',
//             'doc'  => 'word',
//             'docx' => 'word',
//             'xls'  => 'excel',
//             'xlsx' => 'excel',
//             'csv'  => 'csv',
//             'ppt'  => 'powerpoint',
//             'pptx' => 'powerpoint',
//             'jpg'  => 'image',
//             'jpeg' => 'image',
//             'png'  => 'image',
//             'gif'  => 'image',
//             'svg'  => 'image',
//             'webp' => 'image',
//             'zip'  => 'archive',
//             'rar'  => 'archive',
//             'txt'  => 'texte',
//         ];

//         if (isset($mapping[$extension])) {
//             return $mapping[$extension];
//         }

//         if (Str::startsWith($mimeType, 'image/')) return 'image';
//         if (Str::startsWith($mimeType, 'video/')) return 'video';
//         if (Str::startsWith($mimeType, 'audio/')) return 'audio';
//         if (Str::startsWith($mimeType, 'text/')) return 'texte';

//         return 'autre';
//     }

//     protected function resoudreCheminAbsolu(?string $chemin): ?string
//     {
//         if (empty($chemin)) {
//             return null;
//         }

//         $candidats = [
//             base_path('..' . DIRECTORY_SEPARATOR . ltrim(str_replace('../', '', $chemin), '/\\')),
//             base_path('../public_html/' . ltrim($chemin, '/\\')),
//             public_path($chemin),
//             storage_path('app/' . ltrim($chemin, '/\\')),
//             storage_path('app/public/' . ltrim($chemin, '/\\')),
//         ];

//         foreach ($candidats as $candidat) {
//             if (File::exists($candidat)) {
//                 return $candidat;
//             }
//         }

//         return $candidats[0] ?? null;
//     }

//     // ========================================================================
//     // POINT D'ENTRÉE PUBLIC
//     // ========================================================================

//     /**
//      * Traite un fichier uploadé : décide s'il faut compresser, stocke,
//      * et retourne les infos finales du document.
//      *
//      * @return array{
//      *     chemin_relatif: string,
//      *     url_publique: string,
//      *     nom_stocke: string,
//      *     taille: int,
//      *     mime_type: string,
//      *     extension: string,
//      *     compresse: bool,
//      *     dimensions: array{width: int, height: int}
//      * }
//      */
//     public function traiter(UploadedFile $file): array
//     {
//         // 1. Extraction des métadonnées de base
//         $extension = strtolower($file->getClientOriginalExtension());
//         $poidsOctets = $file->getSize();
//         $poidsKo     = (int) ceil($poidsOctets / 1024);

//         // 2. Lecture des dimensions (rapide, juste l'en-tête du fichier)
//         [$largeur, $hauteur] = $this->lireDimensions($file);

//         // 3. Génération d'un nom unique
//         $nomUnique = $this->genererNomUnique($file);

//         // 4. Création du dossier du mois s'il n'existe pas
//         $disque = Storage::disk($this->disk);
//         if (!$disque->exists($this->dossierRelatif)) {
//             $disque->makeDirectory($this->dossierRelatif, 0755, true, true);
//         }

//         // 5. Décision : compresser ou pas ?
//         $compresser = config('documents.image.enabled')
//             && $this->estImageCompressible($extension)
//             && $this->doitEtreCompresse($largeur, $hauteur, $poidsKo);

//         // 6. Traitement
//         if ($compresser) {
//             return $this->compresserImage($file, $nomUnique, $largeur, $hauteur, $extension);
//         }

//         return $this->copieDirecte($file, $nomUnique, $extension, $largeur, $hauteur);
//     }

//     /**
//      * Supprime un fichier du disque (utilisé pour le rollback).
//      */
//     public function supprimer(string $cheminRelatif): bool
//     {
//         $disque = Storage::disk($this->disk);

//         if ($disque->exists($cheminRelatif)) {
//             return $disque->delete($cheminRelatif);
//         }

//         return false;
//     }

//     // ========================================================================
//     // LOGIQUE DE DÉCISION
//     // ========================================================================

//     /**
//      * Vérifie si l'extension est dans la liste des images compressibles.
//      */
//     protected function estImageCompressible(string $extension): bool
//     {
//         $extensions = config('documents.image.extensions', ['jpg', 'jpeg', 'webp']);

//         return in_array($extension, $extensions, true);
//     }

//     /**
//      * Décide si on doit compresser en fonction des dimensions et du poids.
//      *
//      * Règle :
//      *   - Si largeur > max_width OU hauteur > max_height → OUI
//      *   - Si poids > seuil_poids (Ko)                    → OUI
//      *   - Sinon                                          → NON
//      */
//     protected function doitEtreCompresse(int $largeur, int $hauteur, int $poidsKo): bool
//     {
//         $maxWidth   = (int) config('documents.image.max_width', 1920);
//         $maxHeight  = (int) config('documents.image.max_height', 1920);
//         $seuilPoids = (int) config('documents.image.seuil_poids', 500);

//         if ($largeur > $maxWidth || $hauteur > $maxHeight) {
//             return true;
//         }

//         if ($poidsKo > $seuilPoids) {
//             return true;
//         }

//         return false;
//     }

//     // ========================================================================
//     // TRAITEMENTS
//     // ========================================================================

//     /**
//      * Compresse une image et la stocke sur le disque.
//      *
//      * En cas d'échec, on bascule automatiquement sur une copie directe
//      * (fallback), pour que l'upload ne plante jamais.
//      */
//     protected function compresserImage(
//         UploadedFile $file,
//         string $nomUnique,
//         int $largeurOriginale,
//         int $hauteurOriginale,
//         string $extension
//     ): array {
//         try {
//             $maxWidth  = (int) config('documents.image.max_width', 1920);
//             $maxHeight = (int) config('documents.image.max_height', 1920);
//             $quality   = (int) config('documents.image.quality', 85);

//             // 1. Charger l'image en mémoire (lit le fichier temporaire)
//             $image = $this->manager->read($file->getRealPath());

//             // 2. Redimensionner en gardant le ratio (réduit seulement si plus grand)
//             $image->scaleDown(width: $maxWidth, height: $maxHeight);

//             // 3. Encoder dans le format d'origine
//             $encoded = match ($extension) {
//                 'jpg', 'jpeg' => $image->toJpeg($quality),
//                 'webp'        => $image->toWebp($quality),
//                 default       => throw new \RuntimeException("Format non supporté : {$extension}"),
//             };

//             // 4. Déterminer le chemin final
//             $cheminRelatif = $this->dossierRelatif . '/' . $nomUnique;

//             // 5. Écrire le binaire sur le disque
//             //    (string) est obligatoire : $encoded est un objet EncodedImage
//             Storage::disk($this->disk)->put($cheminRelatif, (string) $encoded);

//             // 6. Récupérer les nouvelles dimensions et le nouveau poids
//             $nouvellesDims = $this->lireDimensionsDepuisDisque($cheminRelatif);
//             $tailleFinale  = Storage::disk($this->disk)->size($cheminRelatif);

//             // 7. Logger l'opération
//             Log::info('[DocumentUploadService] Image compressée', [
//                 'nom_original'   => $file->getClientOriginalName(),
//                 'nom_stocke'     => $nomUnique,
//                 'poids_avant_ko' => (int) ceil($file->getSize() / 1024),
//                 'poids_apres_ko' => (int) ceil($tailleFinale / 1024),
//                 'gain_pourcent'  => $file->getSize() > 0
//                     ? round((1 - $tailleFinale / $file->getSize()) * 100, 1)
//                     : 0,
//                 'dimensions'     => "{$largeurOriginale}x{$hauteurOriginale} → "
//                                   . "{$nouvellesDims['width']}x{$nouvellesDims['height']}",
//             ]);

//             return [
//                 'chemin_relatif' => $cheminRelatif,
//                 'url_publique'   => $this->construireUrlPublique($cheminRelatif),
//                 'nom_stocke'     => $nomUnique,
//                 'taille'         => $tailleFinale,
//                 'mime_type'      => $this->mimeDepuisExtension($extension),
//                 'extension'      => $extension,
//                 'compresse'      => true,
//                 'dimensions'     => $nouvellesDims,
//             ];

//         } catch (\Throwable $e) {
//             // Fallback : si la compression échoue, on copie le fichier brut
//             Log::error('[DocumentUploadService] Échec compression → fallback copie directe', [
//                 'nom_original' => $file->getClientOriginalName(),
//                 'erreur'       => $e->getMessage(),
//                 'trace'        => $e->getTraceAsString(),
//             ]);

//             return $this->copieDirecte($file, $nomUnique, $extension, $largeurOriginale, $hauteurOriginale);
//         }
//     }

//     /**
//      * Copie directe du fichier sans transformation.
//      */
//     protected function copieDirecte(
//         UploadedFile $file,
//         string $nomUnique,
//         string $extension,
//         int $largeur,
//         int $hauteur
//     ): array {
//         $cheminRelatif = $file->storeAs($this->dossierRelatif, $nomUnique, $this->disk);

//         if (!$cheminRelatif) {
//             throw new \RuntimeException(
//                 "Échec du stockage direct du fichier dans {$this->dossierRelatif}"
//             );
//         }

//         $taille = Storage::disk($this->disk)->size($cheminRelatif);

//         return [
//             'chemin_relatif' => $cheminRelatif,
//             'url_publique'   => $this->construireUrlPublique($cheminRelatif),
//             'nom_stocke'     => $nomUnique,
//             'taille'         => $taille,
//             'mime_type'      => $file->getMimeType() ?? 'application/octet-stream',
//             'extension'      => $extension,
//             'compresse'      => false,
//             'dimensions'     => ['width' => $largeur, 'height' => $hauteur],
//         ];
//     }

//     // ========================================================================
//     // UTILITAIRES
//     // ========================================================================

//     /**
//      * Génère un nom unique à partir du nom original + UUID.
//      *
//      * Exemple : "Ma Photo Été.jpg" → "ma-photo-ete_a1b2c3d4-...jpg"
//      */
//     protected function genererNomUnique(UploadedFile $file): string
//     {
//         $nomOriginal = $file->getClientOriginalName();
//         $extension   = strtolower($file->getClientOriginalExtension());

//         // Slug du nom sans extension
//         $slug = Str::slug(pathinfo($nomOriginal, PATHINFO_FILENAME));

//         if (empty($slug)) {
//             $slug = 'fichier';
//         }

//         return $slug . '_' . now()->format('YmdHis') . '.' . $extension;
//     }

//     /**
//      * Lit les dimensions d'une image via getimagesize() (rapide, sans charger en mémoire).
//      * Retourne [0, 0] si le fichier n'est pas une image.
//      */
//     protected function lireDimensions(UploadedFile $file): array
//     {
//         // @ supprime les warnings sur les fichiers non-image (PDF, DOCX, etc.)
//         $infos = @getimagesize($file->getRealPath());

//         if ($infos === false) {
//             return [0, 0];
//         }

//         return [(int) $infos[0], (int) $infos[1]];
//     }

//     /**
//      * Lit les dimensions d'un fichier déjà stocké sur le disque.
//      */
//     protected function lireDimensionsDepuisDisque(string $cheminRelatif): array
//     {
//         $cheminAbsolu = Storage::disk($this->disk)->path($cheminRelatif);
//         $infos        = @getimagesize($cheminAbsolu);

//         if ($infos === false) {
//             return ['width' => 0, 'height' => 0];
//         }

//         return ['width' => (int) $infos[0], 'height' => (int) $infos[1]];
//     }

//     /**
//      * Construit l'URL publique à partir du chemin relatif.
//      */
//     protected function construireUrlPublique(string $cheminRelatif): string
//     {
//         return rtrim(config('documents.url'), '/') . '/' . $cheminRelatif;
//     }

//     /**
//      * Retourne le MIME type à partir de l'extension.
//      */
//     protected function mimeDepuisExtension(string $extension): string
//     {
//         return match (strtolower($extension)) {
//             'jpg', 'jpeg' => 'image/jpeg',
//             'png'         => 'image/png',
//             'gif'         => 'image/gif',
//             'webp'        => 'image/webp',
//             default       => 'application/octet-stream',
//         };
//     }

//     /**
//      * Crée le manager Intervention Image avec le meilleur driver disponible.
//      *
//      * Utilise Imagick si l'extension est chargée (meilleure qualité),
//      * sinon GD (par défaut).
//      */
//     protected function creerManager(): ImageManager
//     {
//         $driver = extension_loaded('imagick')
//             ? new ImagickDriver()
//             : new GdDriver();

//         return new ImageManager($driver);
//     }
// }