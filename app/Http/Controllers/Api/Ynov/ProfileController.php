<?php
namespace App\Http\Controllers\Api\Ynov;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Ynov\UpdateProfileRequest;
use App\Http\Resources\Api\Ynov\UserResource;
use App\Services\Api\Ynov\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    public function __construct(
        private ProfileService $profileService
    ) {}

    /**
     * Afficher le profil de l'utilisateur connecté
     */
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
            'message' => 'Profil récupéré avec succès.',
            'code' => 'PROFILE_FOUND',
            'data' => new UserResource($user),
        ]);
    }

    /**
     * Mettre à jour le profil de l'utilisateur connecté
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        
        $updatedUser = $this->profileService->updateProfile(
            $user,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Profil mis à jour avec succès.',
            'code' => 'PROFILE_UPDATED',
            'data' => new UserResource($updatedUser->load([
                'role.permissions.group',
                'details',
                'partner',
                'reseau',
                'agences',
                'groupNotifs',
                'userContrats'
            ])),
        ]);
    }

    /**
     * Supprimer la photo de profil
     */
    public function deletePhoto(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if ($user->details && $user->details->photo_path) {
            $this->profileService->deletePhoto($user->details->photo_path);
            
            $user->details->update([
                'photo_path' => null,
                'photo' => null,
                'updated_by' => $user->uuid_user,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Photo de profil supprimée avec succès.',
                'code' => 'PHOTO_DELETED',
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'Aucune photo de profil à supprimer.',
            'code' => 'NO_PHOTO_FOUND',
        ], 404);
    }
}
