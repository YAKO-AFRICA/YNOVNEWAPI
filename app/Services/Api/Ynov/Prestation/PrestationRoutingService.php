<?php

namespace App\Services\Api\Ynov\Prestation;

use App\Models\Api\Ynov\Prestation;
use App\Models\Api\Ynov\parameter\ActivityLog;
use App\Models\Api\Ynov\parameter\GroupNotif;
use App\Models\Api\Ynov\parameter\User;
use App\Services\Api\Ynov\NotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PrestationRoutingService
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Assignation automatique d'une prestation à un gestionnaire
     * Doit être appelée immédiatement après la création de la prestation
     */
    public function assignerAutomatiquement(Prestation $prestation): array
    {
        // Vérifier si la prestation peut être assignée
        if (!$this->peutEtreAssigne($prestation)) {
            Log::debug('Prestation non assignable', ['prestation_uuid' => $prestation->uuid_prestation]);
            return [
                'success' => false,
                'code' => 'PRESTATION_NON_ASSIGNABLE',
                'message' => 'Cette prestation ne peut pas être assignée automatiquement.',
                'data' => null
            ];
        }

        // Récupérer les gestionnaires disponibles
        $gestionnaires = $this->getGestionnairesDisponibles();

        if (empty($gestionnaires)) {
            // Si aucun gestionnaire disponible, on laisse en attente
            return [
                'success' => false,
                'code' => 'AUCUN_GESTIONNAIRE',
                'message' => 'Aucun gestionnaire de prestation disponible.',
                'data' => null
            ];
        }

        // Vérifier si le client a déjà des prestations avec un gestionnaire
        $gestionnaireExistant = $this->getGestionnaireExistantPourClient($prestation);

        if ($gestionnaireExistant) {
            // Assigner au même gestionnaire
            return $this->assignerAuGestionnaire($prestation, $gestionnaireExistant);
        }

        // Distribution équitable
        $gestionnaireChoisi = $this->getGestionnaireParDistributionEquitable($prestation, $gestionnaires);
        if (!$gestionnaireChoisi) {
            return [
                'success' => false,
                'code' => 'DISTRIBUTION_ECHEC',
                'message' => 'Impossible de déterminer un gestionnaire.',
                'data' => null
            ];
        }
        return $this->assignerAuGestionnaire($prestation, $gestionnaireChoisi);
    }

    /**
     * Assigner au gestionnaire et passer en transmis
     */
    private function assignerAuGestionnaire(Prestation $prestation, string $gestionnaireUuid): array
    {
        return DB::transaction(function () use ($prestation, $gestionnaireUuid) {
            $oldStatus = $prestation->status;

            $prestation->update([
                'gestionnaire_uuid' => $gestionnaireUuid,
                'status' => 'transmis',
                'date_transmission' => now(),
                'updated_by' => 'system',
            ]);

            // Log
            ActivityLog::log([
                'user_uuid' => 'system',
                'action' => 'assignation_auto',
                'action_type' => 'routing',
                'module' => 'prestations',
                'description' => "Assignation automatique de la prestation {$prestation->code} au gestionnaire {$gestionnaireUuid}",
                'resource_type' => 'prestation',
                'resource_id' => $prestation->uuid_prestation,
                'old_values' => ['status' => $oldStatus, 'gestionnaire_uuid' => null],
                'new_values' => ['status' => 'transmis', 'gestionnaire_uuid' => $gestionnaireUuid],
                'level' => 'info',
            ]);

            // Notification au gestionnaire
            $this->notificationService->create([
                'user_uuid' => $gestionnaireUuid,
                'group_notif_uuid' => $this->getPrestationGroupUuid(),
                'title' => '📋 Nouvelle prestation assignée',
                'body' => "La prestation {$prestation->code} vous a été assignée automatiquement.",
                'type' => 'PRESTATION',
                'metadata' => [
                    'prestation_uuid' => $prestation->uuid_prestation,
                    'prestation_code' => $prestation->code,
                    'action' => 'assignation_auto',
                ],
                'channel' => 'database',
                'created_by' => null,
            ]);

            // Notification au client
            $gestionnaire = User::where('uuid_user', $gestionnaireUuid)->with('details')->first();
            $gestionnaireNom = $gestionnaire?->details?->nom ?? null;
            $gestionnairePrenoms = $gestionnaire?->details?->prenoms ?? null;
            $gestionnaireLabel = $gestionnaireNom || $gestionnairePrenoms 
                ? trim(($gestionnaireNom ?? '') . ' ' . ($gestionnairePrenoms ?? '')) 
                : ($gestionnaire?->email ?? '');

            $this->notificationService->create([
                'user_uuid' => $prestation->client_uuid,
                'group_notif_uuid' => $this->getPrestationGroupUuid(),
                'title' => '📋 Prestation transmise',
                'body' => "Votre prestation N° {$prestation->code} a été transmise au gestionnaire {$gestionnaireLabel}.",
                'type' => 'PRESTATION',
                'metadata' => [
                    'prestation_uuid' => $prestation->uuid_prestation,
                    'prestation_code' => $prestation->code,
                    'action' => 'assignation_auto',
                ],
                'channel' => 'database',
                'created_by' => null,
            ]);

            return [
                'success' => true,
                'code' => 'PRESTATION_ASSIGNEE_AUTO',
                'message' => 'Prestation assignée automatiquement avec succès.',
                'data' => $prestation->fresh()->load(['gestionnaire', 'client'])
            ];
        });
    }

    /**
     * Vérifier si la prestation peut être assignée automatiquement
     */
    private function peutEtreAssigne(Prestation $prestation): bool
    {
        // Une prestation en attente sans gestionnaire peut être assignée
        return $prestation->status === 'en_attente' && is_null($prestation->gestionnaire_uuid);
    }

    /**
     * Vérifier si le client a déjà des prestations avec un gestionnaire
     */
    private function getGestionnaireExistantPourClient(Prestation $prestation): ?string
    {
        if (!$prestation->client_uuid) {
            return null;
        }

        $prestationExistante = Prestation::where('client_uuid', $prestation->client_uuid)
            ->where('uuid_prestation', '!=', $prestation->uuid_prestation)
            ->whereNotNull('gestionnaire_uuid')
            ->whereNotIn('status', ['annule', 'rejete'])
            ->where('created_at', '>=', now()->subDays(30)) // Derniers 30 jours
            ->first();

        return $prestationExistante?->gestionnaire_uuid;
    }

    /**
     * Récupérer les gestionnaires de prestations disponibles
     */
    private function getGestionnairesDisponibles(): array
    {
        // Récupérer les utilisateurs actifs qui ont le rôle de gestionnaire_prestation
        $users = User::whereHas('role', function ($query) {
            $query->where('code', 'gestionnaire_prestation');
        })
        ->where('status', 'actif')
        ->get();

        return $users->pluck('uuid_user')->toArray();
    }

    /**
     * Distribution équitable des prestations
     */
    private function getGestionnaireParDistributionEquitable(Prestation $prestation, array $gestionnaires): ?string
    {
        // Récupérer les compteurs en une seule requête pour performance
        $counts = Prestation::query()
            ->select('gestionnaire_uuid', DB::raw('count(*) as cnt'))
            ->whereIn('gestionnaire_uuid', $gestionnaires)
            ->where('created_at', '>=', now()->startOfDay()) // Aujourd'hui
            ->whereNotIn('status', ['annule', 'rejete'])
            ->groupBy('gestionnaire_uuid')
            ->pluck('cnt', 'gestionnaire_uuid')
            ->toArray();

        $charges = [];
        foreach ($gestionnaires as $gestionnaireUuid) {
            $charges[$gestionnaireUuid] = isset($counts[$gestionnaireUuid]) ? (int) $counts[$gestionnaireUuid] : 0;
        }

        if (empty($charges)) {
            return null;
        }

        $minCharge = min($charges);
        $gestionnairesMoinsCharges = array_keys($charges, $minCharge);

        // Si plusieurs gestionnaires ont la même charge, choisir aléatoirement
        return $gestionnairesMoinsCharges[array_rand($gestionnairesMoinsCharges)];
    }

    /**
     * Assigner automatiquement toutes les prestations en attente
     * (À appeler via un endpoint public)
     */
    public function assignerToutesLesPrestationsEnAttente(): array
    {
        $prestations = Prestation::where('status', 'en_attente')
            ->whereNull('gestionnaire_uuid')
            ->get();

        $results = [
            'total' => $prestations->count(),
            'assignees' => 0,
            'echecs' => 0,
            'details' => [],
            'executed_at' => now()->format('Y-m-d H:i:s')
        ];

        foreach ($prestations as $prestation) {
            $result = $this->assignerAutomatiquement($prestation);

            if ($result['success']) {
                $results['assignees']++;
                $results['details'][] = [
                    'prestation_code' => $prestation->code,
                    'gestionnaire_uuid' => $prestation->fresh()->gestionnaire_uuid,
                    'status' => 'assignee',
                ];
            } else {
                $results['echecs']++;
                $results['details'][] = [
                    'prestation_code' => $prestation->code,
                    'status' => 'echec',
                    'raison' => $result['message'],
                ];
            }
        }

        return $results;
    }

    /**
     * Réassigner manuellement une prestation à un autre gestionnaire
     */
    public function reassignerManuellement(Prestation $prestation, array $data, string $userUuid): array
    {
        return DB::transaction(function () use ($prestation, $data, $userUuid) {
            $oldGestionnaireUuid = $prestation->gestionnaire_uuid;
            $nouveauGestionnaireUuid = $data['gestionnaire_uuid'];

            // Vérifier que le nouveau gestionnaire existe
            $gestionnaire = User::where('uuid_user', $nouveauGestionnaireUuid)->first();
            if (!$gestionnaire) {
                return [
                    'success' => false,
                    'message' => 'Le gestionnaire n\'existe pas.',
                    'code' => 'GESTIONNAIRE_NOT_FOUND',
                    'status' => 422,
                ];
            }

            // Vérifier que le gestionnaire a le rôle requis
            if (!$gestionnaire->hasRole('gestionnaire_prestation')) {
                return [
                    'success' => false,
                    'message' => 'Cet utilisateur n\'est pas un gestionnaire de prestation.',
                    'code' => 'NOT_GESTIONNAIRE_PRESTATION',
                    'status' => 422,
                ];
            }

            // Vérifier que ce n'est pas le même gestionnaire
            if ($prestation->gestionnaire_uuid === $nouveauGestionnaireUuid) {
                return [
                    'success' => false,
                    'message' => 'La prestation est déjà assignée à ce gestionnaire.',
                    'code' => 'SAME_GESTIONNAIRE',
                    'status' => 422,
                ];
            }

            // Préparer les données de mise à jour
            $updateData = [
                'gestionnaire_uuid' => $nouveauGestionnaireUuid,
                'observation' => $data['observation'] ?? $prestation->observation,
                'updated_by' => $userUuid,
            ];

            // Mise à jour optionnelle du statut
            if (!empty($data['status'])) {
                $updateData['status'] = $data['status'];
            }

            $prestation->update($updateData);

            // Log
            ActivityLog::log([
                'user_uuid' => $userUuid,
                'action' => 'reassignation_manuelle',
                'action_type' => 'routing',
                'module' => 'prestations',
                'description' => "Réassignation manuelle de la prestation {$prestation->code} du gestionnaire {$oldGestionnaireUuid} vers {$nouveauGestionnaireUuid}",
                'resource_type' => 'prestation',
                'resource_id' => $prestation->uuid_prestation,
                'old_values' => ['gestionnaire_uuid' => $oldGestionnaireUuid],
                'new_values' => ['gestionnaire_uuid' => $nouveauGestionnaireUuid],
                'level' => 'info',
            ]);

            // Notification au nouveau gestionnaire
            $this->notificationService->create([
                'user_uuid' => $nouveauGestionnaireUuid,
                'group_notif_uuid' => $this->getPrestationGroupUuid(),
                'title' => '📋 Prestation réassignée',
                'body' => "La prestation {$prestation->code} vous a été réassignée.",
                'type' => 'PRESTATION',
                'metadata' => [
                    'prestation_uuid' => $prestation->uuid_prestation,
                    'prestation_code' => $prestation->code,
                    'action' => 'reassignation_manuelle',
                ],
                'channel' => 'database',
                'created_by' => $userUuid,
            ]);

            // Notification à l'ancien gestionnaire si existant
            if ($oldGestionnaireUuid) {
                $this->notificationService->create([
                    'user_uuid' => $oldGestionnaireUuid,
                    'group_notif_uuid' => $this->getPrestationGroupUuid(),
                    'title' => '📋 Prestation réassignée',
                    'body' => "La prestation {$prestation->code} a été réassignée à un autre gestionnaire.",
                    'type' => 'PRESTATION',
                    'metadata' => [
                        'prestation_uuid' => $prestation->uuid_prestation,
                        'prestation_code' => $prestation->code,
                        'action' => 'reassignation_manuelle',
                    ],
                    'channel' => 'database',
                    'created_by' => $userUuid,
                ]);
            }

            return [
                'success' => true,
                'code' => 'PRESTATION_REASSIGNEE',
                'message' => 'Prestation réassignée avec succès.',
                'data' => $prestation->fresh()->load(['gestionnaire', 'client'])
            ];
        });
    }

    /**
     * Obtenir l'UUID du groupe de notifications pour les prestations
     */
    private function getPrestationGroupUuid(): ?string
    {
        $group = GroupNotif::where('code', 'prestations')->first();
        return $group?->uuid_group_notif;
    }
}