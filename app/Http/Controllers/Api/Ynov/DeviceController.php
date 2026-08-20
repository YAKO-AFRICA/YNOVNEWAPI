<?php
namespace App\Http\Controllers\Api\Ynov;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Ynov\DeviceResource;
use App\Mail\Api\Ynov\DeviceRevokedMail;
use App\Services\Api\Ynov\Auth\DeviceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class DeviceController extends Controller
{
    public function __construct(private DeviceService $deviceService) {}

    public function index(Request $request): JsonResponse
    {
        $devices = $request->user()->devices()->orderByDesc('last_used_at')->get();
        return response()->json([
            'success' => true,
            'data' => DeviceResource::collection($devices),
        ]);
    }

    public function trust(Request $request, string $uuidDevice): JsonResponse
    {
        $device = $request->user()->devices()->where('uuid_device', $uuidDevice)->firstOrFail();
        
        $device->update(['is_trusted' => true, 'trusted_at' => now()]);
        return response()->json(['success' => true, 'message' => 'Appareil approuvé.']);
    }
    public function revoke(Request $request, string $uuidDevice): JsonResponse
    {
        $user = $request->user();
        $device = $user->devices()->where('uuid_device', $uuidDevice)->first();

        if (!$device) {
            return response()->json(['success' => false, 'message' => 'Appareil non trouvé.'], 404);
        }

        // Copie avant suppression pour le mail (le modèle sera supprimé après)
        $deviceSnapshot = $device->replicate();

        $success = $this->deviceService->revoke($user, $uuidDevice);

        if ($success && $user->email) {
            Mail::to($user->email)->queue(new DeviceRevokedMail($user->fresh('details'), $deviceSnapshot));
        }

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Appareil révoqué.' : 'Appareil non trouvé.',
        ], $success ? 200 : 404);
    }
}