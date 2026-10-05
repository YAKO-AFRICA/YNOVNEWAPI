<?php

namespace App\Http\Controllers\Api\Ynov\Param;

use App\Http\Controllers\Controller;
use App\Models\Api\Ynov\parameter\Ville;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class VilleController extends Controller
{
    public function indexVilles(Request $request): JsonResponse
    {
        $query = Ville::query();
        $search = trim((string) $request->input('search', ''));

        if ($search !== '') {
            $query->where(function ($query) use ($search) {
                $query->where('codeVille', 'LIKE', "%{$search}%")
                    ->orWhere('MonLibelle', 'LIKE', "%{$search}%")
                    ->orWhere('MonPays', 'LIKE', "%{$search}%");
            });
        }

        if ($request->has('code_pays')) {
            $query->where('MonPays', $request->input('code_pays'));
        }

        $perPage = min(max($request->integer('per_page', 20), 1), 100);
        $villes = $query->orderBy('MonLibelle')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Liste des villes récupérée.',
            'code' => 'VILLES_LISTED',
            'data' => $villes,
        ]);
    }

    public function storeVille(Request $request): JsonResponse
    {
        $validated = $request->validate([
            
            'codeVille' => ['required', 'string', 'max:50', Rule::unique('villes', 'codeVille')],
            'MonLibelle' => ['required', 'string', 'max:255'],
            'MonPays' => ['required', 'string', 'max:100'],
        ]);

        $validated['uuid_ville'] = (string) Str::uuid();
 
        $ville = Ville::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Ville créée avec succès.',
            'code' => 'VILLE_CREATED',
            'data' => $ville,
        ], 201);
    }

    public function show(string $uuid_ville): JsonResponse
    {
        $ville = Ville::where('uuid_ville', $uuid_ville)->firstOrFail();

        return response()->json([
            'success' => true,
            'message' => 'Détails de la ville.',
            'code' => 'VILLE_FOUND',
            'data' => $ville,
        ]);
    }

    public function update(Request $request, string $uuid_ville): JsonResponse
    {
        $ville = Ville::where('uuid_ville', $uuid_ville)->firstOrFail();

        $validated = $request->validate([
            'codeVille' => [
                'sometimes', 'required', 'string', 'max:50',
                Rule::unique('villes', 'codeVille')->ignore($ville->uuid_ville, 'uuid_ville'),
            ],
            'MonLibelle' => ['sometimes', 'required', 'string', 'max:255'],
            'MonPays' => ['sometimes', 'required', 'string', 'max:100'],
        ]);

        $ville->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Ville mise à jour avec succès.',
            'code' => 'VILLE_UPDATED',
            'data' => $ville->fresh(),
        ]);
    }

    public function destroy(string $uuid_ville): JsonResponse
    {
        $ville = Ville::where('uuid_ville', $uuid_ville)->firstOrFail();
        $ville->delete();

        return response()->json([
            'success' => true,
            'message' => 'Ville supprimée avec succès.',
            'code' => 'VILLE_DELETED',
        ]);
    }

    
}
