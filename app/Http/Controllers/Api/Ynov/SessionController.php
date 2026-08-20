<?php
namespace App\Http\Controllers\Api\Ynov;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Ynov\TokenResource;
use App\Mail\Api\Ynov\SessionRevokedMail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class SessionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $sessions = $request->user()->tokens()->get();
        return response()->json([
            'success' => true,
            'data' => TokenResource::collection($sessions),
        ]);
    }


    public function revoke(Request $request, string $tokenId): JsonResponse
    {
        $user = $request->user();
        $token = $user->tokens()->find($tokenId);
        if (!$token) {
            return response()->json(['success' => false, 'message' => 'Session non trouvée.'], 404);
        }

        $sessionName = $token->name;
        $token->delete();

        if ($user->tokens()->count() === 0) {
            $user->update(['is_online' => false]);
        }

        if ($user->email) {
            Mail::to($user->email)->queue(new SessionRevokedMail($user->fresh('details'), $sessionName, false));
        }

        return response()->json(['success' => true, 'message' => 'Session révoquée.']);
    }
}