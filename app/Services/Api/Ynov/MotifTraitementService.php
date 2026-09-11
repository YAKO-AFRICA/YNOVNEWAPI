<?php

namespace App\Services\Api\Ynov;

use App\Models\Api\Ynov\parameter\MotifTraitement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;

class MotifTraitementService
{
    private const VALID_STATUS = ['actif', 'inactif'];

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = MotifTraitement::query();

        if (!empty($filters['search'])) {
            $query->search((string) $filters['search']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', (string) $filters['status']);
        }

        if (!empty($filters['type'])) {
            $type = $filters['type'];

            if (is_string($type)) {
                $query->whereJsonContains('type', $type);
            } elseif (is_array($type)) {
                foreach ($type as $value) {
                    $query->whereJsonContains('type', $value);
                }
            }
        }

        if (!empty($filters['module'])) {
            $module = $filters['module'];

            if (is_string($module)) {
                $query->whereJsonContains('module', $module);
            } elseif (is_array($module)) {
                foreach ($module as $value) {
                    $query->whereJsonContains('module', $value);
                }
            }
        }

        return $query
            ->orderBy('libelle', 'asc')
            ->paginate($perPage);
    }

    public function getActifs(): Collection
    {
        return MotifTraitement::query()
            ->where('status', 'actif')
            ->orderBy('libelle', 'asc')
            ->get();
    }

    public function getSuggestedTypes(): array
    {
        return [
            ['value' => 'annulation', 'label' => 'Annulation'],
            ['value' => 'rejet', 'label' => 'Rejet'],
            ['value' => 'report', 'label' => 'Report'],
            ['value' => 'validation', 'label' => 'Validation'],
            ['value' => 'refus', 'label' => 'Refus'],
            ['value' => 'demande', 'label' => 'Demande'],
            ['value' => 'informations', 'label' => 'Informations'],
            ['value' => 'correction', 'label' => 'Correction'],
            ['value' => 'suspension', 'label' => 'Suspension'],
            ['value' => 'cloture', 'label' => 'Clôture'],
            ['value' => 'autre', 'label' => 'Autre'],
        ];
    }

    public function getByUuid(string $uuid): MotifTraitement
    {
        return MotifTraitement::query()
            ->where('uuid_motif_traitements', $uuid)
            ->firstOrFail();
    }

    public function create(array $data): MotifTraitement
    {
        $validated = $this->validatePayload($data);

        return MotifTraitement::query()->create([
            'uuid_motif_traitements' => (string) Str::uuid(),
            'libelle' => $validated['libelle'],
            'type' => $validated['type'] ?? [],
            'status' => $validated['status'] ?? 'actif',
            'module' => $validated['module'] ?? [],
        ]);
    }

    public function update(string $uuid, array $data): MotifTraitement
    {
        $motif = $this->getByUuid($uuid);
        $validated = $this->validatePayload($data, true);

        $motif->fill([
            'libelle' => $validated['libelle'] ?? $motif->libelle,
            'type' => $validated['type'] ?? $motif->type,
            'status' => $validated['status'] ?? $motif->status,
            'module' => $validated['module'] ?? $motif->module,
        ]);

        $motif->save();

        return $motif->fresh();
    }

    public function toggleStatus(string $uuid): MotifTraitement
    {
        $motif = $this->getByUuid($uuid);
        $motif->status = $motif->status === 'actif' ? 'inactif' : 'actif';
        $motif->save();

        return $motif->fresh();
    }

    public function delete(string $uuid): bool
    {
        $motif = $this->getByUuid($uuid);

        return (bool) $motif->delete();
    }

    private function validatePayload(array $data, bool $isUpdate = false): array
    {
        if (!$isUpdate && empty($data['libelle'] ?? null)) {
            throw new InvalidArgumentException('Le libellé est obligatoire.');
        }

        if (isset($data['libelle']) && trim((string) $data['libelle']) === '') {
            throw new InvalidArgumentException('Le libellé ne peut pas être vide.');
        }

        if (isset($data['status']) && !in_array($data['status'], self::VALID_STATUS, true)) {
            throw new InvalidArgumentException('Le statut doit être actif ou inactif.');
        }

        if (isset($data['type'])) {
            $type = $data['type'];

            if (!is_array($type)) {
                throw new InvalidArgumentException('Le champ type doit être un tableau.');
            }

            $data['type'] = array_values(array_unique(array_filter(array_map('strval', $type))));
        }

        if (isset($data['module'])) {
            $module = $data['module'];

            if (!is_array($module)) {
                throw new InvalidArgumentException('Le champ module doit être un tableau.');
            }

            $data['module'] = array_values(array_unique(array_filter(array_map('strval', $module))));
        }

        return $data;
    }
}
