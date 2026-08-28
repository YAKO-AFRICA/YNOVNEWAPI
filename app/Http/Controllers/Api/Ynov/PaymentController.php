<?php

namespace App\Http\Controllers\Api\Ynov;


use App\Http\Controllers\Controller;
use App\Models\Api\Ynov\Facture;
use App\Models\Api\Ynov\Paiement;
use App\Services\Api\Ynov\PaymentService;
use App\Services\Api\Ynov\PrimePaymentOrchestrator;
use App\Services\EncaissementBisService;
use Dompdf\Dompdf;
use Dompdf\Options;
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

        try {
            // 2) Appel Jeko
            $resultat = $this->jekoService->initialiserPaiement([
                'amountCents' => $preparation['montantTotal'] * 100,
                'currency' => $donnees['currency'] ?? 'XOF',
                'reference' => $referenceInterne,
                'paymentMethod' => $donnees['paymentMethod'],
                'successUrl' => $donnees['successUrl'] ??  route('paiement.recu', ['referenceInterne' => $referenceInterne]),
                'errorUrl' => $donnees['errorUrl'] ?? null,
                'customerEmail' => $donnees['customerEmail'] ?? null,
                'customerName' => $donnees['customerName'] ?? null,
                'description' => $donnees['description'] ?? null,
                'metadata' => $donnees['metadata'] ?? null,
            ], $referenceInterne);

            Log::info('JeKO: ' . json_encode($resultat));

            if (!$resultat['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $resultat['message'] ?? "Impossible d'initialiser le paiement.",
                    'code' => $resultat['code'] ?? 'JEKO_INIT_FAILED',
                    'data' => null,
                ], $resultat['status'] ?? 502);
            }

            // 3) Enregistrement du paiement et des factures
            $paiement = $this->orchestrator->enregistrer($donnees, $preparation, $referenceInterne, $resultat);

            Log::info('Paiement enregistré', [
                'paiement' => $paiement,
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
                    'recuUrl' => route('paiement.recu', ['referenceInterne' => $referenceInterne]),
                ],
            ]);
        } catch (\Throwable $e) {

            Log::error('Erreur lors de l\'initialisation du paiement', [
                'referenceInterne' => $referenceInterne,
                'exception' => $e,
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
     * Webhook pour recevoir les notifications de Jeko
     */
    public function webhook(Request $request)
    {
        $payload = $request->all();
        Log::info('Webhook Jeko reçu', ['payload' => $payload]);

        try {
            // Valider le webhook
            if (!$this->jekoService->validerWebhook($request)) {
                Log::warning('Webhook Jeko non valide', ['payload' => $payload]);
                return response()->json(['error' => 'Invalid webhook'], 401);
            }

            // Traiter le webhook
            $resultat = $this->jekoService->traiterWebhook($payload);

            if (!$resultat) {
                return response()->json(['status' => 'ignored'], 200);
            }

            // Mettre à jour le paiement
            $paiement = Paiement::where('command_number', $resultat['reference'])->first();
            // $paiement = Paiement::where('command_number', $reference)->first();

            if (!$paiement) {
                Log::warning('Paiement non trouvé pour le webhook', ['reference' => $resultat['reference']]);
                return response()->json(['status' => 'ignored'], 200);
            }

            // Mettre à jour le statut
            $this->orchestrator->mettreAJourStatut(
                $paiement,
                $resultat['status'],
                [
                    'phone' => $resultat['phone'],
                    'payment_token' => $resultat['payment_token'],
                    'payment_code' => $resultat['payment_code'],
                ]
            );

            // Si le paiement est réussi, générer le reçu
            if ($resultat['status'] === 'success') {
                $this->generateReceipt($paiement);
            }

            Log::info('Webhook traité avec succès', [
                'reference' => $resultat['reference'],
                'statut' => $resultat['status'],
            ]);

            return response()->json(['status' => 'success'], 200);
        } catch (\Throwable $e) {
            Log::error('Erreur traitement webhook Jeko', [
                'message' => $e->getMessage(),
                'payload' => $payload,
            ]);

            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Génère le reçu PDF du paiement
     */

    private function generateReceipt(Paiement $paiement)
    {
        try {
            $externalUploadDir = base_path(env('UPLOADS_PATH'));
            if (!is_dir($externalUploadDir)) {
                mkdir($externalUploadDir, 0777, true);
            }

            $paiement = Paiement::where('uuid_paiement', $paiement->uuid_paiement)->firstOrFail();
            
            $libellesTypeFacture = [
                'N' => 'Prime principale',
                'F' => "Frais d'adhésion",
                'P' => 'Partielle (Reste à payer)',
                'U' => 'Unique',
                'B' => 'Participation aux Bénéfices',
                'E' => 'Exceptionnelle',
                'A' => 'Avance (Remboursement de prêts)',
            ];

            // Libellés des types de paiement
            $libellesType = [
                'firstPayment' => 'Premier paiement',
                'earlyPayment' => 'Paiement anticipé',
                'recoveryPrime' => 'Régularisation de primes',
            ];
            
            $libelleType = $libellesType[$paiement->payment_type] ?? $paiement->payment_type;

            $factures = Facture::where('payment_uuid', $paiement->uuid_paiement)
                ->orderBy('created_at')
                ->get()
                ->map(function ($facture) use ($libellesTypeFacture) {
                    $facture->libelleTypeFacture =
                        $libellesTypeFacture[$facture->type_facture]
                        ?? $facture->type_facture;
                    return $facture;
                });

            // Charger la vue et générer le HTML
            $html = view('paiement.recu_pdf', compact('paiement', 'factures', 'libelleType'))->render();

            // Configurer DomPDF
            $options = new Options();
            $options->set('defaultFont', 'DejaVu Sans');
            $options->set('isRemoteEnabled', false);
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isPhpEnabled', false);

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            $identifiantContrat = $paiement->id_contrat;

            $fileName = 'recu-paiement-' . $paiement->payment_code . '-' . $identifiantContrat . '.pdf';
            $filePath = $externalUploadDir . DIRECTORY_SEPARATOR . $fileName;
            
            // Sauvegarder le PDF
            file_put_contents($filePath, $dompdf->output());
            
            Log::info('PDF généré avec succès pour : ' . $paiement->payment_code);

            return [
                'status' => 'success',
                'file_name' => $fileName,
                'file_path' => $filePath,
            ];
            
        } catch (\Exception $e) {
            Log::error('Erreur génération PDF : ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return [
                'status' => 'error',
                'message' => 'Erreur lors de la génération du PDF : ' . $e->getMessage()
            ];
        }
    }
}


// private function generateReceipt(Paiement $paiement)
// {
//     try {
//         $externalUploadDir = base_path(env('UPLOADS_PATH'));
//         if (!is_dir($externalUploadDir)) {
//             mkdir($externalUploadDir, 0777, true);
//         }

//         $paiement = Paiement::where('uuid_paiement', $paiement->uuid_paiement)->firstOrFail();
//         // $contrat = Contrat::where('id', $paiement->idContrat)->firstOrFail();
        
//         $libellesTypeFacture = [
//             'N' => 'Prime principale',
//             'F' => "Frais d'adhésion",
//             'P' => 'Partielle (Reste à payer)',
//             'U' => 'Unique',
//             'B' => 'Participation aux Bénéfices',
//             'E' => 'Exceptionnelle',
//             'A' => 'Avance (Remboursement de prêts)',
//         ];

//         // Libellés des types de paiement
//         $libellesType = [
//             'firstPayment' => 'Premier paiement',
//             'earlyPayment' => 'Paiement anticipé',
//             'recoveryPrime' => 'Régularisation de primes',
//         ];
        
//         $libelleType = $libellesType[$paiement->payment_type] ?? $paiement->payment_type;

//         $factures = Facture::where('payment_uuid', $paiement->uuid_paiement)
//             ->orderBy('created_at')
//             ->get()
//             ->map(function ($facture) use ($libellesTypeFacture) {
//                 $facture->libelleTypeFacture =
//                     $libellesTypeFacture[$facture->type_facture]
//                     ?? $facture->type_facture;
//                 return $facture;
//             });

//         // Charger la vue et générer le HTML
//         $html = view('paiement.recu_pdf', compact('paiement', 'factures', 'libelleType'))->render();

//         // Configurer DomPDF
//         $options = new Options();
//         $options->set('defaultFont', 'DejaVu Sans');
//         $options->set('isRemoteEnabled', false);
//         $options->set('isHtml5ParserEnabled', true);
//         $options->set('isPhpEnabled', false);

//         $dompdf = new Dompdf($options);
//         $dompdf->loadHtml($html);
//         $dompdf->setPaper('A4', 'portrait');
//         $dompdf->render();

//         $identifiantContrat = $paiement->id_contrat;

//         $fileName = 'recu-paiement-' . $paiement->payment_code . '-' . $identifiantContrat . '.pdf';
//         $filePath = $externalUploadDir . DIRECTORY_SEPARATOR . $fileName;
        
//         // Sauvegarder le PDF
//         file_put_contents($filePath, $dompdf->output());
        
//         Log::info('PDF généré avec succès pour : ' . $paiement->payment_code);

//         // if ($payment_type === 'firstPayment') {

//         //     // Ajoute le reçu au contrat
//         //     TblDocument::create([
//         //         'codecontrat' => $paiement->idContrat,
//         //         'filename' => $fileName,
//         //         'libelle' => 'Recu de paiement',
//         //         'saisiele' => now(),
//         //         'saisiepar' => $contrat->saisiepar ?? null,
//         //         'source' => "ES",
//         //     ]);
            
//         //     // Mettre le contrat en statut "payé"
//         //     $contrat->update(['estpaye' => 1]);
//         // }

//         return [
//             'status' => 'success',
//             'file_name' => $fileName,
//             'file_path' => $filePath,
//         ];
        
//     } catch (\Exception $e) {
//         Log::error('Erreur génération PDF : ' . $e->getMessage());
//         Log::error('Stack trace: ' . $e->getTraceAsString());
        
//         return [
//             'status' => 'error',
//             'message' => 'Erreur lors de la génération du PDF : ' . $e->getMessage()
//         ];
//     }
// }
