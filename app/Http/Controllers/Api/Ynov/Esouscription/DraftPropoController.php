<?php

namespace App\Http\Controllers\Api\Ynov\Esouscription;

use App\Http\Controllers\Controller;
use App\Models\Api\Ynov\Esouscription\Contrat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DraftPropoController extends Controller
{
    public function __construct(private PropositionController $propositionController) {}

    public function createDraftPropo(Request $request)
    {
        $userCretead = $request->user()?->uuid_user ?? $request->created_by ?? 'ND';
        $userConseiller = $request->user()?->uuid_user ?? $request->conseiller_uuid ?? 'ND';

        $createDraft = Contrat::create([
            'uuid_contrat' => str()->uuid(),
            'id_contrat' => $this->generateNextIdContrat(),
            'conseiller_uuid' => $userConseiller,
            'code_produit' => $request->code_produit,
            'libelle_produit' => $request->libelle_produit,
            'formule_produit_code' => $request->formule_produit_code,
            'etape' => 0,
            'created_by' => $userCretead,
        ]);


        return response()->json([
            'message' => 'Draft proposal created successfully.',
            'data' => $createDraft,
        ], 201);
    }

    public function completeDraftPropo(Request $request, string $uuid)
    {
        return $this->propositionController->storeSouscription($request, $uuid);
    }

    protected function generateNextIdContrat(): int
    {
        return DB::transaction(function () {
            $lastId = Contrat::withTrashed()
                ->lockForUpdate()
                ->max('id_contrat');

            return ($lastId ?? 0) + 1;
        });
    }
}
