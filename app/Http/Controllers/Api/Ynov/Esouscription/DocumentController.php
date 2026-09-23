<?php

namespace App\Http\Controllers\Api\Ynov\Esouscription;

use App\Http\Controllers\Controller;
use App\Models\Api\Ynov\Esouscription\Document;
use App\Services\DocumentUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class DocumentController extends Controller
{

    protected DocumentUploadService $uploadService;

    public function __construct(DocumentUploadService $uploadService)
    {
        $this->uploadService = $uploadService;
    }
    /**
     * Liste tous les documents (avec pagination et filtres).
     */
    public function getDocuments(Request $request): JsonResponse
    {
        try {
            $query = Document::query();

            // Filtres optionnels
            if ($request->filled('statut')) {
                $query->where('statut', $request->statut);
            }

            if ($request->filled('reference_uuid')) {
                $query->where('reference_uuid', $request->reference_uuid);
            }

            if ($request->filled('source')) {
                $query->where('source', $request->source);
            }

            // Recherche par nom ou libellé
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('nom_fichier', 'like', "%{$search}%")
                      ->orWhere('libelle', 'like', "%{$search}%");
                });
            }

            // Inclure les documents supprimés si demandé
            if ($request->boolean('with_trashed')) {
                $query->withTrashed();
            }

            // Uniquement les supprimés
            if ($request->boolean('only_trashed')) {
                $query->onlyTrashed();
            }

            $perPage = $request->integer('per_page', 15);
            $documents = $query->orderByDesc('created_at')->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Liste des documents récupérée avec succès.',
                'data'    => $documents,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des documents.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Créer un nouveau document.
     */
    public function storeDoc(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reference_uuid' => 'nullable|string|max:255',
            'nom_fichier'    => 'required|string|max:255',
            'libelle'        => 'nullable|string|max:255',
            'source'         => 'nullable|string|max:255',
            'chemin'         => 'required|string|max:500',
            'type_document'  => 'nullable|string|max:100',
            'taille_fichier' => 'nullable|integer|min:0',
            'mime_type'      => 'nullable|string|max:150',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            $data = $validator->validated();

            $document = Document::create([
                'uuid_document' => Str::uuid(),
                'reference_uuid' => $data['reference_uuid'],
                'nom_fichier' => $data['nom_fichier'],
                'libelle' => $data['libelle'],
                'source' => $data['source'],
                'chemin' => env('DOC_PATH') . $data['chemin'],
                'type_document' => $data['type_document'],
                'taille_fichier' => $data['taille_fichier'],
                'mime_type' => $data['mime_type'],
                'statut' => 'actif',
            ]);

            

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Document créé avec succès.',
                'data'    => $document,
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du document.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
    
    public function uploadDoc(Request $request): JsonResponse
    {
        // --- 1. Validation de la requête ---
        $validator = Validator::make($request->all(), [
            'fichier'        => 'required|file|max:' . config('documents.max_size'),
            'reference_uuid' => 'required|string|max:255',
            'libelle'        => 'nullable|string|max:255',
            'source'         => 'required|string|max:255',
            'type_document'  => 'nullable|string|max:100',
            'created_by'     => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        /** @var \Illuminate\Http\UploadedFile $file */
        $file      = $request->file('fichier');
        $extension = strtolower($file->getClientOriginalExtension());

        // --- 2. Vérification de l'extension (whitelist) ---
        $extensionsAutorisees = config('documents.allowed_extensions', []);
        if (!in_array($extension, $extensionsAutorisees, true)) {
            return response()->json([
                'success' => false,
                'message' => "Extension non autorisée : .{$extension}",
                'allowed' => $extensionsAutorisees,
            ], 422);
        }

        DB::beginTransaction();

        $cheminRelatif = null;

        try {
            // --- 3. Traitement via le service (compression + stockage) ---
            $resultat = $this->uploadService->traiter($file);

            $cheminRelatif = $resultat['chemin_relatif'];

            // --- 4. Type de document (fourni ou deviné) ---
            $typeDocument = $request->input('type_document')
                ?? $this->devinerTypeDocument($resultat['extension'], $resultat['mime_type']);

            // --- 5. Enregistrement en BDD ---
            $document = Document::create([
                'uuid_document'  => (string) Str::uuid(),
                'reference_uuid' => $request->input('reference_uuid'),
                // 'nom_fichier'    => $file->getClientOriginalName(),
                'nom_fichier'    => $resultat['nom_stocke'] ?? $file->getClientOriginalName(),
                'libelle'        => $request->input('libelle')
                                    ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'source'         => $request->input('source'),
                'chemin'         => $resultat['url_publique'],
                'type_document'  => $typeDocument,
                'taille_fichier' => $resultat['taille'],
                'mime_type'      => $resultat['mime_type'],
                'statut'         => 'actif',
                'created_by'     => $request->input('created_by'),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Fichier uploadé et document créé avec succès.',
                'data'    => [
                    'document'       => $document,
                    'nom_original'   => $file->getClientOriginalName(),
                    'nom_stocke'     => $resultat['nom_stocke'],
                    'extension'      => $resultat['extension'],
                    'mime_type'      => $resultat['mime_type'],
                    'taille'         => $resultat['taille'],
                    'compresse'      => $resultat['compresse'],
                    'dimensions'     => $resultat['dimensions'],
                    'chemin_relatif' => $cheminRelatif,
                    'url_publique'   => $resultat['url_publique'],
                ],
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();

            // Nettoyage du fichier orphelin si la BDD a échoué
            if ($cheminRelatif) {
                $this->uploadService->supprimer($cheminRelatif);
            }

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'upload du document.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function uploadMultidoc(Request $request): JsonResponse
    {
        // --- 1. Validation ---
        $validator = Validator::make($request->all(), [
            'reference_uuid' => 'required|uuid',
            'source'         => 'required|string|max:255',
            'created_by'     => 'required|string|max:255',
            'documents'                       => 'required|array|min:1',
            'documents.*.groupe'              => 'required|string|max:100',
            'documents.*.fichiers'            => 'required|array|min:1',
            'documents.*.fichiers.*.file'     => 'required|file|max:' . config('documents.max_size'),
            'documents.*.fichiers.*.libelle'  => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $referenceUuid = $request->input('reference_uuid');
        $source        = $request->input('source');
        $createdBy     = $request->input('created_by');
        $extensionsAutorisees = config('documents.allowed_extensions', []);

        // ✅ IMPORTANT : utiliser all() pour récupérer les UploadedFile
        $documents = $request->all()['documents'] ?? [];

        DB::beginTransaction();

        // Suivi des fichiers physiques créés (pour rollback)
        $cheminsCrees = [];
        $documentsCrees = [];

        try {
            foreach ($documents as $groupe) {
                $typeDocumentGroupe = $groupe['groupe'] ?? null;
                $fichiers           = $groupe['fichiers'] ?? [];

                foreach ($fichiers as $fichierData) {
                    /** @var \Illuminate\Http\UploadedFile|null $file */
                    $file = $fichierData['file'] ?? null;

                    if (!$file instanceof \Illuminate\Http\UploadedFile) {
                        continue;
                    }

                    // --- Vérification extension (comme uploadDoc) ---
                    $extension = strtolower($file->getClientOriginalExtension());
                    if (!in_array($extension, $extensionsAutorisees, true)) {
                        throw new \RuntimeException(
                            "Extension non autorisée : .{$extension} (fichier {$file->getClientOriginalName()})"
                        );
                    }

                    // --- Traitement via le MÊME service que uploadDoc ---
                    // Compression images + stockage sur le disque 'docnumerises' + nom unique
                    $resultat = $this->uploadService->traiter($file);
                    $cheminsCrees[] = $resultat['chemin_relatif'];

                    // --- Type de document ---
                    // Priorité : le groupe (ex: 'bulletin_souscription') s'il est fourni,
                    // sinon on devine à partir de l'extension/mime (comme uploadDoc)
                    $typeDocument = $typeDocumentGroupe
                        ?: $this->devinerTypeDocument($resultat['extension'], $resultat['mime_type']);

                    // --- Enregistrement en BDD ---
                    $document = Document::create([
                        'uuid_document'  => (string) Str::uuid(),
                        'reference_uuid' => $referenceUuid,
                        'nom_fichier'    => $resultat['nom_stocke'] ?? $file->getClientOriginalName(),
                        'libelle'        => $fichierData['libelle']
                                            ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                        'source'         => $source,
                        'chemin'         => $resultat['url_publique'],
                        'type_document'  => $typeDocument,
                        'taille_fichier' => $resultat['taille'],
                        'mime_type'      => $resultat['mime_type'],
                        'statut'         => 'actif',
                        'created_by'     => $createdBy,
                    ]);

                    $documentsCrees[] = [
                        'document'       => $document,
                        'nom_original'   => $file->getClientOriginalName(),
                        'nom_stocke'     => $resultat['nom_stocke'],
                        'extension'      => $resultat['extension'],
                        'mime_type'      => $resultat['mime_type'],
                        'taille'         => $resultat['taille'],
                        'compresse'      => $resultat['compresse'],
                        'dimensions'     => $resultat['dimensions'],
                        'chemin_relatif' => $resultat['chemin_relatif'],
                        'url_publique'   => $resultat['url_publique'],
                    ];
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => count($documentsCrees) . ' document(s) enregistré(s) avec succès.',
                'total'   => count($documentsCrees),
                'data'    => $documentsCrees,
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();

            // Nettoyage : on supprime tous les fichiers déjà stockés
            foreach ($cheminsCrees as $chemin) {
                $this->uploadService->supprimer($chemin);
            }

            Log::error('[uploadMultidoc] Erreur', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement des documents.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    private function devinerTypeDocument(string $extension, string $mimeType): string
    {
        $extension = strtolower($extension);

        $mapping = [
            'pdf'  => 'pdf',
            'doc'  => 'word',
            'docx' => 'word',
            'xls'  => 'excel',
            'xlsx' => 'excel',
            'csv'  => 'csv',
            'ppt'  => 'powerpoint',
            'pptx' => 'powerpoint',
            'jpg'  => 'image',
            'jpeg' => 'image',
            'png'  => 'image',
            'gif'  => 'image',
            'svg'  => 'image',
            'webp' => 'image',
            'zip'  => 'archive',
            'rar'  => 'archive',
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

    /**
     * Afficher un document spécifique.
     */
    public function showDocument(string $uuid): JsonResponse
    {
        try {
            $document = Document::withTrashed()->find($uuid);

            if (!$document) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document introuvable.',
                ], 404);
            }

            $url = url('preview/doc/' . $document->nom_fichier); // env('APP_URL') . 'preview/doc/' . $document->nom_fichier;
            // $url = env('APP_URL') . 'preview/doc/' . $document->nom_fichier;

            return response()->json([
                'success' => true,
                'message' => 'Document récupéré avec succès.',
                'data' => $document,
                'preview_url' => $url,
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du document.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mettre à jour un document existant.
     */
    public function updateDocument(Request $request, string $uuid): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reference_uuid' => 'sometimes|nullable|string|max:255',
            'nom_fichier'    => 'sometimes|required|string|max:255',
            'libelle'        => 'sometimes|nullable|string|max:255',
            'source'         => 'sometimes|nullable|string|max:255',
            'type_document'  => 'sometimes|nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            $document = Document::find($uuid);

            if (!$document) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document introuvable.',
                ], 404);
            }

            $data = $validator->validated();
            $data['update_by'] = $request->input('update_by');
            $data['updated_at'] = $request->input('updated_at');

            $document->update($data);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Document mis à jour avec succès.',
                'data'    => $document->fresh(),
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du document.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Suppression logique (soft delete) d'un document.
     */
    public function destroyDocument(Request $request, string $uuid): JsonResponse
    {
        DB::beginTransaction();

        try {
            $document = Document::find($uuid);

            if (!$document) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document introuvable ou déjà supprimé.',
                ], 404);
            }

            $document->delete_by = $request->input('delete_by');
            $document->save();
            $document->delete(); // soft delete

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Document supprimé avec succès (suppression logique).',
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du document.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Restaurer un document supprimé.
     */
    public function restoreDocument(string $uuid): JsonResponse
    {
        DB::beginTransaction();

        try {
            $document = Document::onlyTrashed()->find($uuid);

            if (!$document) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document introuvable ou non supprimé.',
                ], 404);
            }

            $document->restore();
            $document->delete_by = null;
            $document->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Document restauré avec succès.',
                'data'    => $document->fresh(),
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la restauration du document.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }


    public function forceDeleteDocument(string $uuid): JsonResponse
    {
        DB::beginTransaction();

        try {
            $document = Document::withTrashed()->find($uuid);

            if (!$document) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document introuvable.',
                ], 404);
            }

            // On garde le chemin en mémoire avant suppression en BDD
            $cheminEnregistre = $document->chemin;

            // --- 1. Suppression du fichier physique ---
            $cheminAbsolu = $this->resoudreCheminAbsolu($cheminEnregistre);

            if ($cheminAbsolu && File::exists($cheminAbsolu)) {
                try {
                    File::delete($cheminAbsolu);
                    Log::info('[forceDelete] Fichier supprimé', [
                        'uuid'   => $uuid,
                        'chemin' => $cheminAbsolu,
                    ]);
                } catch (\Throwable $e) {
                    // On log mais on ne bloque pas : la BDD doit être nettoyée
                    Log::warning('[forceDelete] Échec suppression fichier', [
                        'uuid'   => $uuid,
                        'chemin' => $cheminAbsolu,
                        'erreur' => $e->getMessage(),
                    ]);
                }
            } else {
                Log::info('[forceDelete] Fichier déjà absent', [
                    'uuid'   => $uuid,
                    'chemin' => $cheminEnregistre,
                ]);
            }

            // --- 2. Suppression en BDD ---
            $document->forceDelete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Document supprimé définitivement.',
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression définitive.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Transforme le chemin stocké en chemin absolu exploitable par File.
     *
     * Gère plusieurs cas :
     *  - URL complète  : https://.../docnumerises/...   → extrait la partie après le domaine
     *  - chemin relatif : docnumerises/2026/09/x.jpg    → préfixe avec public_path
     *  - chemin déjà absolu : C:\...\public_html\...    → tel quel
     *  - chemin avec ".." : ../public_html/docnumerises/...
     */
    protected function resoudreCheminAbsolu(?string $chemin): ?string
    {
        if (empty($chemin)) {
            return null;
        }

        $candidats = [
            // Ce que vous avez dans votre code existant
            base_path('..' . DIRECTORY_SEPARATOR . ltrim(str_replace('../', '', $chemin), '/\\')),

            // Tentative directe avec public_html à la racine du projet
            base_path('../public_html/' . ltrim($chemin, '/\\')),

            // Chemin dans le dossier public classique de Laravel
            public_path($chemin),

            // Stockage local (storage/app/)
            storage_path('app/' . ltrim($chemin, '/\\')),

            // Dossier configuré (ex: docnumerises)
            storage_path('app/public/' . ltrim($chemin, '/\\')),
        ];

        foreach ($candidats as $candidat) {
            if (File::exists($candidat)) {
                return $candidat;
            }
        }

        // Aucun candidat trouvé → on renvoie quand même le premier pour log
        return $candidats[0] ?? null;
    }

    public function getTrashedDocuments(): JsonResponse
    {
        $documents = Document::onlyTrashed()->get();

        return response()->json([
            'success' => true,
            'message' => 'Liste des documents supprimés.',
            'count'   => $documents->count(),
            'data'    => $documents,
        ], 200);
    }  

}