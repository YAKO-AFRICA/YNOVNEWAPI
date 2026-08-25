<?php
namespace App\Services\Api\Ynov\Auth;

use App\Mail\Api\Ynov\OtpMail;
use App\Models\Api\Ynov\parameter\OtpCode;
use App\Models\Api\Ynov\parameter\User;
use App\Services\SMSService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class OtpService
{
    public function __construct(
        private SMSService $SMSService,
    ) {
    }

    public function generate(
        User $user,
        string $channel,
        string $purpose,
        ?string $ip = null,
        ?string $ua = null,
        int $expiryMinutes = 2
    ): string {
        $code = str_pad(
            (string) random_int(0, 999999),
            6,
            '0',
            STR_PAD_LEFT
        );

        OtpCode::create([
            'user_uuid' => $user->uuid_user,
            'code' => Hash::make($code),

            // À éviter si la sécurité est prioritaire :
            // idéalement ne pas conserver l'OTP en clair.
            // 'code_plain' => $code,

            'channel' => $channel,
            'purpose' => $purpose,
            'length' => 6,

            'expires_at' => now()->addMinutes($expiryMinutes),

            'ip_address' => $ip,
            'user_agent' => $ua,
        ]);

        return $code;
    }

    public function verify(
        User $user,
        string $code,
        string $purpose
    ): bool {
        $record = OtpCode::where('user_uuid', $user->uuid_user)
            ->where('purpose', $purpose)
            ->where('is_valid', true)
            ->where('is_used', false)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$record) {
            return false;
        }

        if (Hash::check($code, $record->code)) {

            $record->update([
                'is_used' => true,
                'used_at' => now(),
            ]);

            return true;
        }

        $record->incrementAttempts();

        return false;
    }

    public function sendOtp(
        User $user,
        string $channel,
        string $purpose,
        ?string $ip = null,
        ?string $ua = null,
        int $expiryMinutes = 2,
        array $data = []
    ): array {

        /*
         * Vérifier le canal avant de générer/enregistrer l'OTP.
         */
        if (!in_array($channel, [
            'sms',
            'email',
            'whatsapp',
        ], true)) {
            return [
                'success' => false,
                'code' => 'CHANNEL_INVALID',
                'message' => 'Canal d\'envoi OTP invalide.',
            ];
        }

        /*
         * Génération et stockage de l'OTP.
         */
        $code = $this->generate(
            $user,
            $channel,
            $purpose,
            $ip,
            $ua,
            $expiryMinutes
        );

        /*
         * EMAIL
         */
        if ($channel === 'email') {

            $email = $user->email
                ?? $user->details?->email_pro
                ?? $data['email'] 
                ?? null;

            if (empty($email)) {
                return [
                    'success' => false,
                    'code' => 'EMAIL_INVALID',
                    'message' => 'Aucune adresse email disponible pour l\'envoi de l\'OTP.',
                ];
            }

            Mail::to($email)->queue(
                new OtpMail(
                    $user->details,
                    $purpose,
                    $code,
                    $expiryMinutes
                )
            );

            return [
                'success' => true,
                'code' => 'OTP_SENT',
                'message' => 'Code OTP envoyé par email.',
                'data' => [
                    'channel' => 'email',
                    'purpose' => $purpose,
                    'expires_in' => $expiryMinutes,
                ],
            ];
        }

        /*
         * SMS
         */
        if ($channel === 'sms') {

            $phone = preg_replace(
                '/\D/',
                '', $user->details?->mobile_1
                    ?? $data['tel']
                    ?? $data['login']
                    ?? ''
            );

            $phone = substr($phone, -10);

            if (strlen($phone) !== 10) {
                return [
                    'success' => false,
                    'code' => 'TELEPHONE_INVALID',
                    'message' => 'Numéro de téléphone invalide pour l\'envoi SMS.',
                ];
            }

            $phoneNumber = '+225' . $phone;

            $message = sprintf(
                'Votre code OTP YNOV est : %s (valable %d min)',
                $code,
                $expiryMinutes
            );

            if (str_starts_with($phone, '05')) {
                $this->SMSService->sendSmsBySayeliAPI($phoneNumber, $message);
            } else {
                $this->SMSService->sendSmsByInfobipAPI($phoneNumber, $message);
            }
            // $this->SMSService->sendSmsByInfobipAPI(
            //     $phoneNumber,
            //     $message
            // );

            return [
                'success' => true,
                'code' => 'OTP_SENT',
                'message' => 'Code OTP envoyé par SMS.',
                'data' => [
                    'channel' => 'sms',
                    'purpose' => $purpose,
                    'expires_in' => $expiryMinutes,
                ],
            ];
        }

        /*
         * WHATSAPP
         *
         * À connecter à ton service WhatsApp.
         */
        if ($channel === 'whatsapp') {

            $phone = preg_replace(
                '/\D/',
                '',
                $user->details?->mobile_1
                ?? $data['tel']
                ?? $data['login']
                    ?? ''
            );

            $phone = substr($phone, -10);

            if (strlen($phone) !== 10) {
                return [
                    'success' => false,
                    'code' => 'TELEPHONE_INVALID',
                    'message' => 'Numéro de téléphone invalide pour WhatsApp.',
                ];
            }

            $phoneNumber = '+225' . $phone;

            /*
             * Exemple :
             *
             * $this->WhatsAppService->send(...);
             *
             * Ne pas simuler l'envoi tant que le service
             * WhatsApp n'est pas réellement configuré.
             */

            return [
                'success' => false,
                'code' => 'WHATSAPP_NOT_CONFIGURED',
                'message' => 'Le canal WhatsApp n\'est pas encore configuré.',
            ];
        }

        return [
            'success' => false,
            'code' => 'OTP_SEND_FAILED',
            'message' => 'Impossible d\'envoyer le code OTP.',
        ];
    }

    public function getOtpByUser(
        User $user,
        string $purpose,
        string $channel,
        int $dateInHours = 24
    ): ?OtpCode {
        return OtpCode::query()
            ->where('user_uuid', $user->uuid_user)
            ->where('purpose', $purpose)
            ->where('channel', $channel)
            ->where('created_at', '>=', now()->subHours($dateInHours))
            ->latest('created_at')
            ->first();
    }
}
