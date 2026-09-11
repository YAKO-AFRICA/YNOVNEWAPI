<?php

namespace App\Services\Api\Ynov\Rdv;

use App\Models\Api\Ynov\parameter\ActivityLog;
use App\Models\Api\Ynov\parameter\GroupNotif;
use App\Models\Api\Ynov\parameter\User;
use App\Models\Api\Ynov\Rdv;
use App\Services\Api\Ynov\NotificationService;
use App\Services\Api\Ynov\Rdv\BordereauRdvService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RoutingService
{
    public function __construct(
        private NotificationService $notificationService,
        private TraitementService $traitementService,
        private BordereauRdvService $bordereauRdvService
    ) {}

    /**
     * Assignation automatique d'un RDV à un gestionnaire
     * Doit être appelée immédiatement après la création du RDV (3 min max)
     */
    public function assignerAutomatiquement(Rdv $rdv): array
    {
        // Vérifier si le RDV peut être assigné
        if (!$this->peutEtreAssigne($rdv)) {
            Log::debug('RDV non assignable');
            return [
                'success' => false,
                'code' => 'RDV_NON_ASSIGNABLE',
                'message' => 'Ce rendez-vous ne peut pas être assigné automatiquement.',
                'data' => null
            ];
        }

        // Récupérer les gestionnaires disponibles pour l'agence
        $gestionnaires = $this->getGestionnairesDisponibles($rdv->agence_souhaiter_uuid);

        if (empty($gestionnaires)) {
            // Si aucun gestionnaire disponible, on laisse en attente
            return [
                'success' => false,
                'code' => 'AUCUN_GESTIONNAIRE',
                'message' => 'Aucun gestionnaire disponible pour cette agence.',
                'data' => null
            ];
        }

        // Vérifier si le client a déjà des RDV le même jour
        $gestionnaireExistant = $this->getGestionnaireExistantPourClient($rdv);

        if ($gestionnaireExistant) {
            // Assigner au même gestionnaire
            return $this->assignerAuGestionnaire($rdv, $gestionnaireExistant);
        }

        // Distribution équitable par agence et par jour
        $gestionnaireChoisi = $this->getGestionnaireParDistributionEquitable($rdv, $gestionnaires);
        if (!$gestionnaireChoisi) {
            return [
                'success' => false,
                'code' => 'DISTRIBUTION_ECHEC',
                'message' => 'Impossible de déterminer un gestionnaire.',
                'data' => null
            ];
        }
        return $this->assignerAuGestionnaire($rdv, $gestionnaireChoisi);
    }

    /**
     * Assigner au gestionnaire et passer en transmis
     */
    private function assignerAuGestionnaire(Rdv $rdv, string $gestionnaireUuid): array
    {
        return DB::transaction(function () use ($rdv, $gestionnaireUuid) {
            $oldStatus = $rdv->status;

            $rdv->update([
                'gestionnaire_uuid' => $gestionnaireUuid,
                'status' => 'transmis',
                'date_rdv_effective' => $rdv->date_rdv_souhaiter,
                'agence_effective_uuid' => $rdv->agence_souhaiter_uuid,
                'date_transmission' => now(),
                'transmis_par' => 'system',
                'updated_by' => 'system',
            ]);

            $this->bordereauRdvService->ensureForRdv($rdv);

            // Log
            ActivityLog::log([
                'user_uuid' => 'system',
                'action' => 'assignation_auto',
                'action_type' => 'routing',
                'module' => 'rdvs',
                'description' => "Assignation automatique du RDV {$rdv->code} au gestionnaire {$gestionnaireUuid}",
                'resource_type' => 'rdv',
                'resource_id' => $rdv->uuid_rdvs,
                'old_values' => ['status' => $oldStatus, 'gestionnaire_uuid' => null],
                'new_values' => ['status' => 'transmis', 'gestionnaire_uuid' => $gestionnaireUuid],
                'level' => 'info',
            ]);

            //  $carbon = Carbon::createFromFormat('Y-m', $item->mois);

            // Notification au gestionnaire
            $this->notificationService->create([
                'user_uuid' => $gestionnaireUuid,
                'group_notif_uuid' => $this->getRdvGroupUuid(),
                'title' => '📋 Nouveau RDV assigné',
                'body' => "Le rendez-vous {$rdv->code} vous a été assigné automatiquement.",
                'type' => 'RENDEZ-VOUS',
                'metadata' => [
                    'rdv_uuid' => $rdv->uuid_rdvs,
                    'rdv_code' => $rdv->code,
                    'action' => 'assignation_auto',
                ],
                'channel' => 'database',
                'created_by' => null,
            ]);

            // Notification au client
            $gestionnaireNom = $rdv->gestionnaire?->details?->nom ?? null;
            $gestionnairePrenoms = $rdv->gestionnaire?->details?->prenoms ?? null;
            $gestionnaireLabel = $gestionnaireNom || $gestionnairePrenoms ? trim(($gestionnaireNom ?? '') . ' ' . ($gestionnairePrenoms ?? '')) : ($rdv->gestionnaire?->email ?? '');
            $agenceLabel = $rdv->agenceEffective?->libelle ?? '';

            $this->notificationService->create([
                'user_uuid' => $rdv->client_uuid,
                'group_notif_uuid' => $this->getRdvGroupUuid(),
                'title' => '📋 RDV confirmé et assigné',
                'body' => "Votre rendez-vous N° {$rdv->code} a été confirmé et assigné au gestionnaire {$gestionnaireLabel}.\n\n"
                        . "Lieu de rendez-vous : {$agenceLabel}\n\n"
                        . "Date du rendez-vous : "
                        . Carbon::parse($rdv->date_rdv_souhaiter)->locale('fr')->translatedFormat('l d F Y'), 
                'type' => 'RENDEZ-VOUS',
                'metadata' => [
                    'rdv_uuid' => $rdv->uuid_rdvs,
                    'rdv_code' => $rdv->code,
                    'action' => 'assignation_auto',
                ],
                'channel' => 'database',
                'created_by' => null,
            ]);

            return [
                'success' => true,
                'code' => 'RDV_ASSIGNE_AUTO',
                'message' => 'Rendez-vous assigné automatiquement avec succès.',
                'data' => $rdv->fresh()->load(['gestionnaire', 'client'])
            ];
        });
    }

    /**
     * Vérifier si le RDV peut être assigné automatiquement
     */
    private function peutEtreAssigne(Rdv $rdv): bool
    {
        // Un RDV en attente sans gestionnaire peut être assigné
        return $rdv->status === 'en_attente' && is_null($rdv->gestionnaire_uuid);
    }

    /**
     * Vérifier si le client a déjà des RDV le même jour avec un gestionnaire
     */
    private function getGestionnaireExistantPourClient(Rdv $rdv): ?string
    {
        if (!$rdv->client_uuid || !$rdv->date_rdv_souhaiter) {
            return null;
        }

        $rdvExistant = Rdv::where('client_uuid', $rdv->client_uuid)
            ->whereDate('date_rdv_souhaiter', $rdv->date_rdv_souhaiter->format('Y-m-d'))
            ->where('uuid_rdvs', '!=', $rdv->uuid_rdvs)
            ->whereNotNull('gestionnaire_uuid')
            ->whereNotIn('status', ['annule', 'rejete', 'expire'])
            ->first();

        return $rdvExistant?->gestionnaire_uuid;
    }

    /**
     * Récupérer les gestionnaires disponibles pour une agence
     */
    private function getGestionnairesDisponibles(string $agenceUuid): array
    {
        // Récupérer les utilisateurs actifs de cette agence qui ont le rôle de gestionnaire
        // On suppose que les gestionnaires sont les users avec permission 'rdvs.traiter'
        $users = User::whereHas('agences', function ($query) use ($agenceUuid) {
            $query->where('agences.uuid_agence', $agenceUuid)
                ->where('user_agences.is_active', true);
        })->where('status', 'actif')->get();
            // ->whereHas('role', function ($query) {
            //     $query->whereHas('permissions', function ($q) {
            //         $q->where('code', 'rdvs.traiter');
            //     });
            // })
            

            $users = $users->filter(function ($user) {
                return $user && method_exists($user, 'hasRole') && $user->hasRole('gestionnaire_rdv');
            });

        return $users->pluck('uuid_user')->toArray();
    }

    /**
     * Distribution équitable par agence et par jour
     */
    private function getGestionnaireParDistributionEquitable(Rdv $rdv, array $gestionnaires): ?string
    {
        $dateRdv = $rdv->date_rdv_souhaiter;
        $agenceUuid = $rdv->agence_souhaiter_uuid;

        if (!$dateRdv || !$agenceUuid) {
            return null;
        }
        // Récupérer les compteurs en une seule requête pour performance
        $counts = Rdv::query()
            ->select('gestionnaire_uuid', DB::raw('count(*) as cnt'))
            ->whereIn('gestionnaire_uuid', $gestionnaires)
            ->where('agence_souhaiter_uuid', $agenceUuid)
            ->whereDate('date_rdv_souhaiter', $dateRdv->format('Y-m-d'))
            ->whereNotIn('status', ['annule', 'rejete', 'expire'])
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
     * Assigner automatiquement tous les RDV en attente
     * (À appeler via un endpoint public)
     */
    public function assignerTousLesRdvsEnAttente(): array
    {
        $rdvs = Rdv::where('status', 'en_attente')
            ->whereNull('gestionnaire_uuid')
            ->get();

        $results = [
            'total' => $rdvs->count(),
            'assignes' => 0,
            'echecs' => 0,
            'details' => [],
            'executed_at' => now()->format('Y-m-d H:i:s')
        ];

        foreach ($rdvs as $rdv) {
            $result = $this->assignerAutomatiquement($rdv);

            if ($result['success']) {
                $results['assignes']++;
                $results['details'][] = [
                    'rdv_code' => $rdv->code,
                    'gestionnaire_uuid' => $rdv->fresh()->gestionnaire_uuid,
                    'status' => 'assigne',
                ];
            } else {
                $results['echecs']++;
                $results['details'][] = [
                    'rdv_code' => $rdv->code,
                    'status' => 'echec',
                    'raison' => $result['message'],
                ];
            }
        }

        return $results;
    }

    /**
     * Gérer les RDV expirés automatiquement
     */
    public function gererRdvsExpires(): array
    {
        $dateActuelle = now()->format('Y-m-d');

        $rdvs = Rdv::whereIn('status', ['en_attente', 'transmis'])
            ->whereDate('date_rdv_souhaiter', '<', $dateActuelle)
            ->get();

        $results = [
            'total' => $rdvs->count(),
            'expires' => 0,
            'rejetes' => 0,
            'details' => [],
            'executed_at' => now()->format('Y-m-d H:i:s')
        ];

        foreach ($rdvs as $rdv) {
            // Si le RDV a expiré depuis plus de 3 jours, on le rejette
            $dateRdv = ($rdv->date_rdv_effective) ? Carbon::parse($rdv->date_rdv_effective) : Carbon::parse($rdv->date_rdv_souhaiter);
            $joursDepuis = $dateRdv->diffInDays(now());

            if ($joursDepuis > 3) {
                // Annuler automatiquement
                $motifAnnulation = "Annulation automatique : RDV non traité et expiré depuis plus de 3 jours";
                $result = $this->traitementService->annuler($rdv, $motifAnnulation, 'system');
                // $result = $this->traitementService->annuler($rdv, [
                //     'motif_rejet' => 'Rejet automatique après 3 jours d\'expiration',
                //     'observation' => 'RDV non traité et expiré depuis plus de 3 jours'
                // ], 'system');

                if ($result['success']) {
                    $results['annulles']++;
                    $results['details'][] = [
                        'rdv_code' => $rdv->code,
                        'status' => 'annule',
                        'raison' => 'Expiré depuis 3 jours',
                    ];

                }
            } else {
                // Marquer comme expiré
                $result = $this->traitementService->expirer($rdv, [
                    'motif_expiration' => 'Expiration automatique',
                    'observation' => "Le RDV a expiré le {$dateRdv->format('d/m/Y')}"
                ], 'system');

                if ($result['success']) {
                    $results['expires']++;
                    $results['details'][] = [
                        'rdv_code' => $rdv->code,
                        'status' => 'expire',
                        'jours_restants' => 3 - $joursDepuis,
                    ];
                }
            }
        }

        return $results;
    }

    /**
     * Rééquilibrer la charge des gestionnaires pour une agence et un jour donné
     */
    public function reequilibrerCharge(string $agenceUuid, string $dateRdv): array
    {
        $rdvs = Rdv::where('agence_souhaiter_uuid', $agenceUuid)
            ->whereDate('date_rdv_souhaiter', $dateRdv)
            ->where('status', 'en_attente')
            ->whereNotNull('gestionnaire_uuid')
            ->get();

        $gestionnaires = $this->getGestionnairesDisponibles($agenceUuid);

        if (empty($gestionnaires)) {
            return [
                'success' => false,
                'message' => 'Aucun gestionnaire disponible pour cette agence.',
                'code' => 'AUCUN_GESTIONNAIRE',
            ];
        }

        $charges = [];
        foreach ($gestionnaires as $gestionnaireUuid) {
            $count = Rdv::where('gestionnaire_uuid', $gestionnaireUuid)
                ->where('agence_souhaiter_uuid', $agenceUuid)
                ->whereDate('date_rdv_souhaiter', $dateRdv)
                ->whereNotIn('status', ['annule', 'rejete', 'expire'])
                ->count();

            $charges[$gestionnaireUuid] = $count;
        }

        $chargeMoyenne = array_sum($charges) / count($charges);
        $results = [
            'success' => true,
            'total_reassignes' => 0,
            'details' => [],
        ];

        // Réassigner les RDV des gestionnaires surchargés
        foreach ($charges as $gestionnaireUuid => $charge) {
            if ($charge > $chargeMoyenne + 1) {
                $rdvsSurcharge = Rdv::where('gestionnaire_uuid', $gestionnaireUuid)
                    ->where('agence_souhaiter_uuid', $agenceUuid)
                    ->whereDate('date_rdv_souhaiter', $dateRdv)
                    ->where('status', 'en_attente')
                    ->limit($charge - floor($chargeMoyenne))
                    ->get();

                foreach ($rdvsSurcharge as $rdv) {
                    $nouveauGestionnaire = $this->getGestionnaireMoinsCharge($charges);

                    if ($nouveauGestionnaire && $nouveauGestionnaire !== $gestionnaireUuid) {
                        $rdv->update([
                            'gestionnaire_uuid' => $nouveauGestionnaire,
                            'updated_by' => 'system',
                        ]);

                        $charges[$gestionnaireUuid]--;
                        $charges[$nouveauGestionnaire]++;
                        $results['total_reassignes']++;

                        $results['details'][] = [
                            'rdv_code' => $rdv->code,
                            'ancien_gestionnaire' => $gestionnaireUuid,
                            'nouveau_gestionnaire' => $nouveauGestionnaire,
                        ];
                    }
                }
            }
        }

        return $results;
    }

    /**
     * Trouver le gestionnaire avec la charge la plus faible
     */
    private function getGestionnaireMoinsCharge(array $charges): ?string
    {
        if (empty($charges)) {
            return null;
        }

        $minCharge = min($charges);
        $gestionnairesMoinsCharges = array_keys($charges, $minCharge);

        return $gestionnairesMoinsCharges[array_rand($gestionnairesMoinsCharges)];
    }

    /**
     * Réassigner manuellement un RDV à un autre gestionnaire
     */
    public function reassignerManuellement(Rdv $rdv, string $nouveauGestionnaireUuid, string $motif, string $userUuid): array
    {
        return DB::transaction(function () use ($rdv, $nouveauGestionnaireUuid, $motif, $userUuid) {
            $oldGestionnaireUuid = $rdv->gestionnaire_uuid;
            $oldStatus = $rdv->status;

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

            // Vérifier que le gestionnaire appartient à l'agence du RDV
            if (!$gestionnaire->belongsToAgence($rdv->agence_souhaiter_uuid)) {
                return [
                    'success' => false,
                    'message' => 'Le gestionnaire n\'appartient pas à cette agence.',
                    'code' => 'GESTIONNAIRE_NOT_IN_AGENCE',
                    'status' => 422,
                ];
            }

            $rdv->update([
                'gestionnaire_uuid' => $nouveauGestionnaireUuid,
                'motif_traitement' => array_merge($rdv->motif_traitement ?? [], [
                    'reassignation' => [
                        'ancien_gestionnaire' => $oldGestionnaireUuid,
                        'nouveau_gestionnaire' => $nouveauGestionnaireUuid,
                        'motif' => $motif,
                        'fait_par' => $userUuid,
                        'date' => now()->toISOString(),
                    ]
                ]),
                'updated_by' => $userUuid,
            ]);

            // Si le RDV était en attente, le passer en transmis
            if ($rdv->status === 'en_attente') {
                $rdv->update([
                    'status' => 'transmis',
                    'date_transmission' => now(),
                    'transmis_par' => $userUuid,
                ]);
            }

            // Log
            ActivityLog::log([
                'user_uuid' => $userUuid,
                'action' => 'reassignation_manuelle',
                'action_type' => 'routing',
                'module' => 'rdvs',
                'description' => "Réassignation manuelle du RDV {$rdv->code}",
                'resource_type' => 'rdv',
                'resource_id' => $rdv->uuid_rdvs,
                'old_values' => ['gestionnaire_uuid' => $oldGestionnaireUuid, 'status' => $oldStatus],
                'new_values' => ['gestionnaire_uuid' => $nouveauGestionnaireUuid, 'status' => $rdv->status],
                'level' => 'info',
            ]);

            // Notification au nouveau gestionnaire
            $gestionnaireNom = $gestionnaire->details?->nom ?? '';
            $gestionnairePrenoms = $gestionnaire->details?->prenoms ?? '';
            $gestionnaireLabel = trim($gestionnaireNom . ' ' . $gestionnairePrenoms) ?: ($gestionnaire->email ?? '');

            $this->notificationService->create([
                'user_uuid' => $nouveauGestionnaireUuid,
                'group_notif_uuid' => $this->getRdvGroupUuid(),
                'title' => "📋 RDV N°{$rdv->code} assigné",
                'body' => "Bonjour {$gestionnaireLabel}, le rendez-vous N°{$rdv->code} vous a été assigné.",
                'type' => 'RENDEZ-VOUS',
                'metadata' => [
                    'rdv_uuid' => $rdv->uuid_rdvs,
                    'rdv_code' => $rdv->code,
                    'action' => 'reassignation',
                    'ancien_gestionnaire' => $oldGestionnaireUuid,
                ],
                'channel' => 'database',
                'created_by' => null,
            ]);

            return [
                'success' => true,
                'message' => 'RDV réassigné avec succès.',
                'code' => 'RDV_REASSIGNE',
                'status' => 200,
                'data' => $rdv->fresh()->load(['gestionnaire', 'client']),
            ];
        });
    }

    private function getRdvGroupUuid(): ?string
    {
        $group = GroupNotif::where('code', 'rendezvous')->first();
        return $group?->uuid_group_notif;
    }
}
