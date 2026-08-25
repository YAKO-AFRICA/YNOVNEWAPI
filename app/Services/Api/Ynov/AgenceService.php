<?php
// app/Services/Api/Ynov/AgenceService.php
namespace App\Services\Api\Ynov;

use App\Models\Api\Ynov\parameter\ActivityLog;
use App\Models\Api\Ynov\parameter\Agence;
use App\Models\Api\Ynov\parameter\AgenceHoraire;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// class AgenceService
// {
//     /**
//      * Créer une nouvelle agence
//      */
//     public function create(array $data, string $creatorUuid): Agence
//     {
//         return DB::transaction(function () use ($data, $creatorUuid) {
//             // Formater les horaires
//             $horaires = $this->formatHoraires($data['horaires'] ?? []);
            
//             $agence = Agence::create([
//                 'uuid_agence' => (string) Str::uuid(),
//                 'code' => $data['code'],
//                 'libelle' => $data['libelle'],
//                 'description' => $data['description'] ?? null,
//                 'reseau_uuid' => $data['reseau_uuid'] ?? null,
//                 'email' => $data['email'] ?? null,
//                 'telephone' => $data['telephone'] ?? null,
//                 'telephone_2' => $data['telephone_2'] ?? null,
//                 'adresse' => $data['adresse'] ?? null,
//                 'ville' => $data['ville'] ?? null,
//                 'quartier' => $data['quartier'] ?? null,
//                 'code_postal' => $data['code_postal'] ?? null,
//                 'pays' => $data['pays'] ?? 'Côte d\'Ivoire',
//                 'latitude' => $data['latitude'] ?? null,
//                 'longitude' => $data['longitude'] ?? null,
//                 'horaires' => $horaires,
//                 'photo' => $data['photo'] ?? null,
//                 'photos' => $data['photos'] ?? null,
//                 'responsable' => $data['responsable'] ?? null,
//                 'site_web' => $data['site_web'] ?? null,
//                 'status' => $data['status'] ?? 'actif',
//                 'created_by' => $creatorUuid,
//             ]);

//             // Journaliser l'action
//             ActivityLog::log([
//                 'user_uuid' => $creatorUuid,
//                 'action' => 'create',
//                 'action_type' => 'crud',
//                 'module' => 'agences',
//                 'description' => "Création de l'agence : {$agence->libelle}",
//                 'resource_type' => 'agence',
//                 'resource_id' => $agence->uuid_agence,
//                 'new_values' => $agence->toArray(),
//                 'level' => 'info',
//             ]);

//             return $agence;
//         });
//     }

//     /**
//      * Mettre à jour une agence
//      */
//     public function update(Agence $agence, array $data, string $updaterUuid): Agence
//     {
//         return DB::transaction(function () use ($agence, $data, $updaterUuid) {
//             $oldValues = $agence->toArray();
            
//             // Formater les horaires
//             if (isset($data['horaires'])) {
//                 $data['horaires'] = $this->formatHoraires($data['horaires']);
//             }
            
//             $agence->update([
//                 'code' => $data['code'] ?? $agence->code,
//                 'libelle' => $data['libelle'] ?? $agence->libelle,
//                 'description' => $data['description'] ?? $agence->description,
//                 'reseau_uuid' => $data['reseau_uuid'] ?? $agence->reseau_uuid,
//                 'email' => $data['email'] ?? $agence->email,
//                 'telephone' => $data['telephone'] ?? $agence->telephone,
//                 'telephone_2' => $data['telephone_2'] ?? $agence->telephone_2,
//                 'adresse' => $data['adresse'] ?? $agence->adresse,
//                 'ville' => $data['ville'] ?? $agence->ville,
//                 'quartier' => $data['quartier'] ?? $agence->quartier,
//                 'code_postal' => $data['code_postal'] ?? $agence->code_postal,
//                 'pays' => $data['pays'] ?? $agence->pays,
//                 'latitude' => $data['latitude'] ?? $agence->latitude,
//                 'longitude' => $data['longitude'] ?? $agence->longitude,
//                 'horaires' => $data['horaires'] ?? $agence->horaires,
//                 'photo' => $data['photo'] ?? $agence->photo,
//                 'photos' => $data['photos'] ?? $agence->photos,
//                 'responsable' => $data['responsable'] ?? $agence->responsable,
//                 'site_web' => $data['site_web'] ?? $agence->site_web,
//                 'status' => $data['status'] ?? $agence->status,
//                 'updated_by' => $updaterUuid,
//             ]);

//             // Journaliser l'action
//             ActivityLog::log([
//                 'user_uuid' => $updaterUuid,
//                 'action' => 'update',
//                 'action_type' => 'crud',
//                 'module' => 'agences',
//                 'description' => "Mise à jour de l'agence : {$agence->libelle}",
//                 'resource_type' => 'agence',
//                 'resource_id' => $agence->uuid_agence,
//                 'old_values' => $oldValues,
//                 'new_values' => $agence->toArray(),
//                 'level' => 'info',
//             ]);

//             return $agence->fresh();
//         });
//     }

//     /**
//      * Supprimer une agence (soft delete)
//      */
//     public function delete(Agence $agence, string $deleterUuid): void
//     {
//         $agence->update([
//             'status' => 'inactif',
//             'deleted_by' => $deleterUuid,
//         ]);
        
//         $agence->delete();

//         ActivityLog::log([
//             'user_uuid' => $deleterUuid,
//             'action' => 'delete',
//             'action_type' => 'crud',
//             'module' => 'agences',
//             'description' => "Suppression de l'agence : {$agence->libelle}",
//             'resource_type' => 'agence',
//             'resource_id' => $agence->uuid_agence,
//             'level' => 'warning',
//         ]);
//     }

//     /**
//      * Formater les horaires pour le stockage JSON
//      */
//     private function formatHoraires(array $horaires): array
//     {
//         $jours = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];
//         $formatted = [];
        
//         // Initialiser tous les jours par défaut
//         foreach ($jours as $jour) {
//             $formatted[$jour] = [
//                 'ouverture' => '08:00',
//                 'fermeture' => '17:30',
//                 'ferme' => false,
//             ];
//         }
        
//         // Appliquer les horaires fournis
//         foreach ($horaires as $horaire) {
//             if (isset($horaire['jour'])) {
//                 $jour = $horaire['jour'];
//                 $formatted[$jour] = [
//                     'ouverture' => $horaire['ouverture'] ?? null,
//                     'fermeture' => $horaire['fermeture'] ?? null,
//                     'ferme' => $horaire['ferme'] ?? false,
//                 ];
//             }
//         }
        
//         return $formatted;
//     }

//     /**
//      * Récupérer les agences avec filtres
//      */
//     public function getAgences(array $filters = [], int $perPage = 20)
//     {
//         $query = Agence::query()->with(['reseau']);
        
//         // Filtres
//         if (isset($filters['status'])) {
//             $query->where('status', $filters['status']);
//         }
        
//         if (isset($filters['reseau_uuid'])) {
//             $query->where('reseau_uuid', $filters['reseau_uuid']);
//         }
        
//         if (isset($filters['ville'])) {
//             $query->where('ville', 'LIKE', "%{$filters['ville']}%");
//         }
        
//         if (isset($filters['quartier'])) {
//             $query->where('quartier', 'LIKE', "%{$filters['quartier']}%");
//         }
        
//         if (isset($filters['search'])) {
//             $search = $filters['search'];
//             $query->where(function ($q) use ($search) {
//                 $q->where('libelle', 'LIKE', "%{$search}%")
//                   ->orWhere('description', 'LIKE', "%{$search}%")
//                   ->orWhere('adresse', 'LIKE', "%{$search}%")
//                   ->orWhere('ville', 'LIKE', "%{$search}%")
//                   ->orWhere('quartier', 'LIKE', "%{$search}%");
//             });
//         }
        
//         if (isset($filters['open_now']) && $filters['open_now']) {
//             $query->openNow();
//         }
        
//         if (isset($filters['latitude']) && isset($filters['longitude']) && isset($filters['radius'])) {
//             // Distance en kilomètres
//             $lat = $filters['latitude'];
//             $lng = $filters['longitude'];
//             $radius = $filters['radius'] ?? 10;
            
//             $query->whereRaw("
//                 (6371 * acos(
//                     cos(radians(?)) 
//                     * cos(radians(latitude)) 
//                     * cos(radians(longitude) - radians(?)) 
//                     + sin(radians(?)) 
//                     * sin(radians(latitude))
//                 )) <= ?
//             ", [$lat, $lng, $lat, $radius]);
//         }
        
//         return $query->orderBy('libelle')->paginate($perPage);
//     }
// }

class AgenceService
{
    /**
     * Créer une nouvelle agence avec ses horaires
     */
    public function create(array $data, string $creatorUuid): Agence
    {
        return DB::transaction(function () use ($data, $creatorUuid) {
            // Créer l'agence
            $agence = Agence::create([
                'uuid_agence' => (string) Str::uuid(),
                'code' => $data['code'],
                'libelle' => $data['libelle'],
                'description' => $data['description'] ?? null,
                'reseau_uuid' => $data['reseau_uuid'] ?? null,
                'email' => $data['email'] ?? null,
                'telephone' => $data['telephone'] ?? null,
                'telephone_2' => $data['telephone_2'] ?? null,
                'adresse' => $data['adresse'] ?? null,
                'ville' => $data['ville'] ?? null,
                'quartier' => $data['quartier'] ?? null,
                'code_postal' => $data['code_postal'] ?? null,
                'pays' => $data['pays'] ?? 'Côte d\'Ivoire',
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'photo' => $data['photo'] ?? null,
                'photos' => $data['photos'] ?? null,
                'responsable' => $data['responsable'] ?? null,
                'site_web' => $data['site_web'] ?? null,
                'config' => $data['config'] ?? null,
                'status' => $data['status'] ?? 'actif',
                'created_by' => $creatorUuid,
            ]);

            // Créer les horaires
            if (isset($data['horaires']) && !empty($data['horaires'])) {
                $this->syncHoraires($agence, $data['horaires'], $creatorUuid);
            }

            // Journaliser l'action
            ActivityLog::log([
                'user_uuid' => $creatorUuid,
                'action' => 'create',
                'action_type' => 'crud',
                'module' => 'agences',
                'description' => "Création de l'agence : {$agence->libelle}",
                'resource_type' => 'agence',
                'resource_id' => $agence->uuid_agence,
                'new_values' => $agence->toArray(),
                'level' => 'info',
            ]);

            return $agence->load('horaires');
        });
    }

    /**
     * Mettre à jour une agence et ses horaires
     */
    public function update(Agence $agence, array $data, string $updaterUuid): Agence
    {
        return DB::transaction(function () use ($agence, $data, $updaterUuid) {
            $oldValues = $agence->toArray();
            
            $agence->update([
                'code' => $data['code'] ?? $agence->code,
                'libelle' => $data['libelle'] ?? $agence->libelle,
                'description' => $data['description'] ?? $agence->description,
                'reseau_uuid' => $data['reseau_uuid'] ?? $agence->reseau_uuid,
                'email' => $data['email'] ?? $agence->email,
                'telephone' => $data['telephone'] ?? $agence->telephone,
                'telephone_2' => $data['telephone_2'] ?? $agence->telephone_2,
                'adresse' => $data['adresse'] ?? $agence->adresse,
                'ville' => $data['ville'] ?? $agence->ville,
                'quartier' => $data['quartier'] ?? $agence->quartier,
                'code_postal' => $data['code_postal'] ?? $agence->code_postal,
                'pays' => $data['pays'] ?? $agence->pays,
                'latitude' => $data['latitude'] ?? $agence->latitude,
                'longitude' => $data['longitude'] ?? $agence->longitude,
                'photo' => $data['photo'] ?? $agence->photo,
                'photos' => $data['photos'] ?? $agence->photos,
                'responsable' => $data['responsable'] ?? $agence->responsable,
                'site_web' => $data['site_web'] ?? $agence->site_web,
                'config' => $data['config'] ?? $agence->config,
                'status' => $data['status'] ?? $agence->status,
                'updated_by' => $updaterUuid,
            ]);

            // Mettre à jour les horaires
            if (isset($data['horaires'])) {
                $this->syncHoraires($agence, $data['horaires'], $updaterUuid);
            }

            ActivityLog::log([
                'user_uuid' => $updaterUuid,
                'action' => 'update',
                'action_type' => 'crud',
                'module' => 'agences',
                'description' => "Mise à jour de l'agence : {$agence->libelle}",
                'resource_type' => 'agence',
                'resource_id' => $agence->uuid_agence,
                'old_values' => $oldValues,
                'new_values' => $agence->toArray(),
                'level' => 'info',
            ]);

            return $agence->fresh('horaires');
        });
    }

    /**
     * Synchroniser les horaires d'une agence
     */
    public function syncHoraires(Agence $agence, array $horaires, string $creatorUuid): void
    {
        // Supprimer les anciens horaires
        $agence->horaires()->delete();

        // Créer les nouveaux horaires
        foreach ($horaires as $horaire) {
            AgenceHoraire::create([
                'uuid_horaire' => (string) Str::uuid(),
                'agence_uuid' => $agence->uuid_agence,
                'jour' => $horaire['jour'],
                'heure_ouverture' => $horaire['heure_ouverture'] ?? null,
                'heure_fermeture' => $horaire['heure_fermeture'] ?? null,
                'heure_ouverture_midi' => $horaire['heure_ouverture_midi'] ?? null,
                'heure_fermeture_midi' => $horaire['heure_fermeture_midi'] ?? null,
                'ferme' => $horaire['ferme'] ?? false,
                'commentaire' => $horaire['commentaire'] ?? null,
                // 'created_by' => $creatorUuid,
            ]);
        }
    }

    /**
     * Supprimer une agence (soft delete)
     */
    public function delete(Agence $agence, string $deleterUuid): void
    {
        $agence->update([
            'status' => 'inactif',
            'deleted_by' => $deleterUuid,
        ]);
        
        $agence->delete();

        ActivityLog::log([
            'user_uuid' => $deleterUuid,
            'action' => 'delete',
            'action_type' => 'crud',
            'module' => 'agences',
            'description' => "Suppression de l'agence : {$agence->libelle}",
            'resource_type' => 'agence',
            'resource_id' => $agence->uuid_agence,
            'level' => 'warning',
        ]);
    }

    /**
     * Récupérer les agences avec filtres
     */
    public function getAgences(array $filters = [], int $perPage = 20)
    {
        $query = Agence::query()->with(['reseau', 'horaires']);
        
        // Filtres
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (isset($filters['reseau_uuid'])) {
            $query->where('reseau_uuid', $filters['reseau_uuid']);
        }
        
        if (isset($filters['ville'])) {
            $query->where('ville', 'LIKE', "%{$filters['ville']}%");
        }
        
        if (isset($filters['quartier'])) {
            $query->where('quartier', 'LIKE', "%{$filters['quartier']}%");
        }
        
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('libelle', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%")
                  ->orWhere('adresse', 'LIKE', "%{$search}%")
                  ->orWhere('ville', 'LIKE', "%{$search}%")
                  ->orWhere('quartier', 'LIKE', "%{$search}%");
            });
        }
        
        if (isset($filters['open_now']) && $filters['open_now']) {
            $query->openNow();
        }
        
        if (isset($filters['latitude']) && isset($filters['longitude']) && isset($filters['radius'])) {
            $lat = $filters['latitude'];
            $lng = $filters['longitude'];
            $radius = $filters['radius'] ?? 10;
            
            $query->whereRaw("
                (6371 * acos(
                    cos(radians(?)) 
                    * cos(radians(latitude)) 
                    * cos(radians(longitude) - radians(?)) 
                    + sin(radians(?)) 
                    * sin(radians(latitude))
                )) <= ?
            ", [$lat, $lng, $lat, $radius]);
        }
        
        return $query->orderBy('libelle')->paginate($perPage);
    }
}