<?php

namespace App\Http\Controllers\Api\Ynov\Esouscription;

use App\Http\Controllers\Controller;
use App\Models\Api\Ynov\Esouscription\Contrat;
use App\Models\Api\Ynov\Esouscription\Document;
use App\Models\Api\Ynov\parameter\Partner;
use App\Services\Api\Ynov\ESouscription\ContratService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Throwable;

class ValidationController extends Controller
{
    public function __construct(private ContratService $contratService)
    {
    }

    public function dashboard()
    {
        $now = now();
        $periods = [
            'jour' => [$now->copy()->startOfDay(), $now->copy()->addDay()->startOfDay()],
            'semaine' => [$now->copy()->startOfWeek(), $now->copy()->startOfWeek()->addWeek()],
            'mois' => [$now->copy()->startOfMonth(), $now->copy()->startOfMonth()->addMonth()],
        ];

        $accepted = $this->countContractsByDecisionPeriod(3, 'acceptance_date', $periods);
        $rejected = $this->countContractsByDecisionPeriod(4, 'date_rejet', $periods);
        $acceptanceRates = [];
        $rejectionRates = [];

        foreach ($periods as $period => $_bounds) {
            $processed = $accepted[$period] + $rejected[$period];
            $acceptanceRates[$period] = $processed === 0
                ? 0
                : round(($accepted[$period] / $processed) * 100, 2);
            $rejectionRates[$period] = $processed === 0
                ? 0
                : round(($rejected[$period] / $processed) * 100, 2);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tableau de bord de validation récupéré avec succès',
            'code' => 200,
            'data' => [
                'contrats_en_attente_validation' => Contrat::query()->where('etape', 2)->count(),
                'contrats_acceptes' => $accepted,
                'contrats_rejetes' => $rejected,
                'taux_acceptation' => $acceptanceRates,
                'taux_rejet' => $rejectionRates,
                'top_5_motifs_rejet' => $this->getTopRejectionReasons(),
                'top_agences_par_rejets' => $this->getTopRejectedAgencies(),
                'top_partenaires_par_rejets' => $this->getTopRejectedPartners(),
            ],
        ]);
    }

    /**
     * @param array<string, array{0: Carbon, 1: Carbon}> $periods
     * @return array<string, int>
     */
    private function countContractsByDecisionPeriod(int $etape, string $dateColumn, array $periods): array
    {
        $counts = [];

        foreach ($periods as $period => [$start, $end]) {
            $counts[$period] = Contrat::query()
                ->where('etape', $etape)
                ->whereNotNull($dateColumn)
                ->where($dateColumn, '>=', $start)
                ->where($dateColumn, '<', $end)
                ->count();
        }

        return $counts;
    }

    /**
     * @return array<int, array{motif: string, total: int}>
     */
    private function getTopRejectionReasons(): array
    {
        $counts = [];

        foreach (
            Contrat::query()
                ->where('etape', 4)
                ->whereNotNull('motif_rejet')
                ->select('motif_rejet')
                ->cursor() as $contrat
        ) {
            if (!is_array($contrat->motif_rejet)) {
                continue;
            }

            $motifs = array_filter(
                $contrat->motif_rejet,
                static fn ($motif): bool => is_string($motif) && trim($motif) !== ''
            );

            foreach (array_unique($motifs) as $motif) {
                $motif = trim($motif);
                $counts[$motif] = ($counts[$motif] ?? 0) + 1;
            }
        }

        $reasons = [];
        foreach ($counts as $motif => $total) {
            $reasons[] = ['motif' => $motif, 'total' => $total];
        }

        usort($reasons, static function (array $left, array $right): int {
            return ($right['total'] <=> $left['total'])
                ?: strcmp($left['motif'], $right['motif']);
        });

        return array_slice($reasons, 0, 5);
    }

    private function getTopRejectedAgencies(): EloquentCollection
    {
        return Contrat::query()
            ->leftJoin('agences as validation_agences', 'contrats.agence_uuid', '=', 'validation_agences.uuid_agence')
            ->where('contrats.etape', 4)
            ->whereNotNull('contrats.agence_uuid')
            ->selectRaw(
                'contrats.agence_uuid, validation_agences.libelle as agence, COUNT(contrats.uuid_contrat) as total_rejets'
            )
            ->groupBy('contrats.agence_uuid', 'validation_agences.libelle')
            ->orderByDesc('total_rejets')
            ->orderBy('agence')
            ->limit(5)
            ->get();
    }

    private function getTopRejectedPartners(): EloquentCollection
    {
        return Contrat::query()
            ->leftJoin('partners as validation_partners', 'contrats.partner_uuid', '=', 'validation_partners.uuid_partner')
            ->where('contrats.etape', 4)
            ->whereNotNull('contrats.partner_uuid')
            ->selectRaw(
                'contrats.partner_uuid, validation_partners.designation as partenaire, COUNT(contrats.uuid_contrat) as total_rejets'
            )
            ->groupBy('contrats.partner_uuid', 'validation_partners.designation')
            ->orderByDesc('total_rejets')
            ->orderBy('partenaire')
            ->limit(5)
            ->get();
    }

    public function getPartnerDistinct(Request $request)
    {
        // Compter les contrats par partenaire (étape = 2 et partner_uuid non null)
        $contratsParPartenaire = Contrat::query()
            ->where('etape', 2)
            ->whereNotNull('partner_uuid')
            ->selectRaw('partner_uuid, COUNT(*) as total')
            ->groupBy('partner_uuid')
            ->pluck('total', 'partner_uuid');
            
        $partners = Partner::query()
            ->whereIn('uuid_partner', $contratsParPartenaire->keys())
            ->orderBy('designation')
            ->get()
            ->map(function ($partner) use ($contratsParPartenaire) {
                $document = Document::withoutTrashed()->where('reference_uuid', $partner->uuid_partner)->first();
                $url = ($document) ? env('APP_URL') .'preview/doc/' . $document->nom_fichier : null;
                $partner->contrats = $contratsParPartenaire->get($partner->uuid_partner, 0);
                $partner->logo_url = $url;
                return $partner;
            });

        return response()->json([
            'success' => true,
            'message' => 'Partenaires récupérés avec succès',
            'code' => 200,
            'data' => $partners,
        ]);
    }

    public function getContratsByPartner(Request $request, string $partner_uuid)
    {
        $partner = Partner::query()
            ->where('uuid_partner', $partner_uuid)
            ->first();

        if (!$partner) {
            return response()->json([
                'success' => false,
                'message' => 'Partenaire introuvable',
                'code' => 404,
            ], 404);
        }

        $validated = $request->validate([
            'etape' => ['sometimes', 'nullable', 'integer'],
        ]);

        $query = Contrat::query()
            ->where('partner_uuid', $partner->uuid_partner)
            ->orderByDesc('created_at');

        if (isset($validated['etape'])) {
            $query->where('etape', $validated['etape']);
        }

        $contrats = $query->get();

        return response()->json([
            'success' => true,
            'message' => 'Contrats récupérés avec succès',
            'code' => 200,
            'total' => $contrats->count(),
            'data' => $contrats,
        ]);
    }

    public function acceptContrat(Request $request, string $uuid)
    {
        $contrat = $this->contratService->findByUuid($uuid);

        if (!$contrat) {
            return response()->json([
                'success' => false,
                'message' => 'Contrat introuvable',
                'code' => 404,
            ], 404);
        }

        try {
            $contrat = $this->contratService->accept(
                $contrat,
                (string) $request->user()->uuid_user
            );

            return response()->json([
                'success' => true,
                'message' => 'Contrat accepté avec succès',
                'code' => 200,
                'data' => $contrat,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l’acceptation du contrat',
                'code' => 500,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function rejectContrat(Request $request, string $uuid)
    {
        $contrat = $this->contratService->findByUuid($uuid);

        if (!$contrat) {
            return response()->json([
                'success' => false,
                'message' => 'Contrat introuvable',
                'code' => 404,
            ], 404);
        }

        $validated = $request->validate([
            'motif_rejet' => ['required', 'array', 'min:1'],
            'motif_rejet.*' => ['required', 'string', 'distinct'],
        ]);

        try {
            $contrat = $this->contratService->reject(
                $contrat,
                $validated['motif_rejet'],
                (string) $request->user()->uuid_user
            );

            return response()->json([
                'success' => true,
                'message' => 'Contrat rejeté avec succès',
                'code' => 200,
                'data' => $contrat,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du rejet du contrat',
                'code' => 500,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
