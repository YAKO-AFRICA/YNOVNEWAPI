<?php
namespace App\Http\Controllers\Api\Ynov;

use App\Http\Controllers\Controller;
use App\Mail\Api\Ynov\EmailVerificationMail;
use App\Models\Api\Ynov\parameter\EmailVerificationToken;
use App\Models\Api\Ynov\parameter\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class EmailVerificationController extends Controller
{
    public function send(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->email_verified_at) {
            return response()->json(['success' => false, 'message' => 'Email déjà vérifié.']);
        }

        $token = Str::random(64);
        EmailVerificationToken::updateOrCreate(
            ['user_uuid' => $user->uuid_user],
            [
                'token' => Hash::make($token),
                'expires_at' => now()->addHours(24),
                'created_at' => now(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]
        );
        Mail::to($user->email)->queue(new EmailVerificationMail($user->fresh('details'), $token, 24));
        return response()->json(['success' => true, 'message' => 'Email de vérification envoyé.']);
    }

    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $request->email)->firstOrFail();

        $record = EmailVerificationToken::where('user_uuid', $user->uuid_user)
            ->where('expires_at', '>', now())
            ->first();

        if (!$record || !Hash::check($request->token, $record->token)) {
            return response()->json(['success' => false, 'message' => 'Token invalide ou expiré.'], 422);
        }

        $user->update(['email_verified_at' => now()]);
        $record->delete();

        return response()->json(['success' => true, 'message' => 'Email vérifié avec succès.']);
    }
}