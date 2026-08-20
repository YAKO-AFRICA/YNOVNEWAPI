<?php
namespace App\Http\Controllers\Api\Ynov;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Ynov\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load([
            'role.permissions.group',
            'details',
            'partner',
            'reseau',
            'agences',
            'groupNotifs',
            'userContrats'
        ]);

        $user->setAttribute('permissions_grouped', $user->getGroupedPermissions());

        return response()->json([
            'success' => true,
            'data' => new UserResource($user),
        ]);
        // return response()->json([
        //     'success' => true,
        //     'data' => new UserResource($request->user()->load([
        //         'role.permissions.group', 'details', 'partner', 'reseau', 'agences', 'groupNotifs'
        //     ])),
        // ]);
    }

    public function update(Request $request): JsonResponse
    {
        // Log::info('update profile request ', $request->all());
        $user = $request->user();
        $validated = $request->validate([
            'login' => ['sometimes', 'nullable', 'string', 'max:100', "unique:users,login,{$user->uuid_user},uuid_user"],
            'nom' => ['sometimes', 'string', 'max:55'],
            'prenoms' => ['sometimes', 'string', 'max:255'],
            'fonction' => ['nullable', 'string', 'max:55'],
            'mobile_1' => ['nullable', 'string', 'max:25'],
            'mobile_2' => ['nullable', 'string', 'max:25'],
            'photo' => ['nullable', 'string', 'max:255'],
            'ville' => ['nullable', 'string', 'max:100'],
            'pays' => ['nullable', 'string', 'max:100'],
        ]);

        $user->update(['login' => $validated['login'] ?? $user->login]);

        if ($user->details) {
            $user->details->update([
                'nom' => $validated['nom'] ?? $user->details->nom,
                'prenoms' => $validated['prenoms'] ?? $user->details->prenoms,
                'fonction' => $validated['fonction'] ?? $user->details->fonction,
                'mobile_1' => $validated['mobile_1'] ?? $user->details->mobile_1,
                'mobile_2' => $validated['mobile_2'] ?? $user->details->mobile_2,
                'photo' => $validated['photo'] ?? $user->details->photo,
                'ville' => $validated['ville'] ?? $user->details->ville,
                'pays' => $validated['pays'] ?? $user->details->pays,
                'updated_by' => $user->details?->uuid_user_details,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Profil mis à jour.',
            'data' => new UserResource($user->fresh()->load('details')),
        ]);
    }
}