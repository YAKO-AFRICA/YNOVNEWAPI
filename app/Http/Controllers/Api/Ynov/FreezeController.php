<?php
// app/Http/Controllers/Api/Ynov/FreezeController.php

namespace App\Http\Controllers\Api\Ynov;

use App\Http\Controllers\Controller;
use App\Models\Api\Ynov\parameter\User;
use App\Services\Api\Ynov\Auth\FreezeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FreezeController extends Controller
{
    public function __construct(
        private FreezeService $freezeService,
    ) {}


    /**
     * POST /users/{uuid}/freeze
     */
    public function freeze(Request $request, string $uuid): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'duration' => 'required|integer|min:10|max:86400',
            'reason' => 'required|string|min:3|max:255',
        ], [
            'duration.min' => 'La durée minimale de gel est de 10 secondes.',
            'duration.max' => 'La durée maximale de gel est de 24 heures (86400 secondes).',
            'reason.required' => 'Le motif du gel est obligatoire.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Données invalides.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('uuid_user', $uuid)->firstOrFail();
        $admin = $request->user();

        if ($user->uuid_user === $admin->uuid_user) {
            return response()->json([
                'message' => 'Vous ne pouvez pas geler votre propre compte.',
            ], 422);
        }

        try {
            $success = $this->freezeService->manualFreeze(
                $user,
                $admin,
                (int) $request->input('duration'),
                $request->input('reason')
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        if (!$success) {
            return response()->json([
                'message' => "Ce compte ne peut pas être gelé actuellement (déjà gelé, bloqué ou inactif).",
            ], 409);
        }

        return response()->json([
            'message' => 'Compte gelé avec succès.',
            'data' => $this->freezeService->getCurrentLevel($user->fresh()),
        ]);
    }


    
    /**
     * POST /users/{uuid}/unfreeze
     */
    public function unfreeze(Request $request, string $uuid): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Données invalides.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('uuid_user', $uuid)->firstOrFail();
        $admin = $request->user();

        $success = $this->freezeService->manualUnfreeze(
            $user,
            $admin,
            $request->input('reason')
        );

        if (!$success) {
            return response()->json([
                'message' => "Ce compte n'est pas gelé ou ne peut pas être dégelé manuellement.",
            ], 409);
        }

        return response()->json([
            'message' => 'Compte dégelé avec succès.',
            'data' => $this->freezeService->getCurrentLevel($user->fresh()),
        ]);
    }


    /**
     * GET /users/{uuid}/freeze-status
     */
    public function status(string $uuid): JsonResponse
    {
        $user = User::where('uuid_user', $uuid)->firstOrFail();
        $freeze = $this->freezeService->getCurrentLevel($user);

        return response()->json([
            'data' => [
                'user' => [
                    'uuid_user' => $user->uuid_user,
                    'email' => $user->email,
                    'login' => $user->login,
                    'status' => $user->status,
                ],
                'freeze' => $freeze,
                'can_be_frozen' => $freeze['can_be_frozen'],
                'can_be_unfrozen' => $freeze['can_be_unfrozen'],
            ],
        ]);
    }
}
