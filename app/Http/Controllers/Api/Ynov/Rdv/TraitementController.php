<?php

namespace App\Http\Controllers\Api\Ynov\Rdv;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Ynov\Rdv\Traitement\AnnulerRdvRequest;
use App\Http\Requests\Api\Ynov\Rdv\Traitement\ExpirerRdvRequest;
use App\Http\Requests\Api\Ynov\Rdv\Traitement\ObservationRequest;
use App\Http\Requests\Api\Ynov\Rdv\Traitement\RejeterRdvRequest;
use App\Http\Requests\Api\Ynov\Rdv\Traitement\ReporterRdvRequest;
use App\Http\Requests\Api\Ynov\Rdv\Traitement\TraiterRdvRequest;
use App\Models\Api\Ynov\Rdv;
use App\Services\Api\Ynov\Rdv\TraitementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TraitementController extends Controller
{
    public function __construct(
        private TraitementService $traitementService,
    ) {}

    /**
     * Transmettre/Assigner un RDV à un gestionnaire
     */
    // public function assignGestionnaire(AssignerGestionnaireRequest $request, string $uuid_rdvs): JsonResponse
    // {
    //     $rdv = Rdv::where('uuid_rdvs', $uuid_rdvs)->firstOrFail();

    //     $result = $this->traitementService->assignGestionnaire(
    //         $rdv,
    //         $request->gestionnaire_uuid,
    //         $request->user()->uuid_user
    //     );

    //     return response()->json([
    //         'success' => $result['success'],
    //         'message' => $result['message'],
    //         'code' => $result['code'],
    //         'data' => $result['data'] ?? null,
    //     ], $result['status'] ?? 200);
    // }

    /**
     * Traiter un RDV (effectuer le traitement)
     */
    public function traiter(TraiterRdvRequest $request, string $uuid_rdvs): JsonResponse
    {
        $rdv = Rdv::where('uuid_rdvs', $uuid_rdvs)->firstOrFail();

        $result = $this->traitementService->traiter(
            $rdv,
            $request->validated(),
            $request->user()->uuid_user
        );

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'code' => $result['code'],
            'data' => $result['data'] ?? null,
        ], $result['status'] ?? 200);
    }

    /**
     * Reporter un RDV
     */
    public function reporter(ReporterRdvRequest $request, string $uuid_rdvs): JsonResponse
    {
        $rdv = Rdv::where('uuid_rdvs', $uuid_rdvs)->firstOrFail();

        $result = $this->traitementService->reporter(
            $rdv,
            $request->validated(),
            $request->user()->uuid_user
        );

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'code' => $result['code'],
            'data' => $result['data'] ?? null,
        ], $result['status'] ?? 200);
    }

    /**
     * Rejeter un RDV
     */
    public function rejeter(RejeterRdvRequest $request, string $uuid_rdvs): JsonResponse
    {
        $rdv = Rdv::where('uuid_rdvs', $uuid_rdvs)->firstOrFail();

        $result = $this->traitementService->rejeter(
            $rdv,
            $request->validated(),
            $request->user()->uuid_user
        );

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'code' => $result['code'],
            'data' => $result['data'] ?? null,
        ], $result['status'] ?? 200);
    }

    /**
     * Annuler un RDV (admin)
     */
    public function annuler(AnnulerRdvRequest $request, string $uuid_rdvs): JsonResponse
    {
        $rdv = Rdv::where('uuid_rdvs', $uuid_rdvs)->firstOrFail();

        $result = $this->traitementService->annuler(
            $rdv,
            $request->validated(),
            $request->user()->uuid_user
        );

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'code' => $result['code'],
            'data' => $result['data'] ?? null,
        ], $result['status'] ?? 200);
    }

    /**
     * Ajouter une observation/commentaire
     */
    // public function addObservation(ObservationRequest $request, string $uuid_rdvs): JsonResponse
    // {
    //     $rdv = Rdv::where('uuid_rdvs', $uuid_rdvs)->firstOrFail();

    //     $result = $this->traitementService->addObservation(
    //         $rdv,
    //         $request->validated(),
    //         $request->user()->uuid_user
    //     );

    //     return response()->json([
    //         'success' => $result['success'],
    //         'message' => $result['message'],
    //         'code' => $result['code'],
    //         'data' => $result['data'] ?? null,
    //     ], $result['status'] ?? 200);
    // }

    /**
     * Historique des traitements d'un RDV
     */
    public function historique(Request $request, string $uuid_rdvs): JsonResponse
    {
        $rdv = Rdv::where('uuid_rdvs', $uuid_rdvs)->firstOrFail();

        $historique = $this->traitementService->getHistorique($rdv);

        return response()->json([
            'success' => true,
            'message' => 'Historique des traitements récupéré avec succès.',
            'code' => 'HISTORIQUE_RDV',
            'data' => $historique,
        ]);
    }

    /**
     * Marquer comme expiré
     */
    public function expirer(ExpirerRdvRequest $request, string $uuid_rdvs): JsonResponse
    {
        $rdv = Rdv::where('uuid_rdvs', $uuid_rdvs)->firstOrFail();

        $result = $this->traitementService->expirer(
            $rdv,
            $request->validated(),
            $request->user()->uuid_user
        );

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'code' => $result['code'],
            'data' => $result['data'] ?? null,
        ], $result['status'] ?? 200);
    }

}
