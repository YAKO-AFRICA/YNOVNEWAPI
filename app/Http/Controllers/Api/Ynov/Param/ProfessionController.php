<?php

namespace App\Http\Controllers\Api\Ynov\Param;

use App\Http\Controllers\Controller;
use App\Models\Api\Ynov\parameter\Profession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;


class ProfessionController extends Controller
{
    public function indexProfession(Request $request): JsonResponse
    {
        $query = Profession::query();
        $search = trim((string) $request->input('search', ''));

        if ($search !== '') {
            $query->where(function ($query) use ($search) {
                $query->where('code_profession', 'LIKE', "%{$search}%")
                    ->orWhere('MonLibelle', 'LIKE', "%{$search}%");
            });
        }

        if ($request->has('code_profession')) {
            $query->where('code_profession', $request->input('code_profession'));
        }

        $perPage = min(max($request->integer('per_page', 20), 1), 100);
        $professions = $query->orderBy('MonLibelle')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Liste des professions récupérée.',
            'code' => 'PROFESSIONS_LISTED',
            'data' => $professions,
        ]);
    }

    public function storeProfession(Request $request): JsonResponse
    {
        $validated = $request->validate([
            
            'code_profession' => ['required', 'string', 'max:50', Rule::unique('professions', 'code_profession')],
            'MonLibelle' => ['required', 'string', 'max:255'],
        ]);

        $validated['uuid_profession'] = (string) Str::uuid();
 
        $profession = Profession::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Profession créée avec succès.',
            'code' => 'PROFESSION_CREATED',
            'data' => $profession,
        ], 201);
    }

    public function updateProfession(Request $request, string $uuid_profession): JsonResponse
    {
        $profession = Profession::where('uuid_profession', $uuid_profession)->firstOrFail();

        $validated = $request->validate([
            'code_profession' => [
                'sometimes', 'required', 'string', 'max:50',
                Rule::unique('professions', 'code_profession')->ignore($profession->uuid_profession, 'uuid_profession'),
            ],
            'MonLibelle' => ['sometimes', 'required', 'string', 'max:255'],
        ]);

        $profession->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Ville mise à jour avec succès.',
            'code' => 'PROFESSION_UPDATED',
            'data' => $profession->fresh(),
        ]);
    }

    public function destroy(string $uuid_profession): JsonResponse
    {
        $profession = Profession::where('uuid_profession', $uuid_profession)->firstOrFail();
        $profession->delete();

        return response()->json([
            'success' => true,
            'message' => 'Profession supprimée avec succès.',
            'code' => 'PROFESSION_DELETED',
        ]);
    }

    
}
