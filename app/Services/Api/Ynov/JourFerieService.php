<?php
// app/Services/Api/Ynov/JourFerieService.php

namespace App\Services\Api\Ynov;

use App\Models\Api\Ynov\parameter\ActivityLog;
use App\Models\Api\Ynov\parameter\JourFerie;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class JourFerieService
{
    /**
     * Liste des jours fériés avec filtres
     */
    public function getFeries(array $filters = [], int $perPage = 20)
    {
        $query = JourFerie::query();

        if (isset($filters['year'])) {
            $query->forYear($filters['year']);
        }

        if (isset($filters['est_recurrent'])) {
            if ($filters['est_recurrent']) {
                $query->recurrent();
            } else {
                $query->nonRecurrent();
            }
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('libelle', 'LIKE', "%{$search}%")
                  ->orWhere('code', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        return $query->orderBy('date')->paginate($perPage);
    }

    /**
     * Créer un jour férié
     */
    public function create(array $data, string $creatorUuid): JourFerie
    {
        return DB::transaction(function () use ($data, $creatorUuid) {
            // Vérifier si la date existe déjà
            if (JourFerie::where('date', $data['date'])->exists()) {
                throw ValidationException::withMessages([
                    'date' => ['Cette date est déjà enregistrée comme jour férié.']
                ]);
            }

            $ferie = JourFerie::create([
                'uuid_jour_ferie' => (string) Str::uuid(),
                'date' => $data['date'],
                'libelle' => $data['libelle'],
                'est_recurrent' => $data['est_recurrent'] ?? false,
                'code' => $data['code'] ?? null,
                'description' => $data['description'] ?? null,
                'created_by' => $creatorUuid,
            ]);

            ActivityLog::log([
                'user_uuid' => $creatorUuid,
                'action' => 'create',
                'action_type' => 'crud',
                'module' => 'jour_feries',
                'description' => "Création du jour férié : {$ferie->libelle} ({$ferie->date})",
                'resource_type' => 'jour_ferie',
                'resource_id' => $ferie->uuid_jour_ferie,
                'new_values' => $ferie->toArray(),
                'level' => 'info',
            ]);

            return $ferie;
        });
    }

    /**
     * Mettre à jour un jour férié
     */
    public function update(JourFerie $ferie, array $data, string $updaterUuid): JourFerie
    {
        return DB::transaction(function () use ($ferie, $data, $updaterUuid) {
            // Vérifier si la date existe déjà (pour un autre jour férié)
            if (isset($data['date']) && 
                JourFerie::where('date', $data['date'])
                    ->where('uuid_jour_ferie', '!=', $ferie->uuid_jour_ferie)
                    ->exists()) {
                throw ValidationException::withMessages([
                    'date' => ['Cette date est déjà enregistrée comme jour férié.']
                ]);
            }

            $oldValues = $ferie->toArray();

            $ferie->update([
                'date' => $data['date'] ?? $ferie->date,
                'libelle' => $data['libelle'] ?? $ferie->libelle,
                'est_recurrent' => $data['est_recurrent'] ?? $ferie->est_recurrent,
                'code' => $data['code'] ?? $ferie->code,
                'description' => $data['description'] ?? $ferie->description,
                'updated_by' => $updaterUuid,
            ]);

            ActivityLog::log([
                'user_uuid' => $updaterUuid,
                'action' => 'update',
                'action_type' => 'crud',
                'module' => 'jour_feries',
                'description' => "Mise à jour du jour férié : {$ferie->libelle} ({$ferie->date})",
                'resource_type' => 'jour_ferie',
                'resource_id' => $ferie->uuid_jour_ferie,
                'old_values' => $oldValues,
                'new_values' => $ferie->toArray(),
                'level' => 'info',
            ]);

            return $ferie->fresh();
        });
    }

    /**
     * Supprimer un jour férié
     */
    public function delete(JourFerie $ferie, string $deleterUuid): void
    {
        $ferie->update([
            'deleted_by' => $deleterUuid,
        ]);

        $ferie->delete();

        ActivityLog::log([
            'user_uuid' => $deleterUuid,
            'action' => 'delete',
            'action_type' => 'crud',
            'module' => 'jour_feries',
            'description' => "Suppression du jour férié : {$ferie->libelle} ({$ferie->date})",
            'resource_type' => 'jour_ferie',
            'resource_id' => $ferie->uuid_jour_ferie,
            'level' => 'warning',
        ]);
    }

    /**
     * Vérifier si une date est un jour férié
     */
    public function isFerie($date): bool
    {
        // S'assurer que $date est une instance de Carbon ou un string valide
        if (!$date instanceof Carbon && !$date instanceof \DateTime) {
            $date = Carbon::parse($date);
        }
        
        return JourFerie::isFerie($date);
    }

    /**
     * Récupérer tous les jours fériés pour une période
     */
    public function getFeriesBetween($dateDebut, $dateFin): array
    {
        // S'assurer que les dates sont des instances de Carbon
        if (!$dateDebut instanceof Carbon) {
            $dateDebut = Carbon::parse($dateDebut);
        }
        if (!$dateFin instanceof Carbon) {
            $dateFin = Carbon::parse($dateFin);
        }
        
        return JourFerie::getFeriesBetween($dateDebut, $dateFin);
    }

    /**
     * Récupérer tous les jours fériés d'une année
     */
    public function getFeriesForYear(int $year): array
    {
        return JourFerie::getFeriesForYear($year);
    }
    
    /**
     * Récupérer tous les jours fériés d'une année
     */
    // public function getFeriesForYear(int $year): array
    // {
    //     // Utiliser la méthode sécurisée
    //     return JourFerie::getFeriesForYearSafe($year);
    // }
    /**
     * Vérifier si une date est un jour ouvré (non férié et non week-end)
     */
    public function isJourOuvre($date): bool
    {
        // S'assurer que $date est une instance de Carbon
        if (!$date instanceof Carbon) {
            $date = Carbon::parse($date);
        }
        
        // Vérifier si c'est un week-end
        if (in_array($date->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY])) {
            return false;
        }
        
        // Vérifier si c'est un jour férié
        if ($this->isFerie($date)) {
            return false;
        }
        
        return true;
    }

    /**
     * Récupérer les prochains jours ouvrés
     */
    public function getProchainsJoursOuvres(int $nbJours = 30, string $dateDebut = null): array
    {
        $dateDebut = $dateDebut ? Carbon::parse($dateDebut) : Carbon::today();
        $jours = [];
        $current = clone $dateDebut;
        
        while (count($jours) < $nbJours) {
            if ($this->isJourOuvre($current)) {
                $jours[] = $current->format('Y-m-d');
            }
            $current->addDay();
        }
        
        return $jours;
    }

    /**
     * Statistiques des jours fériés
     */
    public function getStats(): array
    {
        return [
            'total' => JourFerie::count(),
            'recurrent' => JourFerie::recurrent()->count(),
            'non_recurrent' => JourFerie::nonRecurrent()->count(),
            'this_year' => JourFerie::forYear(now()->year)->count(),
        ];
    }
}