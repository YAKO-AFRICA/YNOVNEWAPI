<?php
// app/Http/Requests/Api/Ynov/Rdv/RdvListRequest.php

namespace App\Http\Requests\Api\Ynov\Rdv;

use Illuminate\Foundation\Http\FormRequest;

class RdvListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'in:en_attente,transmis,confirme,traite,termine,annule,rejete,reporte,expire'],
            'date' => ['nullable', 'date'],
            'date_debut' => ['nullable', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
            'agence_uuid' => ['nullable', 'exists:agences,uuid_agence'],
            'gestionnaire_uuid' => ['nullable', 'exists:users,uuid_user'],
            'motif_uuid' => ['nullable', 'exists:type_prestations,uuid_type_prestation'],
            'is_present' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'sort_by' => ['nullable', 'string', 'in:created_at,date_rdv_souhaiter,status,code'],
            'sort_order' => ['nullable', 'string', 'in:asc,desc'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'Le statut sélectionné n\'est pas valide.',
            'date_fin.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',
            'agence_uuid.exists' => 'L\'agence sélectionnée n\'existe pas.',
            'gestionnaire_uuid.exists' => 'Le gestionnaire sélectionné n\'existe pas.',
            'motif_uuid.exists' => 'Le motif sélectionné n\'existe pas.',
        ];
    }

    public function getFilters(): array
    {
        $filters = $this->only([
            'search',
            'status',
            'date',
            'date_debut',
            'date_fin',
            'agence_uuid',
            'gestionnaire_uuid',
            'motif_uuid',
            'is_present',
            'sort_by',
            'sort_order',
        ]);

        if ($this->has('is_present')) {
            $filters['is_present'] = filter_var($this->input('is_present'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($filters['is_present'] === null) {
                $filters['is_present'] = (bool) $this->input('is_present');
            }
        }

        return $filters;
    }

    public function getPerPage(): int
    {
        return $this->integer('per_page', 15);
    }
}