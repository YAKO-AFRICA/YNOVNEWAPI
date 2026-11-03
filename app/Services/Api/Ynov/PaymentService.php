<?php

namespace App\Services\Api\Ynov;

use App\Models\Api\Ynov\Esouscription\Contrat;
use App\Models\Api\Ynov\Facture;
use App\Models\Api\Ynov\Paiement;
use App\Models\Api\Ynov\parameter\ActivityLog;
use App\Services\Api\Ynov\Documents\DocumentService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service de communication avec l'API Jeko
 */
class PaymentService
{
    // Configuration Jeko
    protected string $jekoBaseUrl;
    protected string $jekoStoreId;
    protected string $jekoApiKey;
    protected string $jekoApiKeyId;
    protected string $jekoWebhookSecret;

    // Configuration Wave
    protected string $waveApiKey;
    protected string $waveBaseUrl;
    protected string $waveWebhookSecret;

    protected DocumentService $documentService;

    public function __construct(DocumentService $documentService)
    {
        $this->documentService = $documentService;

        // Jeko
        $this->jekoBaseUrl = config('services.jeko.base_url', 'https://api.jeko.africa');
        $this->jekoStoreId = config('services.jeko.store_id');
        $this->jekoApiKey = config('services.jeko.api_key');
        $this->jekoApiKeyId = config('services.jeko.api_key_id');
        $this->jekoWebhookSecret = config('services.jeko.webhook_secret');

        // Wave
        $this->waveApiKey = (string) config('services.wave.api_key');
        $this->waveBaseUrl = rtrim((string) config('services.wave.base_url', 'https://api.wave.com'), '/');
        $this->waveWebhookSecret = config('services.wave.webhook_secret');
    }

    /**
     * Route l'initialisation vers Wave ou Jeko selon paymentMethod
     */
    public function initialiserPaiement(array $donnees, string $referenceInterne): array
    {
        $paymentMethod = $donnees['paymentMethod'] ?? '';

        if ($paymentMethod === 'wave') {
            return $this->initialiserPaiementWave($donnees, $referenceInterne);
        } else {
            return $this->initialiserPaiementJeko($donnees, $referenceInterne);
        }
    }

    /**
     * Initialise un paiement chez Wave
     */
    protected function initialiserPaiementWave(array $donnees, string $referenceInterne): array
    {
        $payload = [
            'amount' => $donnees['amountCents'],
            'currency' => $donnees['currency'] ?? 'XOF',
            'client_reference' => $referenceInterne,
            'success_url' => (isset($donnees['successUrl']) && !empty($donnees['successUrl'])) ? $donnees['successUrl'] : env('APP_URL') . '/paiement/recu/' . $referenceInterne,
            'error_url' => (isset($donnees['errorUrl']) && !empty($donnees['errorUrl'])) ? $donnees['errorUrl'] : env('APP_URL') . '/paiement/error/' . $referenceInterne,
        ];

        $response = Http::withToken($this->waveApiKey)
            ->withHeaders(['Idempotency-Key' => 'order-' . $referenceInterne])
            ->acceptJson()
            ->post("{$this->waveBaseUrl}/v1/checkout/sessions", $payload);

        $data = $response->json();

        if (!$response->successful()) {
            Log::warning('Échec initialisation paiement Wave', [
                'status' => $response->status(),
                'response' => $data,
                'request' => $payload,
            ]);

            return [
                'success' => false,
                'message' => $data['message'] ?? 'Erreur lors de l\'initialisation du paiement',
                'code' => $data['code'] ?? 'WAVE_API_ERROR',
                'status' => $response->status(),
            ];
        }

        Log::info('Paiement Wave initialisé avec succès', [
            'reference' => $referenceInterne,
        ]);

        return [
            'success' => true,
            'redirectUrl' => $data['wave_launch_url'],
            'status' => $data['status'] ?? 'pending',
            'code' => 'PAYMENT_INITIATED',
            'paymentId' => $data['id'] ?? null,
        ];
    }

    /**
     * Initialise un paiement chez Jeko
     */
    protected function initialiserPaiementJeko(array $donnees, string $referenceInterne): array
    {
        $corpsRequete = [
            'storeId' => $this->jekoStoreId,
            'amountCents' => $donnees['amountCents'],
            'currency' => $donnees['currency'] ?? 'XOF',
            'reference' => $referenceInterne,
            'paymentDetails' => [
                'type' => 'redirect',
                'data' => [
                    'paymentMethod' => $donnees['paymentMethod'],
                    'successUrl' => $donnees['successUrl'] ?? null,
                    'errorUrl' => $donnees['errorUrl'] ?? null,
                    'payerPhone' => $donnees['payerPhone'] ?? null,
                ],
            ],
        ];

        // Ajout des informations optionnelles
        if (!empty($donnees['customerEmail'])) {
            $corpsRequete['customerEmail'] = $donnees['customerEmail'];
        }
        if (!empty($donnees['customerName'])) {
            $corpsRequete['customerName'] = $donnees['customerName'];
        }
        if (!empty($donnees['description'])) {
            $corpsRequete['description'] = $donnees['description'];
        }
        if (!empty($donnees['metadata'])) {
            $corpsRequete['metadata'] = $donnees['metadata'];
        }

        Log::info('Initialisation du paiement Jeko', [
            'reference' => $referenceInterne,
            'currency' => $donnees['currency'] ?? 'XOF',
            'payment_method' => $donnees['paymentMethod'],
        ]);

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-API-KEY' => $this->jekoApiKey,
            'X-API-KEY-ID' => $this->jekoApiKeyId,
        ])
            ->timeout(15)
            ->retry(3, 100)
            ->post($this->jekoBaseUrl . '/partner_api/payment_requests', $corpsRequete);

        $data = $response->json();

        if (!$response->successful() || empty($data['redirectUrl'])) {
            Log::error('Échec initialisation paiement Jeko', [
                'reference' => $referenceInterne,
                'status' => $response->status(),
            ]);

            return [
                'success' => false,
                'message' => $data['message'] ?? 'Erreur lors de l\'initialisation du paiement',
                'code' => $data['code'] ?? 'JEKO_ERROR',
                'status' => $response->status(),
            ];
        }

        Log::info('Paiement Jeko initialisé avec succès', [
            'reference' => $referenceInterne,
            'status' => $data['status'] ?? 'pending',
        ]);

        return [
            'success' => true,
            'redirectUrl' => $data['redirectUrl'],
            'paymentId' => $data['id'] ?? null,
            'status' => $data['status'] ?? 'pending',
            'code' => 'PAYMENT_INITIATED',
        ];
    }


    /**
     * Vérifie le statut d'un paiement selon le provider
     */
    public function verifierStatut($paiement, string $provider = null): array
    {
        $provider = $provider ?? $paiement->reglement_source ?? 'JEKO';

        if ($provider === 'WAVE_API' || $provider === 'WAVE') {
            return $this->verifierStatutWave($paiement);
        } else {
            return $this->verifierStatutJeko($paiement);
        }
    }

    /**
     * Vérifie le statut d'un paiement Wave
     */
    protected function verifierStatutWave($paiement): array
    {
        if (empty($paiement->payment_token)) {
            return [
                'success' => false,
                'message' => 'Aucune session Wave associée à ce paiement.',
                'details' => [],
            ];
        }

        try {
            $data = $this->getWaveSession($paiement->payment_token);
            $status = strtolower((string) ($data['payment_status'] ?? ''));
            $status = $this->convertirStatut($status ?? 'pending');

            return [
                'success' => true,
                'status' => $status,
                'details' => $data,
            ];
        } catch (\Throwable $e) {
            Log::warning('verifierStatutWave impossible', [
                'reference' => $paiement->payment_code,
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'details' => [],
            ];
        }
    }

    /**
     * Vérifie le statut d'un paiement Jeko
     */
    protected function verifierStatutJeko($paiement): array
    {
        if (empty($paiement->payment_token)) {
            Log::warning('Tentative de vérification de statut avec un token de paiement vide');

            return [
                'success' => false,
                'code' => 'INVALID_PAYMENT_CODE',
                'status' => $paiement->status ?? 'unknown',
                'message' => 'Token de paiement manquant ou invalide',
                'details' => [],
            ];
        }

        // Utiliser l'endpoint payment_requests pour obtenir le statut (et non payment_links)
        $response = Http::withHeaders([
            'X-API-KEY' => $this->jekoApiKey,
            'X-API-KEY-ID' => $this->jekoApiKeyId,
        ])
            ->timeout(10)
            ->retry(2, 100)
            ->get($this->jekoBaseUrl . '/partner_api/payment_requests/' . $paiement->payment_token);

        if (!$response->successful()) {
            Log::warning('Échec de la vérification du statut du paiement', [
                'status' => $response->status(),
            ]);

            return [
                'success' => false,
                'code' => 'TRANSACTION_VERIFICATION_FAILED',
                'status' => $paiement->status ?? 'unknown',
                'message' => 'Erreur lors de la vérification du statut du paiement',
                'details' => [
                    'http_status' => $response->status(),
                    'payment_token' => $paiement->payment_token,
                ],
            ];
        }

        $data = $response->json();

        Log::info('Statut du paiement vérifié avec succès', [
            'status' => $data['status'] ?? 'unknown',
        ]);

        return [
            'success' => true,
            'code' => 'TRANSACTION_VERIFIED',
            'message' => 'Statut du paiement vérifié',
            'status' => $data['status'] ?? 'unknown',
            'details' => $data,
        ];
    }

    /**
     * Récupère une session Wave par son ID
     */
    protected function getWaveSession(string $sessionId): array
    {
        $response = Http::withToken($this->waveApiKey)
            ->acceptJson()
            ->get("{$this->waveBaseUrl}/v1/checkout/sessions/{$sessionId}");

        if ($response->failed()) {
            throw new \Exception('Wave check failed: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Valide le webhook Jeko
     */
    public function validerWebhookJeko(Request $request): bool
    {
        $signature = $request->header('jeko-signature');
        $payload = $request->getContent();

        if (empty($signature) || empty($payload)) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $payload, $this->jekoWebhookSecret);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Valide la signature du webhook Wave
     */
    public function validerWebhookWave(string $rawBody, string $signatureHeader): bool
    {
        $secret = $this->waveWebhookSecret;

        // Fail closed : jamais d'acceptation sans secret
        if (empty($secret)) {
            Log::channel('wave_webhooks')->error('Webhook Wave : secret non configuré, requête rejetée');
            return false;
        }

        if ($signatureHeader === '') {
            Log::channel('wave_webhooks')->warning('Webhook Wave : header Wave-Signature manquant');
            return false;
        }

        // Header : t=timestamp,v1=sig1[,v1=sig2] (plusieurs v1 possibles pendant une rotation)
        $timestamp = null;
        $signatures = [];
        foreach (explode(',', $signatureHeader) as $part) {
            $kv = explode('=', trim($part), 2);
            if (count($kv) !== 2) {
                continue;
            }
            if ($kv[0] === 't') {
                $timestamp = $kv[1];
            } elseif ($kv[0] === 'v1') {
                $signatures[] = $kv[1];
            }
        }

        if (!ctype_digit((string) $timestamp) || empty($signatures)) {
            Log::channel('wave_webhooks')->warning('Webhook Wave : header de signature mal formé');
            return false;
        }

        // Anti-replay : 5 minutes
        if (abs(time() - (int) $timestamp) > 300) {
            Log::channel('wave_webhooks')->warning('Webhook Wave : timestamp hors fenêtre', [
                'diff' => abs(time() - (int) $timestamp),
            ]);
            return false;
        }

        // timestamp + body, SANS séparateur
        $expected = hash_hmac('sha256', $timestamp . $rawBody, $secret);

        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }

        Log::channel('wave_webhooks')->warning('Webhook Wave : signature invalide');
        return false;
    }

    /**
     * Traite le webhook Jeko
     */
    public function traiterWebhookJeko(array $payload): ?array
    {
        $reference = $payload['transactionDetails']['reference'] ?? null;
        $status = $payload['status'] ?? null;

        if (!$reference || !$status) {
            Log::warning('Webhook Jeko avec données incomplètes');
            return null;
        }

        $paiement = Paiement::where('payment_code', $reference)
            ->where('command_number', $reference)
            ->first();

        if (!$paiement) {
            Log::warning('Paiement non trouvé pour le webhook');
            return null;
        }

        // Idempotency: si le paiement est déjà dans l'état final, ignorer le webhook
        $targetStatus = $this->convertirStatut($status);
        if ($paiement->status === $targetStatus && in_array($targetStatus, ['success', 'failed', 'cancelled', 'error'])) {
            Log::info('Webhook ignoré - paiement déjà dans l\'état final', [
                'reference' => $reference,
                'current_status' => $paiement->status,
                'webhook_status' => $targetStatus,
            ]);
            return null;
        }

        // Extraire les informations du payload
        $phone = null;
        if (!empty($payload['counterpartIdentifier'])) {
            $phone = preg_replace('/\D/', '', $payload['counterpartIdentifier']);
            $phone = substr($phone, -10);
        }

        return [
            'reference' => $reference,
            'status' => $targetStatus,
            'phone' => $phone,
            'amount' => ((float)($payload['amount']['amount'] ?? 0)) / 100,
        ];
    }

    /**
     * Traite le webhook Wave
     */
    public function traiterWebhookWave(array $event): ?array
    {
        $type = $event['type'] ?? null;
        $data = $event['data'] ?? [];
        $clientReference = $data['client_reference'] ?? null;
        $sessionId = $data['id'] ?? null;
        $status = strtolower((string) ($data['payment_status'] ?? ''));
        $amount = $data['amount'] ?? 0;
        $transactionId = $data['transaction_id'] ?? null;

        if (!in_array($type, [
            'checkout.session.completed',
            'checkout.session.failed',
            'checkout.session.cancelled',
        ], true)) {
            return null;
        }

        $mappedStatus = $this->convertirStatut($status);

        return [
            'reference' => $clientReference,
            'status' => $mappedStatus,
            'sessionId' => $sessionId,
            'amount' => (float) $amount,
            'transactionId' => $transactionId,
        ];
    }

    /**
     * Convertit le statut en statut interne
     */
    private function convertirStatut(string $status): string
    {
        $map = [
            'pending' => 'pending',
            'processing' => 'pending',
            'completed' => 'success',
            'success' => 'success',
            'succeeded' => 'success',
            'failed' => 'error',
            'cancelled' => 'cancelled',
            'refunded' => 'cancelled',
            'error' => 'error',
        ];

        return $map[strtolower($status)] ?? $status;
    }

    /**
     * Génère le reçu PDF du paiement
     */
    public function generateReceipt(Paiement $paiement)
    {
        try {
            $paiement = Paiement::where('uuid_paiement', $paiement->uuid_paiement)->firstOrFail();

            Log::info('Génération du reçu PDF', [
                'reference' => $paiement->command_number,
            ]);

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

            // Créer un fichier temporaire
            $tempPath = tempnam(sys_get_temp_dir(), 'pdf_');
            file_put_contents($tempPath, $dompdf->output());

            Log::info('PDF généré avec succès', [
                'file_name' => $fileName,
            ]);

            if ($paiement->payment_type === 'firstPayment') {
                $contrat = Contrat::where('id_contrat', $identifiantContrat)->firstOrFail();

                // Créer un UploadedFile à partir du fichier temporaire
                $uploadedFile = new UploadedFile(
                    $tempPath,
                    $fileName,
                    'application/pdf',
                    null,
                    true
                );

                // Utiliser DocumentService pour créer le document
                $this->documentService->createDocument([
                    'reference_uuid' => $contrat->uuid_contrat,
                    'libelle' => 'Recu de paiement',
                    'source' => 'E-SOUSCRIPTION',
                    'created_by' => $contrat->created_by ?? null,
                ], $uploadedFile);

                // Mettre le contrat en statut "payé"
                $oldIsPaid = (bool) $contrat->is_paid;
                $contrat->update(['is_paid' => 1]);

                if (!$oldIsPaid) {
                    ActivityLog::log([
                        'user_uuid' => request()->user()?->uuid_user,
                        'action' => 'contract_marked_paid',
                        'action_type' => 'payment',
                        'module' => 'contracts',
                        'description' => "Contrat {$contrat->id_contrat} marqué comme payé après émission du reçu.",
                        'resource_type' => 'contrat',
                        'resource_id' => $contrat->uuid_contrat,
                        'old_values' => ['is_paid' => false],
                        'new_values' => [
                            'is_paid' => true,
                            'payment_uuid' => $paiement->uuid_paiement,
                        ],
                        'level' => 'info',
                    ]);
                }
            }

            // Supprimer le fichier temporaire
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }

            return [
                'status' => 'success',
                'file_name' => $fileName,
            ];

        } catch (\Exception $e) {
            Log::error('Erreur génération PDF', [
                'message' => $e->getMessage(),
            ]);

            return [
                'status' => 'error',
                'message' => 'Erreur lors de la génération du PDF : ' . $e->getMessage()
            ];
        }
    }
}