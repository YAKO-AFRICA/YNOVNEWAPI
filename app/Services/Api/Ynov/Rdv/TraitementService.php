<?php

namespace App\Services\Api\Ynov\Rdv;

use App\Models\Api\Ynov\parameter\ActivityLog;
use App\Models\Api\Ynov\parameter\GroupNotif;
use App\Models\Api\Ynov\parameter\User;
use App\Models\Api\Ynov\Rdv;
use App\Services\Api\Ynov\NotificationService;
use App\Services\Api\Ynov\Prestation\PrestationService;
use App\Services\Api\Ynov\Rdv\BordereauRdvService;
use App\Services\Api\Ynov\Rdv\RdvService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TraitementService
{
    public function __construct(
        private NotificationService $notificationService,
        private BordereauRdvService $bordereauRdvService,
         private PrestationService $prestationService
    ) {}

    public function traiter(Rdv $rdv, array $data, string $userUuid): array
    {
        return DB::transaction(function () use ($rdv, $data, $userUuid) {
            $oldValues = $rdv->toArray();

            // Vérifier que le RDV peut être traité
            if (!in_array($rdv->status, ['transmis', 'reporte'])) {
                return [
                    'success' => false,
                    'message' => 'Ce rendez-vous ne peut pas être traité. Car il est actuellement ' . $rdv->status . '.',
                    'code' => 'RDV_NON_TRAITABLE',
                    'status' => 422,
                ];
            }

            $isPermitted = $data['is_permitted'] ?? false; // false = conservation, true = sortie de portefeuille

            // Fusionner les motifs de traitement avec les motifs existants
            $motifsActuels = $rdv->motif_traitement ?? [];
            $nouveauxMotifs = $data['motif_traitements'] ?? [];
            
            // Stocker les UUID des motifs dans un tableau sous la clé 'traitement'
            $motifsTraitement = $motifsActuels['traitement'] ?? [];
            $motifsTraitement = array_merge($motifsTraitement, $nouveauxMotifs);
            $motifsTraitement = array_unique($motifsTraitement); // Éviter les doublons

            $data['status'] = 'inacheve'; // valeur par defaut inacheve pour la prestation si le status n'est pas fourni


            $prestation = $this->prestationService->createPrestation(
                $data,
                $userUuid
            );

            if (!$prestation) {
                return [
                    'success' => false,
                    'message' => 'Erreur lors de la création de la prestation.',
                    'code' => 'PRESTATION_CREATION_ERROR',
                    'status' => 500,
                ];
            }

            // mettre à jour le statut du detailBordereauRDV
            if ($rdv->detailBordereau) {
                $rdv->detailBordereau->update([
                    'status' => $rdv->detailBordereau->status,
                    'updated_by' => $userUuid,
                ]);
            }

            $rdv->update([
                'status' => 'traite',
                'motif_rdv' => $data['motif_rdv_uuid'] ?? $rdv->motif_rdv,
                'date_traitement' => Carbon::now(),
                'is_permitted' => $isPermitted,
                'motif_traitement' => array_merge($motifsActuels, ['traitement' => $motifsTraitement]),
                'observation' => $data['observation'] ?? $rdv->observation,
                'updated_by' => $userUuid,
            ]);

            $this->logActivity($userUuid, 'traiter', $rdv, $oldValues, $data);
            $this->sendNotification($rdv, 'traiter', $userUuid);

            $rdv = $rdv->fresh();
            $prestationLibelle = $rdv->prestation->typePrestation->libelle;
            $rdvMotifLibelle = $rdv->motif->libelle;
            return [
                'success' => true,
                'message' => $isPermitted 
                    ? "Rendez-vous traité, permission pour {$rdvMotifLibelle} enregistré avec succès."
                    : "Rendez-vous traité, demande de {$prestationLibelle} enregistré avec succès.",
                'code' => 'RDV_TRAITE',
                'status' => 200,
                'data' => $rdv->load(['client', 'gestionnaire', 'prestation']),
            ];
        });
    }

    /**
     * Reporter un RDV (client n'est pas venu)
     */
    public function reporter(Rdv $rdv, array $data, string $userUuid): array
    {
        return DB::transaction(function () use ($rdv, $data, $userUuid) {
            $oldValues = $rdv->toArray();

            // Vérifier que le RDV peut être reporté
            if (!in_array($rdv->status, ['traite', 'annule', 'rejete'])) {
                return [
                    'success' => false,
                    'message' => 'Ce rendez-vous ne peut pas être reporté. Car il est actuellement ' . $rdv->status . '.',
                    'code' => 'RDV_NON_REPORTABLE',
                    'status' => 422,
                ];
            }

            // Vérifier la disponibilité de la nouvelle date dans la même agence
            $agenceUuid = $rdv->agence_effective_uuid;
            $verifDate = $this->verifierDateDisponible($agenceUuid, $data['nouvelle_date']);

            if (!$verifDate['disponible']) {
                return [
                    'success' => false,
                    'message' => $verifDate['message'],
                    'code' => $verifDate['code'],
                    'status' => 422,
                ];
            }

            // Fusionner les motifs de report avec les motifs existants
            $motifsActuels = $rdv->motif_traitement ?? [];
            $nouveauxMotifs = $data['motif_reports'] ?? [];
            
            // Stocker les UUID des motifs dans un tableau sous la clé 'report'
            $motifsReport = $motifsActuels['report'] ?? [];
            $motifsReport = array_merge($motifsReport, $nouveauxMotifs);
            $motifsReport = array_unique($motifsReport); // Éviter les doublons

            if ($rdv->detailBordereau) {
                $rdv->detailBordereau->update([
                    'status' => $rdv->detailBordereau->status,
                    'updated_by' => $userUuid,
                ]);
            }

            $rdv->update([
                'status' => 'reporte',
                'date_rdv_effective' => Carbon::parse($data['nouvelle_date']), // MAJ de date_rdv_effective
                'agence_effective_uuid' => $agenceUuid, // MAJ de agence_effective_uuid
                'is_present' => false, // Client n'est pas venu
                'motif_traitement' => array_merge($motifsActuels, ['report' => $motifsReport]),
                'observation' => $data['observation'] ?? $rdv->observation,
                'updated_by' => $userUuid,
            ]);

            $this->logActivity($userUuid, 'reporter', $rdv, $oldValues, $data);
            $this->sendNotification($rdv, 'reporter', $userUuid);

            return [
                'success' => true,
                'message' => 'Rendez-vous reporté avec succès.',
                'code' => 'RDV_REPORTE',
                'status' => 200,
                'data' => $rdv->fresh()->load(['client', 'agenceSouhaitee']),
            ];
        });
    }

    /**
     * Rejeter un RDV
     */
    public function rejeter(Rdv $rdv, array $data, string $userUuid): array
    {
        return DB::transaction(function () use ($rdv, $data, $userUuid) {
            $oldValues = $rdv->toArray();

            // Vérifier que le RDV peut être rejeté
            if (in_array($rdv->status, ['annule', 'rejete', 'traite'])) {
                return [
                    'success' => false,
                    'message' => 'Ce rendez-vous ne peut pas être rejeté. Car il est actuellement ' . $rdv->status . '.',
                    'code' => 'RDV_NON_REJETABLE',
                    'status' => 422,
                ];
            }

            // Fusionner les motifs de rejet avec les motifs existants
            $motifsActuels = $rdv->motif_traitement ?? [];
            $nouveauxMotifs = $data['motif_rejets'] ?? [];
            
            // Stocker les UUID des motifs dans un tableau sous la clé 'rejet'
            $motifsRejet = $motifsActuels['rejet'] ?? [];
            $motifsRejet = array_merge($motifsRejet, $nouveauxMotifs);
            $motifsRejet = array_unique($motifsRejet); // Éviter les doublons

            // Vérifier si detailBordereau existe avant de le mettre à jour
            if ($rdv->detailBordereau) {
                $rdv->detailBordereau->update([
                    'status' => $rdv->detailBordereau->status,
                    'updated_by' => $userUuid,
                ]);
            }

            $rdv->update([
                'status' => 'rejete',
                'is_permitted' => false,
                'motif_traitement' => array_merge($motifsActuels, ['rejet' => $motifsRejet]),
                'observation' => $data['observation'] ?? $rdv->observation,
                'updated_by' => $userUuid,
            ]);

            // Logger l'activité seulement si ce n'est pas 'system'
            if ($userUuid !== 'system') {
                $this->logActivity($userUuid, 'rejeter', $rdv, $oldValues, $data);
                $this->sendNotification($rdv, 'rejeter', $userUuid);
            }

            return [
                'success' => true,
                'message' => 'Rendez-vous rejeté avec succès.',
                'code' => 'RDV_REJETE',
                'status' => 200,
                'data' => $rdv->fresh()->load(['client']),
            ];
        });
    }

    /**
     * Annuler un RDV (admin)
     */
    public function annuler(Rdv $rdv, array $data, string $userUuid): array
    {
        return DB::transaction(function () use ($rdv, $data, $userUuid) {
            $oldValues = $rdv->toArray();

            // Vérifier que le RDV peut être annulé
            if (in_array($rdv->status, ['annule', 'rejete', 'traite'])) {
                return [
                    'success' => false,
                    'message' => 'Ce rendez-vous ne peut pas être annulé. Car il est actuellement ' . $rdv->status . '.',
                    'code' => 'RDV_NON_ANNULABLE',
                    'status' => 422,
                ];
            }

            // Fusionner les motifs d'annulation avec les motifs existants
            $motifsActuels = $rdv->motif_traitement ?? [];
            $nouveauxMotifs = $data['motif_annulations'] ?? [];
            
            // Stocker les UUID des motifs dans un tableau sous la clé 'annulation'
            $motifsAnnulation = $motifsActuels['annulation'] ?? [];
            $motifsAnnulation = array_merge($motifsAnnulation, $nouveauxMotifs);
            $motifsAnnulation = array_unique($motifsAnnulation); // Éviter les doublons

            // Vérifier si detailBordereau existe avant de le mettre à jour
            if ($rdv->detailBordereau) {
                $rdv->detailBordereau->update([
                    'status' => $rdv->detailBordereau->status,
                    'updated_by' => $userUuid,
                ]);
            }

            $rdv->update([
                'status' => 'annule',
                'motif_traitement' => array_merge($motifsActuels, ['annulation' => $motifsAnnulation]),
                'observation' => $data['observation'] ?? $rdv->observation,
                'updated_by' => $userUuid,
            ]);

            $this->logActivity($userUuid, 'annuler', $rdv, $oldValues, $data);
            $this->sendNotification($rdv, 'annuler', $userUuid);

            return [
                'success' => true,
                'message' => 'Rendez-vous annulé avec succès.',
                'code' => 'RDV_ANNULE',
                'status' => 200,
                'data' => $rdv->fresh()->load(['client']),
            ];
        });
    }

    /**
     * Ajouter une observation/commentaire
     */
    // public function addObservation(Rdv $rdv, array $data, string $userUuid): array
    // {
    //     return DB::transaction(function () use ($rdv, $data, $userUuid) {
    //         $oldValues = $rdv->toArray();

    //         $observationActuelle = $rdv->observation ?? '';
    //         $nouvelleObservation = $data['observation'];
            
    //         if (!empty($observationActuelle)) {
    //             $nouvelleObservation = $observationActuelle . "\n\n[" . now()->format('d/m/Y H:i') . "] " . $nouvelleObservation;
    //         }

    //         $rdv->update([
    //             'observation' => $nouvelleObservation,
    //             'updated_by' => $userUuid,
    //         ]);

    //         $this->logActivity($userUuid, 'add_observation', $rdv, $oldValues, $data);

    //         return [
    //             'success' => true,
    //             'message' => 'Observation ajoutée avec succès.',
    //             'code' => 'OBSERVATION_AJOUTEE',
    //             'status' => 200,
    //             'data' => [
    //                 'observation' => $rdv->observation,
    //             ],
    //         ];
    //     });
    // }

    /**
     * Historique des traitements d'un RDV
     */
    public function getHistorique(Rdv $rdv): array
    {
        $activityLogs = ActivityLog::where('resource_type', 'rdv')
            ->where('resource_id', $rdv->uuid_rdvs)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return $activityLogs->map(function ($log) {
            return [
                'uuid' => $log->uuid,
                'action' => $log->action,
                'action_label' => $this->getActionLabel($log->action),
                'description' => $log->description,
                'user' => $log->user ? [
                    'uuid_user' => $log->user->uuid_user,
                    'email' => $log->user->email,
                    'nom_complet' => $log->user->details ? 
                        $log->user->details->nom . ' ' . $log->user->details->prenoms : 
                        $log->user->email,
                ] : null,
                'created_at' => $log->created_at->format('d/m/Y H:i'),
                'old_values' => $log->old_values,
                'new_values' => $log->new_values,
            ];
        })->toArray();
    }

    /**
     * Assigner un gestionnaire (passe automatiquement en transmis)
     */
    public function assignGestionnaire(Rdv $rdv, string $gestionnaireUuid, string $userUuid): array
    {
        return DB::transaction(function () use ($rdv, $gestionnaireUuid, $userUuid) {
            $oldValues = $rdv->toArray();

            // Vérifier que le gestionnaire existe
            $gestionnaire = User::where('uuid_user', $gestionnaireUuid)->first();
            if (!$gestionnaire) {
                return [
                    'success' => false,
                    'message' => 'Le gestionnaire n\'existe pas.',
                    'code' => 'GESTIONNAIRE_NOT_FOUND',
                    'status' => 404,
                ];
            }

            $rdv->update([
                'gestionnaire_uuid' => $gestionnaireUuid,
                'status' => 'transmis', // L'assignation passe automatiquement en transmis
                'date_transmission' => now(),
                'date_rdv_effective' => $rdv->date_rdv_souhaiter,
                'agence_effective_uuid' => $rdv->agence_souhaiter_uuid,
                'transmis_par' => $userUuid,
                'updated_by' => $userUuid,
            ]);

            $this->bordereauRdvService->ensureForRdv($rdv);

            $this->logActivity($userUuid, 'assign_gestionnaire', $rdv, $oldValues, [
                'gestionnaire_uuid' => $gestionnaireUuid,
            ]);
            $this->sendNotification($rdv, 'assign_gestionnaire', $userUuid, $gestionnaireUuid);

            return [
                'success' => true,
                'message' => 'Gestionnaire assigné avec succès. RDV transmis.',
                'code' => 'GESTIONNAIRE_ASSIGNE',
                'status' => 200,
                'data' => $rdv->fresh()->load(['gestionnaire']),
            ];
        });
    }

    /**
     * Marquer comme expiré (automatique ou manuel)
     */
    public function expirer(Rdv $rdv, array $data, string $userUuid): array
    {
        return DB::transaction(function () use ($rdv, $data, $userUuid) {
            $oldValues = $rdv->toArray();

            // Vérifier que le RDV peut être marqué comme expiré
            if (in_array($rdv->status, ['annule', 'rejete', 'traite', 'expire'])) {
                return [
                    'success' => false,
                    'message' => 'Ce rendez-vous ne peut pas être marqué comme expiré. Car il est actuellement ' . $rdv->status . '.',
                    'code' => 'RDV_NON_EXPIRABLE',
                    'status' => 422,
                ];
            }

            // Fusionner les motifs d'expiration avec les motifs existants
            $motifsActuels = $rdv->motif_traitement ?? [];
            $nouveauxMotifs = $data['motif_expirations'] ?? [];

            // Stocker les UUID des motifs dans un tableau sous la clé 'expiration'
            $motifsExpiration = $motifsActuels['expiration'] ?? [];
            $motifsExpiration = array_merge($motifsExpiration, $nouveauxMotifs);
            $motifsExpiration = array_unique($motifsExpiration); // Éviter les doublons

            if ($rdv->detailBordereau) {
                $rdv->detailBordereau->update([
                    'status' => 'en_attente',
                    'updated_by' => $userUuid,
                ]);
            }

            $rdv->update([
                'status' => 'expire',
                'motif_traitement' => array_merge($motifsActuels, ['expiration' => $motifsExpiration]),
                'observation' => $data['observation'] ?? $rdv->observation,
                'updated_by' => $userUuid,
            ]);

            // Logger l'activité seulement si ce n'est pas 'system'
            if ($userUuid !== 'system') {
                $this->logActivity($userUuid, 'expirer', $rdv, $oldValues, $data);
                $this->sendNotification($rdv, 'expire', $userUuid);
            }

            return [
                'success' => true,
                'message' => 'Rendez-vous marqué comme expiré.',
                'code' => 'RDV_EXPIRE',
                'status' => 200,
                'data' => $rdv->fresh()->load(['client']),
            ];
        });
    }

    /**
     * Vérifier si une date est disponible (méthode utilitaire)
     */
    private function verifierDateDisponible(string $agenceUuid, string $dateRdv): array
    {
        // Utiliser la méthode existante du RdvService
        $rdvService = app(RdvService::class);
        return $rdvService->verifierDateDisponible($agenceUuid, $dateRdv);
    }

    /**
     * Logger l'activité
     */
    private function logActivity(string $userUuid, string $action, Rdv $rdv, array $oldValues, array $newValues): void
    {
        ActivityLog::log([
            'user_uuid' => $userUuid,
            'action' => $action,
            'action_type' => 'traitement_rdv',
            'module' => 'rdvs',
            'description' => $this->getActionDescription($action, $rdv),
            'resource_type' => 'rdv',
            'resource_id' => $rdv->uuid_rdvs,
            'old_values' => $oldValues,
            'new_values' => array_merge($rdv->toArray(), $newValues),
            'level' => 'info',
        ]);
    }

    /**
     * Envoyer une notification
     */
    private function sendNotification(Rdv $rdv, string $action, string $userUuid, ?string $targetUserUuid = null): void
    {
        $recipientUuid = $targetUserUuid ?? $rdv->client_uuid;
        
        $this->notificationService->create([
            'user_uuid' => $recipientUuid,
            'group_notif_uuid' => $this->getRdvGroupUuid(),
            'title' => $this->getNotificationTitle($action, $rdv),
            'body' => $this->getNotificationBody($action, $rdv),
            'type' => 'RENDEZ-VOUS',
            'metadata' => [
                'rdv' => $rdv->toArray(),
                'action' => $action,
            ],
            'channel' => 'database',
            'created_by' => null,
        ]);
    }

    /**
     * Obtenir le label d'une action
     */
    private function getActionLabel(string $action): string
    {
        $labels = [
            'assigner' => 'assignation',
            'traiter' => 'Traitement',
            'reporter' => 'Report',
            'rejeter' => 'Rejet',
            'annuler' => 'Annulation',
            'add_observation' => 'Ajout observation',
            'reassign_gestionnaire' => 'Réassignation gestionnaire',
            'expirer' => 'Expiration',
        ];

        return $labels[$action] ?? $action;
    }

    /**
     * Obtenir la description d'une action
     */
    private function getActionDescription(string $action, Rdv $rdv): string
    {
        $descriptions = [
            'assigner' => "Assignation d'un gestionnaire au rendez-vous {$rdv->code}",
            'traiter' => "Traitement du rendez-vous {$rdv->code}",
            'reporter' => "Report du rendez-vous {$rdv->code}",
            'rejeter' => "Rejet du rendez-vous {$rdv->code}",
            'annuler' => "Annulation du rendez-vous {$rdv->code}",
            'add_observation' => "Ajout d'observation sur le rendez-vous {$rdv->code}",
            'reassign_gestionnaire' => "Réassignation du gestionnaire du rendez-vous {$rdv->code}",
            'expirer' => "Expiration du rendez-vous {$rdv->code}",
        ];

        return $descriptions[$action] ?? "Action {$action} sur le rendez-vous {$rdv->code}";
    }

    /**
     * Obtenir le titre de notification
     */
    private function getNotificationTitle(string $action, Rdv $rdv): string
    {
        $titles = [
            'assigner' => "📤 RDV {$rdv->code} confirmé et assigné ",
            'traiter' => "✅ RDV {$rdv->code} traité",
            'reporter' => "📅 RDV {$rdv->code} reporté",
            'rejeter' => "❌ RDV {$rdv->code} rejeté",
            'annuler' => "🚫 RDV {$rdv->code} annulé",
            // 'assign_gestionnaire' => "👤 Gestionnaire assigné au RDV {$rdv->code}",
            'reassign_gestionnaire' => "🔄 Gestionnaire du RDV {$rdv->code} changé",
            'expire' => "⏰ RDV {$rdv->code} expiré",
        ];

        return $titles[$action] ?? "Mise à jour du RDV {$rdv->code}";
    }

    /**
     * Obtenir le corps de notification
     */
    private function getNotificationBody(string $action, Rdv $rdv): string
    {
        $dateRdv = $rdv->date_rdv_effective ? Carbon::parse($rdv->date_rdv_effective) : Carbon::parse($rdv->date_rdv_souhaiter);

        $gestionnaireNom = $rdv->gestionnaire?->details?->nom ?? '';
        $gestionnairePrenoms = $rdv->gestionnaire?->details?->prenoms ?? '';
        $gestionnaireLabel = trim($gestionnaireNom . ' ' . $gestionnairePrenoms) ?: ($rdv->gestionnaire?->email ?? '');
        $agenceLabel = $rdv->agenceEffective?->libelle ?? '';

        $dateLong = $dateRdv->locale('fr')->translatedFormat('l d F Y');
        $dateShort = $dateRdv->locale('fr')->translatedFormat('l j F Y');

        $bodies = [
            'assigner' => "Votre rendez-vous N° {$rdv->code} a été confirmé et assigné au gestionnaire {$gestionnaireLabel}.\n\n"
                        . "Lieu de rendez-vous : {$agenceLabel}\n\n"
                        . "Date du rendez-vous : {$dateLong}",
            'traiter' => "Votre rendez-vous {$rdv->code} du {$dateShort} a été traité avec succès.",
            'reporter' => "Votre rendez-vous {$rdv->code} du " . Carbon::parse($rdv->date_rdv_souhaiter)->locale('fr')->translatedFormat('l j F Y') . " a été reporté au {$dateShort}.",
            'rejeter' => "Votre rendez-vous N°{$rdv->code} du {$dateShort} a été rejeté.",
            'annuler' => "Votre rendez-vous N°{$rdv->code} du {$dateShort} a été annulé.",
            'reassign_gestionnaire' => "Le gestionnaire de votre rendez-vous {$rdv->code} a été changé. \n\n Nouveau gestionnaire : {$gestionnaireLabel}.",
            'expire' => "Votre rendez-vous {$rdv->code} a expiré.",
        ];

        return $bodies[$action] ?? "Une mise à jour a été effectuée sur votre rendez-vous {$rdv->code}.";
    }

    /**
     * Obtenir l'UUID du groupe de notification RDV
     */
    private function getRdvGroupUuid(): ?string
    {
        $group = GroupNotif::where('code', 'rendezvous')->first();
        return $group?->uuid_group_notif;
    }
}
