<?php

namespace App\Services\Api\Ynov;


use App\Models\Api\Ynov\Facture;
use App\Models\Api\Ynov\Paiement;
use App\Models\Api\Ynov\parameter\User;
use App\Services\EncaissementBisService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Orchestre la préparation et l'enregistrement des paiements
 * pour les 3 types : firstPayment, earlyPayment, recoveryPrime
 */
class PrimePaymentOrchestrator
{
    public function __construct(
        protected EncaissementBisService $encaissementBis
    ) {}

    /**
     * Prépare les données de paiement selon le type
     * 
     * @throws \RuntimeException
     */
    public function preparer(array $donnees): array
    {
        return match ($donnees['paymentType']) {
            'firstPayment' => $this->preparerFirstPayment($donnees),
            'earlyPayment' => $this->preparerEarlyPayment($donnees),
            'recoveryPrime' => $this->preparerRecoveryPrime($donnees),
            default => throw new \RuntimeException('Type de paiement inconnu.'),
        };
    }

    /**
     * Prépare un premier paiement (souscription)
     */
    private function preparerFirstPayment(array $donnees): array
    {
        if (empty($donnees['contractId'])) {
            throw new \RuntimeException('contractId requis pour un premier paiement.');
        }

        // Récupère les détails du contrat
        $contrat = $this->encaissementBis->recupDetailsContratWeb(
            $donnees['contractId'],
            $donnees['paymentType']
        );

        if (!$contrat['success']) {
            throw new \RuntimeException($contrat['message'] ?? 'Impossible de récupérer les détails du contrat.');
        }

        $primeUnitaire = $contrat['primePrincipale'] ?? 0;
        $fraisAdhesion = $contrat['fraisAdhesion'] ?? 0;

        if ($primeUnitaire <= 0) {
            throw new \RuntimeException('Montant de prime invalide pour le premier paiement.');
        }

        $nombreDePrimes = max(1, (int) ($donnees['numberOfPrimes'] ?? 1));
        $montantTotal = $fraisAdhesion + ($primeUnitaire * $nombreDePrimes);

        return [
            'montantTotal' => $montantTotal,
            'nombreDePrimes' => $nombreDePrimes,
            'contractId' => $donnees['contractId'],
            'reference' => $donnees['reference'],
            'primeUnitaire' => $primeUnitaire,
            'fraisAdhesion' => $fraisAdhesion,
            'facturesAGenerer' => $this->genererLignesFactures(
                $nombreDePrimes,
                $primeUnitaire,
                $fraisAdhesion
            ),
        ];
    }

    /**
     * Prépare un paiement anticipé
     */
    private function preparerEarlyPayment(array $donnees): array
    {
        if (empty($donnees['contractId'])) {
            throw new \RuntimeException('contractId requis pour un paiement anticipé.');
        }

        $contrat = $this->encaissementBis->verifierContrat(
            $donnees['contractId'],
            $donnees['paymentType']
        );

        if (!$contrat['success']) {
            throw new \RuntimeException($contrat['message'] ?? 'Contrat invalide.');
        }

        if ($contrat['aDesImpayes']) {
            throw new \RuntimeException(
                "Ce contrat a des primes impayées. Veuillez effectuer une régularisation avant de payer en avance."
            );
        }

        $nombreDePrimes = max(1, (int) ($donnees['numberOfPrimes'] ?? 1));
        $montantTotal = $contrat['primePrincipale'] * $nombreDePrimes;

        return [
            'montantTotal' => $montantTotal,
            'nombreDePrimes' => $nombreDePrimes,
            'idProposition' => $contrat['idProposition'],
            'contractId' => $donnees['contractId'],
            'reference' => $donnees['reference'],
            'primeUnitaire' => $contrat['primePrincipale'],
            'fraisAdhesion' => 0,
            'facturesAGenerer' => $this->genererLignesFactures(
                $nombreDePrimes,
                $contrat['primePrincipale'],
                0
            ),
        ];
    }

    /**
     * Prépare une régularisation de primes impayées
     */
    private function preparerRecoveryPrime(array $donnees): array
    {
        if (empty($donnees['contractId'])) {
            throw new \RuntimeException('contractId requis pour une régularisation.');
        }

        if (empty($donnees['selectedInvoiceIds'])) {
            throw new \RuntimeException('Aucune facture sélectionnée.');
        }

        $resultat = $this->encaissementBis->recalculerTotalImpayes(
            $donnees['contractId'],
            $donnees['selectedInvoiceIds'],
            $donnees['paymentType']
        );

        if (!$resultat['success']) {
            throw new \RuntimeException($resultat['message'] ?? 'Impossible de recalculer le montant.');
        }

        $facturesSelectionnees = $resultat['facturesSelectionnees'];

        return [
            'montantTotal' => $resultat['totalCents'],
            'nombreDePrimes' => count($facturesSelectionnees),
            'idProposition' => $resultat['contrat']['idProposition'] ?? null,
            'contractId' => $donnees['contractId'],
            'reference' => $donnees['reference'],
            'primeUnitaire' => null,
            'fraisAdhesion' => 0,
            'facturesAGenerer' => array_map(function (array $f) {
                return [
                    'amount' => $f['MontantNet'],
                    'referenceOrigine' => $f['IdPresentation'],
                    'dateFacturation' => $f['MaDate'] ?? null,
                    // 'dateFacturation' => Carbon::createFromFormat('d/m/Y', $f['MaDate'])->format('Y-m-d H:i:s') ?? null,
                    'type' => $f['TypePresentation'],
                ];
            }, $facturesSelectionnees),
        ];
    }

    /**
     * Génère les lignes de factures pour les paiements futurs
     */
    private function genererLignesFactures(
        int $nombreDePrimes,
        int $primeUnitaire,
        int $fraisAdhesion
    ): array {
        $lignes = [];

        // Primes principales
        for ($i = 0; $i < $nombreDePrimes; $i++) {
            $lignes[] = [
                'amount' => $primeUnitaire,
                'referenceOrigine' => date('YmdHis'),
                'dateFacturation' => Carbon::now()->format('Y-m-d H:i:s'),
                'type' => 'N',
            ];
        }

        // Frais d'adhésion (uniquement pour le premier paiement)
        if ($fraisAdhesion > 0) {
            $lignes[] = [
                'amount' => $fraisAdhesion,
                'referenceOrigine' => date('YmdHis'),
                'dateFacturation' => Carbon::now()->format('Y-m-d H:i:s'),
                'type' => 'F',
            ];
        }

        return $lignes;
    }

    /**
     * Enregistre le paiement et les factures associées
     */
    public function enregistrer(
        array $donnees,
        array $preparation,
        string $referenceInterne,
        array $resultatJeko
    ): Paiement {
        return DB::transaction(function () use ($donnees, $preparation, $referenceInterne, $resultatJeko) {
            // Créer le paiement
            $paiement = Paiement::create([
                'command_number' => $referenceInterne ?? null,
                'amount' => $preparation['montantTotal'],
                'payment_mode' => $donnees['paymentMethod'] ?? null,
                'payment_status' => $resultatJeko['status'] ?? 'pending',
                'status' => 'pending',
                'payment_type' => $donnees['paymentType'],
                'reglement_source' => 'JEKO',
                'id_contrat' => $preparation['contractId'] ?? null,
                'facture_count' => count($preparation['facturesAGenerer']),
                'payer_email' => $donnees['customerEmail'] ?? null,
            ]);

            // Créer les factures associées
            foreach ($preparation['facturesAGenerer'] as $ligne) {
                Facture::create([
                    'payment_uuid' => $paiement->uuid_paiement,
                    'id_presentaion' => $ligne['referenceOrigine'] ?? null,
                    'amount' => $ligne['amount'],
                    'type_facture' => $ligne['type'] ?? 'N',
                    'status' => 'pending',

                ]);
            }

            return $paiement;
        });
    }

    /**
     * Met à jour le statut d'un paiement et de ses factures
     */
    public function mettreAJourStatut(Paiement $paiement, string $statut, array $payload = []): void
    {
        DB::transaction(function () use ($paiement, $statut, $payload) {
            // Mise à jour du paiement
            $updateData = [
                'payment_status' => $statut,
            ];

            if ($statut === 'success') {
                $updateData['status'] = 'paid';
                $updateData['paid_at'] = now();
                $updateData['payment_validation_date'] = now();
                $updateData['paid_amount'] = $paiement->amount;
                $updateData['paid_sum'] = $paiement->amount ;

                if (!empty($payload['phone'])) {
                    $updateData['payment_phone'] = $payload['phone'];
                }
                if (!empty($payload['payment_token'])) {
                    $updateData['payment_token'] = $payload['payment_token'];
                }
                if (!empty($payload['payment_code'])) {
                    $updateData['payment_code'] = $payload['payment_code'];
                }
            } elseif ($statut === 'error' || $statut === 'cancelled') {
                $updateData['status'] = $statut;
                if ($statut === 'cancelled') {
                    $updateData['cancelled_at'] = now();
                }
            }

            $paiement->update($updateData);

            // Mise à jour des factures associées
            $factureStatus = match ($statut) {
                'success' => 'paid',
                'error', 'cancelled' => $statut,
                default => 'pending',
            };

            if ($factureStatus === 'paid') {
                Facture::where('payment_uuid', $paiement->uuid_paiement)
                    ->update([
                        'status' => $factureStatus,
                        'paid_at' => now(),
                    ]);
            } else {
                Facture::where('payment_uuid', $paiement->uuid_paiement)
                    ->update(['status' => $factureStatus]);
            }
        });
    }
}