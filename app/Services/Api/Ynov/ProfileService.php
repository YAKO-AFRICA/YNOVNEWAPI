<?php
// app/Services/Api/Ynov/ProfileService.php
namespace App\Services\Api\Ynov;

use App\Models\Api\Ynov\parameter\ActivityLog;
use App\Models\Api\Ynov\parameter\GroupNotif;
use App\Models\Api\Ynov\parameter\User;
use App\Models\Api\Ynov\parameter\UserDetails;
use App\Services\Api\Ynov\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// class ProfileService
// {
//     /**
//      * Mettre à jour le profil utilisateur
//      */
//     public function updateProfile(User $user, array $data): User
//     {
//         return DB::transaction(function () use ($user, $data) {
//             $oldValues = $user->toArray();
            
//             // Mettre à jour l'utilisateur
//             $userData = [];
//             if (isset($data['login'])) {
//                 $userData['login'] = $data['login'];
//             }
//             if (isset($data['email'])) {
//                 $userData['email'] = $data['email'];
//             }
//             if (!empty($userData)) {
//                 $user->update($userData);
//             }
            
//             // Mettre à jour les détails
//             if ($user->details) {
//                 $detailsData = $this->prepareDetailsData($data);
                
//                 // Gérer la photo
//                 if (isset($data['photo']) && $data['photo']->isValid()) {
//                     $photoPath = $this->uploadPhoto($data['photo'], $user->uuid_user);
//                     $detailsData['photo_path'] = $photoPath;
//                     $detailsData['photo'] = null;
//                 }
                
//                 // Si une URL de photo externe est fournie
//                 if (isset($data['photo_url']) && !empty($data['photo_url'])) {
//                     // Supprimer l'ancienne photo locale si elle existe
//                     if ($user->details->photo_path) {
//                         $this->deletePhoto($user->details->photo_path);
//                     }
//                     $detailsData['photo'] = $data['photo_url'];
//                     $detailsData['photo_path'] = null;
//                 }
                
//                 // Supprimer la photo
//                 if (isset($data['remove_photo']) && $data['remove_photo']) {
//                     $this->deletePhoto($user->details->photo_path);
//                     $detailsData['photo'] = null;
//                     $detailsData['photo_path'] = null;
//                 }
                
//                 $detailsData['updated_by'] = $user->uuid_user;
//                 $user->details->update($detailsData);
//             } else {
//                 // Créer les détails si inexistants
//                 $detailsData = $this->prepareDetailsData($data);
//                 $detailsData['user_uuid'] = $user->uuid_user;
//                 $detailsData['uuid_user_details'] = (string) Str::uuid();
//                 $detailsData['created_by'] = $user->uuid_user;
                
//                 if (isset($data['photo']) && $data['photo']->isValid()) {
//                     $photoPath = $this->uploadPhoto($data['photo'], $user->uuid_user);
//                     $detailsData['photo_path'] = $photoPath;
//                 }
                
//                 UserDetails::create($detailsData);
//             }
            
//             // Journaliser l'action
//             ActivityLog::log([
//                 'user_uuid' => $user->uuid_user,
//                 'action' => 'profile_update',
//                 'action_type' => 'crud',
//                 'module' => 'profile',
//                 'description' => "Mise à jour du profil",
//                 'resource_type' => 'user',
//                 'resource_id' => $user->uuid_user,
//                 'old_values' => $oldValues,
//                 'new_values' => $user->fresh()->toArray(),
//                 'level' => 'info',
//             ]);
            
//             return $user->fresh()->load('details');
//         });
//     }
    
//     /**
//      * Préparer les données des détails
//      */
//     private function prepareDetailsData(array $data): array
//     {
//         return array_filter([
//             'nom' => $data['nom'] ?? null,
//             'prenoms' => $data['prenoms'] ?? null,
//             'fonction' => $data['fonction'] ?? null,
//             'service' => $data['service'] ?? null,
//             'departement' => $data['departement'] ?? null,
//             'mobile_1' => $data['mobile_1'] ?? null,
//             'mobile_2' => $data['mobile_2'] ?? null,
//             'telephone_fixe' => $data['telephone_fixe'] ?? null,
//             'email_pro' => $data['email_pro'] ?? null,
//             'ville' => $data['ville'] ?? null,
//             'pays' => $data['pays'] ?? null,
//             'date_naissance' => $data['date_naissance'] ?? null,
//             'lieu_naissance' => $data['lieu_naissance'] ?? null,
//             'lieu_residence' => $data['lieu_residence'] ?? null,
//             'nationalite' => $data['nationalite'] ?? null,
//             'genre' => $data['genre'] ?? null,
//             'civilite' => $data['civilite'] ?? null,
//             'adresse_complete' => $data['adresse_complete'] ?? null,
//             'code_postal' => $data['code_postal'] ?? null,
//             'date_embauche' => $data['date_embauche'] ?? null,
//             'statut_employe' => $data['statut_employe'] ?? null,
//             'type_contrat' => $data['type_contrat'] ?? null,
//             'code_agent' => $data['code_agent'] ?? null,
//             'matricule' => $data['matricule'] ?? null,
//             'numero_client' => $data['numero_client'] ?? null,
//             'preferences' => $data['preferences'] ?? null,
//         ], fn($value) => $value !== null);
//     }
    

//     private function uploadPhoto($photo, string $userUuid): string
//     {
//         $extension = $photo->getClientOriginalExtension();
//         $filename = sprintf(
//             'profile_%s_%s.%s',
//             $userUuid,
//             now()->timestamp,
//             $extension
//         );
        
//         // Utiliser des slashes pour le chemin
//         $path = 'profiles/' . $userUuid;
        
//         // Supprimer l'ancienne photo
//         $this->deletePhotoByUser($userUuid);
        
//         // Créer le dossier
//         $basePath = base_path(env('UPLOADS_PATH', '../public_html/upload/documents-test/'));
//         if (!is_dir($basePath)) {
//             mkdir($basePath, 0777, true);
//         }

//         $fullPath = $basePath . '/' . $path;
        

//         // if (!is_dir($fullPath)) {
//         //     mkdir($fullPath, 0755, true);
//         // }
        
//         // Déplacer le fichier
//         $photo->move($fullPath, $filename);
        
//         // Retourner le chemin avec des slashes
//         return $path . '/' . $filename;
//     }
    
//     /**
//      * Supprimer une photo par son chemin
//      */
//     public function deletePhoto(?string $path): void
//     {
//         if (!$path) {
//             return;
//         }
        
//         $fullPath = base_path(env('UPLOADS_PATH', '../public_html/upload/documents-test/') . $path);
        
//         if (file_exists($fullPath)) {
//             @unlink($fullPath);
//         }
        
//         // Supprimer le dossier si vide
//         $dirPath = dirname($fullPath);
//         if (is_dir($dirPath) && count(scandir($dirPath)) <= 2) {
//             @rmdir($dirPath);
//         }
//     }
    
//     /**
//      * Supprimer la photo d'un utilisateur
//      */
//     private function deletePhotoByUser(string $userUuid): void
//     {
//         $path = 'profiles/' . $userUuid;
//         $fullPath = base_path(env('UPLOADS_PATH', '../public_html/upload/documents-test/') . $path);
        
//         if (is_dir($fullPath)) {
//             $files = glob($fullPath . '/*');
//             foreach ($files as $file) {
//                 if (is_file($file)) {
//                     @unlink($file);
//                 }
//             }
//             @rmdir($fullPath);
//         }
//     }
// }

class ProfileService
{
    public function __construct(
        private NotificationService $notificationService,
    ) {}

    /**
     * Mettre à jour le profil utilisateur
     */
    public function updateProfile(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $oldValues = $user->toArray();
            
            // Mettre à jour l'utilisateur
            $userData = [];
            if (isset($data['login'])) {
                $userData['login'] = $data['login'];
            }
            if (isset($data['email'])) {
                $userData['email'] = $data['email'];
            }
            if (!empty($userData)) {
                $user->update($userData);
            }
            
            // Mettre à jour les détails
            if ($user->details) {
                $detailsData = $this->prepareDetailsData($data);
                
                // Gérer la photo
                if (isset($data['photo']) && $data['photo']->isValid()) {
                    $photoPath = $this->uploadPhoto($data['photo'], $user->uuid_user);
                    $detailsData['photo_path'] = $photoPath;
                    $detailsData['photo'] = null;
                }
                
                // Si une URL de photo externe est fournie
                if (isset($data['photo_url']) && !empty($data['photo_url'])) {
                    if ($user->details->photo_path) {
                        $this->deletePhoto($user->details->photo_path);
                    }
                    $detailsData['photo'] = $data['photo_url'];
                    $detailsData['photo_path'] = null;
                }
                
                // Supprimer la photo
                if (isset($data['remove_photo']) && $data['remove_photo']) {
                    $this->deletePhoto($user->details->photo_path);
                    $detailsData['photo'] = null;
                    $detailsData['photo_path'] = null;
                }
                
                $detailsData['updated_by'] = $user->uuid_user;
                $user->details->update($detailsData);
            } else {
                // Créer les détails si inexistants
                $detailsData = $this->prepareDetailsData($data);
                $detailsData['user_uuid'] = $user->uuid_user;
                $detailsData['uuid_user_details'] = (string) Str::uuid();
                $detailsData['created_by'] = $user->uuid_user;
                
                if (isset($data['photo']) && $data['photo']->isValid()) {
                    $photoPath = $this->uploadPhoto($data['photo'], $user->uuid_user);
                    $detailsData['photo_path'] = $photoPath;
                }
                
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
    
    public function uploadPhoto($photo, string $userUuid): string
    {
        $extension = $photo->getClientOriginalExtension();
        $filename = sprintf(
            'profile_%s_%s.%s',
            $userUuid,
            now()->timestamp,
            $extension
        );
        
        $path = 'profiles/' . $userUuid;
        
        $this->deletePhotoByUser($userUuid);
        
        $basePath = base_path(env('UPLOADS_PATH', '../public_html/upload/documents-test/'));
        if (!is_dir($basePath)) {
            mkdir($basePath, 0777, true);
        }

        $fullPath = $basePath . '/' . $path;
        
        $photo->move($fullPath, $filename);
        
        return $path . '/' . $filename;
    }
    
    /**
     * Supprimer une photo par son chemin
     */
    public function deletePhoto(?string $path): void
    {
        if (!$path) {
            return;
        }
        
        $fullPath = base_path(env('UPLOADS_PATH', '../public_html/upload/documents-test/') . $path);
        
        if (file_exists($fullPath)) {
            @unlink($fullPath);
        }
        
        $dirPath = dirname($fullPath);
        if (is_dir($dirPath) && count(scandir($dirPath)) <= 2) {
            @rmdir($dirPath);
        }
    }
    
    /**
     * Supprimer la photo d'un utilisateur
     */
    private function deletePhotoByUser(string $userUuid): void
    {
        $path = 'profiles/' . $userUuid;
        $fullPath = base_path(env('UPLOADS_PATH', '../public_html/upload/documents-test/') . $path);
        
        if (is_dir($fullPath)) {
            $files = glob($fullPath . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
            @rmdir($fullPath);
        }
    }

    public function notifyPhotoDeleted(User $user): void
    {
        $this->notificationService->create([
            'user_uuid' => $user->uuid_user,
            'group_notif_uuid' => $this->getCompteGroupUuid(),
            'title' => '🖼️ Photo de profil supprimée',
            'body' => 'Votre photo de profil a été supprimée avec succès.',
            'type' => 'account',
            'metadata' => [
                'deleted_at' => now()->toISOString(),
            ],
            'channel' => 'database',
            'created_by' => $user->uuid_user,
        ]);
    }

    private function getCompteGroupUuid(): ?string
    {
        $group = GroupNotif::where('code', 'compte')->first();
        return $group?->uuid_group_notif;
    }
}