<?php
// app/Services/Api/Ynov/ProfileService.php
namespace App\Services\Api\Ynov;

use App\Models\Api\Ynov\Esouscription\Document;
use App\Models\Api\Ynov\parameter\ActivityLog;
use App\Models\Api\Ynov\parameter\GroupNotif;
use App\Models\Api\Ynov\parameter\User;
use App\Models\Api\Ynov\parameter\UserDetails;
use App\Services\Api\Ynov\Documents\DocumentService;
use App\Services\Api\Ynov\NotificationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProfileService
{
    public function __construct(
        private NotificationService $notificationService,
        private DocumentService $documentService,
    ) {}

    /**
     * Mettre à jour le profil utilisateur
     */
    public function updateProfile(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $oldValues = $user->toArray();

            // Mise à jour des champs utilisateur
            $userData = array_filter(
                array_intersect_key($data, array_flip(['login', 'email'])),
                fn ($value) => $value !== null
            );

            if ($userData) {
                $user->update($userData);
            }

            $detailsData = $this->prepareDetailsData($data);
            $this->resolvePhoto($user, $data, $detailsData);

            if ($user->details) {
                $detailsData['updated_by'] = $user->uuid_user;
                $user->details->update($detailsData);
            } else {
                // Créer les détails si inexistants
                $detailsData['user_uuid'] = $user->uuid_user;
                $detailsData['uuid_user_details'] = (string) Str::uuid();
                $detailsData['created_by'] = $user->uuid_user;

                UserDetails::create($detailsData);
            }
            
            // Créer une notification pour la mise à jour du profil
            $this->notificationService->create([
                'user_uuid' => $user->uuid_user,
                'group_notif_uuid' => $this->getCompteGroupUuid(),
                'title' => '📝 Profil mis à jour',
                'body' => 'Votre profil a été mis à jour avec succès.',
                'type' => 'account',
                'metadata' => [
                    'updated_fields' => array_keys($data),
                    'updated_at' => now()->toISOString(),
                ],
                'channel' => 'database',
                'created_by' => $user->uuid_user,
            ]);
            
            // Journaliser l'action
            ActivityLog::log([
                'user_uuid' => $user->uuid_user,
                'action' => 'profile_update',
                'action_type' => 'crud',
                'module' => 'profile',
                'description' => "Mise à jour du profil",
                'resource_type' => 'user',
                'resource_id' => $user->uuid_user,
                'old_values' => $oldValues,
                'new_values' => $user->fresh()->toArray(),
                'level' => 'info',
            ]);
            
            return $user->fresh()->load('details');
        });
    }
    
    /**
     * Résout l'action photo demandée et met à jour $detailsData en conséquence.
     * Priorité : remove_photo > photo_url > photo (upload).
     * L'upload n'a lieu que si c'est l'action retenue (évite les fichiers orphelins).
     */
    private function resolvePhoto(User $user, array $data, array &$detailsData): void
    {
        $ancienPhotoPath = $user->details?->photo_path;
        Log::info('[ProfileService] Ancien chemin de photo pour l\'utilisateur ' . $user->uuid_user . ': ' . ($ancienPhotoPath ?? 'null'));

        if (!empty($data['remove_photo'])) {
            Log::info('[ProfileService] Suppressiondddddd de photo de profil pour l\'utilisateur ' . $user->uuid_user);
            $this->deletePhoto($ancienPhotoPath);
            $detailsData['photo'] = null;
            $detailsData['photo_path'] = null;
            return;
        }

        if (!empty($data['photo_url'])) {
            Log::info('[ProfileService] Suppression de photo de profil pour l\'utilisateur ' . $user->uuid_user);
            $this->deletePhoto($ancienPhotoPath);
            $detailsData['photo'] = $data['photo_url'];
            $detailsData['photo_path'] = null;
            return;
        }

        if (isset($data['photo']) && $data['photo'] instanceof \Illuminate\Http\UploadedFile && $data['photo']->isValid()) {
            Log::info('[ProfileService] Upload de photo de profil pour l\'utilisateur ' . $user->uuid_user);
        // if (isset($data['photo']) && $data['photo']->isValid()) {
            $photoPath = $this->uploadPhoto($data['photo'], $user->uuid_user);
            $detailsData['photo_path'] = $photoPath;
            $detailsData['photo'] = null;
        }
    }

    /**
     * Préparer les données des détails
     */
    private function prepareDetailsData(array $data): array
    {
        return array_filter([
            'nom' => $data['nom'] ?? null,
            'prenoms' => $data['prenoms'] ?? null,
            'fonction' => $data['fonction'] ?? null,
            'service' => $data['service'] ?? null,
            'departement' => $data['departement'] ?? null,
            'mobile_1' => $data['mobile_1'] ?? null,
            'mobile_2' => $data['mobile_2'] ?? null,
            'telephone_fixe' => $data['telephone_fixe'] ?? null,
            'email_pro' => $data['email_pro'] ?? null,
            'ville' => $data['ville'] ?? null,
            'pays' => $data['pays'] ?? null,
            'date_naissance' => $data['date_naissance'] ?? null,
            'lieu_naissance' => $data['lieu_naissance'] ?? null,
            'lieu_residence' => $data['lieu_residence'] ?? null,
            'nationalite' => $data['nationalite'] ?? null,
            'genre' => $data['genre'] ?? null,
            'civilite' => $data['civilite'] ?? null,
            'adresse_complete' => $data['adresse_complete'] ?? null,
            'code_postal' => $data['code_postal'] ?? null,
            'date_embauche' => $data['date_embauche'] ?? null,
            'statut_employe' => $data['statut_employe'] ?? null,
            'type_contrat' => $data['type_contrat'] ?? null,
            'code_agent' => $data['code_agent'] ?? null,
            'matricule' => $data['matricule'] ?? null,
            'numero_client' => $data['numero_client'] ?? null,
            'preferences' => $data['preferences'] ?? null,
        ], fn($value) => $value !== null);
    }
    
    private function uploadPhoto($photo, string $userUuid): string
    {
        $this->deletePhotoByUser($userUuid);

        $resultat = $this->documentService->createDocument([
            'reference_uuid' => $userUuid,
            'source'         => 'PROFILE_PICTURE',
            'libelle'        => 'Photo de profil',
            'created_by'     => $userUuid,
            'documents'      => [
                ['file' => $photo, 'libelle' => 'Photo de profil'],
            ],
        ]);

        $chemin = $resultat['chemin_relatif'] ?? null;

        if (!$chemin) {
            throw new \RuntimeException("Échec de l'upload de la photo de profil.");
        }

        return $chemin;
    }

    /**
     * Supprimer une photo par son chemin
     */
    public function deletePhoto(?string $path): void
    {
        if (!$path) {
            return;
        }

        $cleanPath = str_replace('\\', '/', ltrim($path, '/'));

        // Anciennes photos stockées en local (UPLOADS_PATH)
        if (str_starts_with($cleanPath, 'profiles/')) {
            $fullPath = base_path(env('UPLOADS_PATH', '../public_html/upload/documents-test/') . $cleanPath);

            if (is_file($fullPath)) {
                @unlink($fullPath);
            }

            return;
        }

        // Nouvelles photos sur le disque documents (WebDAV)
        try {
            $this->documentService->supprimer($cleanPath);
        } catch (\Throwable $e) {
            Log::warning('[ProfileService] Échec suppression fichier photo', [
                'path'  => $cleanPath,
                'error' => $e->getMessage(),
            ]);
        }

        Document::where('chemin', $cleanPath)
            ->orWhere('nom_fichier', basename($cleanPath))
            ->get()
            ->each(fn (Document $doc) => $doc->delete());
    }

    /**
     * Supprimer la photo d'un utilisateur
     */
    private function deletePhotoByUser(string $userUuid): void
    {
        // Anciennes photos stockées en local (UPLOADS_PATH)
        $legacyPath = 'profiles/' . $userUuid;
        $legacyFullPath = base_path(env('UPLOADS_PATH', '../public_html/upload/documents-test/') . $legacyPath);

        if (is_dir($legacyFullPath)) {
            foreach (glob($legacyFullPath . '/*') ?: [] as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
            @rmdir($legacyFullPath);
        }

        // Photos stockées via DocumentService (WebDAV)
        $documents = Document::where('reference_uuid', $userUuid)
            ->where('source', 'PROFILE_PICTURE')
            ->get();

        foreach ($documents as $document) {
            if ($document->chemin) {
                try {
                    $this->documentService->supprimer($this->extraireChemin($document->chemin));
                } catch (\Throwable $e) {
                    Log::warning('[ProfileService] Échec suppression photo utilisateur', [
                        'user_uuid' => $userUuid,
                        'chemin'    => $document->chemin,
                        'error'     => $e->getMessage(),
                    ]);
                }
            }

            $document->delete();
        }
    }

    /**
     * Extrait le chemin relatif depuis une valeur `chemin` qui peut être
     * une URL complète ou un chemin relatif.
     */
    private function extraireChemin(?string $chemin): string
    {
        $chemin = (string) $chemin;

        if (str_contains($chemin, '://')) {
            $path = parse_url($chemin, PHP_URL_PATH) ?: '';
            if (preg_match('#/docnumerises/(?:PROD|TEST)/(.*)$#', $path, $m)) {
                return ltrim($m[1], '/');
            }

            return ltrim($path, '/');
        }

        return ltrim($chemin, '/');
    }

    private function getCompteGroupUuid(): ?string
    {
        return Cache::remember('group_notif_uuid_compte', now()->addDay(), function () {
            return GroupNotif::where('code', 'compte')->value('uuid_group_notif');
        });
    }
}