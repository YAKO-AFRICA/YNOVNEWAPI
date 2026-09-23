<?php

namespace App\Http\Controllers\Api\Ynov;

use App\Http\Controllers\Controller;
use App\Models\Api\Ynov\parameter\User;
use App\Services\Api\Ynov\Auth\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class OtpController extends Controller
{
    public function __construct(
        private OtpService $otpService,
    ) {}

    protected function resolveUser(Request $request): ?User
    {
        if ($request->filled('user_uuid')) {
            return User::where('uuid_user', $request->user_uuid)->first();
        }

        if ($request->filled('login')) {
            return User::where('login', $request->login)->first();
        }

        return null;
    }

    public function sendOtp(Request $request): JsonResponse
    {
        $request->validate([
            'channel' => ['required', 'string', 'in:sms,email,whatsapp'],
            'purpose' => ['required', 'string', 'max:120'],
            'login' => ['nullable', 'string', 'max:100'],
            'user_uuid' => ['nullable', 'uuid'],
            'email' => ['nullable', 'email'],
            'tel' => ['nullable', 'string', 'max:20'],
        ]);

        $user = $this->resolveUser($request);

        if (!$user) {
            return response()->json([
                'success' => false,
                'code' => 'USER_NOT_FOUND',
                'message' => 'Utilisateur introuvable.',
            ], 404);
        }

        $result = $this->otpService->sendOtp(
            user: $user,
            channel: $request->channel,
            purpose: $request->purpose,
            ip: $request->ip(),
            ua: $request->userAgent(),
            expiryMinutes: (int) ($request->expiry_minutes ?? 5),
            data: $request->all(),
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function resendOtp(Request $request): JsonResponse
    {
        $request->validate([
            'channel' => ['required', 'string', 'in:sms,email,whatsapp'],
            'purpose' => ['required', 'string', 'max:120'],
            'login' => ['nullable', 'string', 'max:100'],
            'user_uuid' => ['nullable', 'uuid'],
            'email' => ['nullable', 'email'],
            'tel' => ['nullable', 'string', 'max:20'],
        ]);

        $user = $this->resolveUser($request);

        if (!$user) {
            return response()->json([
                'success' => false,
                'code' => 'USER_NOT_FOUND',
                'message' => 'Utilisateur introuvable.',
            ], 404);
        }

        $result = $this->otpService->resendOtp(
            user: $user,
            channel: $request->channel,
            purpose: $request->purpose,
            ip: $request->ip(),
            ua: $request->userAgent(),
            expiryMinutes: (int) ($request->expiry_minutes ?? 5),
            data: $request->all(),
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6', 'regex:/^[0-9]{6}$/'],
            'purpose' => ['required', 'string', 'max:120'],
            'login' => ['nullable', 'string', 'max:100'],
            'user_uuid' => ['nullable', 'uuid'],
        ]);

        $user = $this->resolveUser($request);

        if (!$user) {
            return response()->json([
                'success' => false,
                'code' => 'OTP_INVALID',
                'message' => 'Code OTP invalide ou expiré.',
            ], 422);
        }

        $result = $this->otpService->verify(
            $user,
            $request->code,
            $request->purpose
        );

        if (!$result) {
            return response()->json([
                'success' => false,
                'code' => 'OTP_INVALID',
                'message' => 'Code OTP invalide ou expiré.',
            ], 422);
        }

        if (strtolower($request->purpose) === 'reset') {
            $resetToken = Str::random(64);
            $hashedToken = Hash::make($resetToken);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['login' => $user->login],
                [
                    'token' => $hashedToken,
                    'created_at' => now(),
                    'expires_at' => now()->addMinutes(60),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]
            );

            return response()->json([
                'success' => true,
                'code' => 'OTP_VERIFIED',
                'message' => 'Code OTP vérifié.',
                'data' => [
                    'user_uuid' => $user->uuid_user,
                    'reset_token' => $resetToken,
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'code' => 'OTP_VERIFIED',
            'message' => 'Code OTP vérifié.',
            'data' => [
                'user_uuid' => $user->uuid_user,
                'purpose' => $this->otpService->normalizePurpose($request->purpose),
            ],
        ]);
    }
}
