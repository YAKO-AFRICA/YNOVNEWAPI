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

    public function normalizePurpose(string $purpose): string
    {
        $cleaned = trim((string) $purpose);

        if ($cleaned === '') {
            throw new \InvalidArgumentException('Le but de l\'OTP est requis.');
        }

        return strtolower(preg_replace('/[^a-z0-9_\-]+/i', '_', $cleaned));
    }

    public function generate(
        User $user,
        string $channel,
        string $purpose,
        ?string $ip = null,
        ?string $ua = null,
        int $expiryMinutes = 2
    ): string {
        $purpose = $this->normalizePurpose($purpose);

        OtpCode::query()
            ->where('user_uuid', $user->uuid_user)
            ->where('purpose', $purpose)
            ->where('is_valid', true)
            ->where('is_used', false)
            ->where('expires_at', '>', now())
            ->update([
                'is_valid' => false,
            ]);

        $code = str_pad(
            (string) random_int(0, 999999),
            6,
            '0',
            STR_PAD_LEFT
        );

        OtpCode::create([
            'user_uuid' => $user->uuid_user,
            'code' => Hash::make($code),
            // 'code_plain' => $code,
            'channel' => $channel,
            'purpose' => $purpose,
            'length' => 6,
            'expires_at' => now()->addMinutes($expiryMinutes),
            'is_valid' => true,
            'is_used' => false,
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
        $purpose = $this->normalizePurpose($purpose);

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
                'is_valid' => false,
                'used_at' => now(),
            ]);

            return true;
        }

        $record->incrementAttempts();

        return false;
    }

    protected function dispatchOtp(
        User $user,
        string $channel,
        string $purpose,
        string $code,
        int $expiryMinutes,
        array $data = []
    ): array {
        if (!in_array($channel, ['sms', 'email', 'whatsapp'], true)) {
            return [
                'success' => false,
                'code' => 'CHANNEL_INVALID',
                'message' => 'Canal d\'envoi OTP invalide.',
            ];
        }

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

        if ($channel === 'sms') {
            $phone = preg_replace('/\D/', '', $data['tel'] ?? $data['login'] ??  $user->details?->mobile_1 ?? '');
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

        $phone = preg_replace('/\D/', '', $data['tel'] ?? $data['login'] ??  $user->details?->mobile_1 ?? '');
        $phone = substr($phone, -10);

        if (strlen($phone) !== 10) {
            return [
                'success' => false,
                'code' => 'TELEPHONE_INVALID',
                'message' => 'Numéro de téléphone invalide pour WhatsApp.',
            ];
        }

        return [
            'success' => false,
            'code' => 'WHATSAPP_NOT_CONFIGURED',
            'message' => 'Le canal WhatsApp n\'est pas encore configuré.',
        ];
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
        $purpose = $this->normalizePurpose($purpose);
        $code = $this->generate(
            $user,
            $channel,
            $purpose,
            $ip,
            $ua,
            $expiryMinutes
        );

        return $this->dispatchOtp(
            $user,
            $channel,
            $purpose,
            $code,
            $expiryMinutes,
            $data
        );
    }

    public function resendOtp(
        User $user,
        string $channel,
        string $purpose,
        ?string $ip = null,
        ?string $ua = null,
        int $expiryMinutes = 2,
        array $data = []
    ): array {
        $purpose = $this->normalizePurpose($purpose);

        $latest = OtpCode::where('user_uuid', $user->uuid_user)
            ->where('purpose', $purpose)
            ->latest('created_at')
            ->first();

        if (!$latest) {
            return $this->sendOtp(
                $user,
                $channel,
                $purpose,
                $ip,
                $ua,
                $expiryMinutes,
                $data
            );
        }

        if (!$latest->canResend(3, 1)) {
            return [
                'success' => false,
                'code' => 'OTP_RESEND_LIMIT_REACHED',
                'message' => 'Vous avez déjà demandé un renvoi trop récemment. Veuillez réessayer plus tard.',
            ];
        }

        $code = $this->generate(
            $user,
            $channel,
            $purpose,
            $ip,
            $ua,
            $expiryMinutes
        );

        $result = $this->dispatchOtp(
            $user,
            $channel,
            $purpose,
            $code,
            $expiryMinutes,
            $data
        );

        if ($result['success']) {
            $latest->incrementResendCount();
        }

        return $result;
    }

    public function getOtpByUser(
        User $user,
        string $purpose,
        string $channel,
        int $dateInHours = 24
    ): ?OtpCode {
        return OtpCode::query()
            ->where('user_uuid', $user->uuid_user)
            ->where('purpose', $this->normalizePurpose($purpose))
            ->where('channel', $channel)
            ->where('created_at', '>=', now()->subHours($dateInHours))
            ->latest('created_at')
            ->first();
    }
}
