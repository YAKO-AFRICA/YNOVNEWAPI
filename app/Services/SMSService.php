<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SMSService
{
    // /**
    //  * Envoyer un OTP via l'API Orange
    //  */

    public function sendSmsByInfobipAPI($phoneNumber,$dataMessage)
    {
        $from="YAKO AFRICA";
        $url = "https://wp2e3q.api.infobip.com/sms/2/text/advanced";
        $cleApi = config('services.info_bip_api_key');
        $headers = [
                    'Authorization' => "App $cleApi",
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
        ];

        $body = [
            "messages" => [
                [
                    "from" => $from,
                    "destinations" => [
                        ["to" => $phoneNumber]
                    ],
                    "text" => $dataMessage
                ]
            ]
        ];

        try {
            
            $response = Http::withHeaders($headers)
                ->post($url, $body);

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }

       
    }


    /**
     * Envoyer un SMS.
     */
    public function sendSms(
        string $phone,
        string $message
    ): array {
        try {

            /*
             * Nettoyage du numéro
             */
            $phone = preg_replace('/\D/', '', $phone);

            if (empty($phone)) {
                return [
                    'success' => false,
                    'message' => 'Numéro de téléphone invalide.',
                    'status' => 422,
                ];
            }

            /*
             * Côte d'Ivoire
             *
             * Exemple :
             * 0707070707
             * +2250707070707
             * 2250707070707
             *
             * => +2250707070707
             */
            if (str_starts_with($phone, '225')) {
                $phone = substr($phone, 3);
            }

            $phone = substr($phone, -10);

            if (strlen($phone) !== 10) {
                return [
                    'success' => false,
                    'message' => 'Le numéro de téléphone doit contenir 10 chiffres.',
                    'status' => 422,
                ];
            }

            $phoneNumber = '+225' . $phone;

            /*
             * Appel Infobip
             */
            $response = $this->sendSmsByInfobipAPI(
                $phoneNumber,
                $message
            );

            /*
             * Vérification erreur API
             */
            if (
                isset($response['error'])
                || isset($response['errors'])
            ) {
                return [
                    'success' => false,
                    'message' => $response['error']
                        ?? 'Erreur lors de l’envoi du SMS.',
                    'status' => 500,
                ];
            }

            return [
                'success' => true,
                'message' => 'SMS envoyé avec succès.',
                'status' => 200,
                'data' => $response,
            ];

        } catch (\Throwable $e) {

            logger()->error(
                'Erreur service SMS',
                [
                    'phone' => $phone,
                    'error' => $e->getMessage(),
                ]
            );

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'status' => 500,
            ];
        }
    }

  
}
