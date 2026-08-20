<?php
namespace App\Http\Controllers\Api\Ynov;

use App\Http\Controllers\Controller;
use App\Models\Api\Ynov\parameter\ActivityLog;
use App\Models\Api\Ynov\parameter\IpRestriction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class IpRestrictionController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => IpRestriction::all()]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ip_address' => ['required', 'string', 'max:45'],
            'type' => ['required', 'in:whitelist,blacklist'],
            'reason' => ['nullable', 'string', 'max:255'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $restriction = IpRestriction::create([
            'uuid_restriction' => (string) Str::uuid(),
            'ip_address' => $data['ip_address'],
            'type' => $data['type'],
            'reason' => $data['reason'],
            'expires_at' => $data['expires_at'] ?? null,
            'created_by' => $request->user()->details?->uuid_user_details,
        ]);

        // Log l'action
        ActivityLog::log([
            'user_uuid' => $request->user()->uuid_user,
            'action' => 'create',
            'action_type' => 'crud',
            'module' => 'ip-restrictions',
            'description' => "Création de la restriction IP : {$restriction->ip_address}",
            'resource_type' => 'ip-restriction',
            'resource_id' => $restriction->uuid_restriction,
            'new_values' => $restriction->toArray(),
            'level' => 'info',
        ]);

        return response()->json(['success' => true, 'data' => $restriction], 201);
    }

    public function destroy($uuid_restriction): JsonResponse
    {
        $ipRestriction = IpRestriction::where('uuid_restriction', $uuid_restriction)->firstOrFail();
        $ipRestriction->delete();
        ActivityLog::log([
            'user_uuid' => request()->user()->uuid_user,
            'action' => 'delete',
            'action_type' => 'crud',
            'module' => 'ip-restrictions',
            'description' => "Suppression de la restriction IP : {$ipRestriction->ip_address}",
            'resource_type' => 'ip-restriction',
            'resource_id' => $ipRestriction->uuid_restriction,
            'level' => 'info',
        ]);
        return response()->json(['success' => true, 'message' => 'Restriction supprimée.']);
    }
}