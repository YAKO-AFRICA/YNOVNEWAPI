<?php

namespace App\Http\Controllers\Api\Ynov\Param;

use App\Http\Controllers\Controller;
use App\Models\Api\Ynov\parameter\ActivityLog;
use App\Models\Api\Ynov\parameter\Branche;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BrancheController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Branche::query();
        $search = trim((string) $request->input('search', ''));

        if ($search !== '') {
            $query->where(function ($query) use ($search) {
                $query->where('CodeCanalDistribution', 'LIKE', "%{$search}%")
                    ->orWhere('MonLibelle', 'LIKE', "%{$search}%");
            });
        }

        if ($request->has('etat')) {
            $query->where('etat', $request->input('etat'));
        }

        $perPage = min(max($request->integer('per_page', 20), 1), 100);
        $branches = $query->orderBy('MonLibelle')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Liste des branches récupérée.',
            'code' => 'BRANCHES_LISTED',
            'data' => $branches,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'IdCanalDistribution' => ['required', 'integer', 'min:0'],
            'CodeCanalDistribution' => ['required', 'string', 'max:100', Rule::unique('branches', 'CodeCanalDistribution')],
            'MonLibelle' => ['required', 'string', 'max:255'],
            'etat' => ['nullable', 'string', 'max:50'],
        ]);

        $branche = Branche::create([
            ...$validated,
            'uuid_branche' => (string) Str::uuid(),
        ]);

        ActivityLog::log([
            'user_uuid' => $request->user()->uuid_user,
            'action' => 'create',
            'action_type' => 'crud',
            'module' => 'branches',
            'description' => "Création de la branche : {$branche->MonLibelle}",
            'resource_type' => 'branche',
            'resource_id' => $branche->uuid_branche,
            'new_values' => $branche->toArray(),
            'level' => 'info',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Branche créée avec succès.',
            'code' => 'BRANCHE_CREATED',
            'data' => $branche,
        ], 201);
    }

    public function show(string $uuid_branche): JsonResponse
    {
        $branche = Branche::where('uuid_branche', $uuid_branche)->firstOrFail();

        return response()->json([
            'success' => true,
            'message' => 'Détails de la branche.',
            'code' => 'BRANCHE_FOUND',
            'data' => $branche,
        ]);
    }

    public function update(Request $request, string $uuid_branche): JsonResponse
    {
        $branche = Branche::where('uuid_branche', $uuid_branche)->firstOrFail();
        $oldValues = $branche->toArray();

        $validated = $request->validate([
            'IdCanalDistribution' => ['sometimes', 'required', 'integer', 'min:0'],
            'CodeCanalDistribution' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('branches', 'CodeCanalDistribution')->ignore($branche->uuid_branche, 'uuid_branche'),
            ],
            'MonLibelle' => ['sometimes', 'required', 'string', 'max:255'],
            'etat' => ['sometimes', 'nullable', 'string', 'max:50'],
        ]);

        $branche->update($validated);

        ActivityLog::log([
            'user_uuid' => $request->user()->uuid_user,
            'action' => 'update',
            'action_type' => 'crud',
            'module' => 'branches',
            'description' => "Mise à jour de la branche : {$branche->MonLibelle}",
            'resource_type' => 'branche',
            'resource_id' => $branche->uuid_branche,
            'old_values' => $oldValues,
            'new_values' => $branche->fresh()->toArray(),
            'level' => 'info',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Branche mise à jour avec succès.',
            'code' => 'BRANCHE_UPDATED',
            'data' => $branche->fresh(),
        ]);
    }

    public function destroy(Request $request, string $uuid_branche): JsonResponse
    {
        $branche = Branche::where('uuid_branche', $uuid_branche)->firstOrFail();
        $oldValues = $branche->toArray();
        $branche->delete();

        ActivityLog::log([
            'user_uuid' => $request->user()->uuid_user,
            'action' => 'delete',
            'action_type' => 'crud',
            'module' => 'branches',
            'description' => "Suppression de la branche : {$branche->MonLibelle}",
            'resource_type' => 'branche',
            'resource_id' => $branche->uuid_branche,
            'old_values' => $oldValues,
            'level' => 'warning',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Branche supprimée avec succès.',
            'code' => 'BRANCHE_DELETED',
        ]);
    }
}
