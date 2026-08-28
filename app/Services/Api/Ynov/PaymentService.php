<?php

namespace App\Services\Api\Ynov;

use App\Models\Api\Ynov\Paiement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service de communication avec l'API Jeko
 */
class PaymentService
{
    protected string $baseUrl;
    protected string $storeId;
    protected string $apiKey;
    protected string $apiKeyId;
    protected string $webhookSecret;

    public function __construct()
    {
        $this->baseUrl = config('services.jeko.base_url', 'https://api.jeko.africa');
        $this->storeId = config('services.jeko.store_id');
        $this->apiKey = config('services.jeko.api_key');
        $this->apiKeyId = config('services.jeko.api_key_id');
        $this->webhookSecret = config('services.jeko.webhook_secret');
    }

    /**
     * Initialise un paiement chez Jeko
     */
    public function initialiserPaiement(array $donnees, string $referenceInterne): array
    {
        $corpsRequete = [
            'storeId' => $this->storeId,
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

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-API-KEY' => $this->apiKey,
            'X-API-KEY-ID' => $this->apiKeyId,
        ])
            ->timeout(15)
            ->retry(3, 100)
            ->post($this->baseUrl . '/partner_api/payment_requests', $corpsRequete);

        $data = $response->json();

        if (!$response->successful() || empty($data['redirectUrl'])) {
            Log::warning('Échec initialisation paiement Jeko', [
                'status' => $response->status(),
                'response' => $data,
                'request' => $corpsRequete,
            ]);

            return [
                'success' => false,
                'message' => $data['message'] ?? 'Erreur lors de l\'initialisation du paiement',
                'code' => $data['code'] ?? 'JEKO_ERROR',
                'status' => $response->status(),
            ];
        }

        return [
            'success' => true,
            'redirectUrl' => $data['redirectUrl'],
            'paymentId' => $data['paymentId'] ?? null,
            'status' => $data['status'] ?? 'pending',
            'code' => 'PAYMENT_INITIATED',
        ];
    }

    /**
     * Valide le webhook Jeko
     */
    public function validerWebhook(Request $request): bool
    {
        $signature = $request->header('jeko-signature');
        $payload = $request->getContent();

        if (empty($signature) || empty($payload)) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $payload, $this->webhookSecret);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Traite le webhook Jeko
     */
    public function traiterWebhook(array $payload): ?array
    {
        $reference = $payload['reference'] ?? null;
        $status = $payload['status'] ?? null;

        if (!$reference || !$status) {
            return null;
        }

        $paiement = Paiement::where('command_number', $reference)->first();

        if (!$paiement) {
            Log::warning('Paiement non trouvé pour le webhook', ['reference' => $reference]);
            return null;
        }

        // Extraire les informations du payload
        $phone = null;
        if (!empty($payload['counterpartIdentifier'])) {
            $phone = preg_replace('/\D/', '', $payload['counterpartIdentifier']);
            $phone = substr($phone, -10);
        }

        $paymentToken = $payload['transactionDetails']['paymentLinkId'] ?? null;
        $payment_code = $payload['transactionDetails']['paymentLinkId'] ?? null;

        return [
            'reference' => $reference,
            'status' => $this->convertirStatut($status),
            'phone' => $phone,
            'payment_token' => $paymentToken,
            'payment_code' => $payment_code,
            'amount' => $payload['amount']['amount'] / 100 ?? null,
        ];
    }

    /**
     * Convertit le statut Jeko en statut interne
     */
    private function convertirStatut(string $status): string
    {
        $map = [
            'pending' => 'pending',
            'processing' => 'pending',
            'completed' => 'success',
            'success' => 'success',
            'failed' => 'error',
            'cancelled' => 'cancelled',
            'refunded' => 'cancelled',
            'error' => 'error',
        ];

        return $map[strtolower($status)] ?? $status;
    }
}