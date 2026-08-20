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

  
}
