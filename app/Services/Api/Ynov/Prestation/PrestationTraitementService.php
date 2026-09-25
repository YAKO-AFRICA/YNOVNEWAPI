<?php

namespace App\Services\Api\Ynov\Prestation;

use App\Models\Api\Ynov\Prestation;
use App\Models\Api\Ynov\parameter\ActivityLog;
use App\Models\Api\Ynov\parameter\GroupNotif;
use App\Services\Api\Ynov\NotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PrestationTraitementService
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Traiter une prestation (accepter ou rejeter)
     */
    public function traiter(Prestation $prestation, array $data, string $userUuid): array
    {
        return DB::transaction(function () use ($prestation, $data, $userUuid) {
            $oldValues = $prestation->toArray();
            $action = $data['action'] ?? 'accepter'; // 'accepter' ou 'rejeter'

            // Vérifier que la prestation peut être traitée
            if (!in_array($prestation->status, ['transmis', 'en_attente'])) {
                return [
                    'success' => false,
                    'message' => 'Cette prestation ne peut pas être traitée. Car elle est actuellement ' . $prestation->status . '.',
                    'code' => 'PRESTATION_NON_TRAITABLE',
                    'status' => 422,
                ];
            }

            // Valider l'action
            if (!in_array($action, ['accepter', 'rejeter'])) {
                return [
                    'success' => false,
                    'message' => 'L\'action doit être "accepter" ou "rejeter".',
                    'code' => 'ACTION_INVALIDE',
                    'status' => 422,
                ];
            }

            // Fusionner les motifs de traitement avec les motifs existants
            $motifsActuels = $prestation->motif_traitement ?? [];
            $nouveauxMotifs = $data['motif_traitements'] ?? [];
            
            if ($action === 'accepter') {
                // Stocker les UUID des motifs dans un tableau sous la clé 'traitement'
                $motifsTraitement = $motifsActuels['traitement'] ?? [];
                $motifsTraitement = array_merge($motifsTraitement, $nouveauxMotifs);
                $motifsTraitement = array_unique($motifsTraitement);
                
                $newStatus = 'accepte';
                $motifKey = 'traitement';
            } else {
                // Stocker les UUID des motifs dans un tableau sous la clé 'rejet'
                $motifsRejet = $motifsActuels['rejet'] ?? [];
                $motifsRejet = array_merge($motifsRejet, $nouveauxMotifs);
                $motifsRejet = array_unique($motifsRejet);
                
                $newStatus = 'rejete';
                $motifKey = 'rejet';
            }

            $prestation->update([
                'status' => $newStatus,
                'traiter_par' => $userUuid,
                'date_traitement' => Carbon::now(),
                'motif_traitement' => array_merge($motifsActuels, [$motifKey => $action === 'accepter' ? $motifsTraitement : $motifsRejet]),
                'observation' => $data['observation'] ?? $prestation->observation,
                'updated_by' => $userUuid,
            ]);

            $this->logActivity($userUuid, $action, $prestation, $oldValues, $data);
            $this->sendNotification($prestation, $action, $userUuid);

            $prestation = $prestation->fresh();
            $typePrestationLibelle = $prestation->typePrestation->libelle ?? '';

            return [
                'success' => true,
                'message' => $action === 'accepter' 
                    ? "Prestation acceptée avec succès pour {$typePrestationLibelle}."
                    : "Prestation rejetée pour {$typePrestationLibelle}.",
                'code' => $action === 'accepter' ? 'PRESTATION_ACCEPTEE' : 'PRESTATION_REJETEE',
                'data' => $prestation->load(['client', 'typePrestation', 'gestionnaire', 'rdv']),
            ];
        });
    }

    /**
     * Annuler une prestation (admin)
     */

    /**
     * Annuler une prestation (admin)
     */
    public function annuler(Prestation $prestation, array $data, string $userUuid): array
    {
        return DB::transaction(function () use ($prestation, $data, $userUuid) {
            $oldValues = $prestation->toArray();

            // Vérifier que la prestation peut être annulée
            if (in_array($prestation->status, ['annule'])) {
                return [
                    'success' => false,
                    'message' => 'Cette prestation est déjà annulée.',
                    'code' => 'PRESTATION_DEJA_ANNULEE',
                    'status' => 422,
                ];
            }

            // Fusionner les motifs de traitement avec les motifs existants
            $motifsActuels = $prestation->motif_traitement ?? [];
            $nouveauxMotifs = $data['motif_traitements'] ?? [];
            
            // Stocker les UUID des motifs dans un tableau sous la clé 'annulation'
            $motifsAnnulation = $motifsActuels['annulation'] ?? [];
            $motifsAnnulation = array_merge($motifsAnnulation, $nouveauxMotifs);
            $motifsAnnulation = array_unique($motifsAnnulation);

            $prestation->update([
                'status' => 'annule',
                'traiter_par' => $userUuid,
                'date_traitement' => Carbon::now(),
                'motif_traitement' => array_merge($motifsActuels, ['annulation' => $motifsAnnulation]),
                'observation' => $data['observation'] ?? $prestation->observation,
                'updated_by' => $userUuid,
            ]);

            $this->logActivity($userUuid, 'annuler', $prestation, $oldValues, $data);
            $this->sendNotification($prestation, 'annuler', $userUuid);

            return [
                'success' => true,
                'message' => 'Prestation annulée avec succès.',
                'code' => 'PRESTATION_ANNULEE',
                'data' => $prestation->fresh()->load(['client', 'typePrestation', 'gestionnaire', 'rdv']),
            ];
        });
    }

    /**
     * Historique des traitements d'une prestation
     */
    public function getHistorique(Prestation $prestation): array
    {
        $logs = ActivityLog::where('resource_type', 'prestation')
            ->where('resource_id', $prestation->uuid_prestation)
            ->whereIn('action', ['accepter', 'rejeter', 'annuler', 'assignation_auto', 'reassignation_manuelle'])
            ->with(['user.details'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($log) {
                return [
                    'uuid' => $log->uuid_activity_log,
                    'action' => $log->action,
                    'action_type' => $log->action_type,
                    'description' => $log->description,
                    'user' => [
                        'uuid' => $log->user?->uuid_user,
                        'nom' => $log->user?->details?->nom,
                        'prenoms' => $log->user?->details?->prenoms,
                        'email' => $log->user?->email,
                    ],
                    'old_values' => $log->old_values,
                    'new_values' => $log->new_values,
                    'created_at' => $log->created_at,
                ];
            });

        return [
            'prestation_uuid' => $prestation->uuid_prestation,
            'prestation_code' => $prestation->code,
            'historique' => $logs,
        ];
    }

    /**
     * Logger l'activité
     */
    private function logActivity(string $userUuid, string $action, Prestation $prestation, array $oldValues, array $newValues): void
    {
        ActivityLog::log([
            'user_uuid' => $userUuid,
            'action' => $action,
            'action_type' => 'traitement',
            'module' => 'prestations',
            'description' => ucfirst($action) . " la prestation {$prestation->code}",
            'resource_type' => 'prestation',
            'resource_id' => $prestation->uuid_prestation,
            'old_values' => $oldValues,
            'new_values' => array_merge($oldValues, $newValues),
            'level' => 'info',
        ]);
    }

    /**
     * Envoyer les notifications
     */
    private function sendNotification(Prestation $prestation, string $action, string $userUuid): void
    {
        $actionLabels = [
            'accepter' => 'acceptée',
            'rejeter' => 'rejetée',
            'annuler' => 'annulée',
        ];

        $actionLabel = $actionLabels[$action] ?? $action;
        $typePrestationLibelle = $prestation->typePrestation->libelle ?? '';

        // Notification au client
        $this->notificationService->create([
            'user_uuid' => $prestation->client_uuid,
            'group_notif_uuid' => $this->getPrestationGroupUuid(),
            'title' => "📋 Prestation {$actionLabel}",
            'body' => "Votre prestation N° {$prestation->code} ({$typePrestationLibelle}) a été {$actionLabel}.",
            'type' => 'PRESTATION',
            'metadata' => [
                'prestation_uuid' => $prestation->uuid_prestation,
                'prestation_code' => $prestation->code,
                'action' => $action,
            ],
            'channel' => 'database',
            'created_by' => $userUuid,
        ]);

        // Notification au gestionnaire si assigné
        if ($prestation->gestionnaire_uuid && $prestation->gestionnaire_uuid !== $userUuid) {
            $this->notificationService->create([
                'user_uuid' => $prestation->gestionnaire_uuid,
                'group_notif_uuid' => $this->getPrestationGroupUuid(),
                'title' => "📋 Prestation {$actionLabel}",
                'body' => "La prestation N° {$prestation->code} ({$typePrestationLibelle}) a été {$actionLabel}.",
                'type' => 'PRESTATION',
                'metadata' => [
                    'prestation_uuid' => $prestation->uuid_prestation,
                    'prestation_code' => $prestation->code,
                    'action' => $action,
                ],
                'channel' => 'database',
                'created_by' => $userUuid,
            ]);
        }
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