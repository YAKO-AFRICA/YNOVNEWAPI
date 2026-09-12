<?php

namespace App\Services\Api\Ynov\Rdv;

use App\Models\Api\Ynov\BordereauRdv;
use App\Models\Api\Ynov\DetailBordereauRdv;
use App\Models\Api\Ynov\Rdv;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class BordereauRdvService
{

    /**
     * Liste les lots avec filtres et pagination.
     */
    public function listLots(array $filters = [], int $perPage = 20)
    {
        $query = BordereauRdv::query()
            ->withCount('details')
            ->with([
                'details.rdv.client.details',
                'details.rdv.motif',
                'details.rdv.gestionnaire.details',
            ]);

        $this->applyLotFilters($query, $filters);

        $sortBy = in_array($filters['sort_by'] ?? null, ['reference', 'periode_1', 'periode_2', 'status', 'created_at'], true)
            ? $filters['sort_by']
            : 'periode_1';
        $sortOrderValue = strtolower((string) ($filters['sort_order'] ?? 'asc'));
        $sortOrder = in_array($sortOrderValue, ['asc', 'desc'], true)
            ? $sortOrderValue
            : 'asc';

        return $query->orderBy($sortBy, $sortOrder)
            ->paginate((int) ($filters['per_page'] ?? $perPage));
    }

    /**
     * Liste les lignes de bordereau.
     * Si bordereau_rdv_uuid est fourni, on r�cup�re le d�tail complet du lot.
     */
    // public function listDetails(array $filters = [], int $perPage = 20)
    // {
    //     $query = DetailBordereauRdv::query()
    //         ->with([
    //             'bordereauRdv',
    //             'rdv.client.details',
    //             'rdv.motif',
    //             'rdv.gestionnaire.details',
    //             'rdv.agenceSouhaitee',
    //             'rdv.agenceEffective',
    //         ]);

    //     $this->applyDetailFilters($query, $filters);

    //     $sortBy = in_array($filters['sort_by'] ?? null, ['status', 'rdvs.date_rdv_effective', 'rdvs.date_rdv_souhaitee', 'created_at'], true)
    //         ? $filters['sort_by']
    //         : 'rdvs.date_rdv_effective' ?? 'rdvs.date_rdv_souhaitee' ?? 'created_at';
    //     $sortOrderValue = strtolower((string) ($filters['sort_order'] ?? 'asc'));
    //     $sortOrder = in_array($sortOrderValue, ['asc', 'desc'], true)
    //         ? $sortOrderValue
    //         : 'asc';

    //     return $query->orderBy($sortBy, $sortOrder)
    //         ->paginate((int) ($filters['per_page'] ?? $perPage));
    // }

    public function listDetails(string $bordereauRdvUuid, array $filters = [], int $perPage = 20)
    {
        $query = DetailBordereauRdv::query()
            ->where('bordereau_rdv_uuid', $bordereauRdvUuid)
            ->with([
                'bordereauRdv',
                'rdv.client.details',
                'rdv.motif',
                'rdv.gestionnaire.details',
                'rdv.agenceSouhaitee',
                'rdv.agenceEffective',
            ]);

        $this->applyDetailFilters($query, $filters);

        $sortBy = in_array($filters['sort_by'] ?? null, ['status', 'rdv.date_rdv_effective', 'rdv.date_rdv_souhaiter', 'created_at'], true)
            ? $filters['sort_by']
            : 'created_at';
        $sortOrderValue = strtolower((string) ($filters['sort_order'] ?? 'asc'));
        $sortOrder = in_array($sortOrderValue, ['asc', 'desc'], true)
            ? $sortOrderValue
            : 'asc';

        if (str_starts_with($sortBy, 'rdv.')) {
            $column = substr($sortBy, 4); // date_rdv_effective ou date_rdv_souhaiter

            $query->select('detail_bordereau_rdvs.*')
                ->join('rdvs', 'rdvs.uuid_rdvs', '=', 'detail_bordereau_rdvs.rdv_uuid')
                ->orderBy("rdvs.{$column}", $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        return $query->paginate((int) ($filters['per_page'] ?? $perPage));
    }

    private function applyLotFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhereDate('periode_1', $search)
                    ->orWhereDate('periode_2', $search)
                    ->orWhereHas('details.rdv', function ($sub) use ($search) {
                        $sub->where('code', 'like', "%{$search}%")
                            ->orWhereHas('client.details', function ($clientQuery) use ($search) {
                                $clientQuery->where('nom', 'like', "%{$search}%")
                                    ->orWhere('prenoms', 'like', "%{$search}%");
                            });
                    });
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['reference'])) {
            $query->where('reference', 'like', "%{$filters['reference']}%");
        }

        if (!empty($filters['date_debut'])) {
            $query->whereDate('periode_1', $filters['date_debut']);
        }

        if (!empty($filters['date_fin'])) {
            $query->whereDate('periode_2', $filters['date_fin']);
        }

        if (!empty($filters['gestionnaire_uuid'])) {
            $query->whereHas('details.rdv', function ($q) use ($filters) {
                $q->where('gestionnaire_uuid', $filters['gestionnaire_uuid']);
            });
        }
    }

    private function applyDetailFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->whereHas('bordereauRdv', function ($sub) use ($search) {
                    $sub->where('reference', 'like', "%{$search}%");
                })->orWhereHas('rdv', function ($sub) use ($search) {
                    $sub->where('code', 'like', "%{$search}%")
                        ->orWhereHas('client.details', function ($clientQuery) use ($search) {
                            $clientQuery->where('nom', 'like', "%{$search}%")
                                ->orWhere('prenoms', 'like', "%{$search}%");
                        });
                });
            });
        }

        if (!empty($filters['status'])) {
            $query->whereHas('rdv', function ($q) use ($filters) {
                $q->where('status', $filters['status']);
            });
        }

        if (!empty($filters['bordereau_rdv_uuid'])) {
            $query->where('bordereau_rdv_uuid', $filters['bordereau_rdv_uuid']);
        }

        if (!empty($filters['rdv_uuid'])) {
            $query->where('rdv_uuid', $filters['rdv_uuid']);
        }

        if (!empty($filters['date'])) {
            $query->whereHas('rdv', function ($q) use ($filters) {
                $q->whereDate('date_rdv_effective', $filters['date']);
                if (!isset($filters['status'])) {
                    $q->whereIn('status', ['transmis']);
                }
            });
        }

        if (!empty($filters['date_debut'])) {
            $query->whereHas('rdv', function ($q) use ($filters) {
                $q->whereDate('date_rdv_effective', '>=', $filters['date_debut']);
            });
        }

        if (!empty($filters['date_fin'])) {
            $query->whereHas('rdv', function ($q) use ($filters) {
                $q->whereDate('date_rdv_souhaiter', '<=', $filters['date_fin']);
            });
        }

        if (!empty($filters['agence_uuid'])) {
            $query->whereHas('rdv', function ($q) use ($filters) {
                $q->where('agence_effective_uuid', $filters['agence_uuid']);
            });
        }

        if (!empty($filters['gestionnaire_uuid'])) {
            $query->whereHas('rdv', function ($q) use ($filters) {
                $q->where('gestionnaire_uuid', $filters['gestionnaire_uuid']);
            });
        }

        if (!empty($filters['motif_uuid'])) {
            $query->whereHas('rdv', function ($q) use ($filters) {
                $q->where('motif_rdv', $filters['motif_uuid']);
            });
        }
    }



    /**
     * Importe le détail d'un bordereau à partir d'un fichier Excel.
     * Le fichier doit contenir une colonne "Numero du rendez-vous" (ou "Numero")
     * qui correspond au code du RDV.
     */
    public function importDetailExcel(UploadedFile $file, string $reference, ?string $observation = null): array
    {
        $lot = BordereauRdv::where('reference', trim($reference))->first();

        if (!$lot) {
            return [
                'success' => false,
                'message' => 'Référence de bordereau introuvable.',
                'code' => 'BORDEAU_REFERENCE_NOT_FOUND',
                'imported' => 0,
                'errors' => [],
            ];
        }

        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();

        // IMPORTANT : array_values() pour repasser sur des indices 0-indexés
        // continus. toArray(null, true, true, true) renvoie sinon un tableau
        // indexé par les vrais numéros de ligne Excel (1, 2, 3...), ce qui
        // cassait la boucle for ($i = $headerRowIndex + 1; $i < count($rows); $i++)
        // dès que la ligne d'en-tête n'était pas la ligne 0.
        $rows = array_values($sheet->toArray(null, true, true, true));

        if (empty($rows)) {
            return [
                'success' => false,
                'message' => 'Le fichier Excel est vide.',
                'code' => 'BORDEAU_FILE_EMPTY',
                'imported' => 0,
                'errors' => [],
            ];
        }

        // Log les premières lignes pour debug
        $sampleRows = array_slice($rows, 0, min(5, count($rows)));

        // Recherche de l'index de la ligne d'en-tête
        $headerRowIndex = $this->findHeaderRowIndex($rows);

        if ($headerRowIndex === null) {
            return [
                'success' => false,
                'message' => 'Aucune ligne d\'en-tête détectée. Vérifiez le format du fichier.',
                'code' => 'BORDEAU_HEADER_NOT_FOUND',
                'imported' => 0,
                'errors' => [],
            ];
        }

        // Récupération de la ligne d'en-tête et des index des colonnes
        $headerRow = $rows[$headerRowIndex];
        $columnIndexes = $this->resolveDetailColumnIndexes($headerRow);

        $numeroIndex = $columnIndexes['numero'] ?? null;
        if ($numeroIndex === null) {
            return [
                'success' => false,
                'message' => 'Colonne "Numero du rendez-vous" introuvable dans le fichier.',
                'code' => 'BORDEAU_NUMERO_COLUMN_MISSING',
                'imported' => 0,
                'errors' => [],
            ];
        }

        $imported = 0;
        $errors = [];
        $totalRows = count($rows);
        $processedRows = 0;
        $emptyRows = 0;
        $emptyCodeRows = 0;

        // Traitement des lignes de données du fichier
        for ($i = $headerRowIndex + 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            $processedRows++;

            if ($this->isEmptyRow($row)) {
                $emptyRows++;
                continue;
            }

            $numeroRdv = trim((string) ($row[$numeroIndex] ?? ''));
            if ($numeroRdv === '') {
                $emptyCodeRows++;
                continue;
            }

            $rdv = Rdv::where('code', $numeroRdv)->first();
            if (!$rdv) {
                $errors[] = "RDV non trouvé pour le code: {$numeroRdv}";
                continue;
            }

            $detail = DetailBordereauRdv::where('rdv_uuid', $rdv->uuid_rdvs)
                ->where('bordereau_rdv_uuid', $lot->uuid_bordereau_rdv)
                ->first();

            if (!$detail) {
                $detail = new DetailBordereauRdv();
                $detail->uuid_detail_bordereau_rdv = (string) Str::uuid();
                $detail->bordereau_rdv_uuid = $lot->uuid_bordereau_rdv;
                $detail->rdv_uuid = $rdv->uuid_rdvs;
                $detail->status = 'en_attente';
            }

            $filled = $this->mapExcelRowToDetail($row, $columnIndexes, $lot, $rdv);

            $detail->fill($filled);
            $detail->save();

            $imported++;
        }

        $lot->update([
            'status' => $imported > 0 ? 'cloture' : $lot->status,
            'observation' => trim((string) ($observation ?? (
                $imported > 0
                ? 'Import du détail de bordereau terminé.'
                : 'Import échoué : aucune ligne valide trouvée.'
            ))),
            'updated_by' => $lot->created_by ?? null,
        ]);

        return [
            'success' => $imported > 0,
            'message' => $imported > 0
                ? 'Import du détail de bordereau terminé avec succès.'
                : 'Aucune ligne de RDV n\'a été importée.',
            'code' => $imported > 0 ? 'BORDEAU_DETAIL_IMPORTED' : 'BORDEAU_DETAIL_IMPORT_EMPTY',
            'imported' => $imported,
            'reference' => $lot->reference,
            'errors' => $errors,
            'debug' => [
                'total_rows' => $totalRows,
                'header_row_index' => $headerRowIndex,
                'processed_rows' => $processedRows,
                'empty_rows' => $emptyRows,
                'empty_code_rows' => $emptyCodeRows,
                'numero_column_index' => $numeroIndex,
                'column_indexes' => $columnIndexes,
                'sample_rows' => $sampleRows,
            ],
        ];
    }

    private function findHeaderRowIndex(array $rows): ?int
    {
        $foundIndexes = [];

        foreach ($rows as $index => $row) {
            foreach ($row as $cell) {
                $normalized = $this->normalizeHeader((string) $cell);
                if (str_contains($normalized, 'numerodurendezvous') || $normalized === 'numero' || str_contains($normalized, 'numerorendezvous')) {
                    $foundIndexes[] = $index;
                    break;
                }
            }
        }

        if (!empty($foundIndexes)) {
            return min($foundIndexes);
        }

        foreach ($rows as $index => $row) {
            $firstCell = reset($row);
            if ($firstCell && str_contains((string) $firstCell, ' ')) {
                return $index;
            }
        }

        return !empty($rows) ? 0 : null;
    }

    private function resolveDetailColumnIndexes(array $headerRow): array
    {
        $indexes = [];

        foreach ($headerRow as $index => $header) {
            $normalized = $this->normalizeHeader((string) $header);

            if ($normalized === 'numero' || str_contains($normalized, 'numerodurendezvous') || str_contains($normalized, 'numerorendezvous')) {
                $indexes['numero'] = $index;
                continue;
            }

            if (str_contains($normalized, 'datedeffet') || str_contains($normalized, 'dateeffet')) {
                $indexes['date_effet'] = $index;
                continue;
            }

            if (str_contains($normalized, 'dateecheance') || str_contains($normalized, 'datecheance')) {
                $indexes['date_echeance'] = $index;
                continue;
            }

            if (str_contains($normalized, 'dureeducontrat') || str_contains($normalized, 'dureecontrat')) {
                $indexes['duree_contrat'] = $index;
                continue;
            }

            if (str_contains($normalized, 'typesdoperations') || str_contains($normalized, 'typeoperation') || str_contains($normalized, 'operations')) {
                $indexes['type_operation'] = $index;
                continue;
            }

            if (str_contains($normalized, 'cumulrachatspartiels') || str_contains($normalized, 'rachatspartiels')) {
                $indexes['cumul_rachats_partiels'] = $index;
                continue;
            }

            if (str_contains($normalized, 'cumulavances') || str_contains($normalized, 'avances')) {
                $indexes['cumul_avances'] = $index;
                continue;
            }

            if (str_contains($normalized, 'provisionnette') || str_contains($normalized, 'provision')) {
                $indexes['provision_nette'] = $index;
                continue;
            }

            // IMPORTANT : tester les "valeur maximale" AVANT "valeur de rachat"
            // sinon "valeurrachat" matche en premier et écrase les deux colonnes suivantes.
            if (str_contains($normalized, 'valeurmaximaledurachatpartiel') || str_contains($normalized, 'maxrachat')) {
                $indexes['valeur_max_rachat'] = $index;
                continue;
            }

            if (str_contains($normalized, 'valeurmaximaledelavance') || str_contains($normalized, 'maxavance')) {
                $indexes['valeur_max_avance'] = $index;
                continue;
            }

            if (str_contains($normalized, 'valeurderachatducontrat') || str_contains($normalized, 'valeurrachat')) {
                $indexes['valeur_rachat'] = $index;
                continue;
            }

            if (str_contains($normalized, 'montanttransformation') || str_contains($normalized, 'transformation')) {
                $indexes['montant_transformation'] = $index;
                continue;
            }

            if (str_contains($normalized, 'garantiesurete') || str_contains($normalized, 'garantie')) {
                $indexes['garantie_surete'] = $index;
                continue;
            }

            if (str_contains($normalized, 'conservationducapital') || str_contains($normalized, 'conservation')) {
                $indexes['conservation_capital'] = $index;
                continue;
            }

            if ($normalized === 'ville') {
                $indexes['ville'] = $index;
                continue;
            }

            if (str_contains($normalized, 'observation') || str_contains($normalized, 'remarque')) {
                $indexes['observation'] = $index;
                continue;
            }
        }

        return $indexes;
    }

    // private function mapExcelRowToDetail(array $row, array $columnIndexes, BordereauRdv $lot, Rdv $rdv): array
    // {
    //     $data = [];

    //     if (isset($columnIndexes['date_effet'])) {
    //         $data['date_effet'] = $this->parseDateValue($row[$columnIndexes['date_effet']] ?? null);
    //     }

    //     if (isset($columnIndexes['date_echeance'])) {
    //         $data['date_echeance'] = $this->parseDateValue($row[$columnIndexes['date_echeance']] ?? null);
    //     }

    //     if (isset($columnIndexes['duree_contrat'])) {
    //         $data['duree_contrat'] = $this->cleanStringValue($row[$columnIndexes['duree_contrat']] ?? null);
    //     }

    //     if (isset($columnIndexes['type_operation'])) {
    //         $data['type_operation'] = $this->cleanStringValue($row[$columnIndexes['type_operation']] ?? null);
    //     }

    //     if (isset($columnIndexes['cumul_rachats_partiels'])) {
    //         $data['cumul_rachats_partiels'] = $this->parseNumericValue($row[$columnIndexes['cumul_rachats_partiels']] ?? null);
    //     }

    //     if (isset($columnIndexes['cumul_avances'])) {
    //         $data['cumul_avances'] = $this->parseNumericValue($row[$columnIndexes['cumul_avances']] ?? null);
    //     }

    //     if (isset($columnIndexes['provision_nette'])) {
    //         $data['provision_nette'] = $this->parseNumericValue($row[$columnIndexes['provision_nette']] ?? null);
    //     }

    //     if (isset($columnIndexes['valeur_rachat'])) {
    //         $data['valeur_rachat'] = $this->parseNumericValue($row[$columnIndexes['valeur_rachat']] ?? null);
    //     }

    //     if (isset($columnIndexes['valeur_max_rachat'])) {
    //         $data['valeur_max_rachat'] = $this->parseNumericValue($row[$columnIndexes['valeur_max_rachat']] ?? null);
    //     }

    //     if (isset($columnIndexes['valeur_max_avance'])) {
    //         $data['valeur_max_avance'] = $this->parseNumericValue($row[$columnIndexes['valeur_max_avance']] ?? null);
    //     }

    //     if (isset($columnIndexes['montant_transformation'])) {
    //         $data['montant_transformation'] = $this->parseNumericValue($row[$columnIndexes['montant_transformation']] ?? null);
    //     }

    //     if (isset($columnIndexes['garantie_surete'])) {
    //         $data['garantie_surete'] = $this->parseNumericValue($row[$columnIndexes['garantie_surete']] ?? null);
    //     }

    //     if (isset($columnIndexes['conservation_capital'])) {
    //         $data['conservation_capital'] = $this->parseNumericValue($row[$columnIndexes['conservation_capital']] ?? null);
    //     }

    //     if (isset($columnIndexes['observation'])) {
    //         $observation = $this->cleanStringValue($row[$columnIndexes['observation']] ?? null);
    //         if ($observation !== '') {
    //             $data['observation'] = $observation;
    //         }
    //     }

    //     $data['status'] = 'en_attente';
    //     $data['created_by'] = $rdv->created_by ?? $lot->created_by ?? null;

    //     return $data;
    // }

    private function mapExcelRowToDetail(array $row, array $columnIndexes, BordereauRdv $lot, Rdv $rdv): array
{
    $data = [];

    if (isset($columnIndexes['date_effet'])) {
        $data['date_effet'] = $this->parseDateValue($row[$columnIndexes['date_effet']] ?? null);
    }

    if (isset($columnIndexes['date_echeance'])) {
        $data['date_echeance'] = $this->parseDateValue($row[$columnIndexes['date_echeance']] ?? null);
    }

    if (isset($columnIndexes['duree_contrat'])) {
        $data['duree_contrat'] = $this->cleanStringValue($row[$columnIndexes['duree_contrat']] ?? null);
    }

    if (isset($columnIndexes['type_operation'])) {
        $data['type_operation'] = $this->cleanStringValue($row[$columnIndexes['type_operation']] ?? null);
    }

    // Champs numériques financiers : "-" ou vide => 0.0 (pas null, colonnes NOT NULL en base)
    if (isset($columnIndexes['cumul_rachats_partiels'])) {
        $data['cumul_rachats_partiels'] = $this->parseNumericValue($row[$columnIndexes['cumul_rachats_partiels']] ?? null) ?? 0.0;
    }

    if (isset($columnIndexes['cumul_avances'])) {
        $data['cumul_avances'] = $this->parseNumericValue($row[$columnIndexes['cumul_avances']] ?? null) ?? 0.0;
    }

    if (isset($columnIndexes['provision_nette'])) {
        $data['provision_nette'] = $this->parseNumericValue($row[$columnIndexes['provision_nette']] ?? null) ?? 0.0;
    }

    if (isset($columnIndexes['valeur_rachat'])) {
        $data['valeur_rachat'] = $this->parseNumericValue($row[$columnIndexes['valeur_rachat']] ?? null) ?? 0.0;
    }

    if (isset($columnIndexes['valeur_max_rachat'])) {
        $data['valeur_max_rachat'] = $this->parseNumericValue($row[$columnIndexes['valeur_max_rachat']] ?? null) ?? 0.0;
    }

    if (isset($columnIndexes['valeur_max_avance'])) {
        $data['valeur_max_avance'] = $this->parseNumericValue($row[$columnIndexes['valeur_max_avance']] ?? null) ?? 0.0;
    }

    if (isset($columnIndexes['montant_transformation'])) {
        $data['montant_transformation'] = $this->parseNumericValue($row[$columnIndexes['montant_transformation']] ?? null) ?? 0.0;
    }

    if (isset($columnIndexes['garantie_surete'])) {
        $data['garantie_surete'] = $this->parseNumericValue($row[$columnIndexes['garantie_surete']] ?? null) ?? 0.0;
    }

    if (isset($columnIndexes['conservation_capital'])) {
        $data['conservation_capital'] = $this->parseNumericValue($row[$columnIndexes['conservation_capital']] ?? null) ?? 0.0;
    }

    if (isset($columnIndexes['observation'])) {
        $observation = $this->cleanStringValue($row[$columnIndexes['observation']] ?? null);
        if ($observation !== '') {
            $data['observation'] = $observation;
        }
    }

    $data['status'] = 'en_attente';
    $data['created_by'] = $rdv->created_by ?? $lot->created_by ?? null;

    return $data;
}

    /**
     * Parse une valeur numérique au format du fichier fourni par le métier :
     * "  -     " => null
     * "  305,885   " => 305885.0  (virgule = séparateur de milliers, pas décimal)
     * "1 234,56" => 1234.56       (si un jour un vrai décimal apparaît après la virgule
     *                               à 1 ou 2 chiffres, on le traite comme décimal)
     */
    private function parseNumericValue($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $clean = trim((string) $value);
        $clean = str_replace([' ', "\u{00A0}", "\u{202F}"], '', $clean);

        // Valeur vide ou tiret (case "pas de valeur" du fichier métier)
        if ($clean === '' || $clean === '-' || preg_match('/^-+$/', $clean)) {
            return null;
        }

        // Format "1.234,56" (point = milliers, virgule = décimal)
        if (preg_match('/^-?\d{1,3}(\.\d{3})+,\d+$/', $clean)) {
            $clean = str_replace('.', '', $clean);
            $clean = str_replace(',', '.', $clean);
            return (float) $clean;
        }

        // Format "305,885" où la partie après la virgule fait 3 chiffres exactement :
        // dans ce fichier métier, la virgule est un séparateur de milliers, pas un
        // séparateur décimal (ex: 305,885 = 305885, pas 305.885)
        if (preg_match('/^-?\d{1,3}(,\d{3})+$/', $clean)) {
            return (float) str_replace(',', '', $clean);
        }

        // Format avec virgule décimale réelle (1 ou 2 chiffres après la virgule)
        if (preg_match('/^-?\d+,\d{1,2}$/', $clean)) {
            return (float) str_replace(',', '.', $clean);
        }

        // Format déjà "propre" : entier ou décimal avec point
        if (preg_match('/^-?\d+(\.\d+)?$/', $clean)) {
            return (float) $clean;
        }

        return null;
    }

    private function parseDateValue($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        $value = trim((string) $value);

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        // Format "6/1/2021" du fichier (M/D/Y, Excel export US)
        if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $value)) {
            try {
                return Carbon::createFromFormat('n/j/Y', $value)->format('Y-m-d');
            } catch (\Throwable $e) {
                // fallback d/m/Y au cas où
                try {
                    return Carbon::createFromFormat('d/m/Y', $value)->format('Y-m-d');
                } catch (\Throwable $e2) {
                    return null;
                }
            }
        }

        if (is_numeric($value)) {
            try {
                return Date::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (\Throwable $e) {
                return null;
            }
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function normalizeHeader(string $value): string
    {
        $value = strtolower($value);
        $value = str_replace(['é', 'è', 'ê', 'ë'], 'e', $value);
        $value = str_replace(['à', 'â', 'ä'], 'a', $value);
        $value = str_replace(['î', 'ï', 'í'], 'i', $value);
        $value = str_replace(['ô', 'ö', 'ó'], 'o', $value);
        $value = str_replace(['ù', 'û', 'ü', 'ú'], 'u', $value);
        $value = str_replace(['ç'], 'c', $value);
        $value = preg_replace('/[^a-z0-9]/', '', $value) ?? '';

        return $value;
    }

    private function cleanStringValue($value): string
    {
        if ($value === null) {
            return '';
        }

        return trim((string) $value);
    }

    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $cell) {
            if ($cell !== null && trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }
    /**
     * Garantit qu'un RDV transmis appartient à un bordereau de la bonne période.
     * La période est calculée à partir de date_transmission.
     */
    public function ensureForRdv(Rdv $rdv): BordereauRdv
    {
        if (!$rdv->date_transmission) {
            $rdv->update([
                'date_transmission' => now(),
            ]);
        }

        $dateTransmission = Carbon::parse($rdv->date_transmission);
        [$periode1, $periode2] = $this->resolvePeriodFromTransmission($dateTransmission);

        return DB::transaction(function () use ($rdv, $periode1, $periode2) {
            $lot = BordereauRdv::whereDate('periode_1', $periode1->toDateString())
                ->whereDate('periode_2', $periode2->toDateString())
                ->first();

            if (!$lot) {
                $lot = BordereauRdv::create([
                    'reference' => $this->generateReference($periode1, $periode2),
                    'periode_1' => $periode1->toDateString(),
                    'periode_2' => $periode2->toDateString(),
                    'status' => $this->computeLotStatus($periode1, $periode2),
                    'observation' => null,
                    'created_by' => $rdv->created_by ?? null,
                    'updated_by' => $rdv->updated_by ?? null,
                ]);
            }

            $this->syncLotStatus($lot);

            $detailExists = DetailBordereauRdv::where('rdv_uuid', $rdv->uuid_rdvs)
                ->where('bordereau_rdv_uuid', $lot->uuid_bordereau_rdv)
                ->exists();

            if (!$detailExists) {
                DetailBordereauRdv::create([
                    'uuid_detail_bordereau_rdv' => (string) Str::uuid(),
                    'bordereau_rdv_uuid' => $lot->uuid_bordereau_rdv,
                    'rdv_uuid' => $rdv->uuid_rdvs,
                    'status' => 'en_attente',
                    'created_by' => $rdv->created_by ?? null,
                ]);
            }

            return $lot->fresh();
        });
    }

    /**
     * Calcule la période métier à partir de date_transmission.
     * Règle : semaine lundi->dimanche, lot1 lundi->jeudi, lot2 vendredi->dimanche.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function resolvePeriodFromTransmission(Carbon $date): array
    {
        //
        $startOfWeek = $date->copy()->startOfWeek(Carbon::MONDAY);
        $endOfWeek = $date->copy()->endOfWeek(Carbon::SUNDAY);
        $lot1End = $startOfWeek->copy()->addDays(3);

        if ($date->lte($lot1End)) {
            return [$startOfWeek, $lot1End];
        }

        return [$lot1End->copy()->addDay()->startOfDay(), $endOfWeek];
    }

    // Synchronisation du statut du lot en fonction de la période et de la date actuelle
    protected function computeLotStatus(Carbon $periode1, Carbon $periode2): string
    {
        $today = now()->startOfDay();

        if ($today->between(
            $periode1->copy()->startOfDay(),
            $periode2->copy()->endOfDay()
        )) {
            return 'transfere';
        }

        return 'en_attente';
    }


    // Synchronisation du statut du lot en fonction de la période et de la date actuelle
    protected function syncLotStatus(BordereauRdv $lot): void
    {
        $periode1 = Carbon::parse($lot->periode_1)->startOfDay();
        $periode2 = Carbon::parse($lot->periode_2)->endOfDay();
        $today = now()->startOfDay();

        if ($lot->status === 'en_attente' && $today->between($periode1, $periode2)) {
            $lot->update([
                'status' => 'transfere',
                'updated_by' => $lot->created_by ?? null,
            ]);
        }
    }

    /**
     * Génère une référence unique pour le bordereau.
     * Format : BR-YYYY-SWW-XXXXXXXX
     */
    protected function generateReference(Carbon $periode1, Carbon $periode2): string
    {
        $year = $periode1->year;
        $week = $periode1->weekOfYear;

        return sprintf(
            'BR-%s-S%02d-%s',
            $year,
            $week,
            strtoupper(substr(md5(uniqid((string) microtime(true), true)), 0, 8))
        );
    }
}
