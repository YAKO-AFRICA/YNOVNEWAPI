<?php

namespace App\Http\Controllers\Api\Ynov;


use App\Http\Controllers\Controller;
use App\Models\Api\Ynov\Paiement;
use App\Services\Api\Ynov\Documents\DocumentService;
use App\Services\Api\Ynov\PaymentService;
use App\Services\Api\Ynov\PrimePaymentOrchestrator;
use App\Services\EncaissementBisService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    private const METHODES_AUTORISEES = [
        'wave', 'orange', 'moov', 'mtn', 'djamo', 'visa', 'mastercard'
    ];

    private const TYPES_PAIEMENT_AUTORISES = [
        'firstPayment', 'earlyPayment', 'recoveryPrime'
    ];

    private const DEVISES_SUPPORTEES = ['XOF', 'XAF', 'USD', 'EUR'];

    public function __construct(
        protected PaymentService $jekoService,
        protected PrimePaymentOrchestrator $orchestrator,
        protected EncaissementBisService $encaissementBis,
        protected DocumentService $documentService,
    ) {}

    public function demoJekoWidget()
    {
        return view('paiement.demo-jeko-widget');
    }

    public function jekoPaymentWidget()
    {
        $path = public_path('payment-widget/jeko-payment-widget.js');

        if (!File::exists($path)) {
            abort(404);
        }

        return response(File::get($path), 200)
            ->header('Content-Type', 'application/javascript');
    }

    /**
     * Vérifie un contrat auprès de l'API encaissement-bis
     */
    public function verifierContrat(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'idContrat' => ['required', 'string', 'max:100'],
            'paymentType' => ['nullable', 'string', 'in:' . implode(',', self::TYPES_PAIEMENT_AUTORISES)],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Identifiant de contrat invalide.',
                'code' => 'VALIDATION_ERROR',
                'errors' => $validator->errors(),
            ], 422);
        }

        $idContrat = $request->input('idContrat');
        $paymentType = $request->input('paymentType');

        $resultat = ($paymentType === 'firstPayment')
            ? $this->encaissementBis->recupDetailsContratWeb($idContrat, $paymentType)
            : $this->encaissementBis->verifierContrat($idContrat, $paymentType);

        if (!$resultat['success']) {
            return response()->json([
                'success' => false,
                'message' => $resultat['message'] ?? 'Contrat introuvable.',
                'code' => 'CONTRACT_NOT_FOUND',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Contrat vérifié.',
            'code' => 'CONTRACT_VERIFIED',
            'data' => $resultat,
        ]);
    }

    /**
     * Initialise un paiement auprès de Jeko
     */
    public function initierPaiement(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reference' => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z0-9\-_]+$/'],
            'currency' => ['nullable', 'string', 'size:3', 'in:' . implode(',', self::DEVISES_SUPPORTEES)],
            'paymentMethod' => ['required', 'string', 'in:' . implode(',', self::METHODES_AUTORISEES)],
            'paymentType' => ['required', 'string', 'in:' . implode(',', self::TYPES_PAIEMENT_AUTORISES)],
            'contractId' => ['nullable', 'max:100'],
            'numberOfPrimes' => ['nullable', 'integer', 'min:1', 'max:60'],
            'selectedInvoiceIds' => ['nullable', 'array'],
            'selectedInvoiceIds.*' => ['string', 'max:50'],
            'successUrl' => ['nullable', 'url', 'max:500'],
            'errorUrl' => ['nullable', 'url', 'max:500'],
            'description' => ['nullable', 'string', 'max:255'],
            'customerEmail' => ['nullable', 'email', 'max:100'],
            'customerName' => ['nullable', 'string', 'max:100'],
            'metadata' => ['nullable', 'array'],
        ]);

        $validator->after(function ($v) use ($request) {
            $type = $request->input('paymentType');

            if (in_array($type, ['earlyPayment', 'recoveryPrime', 'firstPayment'], true) && !$request->filled('contractId')) {
                $v->errors()->add('contractId', "L'identifiant du contrat est requis pour ce type de paiement.");
            }

            if ($type === 'recoveryPrime' && !$request->filled('selectedInvoiceIds')) {
                $v->errors()->add('selectedInvoiceIds', 'Veuillez sélectionner au moins une facture à régulariser.');
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données de paiement invalides.',
                'code' => 'VALIDATION_ERROR',
                'errors' => $validator->errors(),
            ], 422);
        }

        $donnees = $validator->validated();

        Log::info('Initialisation de paiement', $donnees);

        // 1) Calcul du montant et des lignes de factures
        try {
            $preparation = $this->orchestrator->preparer($donnees);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'code' => 'PREPARATION_FAILED',
                'data' => null,
            ], 422);
        }

        if ($preparation['montantTotal'] <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Le montant calculé est invalide.',
                'code' => 'INVALID_AMOUNT',
                'data' => null,
            ], 422);
        }

        $referenceInterne = $donnees['reference'];
        $paymentMethod = $donnees['paymentMethod'];
        $provider = ($paymentMethod === 'wave') ? 'WAVE_API' : 'JEKO';

        try {
            // 2) Appel Provider (Wave ou Jeko)
            $resultat = $this->jekoService->initialiserPaiement([
                'amountCents' => $paymentMethod === 'wave' ? $preparation['montantTotal'] : $preparation['montantTotal'] * 100,
                'currency' => $donnees['currency'] ?? 'XOF',
                'reference' => $referenceInterne,
                'paymentMethod' => $donnees['paymentMethod'],
                'successUrl' => (isset($donnees['successUrl']) && !empty($donnees['successUrl'])) ? $donnees['successUrl'] : env('APP_URL') . '/paiement/recu/' . $referenceInterne,
                'errorUrl' => (isset($donnees['errorUrl']) && !empty($donnees['errorUrl'])) ? $donnees['errorUrl'] : env('APP_URL') . '/paiement/error/' . $referenceInterne,
                'customerEmail' => $donnees['customerEmail'] ?? null,
                'customerName' => $donnees['customerName'] ?? null,
                'description' => $donnees['description'] ?? null,
                'metadata' => $donnees['metadata'] ?? null,
            ], $referenceInterne);

            Log::info('Paiement initialisé via ' . strtoupper($provider), [
                'reference' => $referenceInterne,
            ]);

            if (!$resultat['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $resultat['message'] ?? "Impossible d'initialiser le paiement.",
                    'code' => $resultat['code'] ?? 'PAYMENT_INIT_FAILED',
                    'data' => null,
                ], $resultat['status'] ?? 502);
            }

            Log::info('Enregistrement du paiement', [
                'referenceInterne' => $referenceInterne,
            ]);

            // 3) Enregistrement du paiement et des factures
            $paiement = $this->orchestrator->enregistrer($donnees, $preparation, $referenceInterne, $resultat, $provider);

            Log::info('Paiement enregistré', [
                'referenceInterne' => $referenceInterne,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Paiement initialisé avec succès.',
                'code' => 'PAYMENT_INITIATED',
                'data' => [
                    'redirectUrl' => $resultat['redirectUrl'],
                    'referenceInterne' => $referenceInterne,
                    'referenceMetier' => $donnees['reference'],
                    'montant' => $preparation['montantTotal'],
                    'devise' => $donnees['currency'] ?? 'XOF',
                    'nombreDePrimes' => $preparation['nombreDePrimes'],
                    'recuUrl' => env('APP_URL') . '/paiement/recu/' . $referenceInterne,
                ],
            ]);
        } catch (\Throwable $e) {

            Log::error('Erreur lors de l\'initialisation du paiement', [
                'referenceInterne' => $referenceInterne,
                // 'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique lors de la communication avec le service de paiement.',
                'code' => 'JEKO_UNREACHABLE',
                'data' => null,
            ], 503);
        }
    }

    /**
     * Vérifie le statut d'un paiement (via referenceInterne = codePaiement).
     */
    public function verifierStatut(Request $request, string $referenceInterne): JsonResponse
    {
        try {
            $paiement = Paiement::where('payment_code', $referenceInterne)
            ->orWhere('command_number', $referenceInterne)->first();

            if (!$paiement) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction non trouvée.',
                    'code' => 'TRANSACTION_NOT_FOUND',
                ], 404);
            }

            $provider = $paiement->reglement_source ?? 'JEKO';
            $statut = $this->jekoService->verifierStatut($paiement, $provider);

            // Si le statut est "error", mettre à jour le statut en base
            if ($statut['success'] && in_array($statut['status'], ['error', 'cancelled', 'failed'])) {
                $this->orchestrator->mettreAJourStatut($paiement, $statut['status'], []);
                Log::info('Statut du paiement mis à jour en base (' . $statut['status'] . ')', [
                    'reference' => $referenceInterne,
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'statut' => $statut['status'],
                    'montant' => $paiement->amount,
                    'reference' => $paiement->payment_code,
                    'payment_type' => $paiement->payment_type,
                    'details' => $statut['details'] ?? null,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Erreur vérification statut', [
                'reference' => $referenceInterne,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification du statut.',
                'code' => 'STATUS_CHECK_FAILED',
            ], 500);
        }
    }
    

    /**
     * Webhook pour recevoir les notifications de Jeko
     */
    public function webhookJeko(Request $request)
    {
        $payload = $request->all();
        Log::info('Webhook Jeko reçu', [
            'payload' => $payload,
        ]);

        try {
            // Valider le webhook
            if (!$this->jekoService->validerWebhookJeko($request)) {
                Log::warning('Webhook Jeko non valide - signature incorrecte');
                return response()->json(['error' => 'Invalid webhook'], 401);
            }

            // Traiter le webhook
            $resultat = $this->jekoService->traiterWebhookJeko($payload);

            if (!$resultat) {
                Log::info('Webhook ignoré - traitement non nécessaire');
                return response()->json(['status' => 'ignored'], 200);
            }

            // Mettre à jour le paiement
            $paiement = Paiement::where('payment_code', $resultat['reference'])
            ->orWhere('command_number', $resultat['reference'])->first();

            if (!$paiement) {
                Log::warning('Paiement non trouvé pour le webhook', ['reference' => $resultat['reference']]);
                return response()->json(['status' => 'ignored'], 200);
            }

            Log::info('Mise à jour du paiement via webhook Jeko', [
                'reference' => $resultat['reference'],
                'nouveau_statut' => $resultat['status'],
            ]);

            // Mettre à jour le statut
            $this->orchestrator->mettreAJourStatut(
                $paiement,
                $resultat['status'],
                [
                    'phone' => $resultat['phone'],
                    'amount' => $resultat['amount'],
                ]
            );

            // Si le paiement est réussi et c'est un firstPayment, générer le reçu
            if ($resultat['status'] === 'success' && $paiement->payment_type === 'firstPayment') {
                $this->jekoService->generateReceipt($paiement);
            }

            Log::info('Webhook Jeko traité avec succès', [
                'reference' => $resultat['reference'],
                'statut' => $resultat['status'],
            ]);

            return response()->json(['status' => 'success'], 200);
        } catch (\Throwable $e) {
            Log::error('Erreur traitement webhook Jeko', [
                'message' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Webhook pour recevoir les notifications de Wave
     */
    public function webhookWave(Request $request)
    {
        $rawBody = $request->getContent();
        $signatureHeader = $request->header('Wave-Signature', '');
        $event = $request->all();

        Log::info('Webhook Wave reçu', [
            'signature' => $signatureHeader,
            'body' => $rawBody,
            'ip' => $request->ip(),
        ]);

        try {
            // Valider le webhook
            if (!$this->jekoService->validerWebhookWave($rawBody, $signatureHeader)) {
                Log::warning('Webhook Wave non valide - signature incorrecte');
                return response()->json(['error' => 'Invalid webhook'], 401);
            }

            // Traiter le webhook
            $resultat = $this->jekoService->traiterWebhookWave($event);

            if (!$resultat) {
                Log::info('Webhook Wave ignoré - traitement non nécessaire');
                return response()->json(['status' => 'ignored'], 200);
            }

            // Mettre à jour le paiement
            $paiement = Paiement::where('payment_code', $resultat['reference'])
            ->orWhere('command_number', $resultat['reference'])->first();

            if (!$paiement) {
                Log::warning('Paiement non trouvé pour le webhook Wave', ['reference' => $resultat['reference']]);
                return response()->json(['status' => 'ignored'], 200);
            }

            Log::info('Mise à jour du paiement via webhook Wave', [
                'reference' => $resultat['reference'],
                'nouveau_statut' => $resultat['status'],
            ]);

            // Mettre à jour le statut
            $this->orchestrator->mettreAJourStatut(
                $paiement,
                $resultat['status'],
                [
                    'amount' => $resultat['amount'],
                ]
            );

            // Si le paiement est réussi et c'est un firstPayment, générer le reçu
            if ($resultat['status'] === 'success' && $paiement->payment_type === 'firstPayment') {
                $this->jekoService->generateReceipt($paiement);
            }

            Log::info('Webhook Wave traité avec succès', [
                'reference' => $resultat['reference'],
                'statut' => $resultat['status'],
                'amount' => $resultat['amount'],
                'transactionId' => $resultat['transactionId'],
            ]);

            return response()->json(['status' => 'success'], 200);
        } catch (\Throwable $e) {
            Log::error('Erreur traitement webhook Wave', [
                'message' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }
}
