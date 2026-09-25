<?php

namespace App\Http\Requests\Api\Ynov\Prestation;

use Illuminate\Foundation\Http\FormRequest;

class PrestationListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'in:inacheve,en_attente,transmis,accepte,rejete,annule'],
            'client_uuid' => ['nullable', 'exists:users,uuid_user'],
            'type_prestation_uuid' => ['nullable', 'exists:type_prestations,uuid_type_prestation'],
            'gestionnaire_uuid' => ['nullable', 'exists:users,uuid_user'],
            'partner_uuid' => ['nullable', 'exists:partners,uuid_partner'],
            'is_migrated' => ['nullable', 'boolean'],
            'motif_type' => ['nullable', 'string', 'in:traitement,rejet,annulation'],
            'has_motifs' => ['nullable', 'boolean'],
            'automatic_only' => ['nullable', 'boolean'],
            'manual_only' => ['nullable', 'boolean'],
            'date' => ['nullable', 'date'],
            'date_debut' => ['nullable', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'sort_by' => ['nullable', 'string', 'in:created_at,date_transmission,date_traitement,status,code'],
            'sort_order' => ['nullable', 'string', 'in:asc,desc'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'Le statut sélectionné n\'est pas valide.',
            'client_uuid.exists' => 'Le client sélectionné n\'existe pas.',
            'type_prestation_uuid.exists' => 'Le type de prestation sélectionné n\'existe pas.',
            'gestionnaire_uuid.exists' => 'Le gestionnaire sélectionné n\'existe pas.',
            'partner_uuid.exists' => 'Le partenaire sélectionné n\'existe pas.',
            'motif_type.in' => 'Le type de motif sélectionné n\'est pas valide.',
            'date_fin.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',
            'sort_by.in' => 'Le champ de tri sélectionné n\'est pas valide.',
            'sort_order.in' => 'L\'ordre de tri sélectionné n\'est pas valide.',
        ];
    }

    public function getFilters(): array
    {
        $filters = $this->only([
            'search',
            'status',
            'client_uuid',
            'type_prestation_uuid',
            'gestionnaire_uuid',
            'partner_uuid',
            'is_migrated',
            'motif_type',
            'has_motifs',
            'automatic_only',
            'manual_only',
            'date',
            'date_debut',
            'date_fin',
            'sort_by',
            'sort_order',
        ]);

        if ($this->has('is_migrated')) {
            $filters['is_migrated'] = filter_var($this->input('is_migrated'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($filters['is_migrated'] === null) {
                $filters['is_migrated'] = (bool) $this->input('is_migrated');
            }
        }

        if ($this->has('has_motifs')) {
            $filters['has_motifs'] = filter_var($this->input('has_motifs'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($filters['has_motifs'] === null) {
                $filters['has_motifs'] = (bool) $this->input('has_motifs');
            }
        }

        if ($this->has('automatic_only')) {
            $filters['automatic_only'] = filter_var($this->input('automatic_only'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($filters['automatic_only'] === null) {
                $filters['automatic_only'] = (bool) $this->input('automatic_only');
            }
        }

        if ($this->has('manual_only')) {
            $filters['manual_only'] = filter_var($this->input('manual_only'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($filters['manual_only'] === null) {
                $filters['manual_only'] = (bool) $this->input('manual_only');
            }
        }

        return $filters;
    }

    public function getPerPage(): int
    {
        return $this->integer('per_page', 20);
    }

    public function getAvailableFilters(): array
    {
        return [
            'search' => [
                'type' => 'string',
                'description' => 'Recherche textuelle (code, notes, observation, ville, email, nom client)',
                'max_length' => 100,
            ],
            'status' => [
                'type' => 'string',
                'description' => 'Filtrer par statut',
                'enum' => ['inacheve', 'en_attente', 'transmis', 'accepte', 'rejete', 'annule'],
            ],
            'client_uuid' => [
                'type' => 'uuid',
                'description' => 'Filtrer par client',
            ],
            'type_prestation_uuid' => [
                'type' => 'uuid',
                'description' => 'Filtrer par type de prestation',
            ],
            'gestionnaire_uuid' => [
                'type' => 'uuid',
                'description' => 'Filtrer par gestionnaire',
            ],
            'partner_uuid' => [
                'type' => 'uuid',
                'description' => 'Filtrer par partenaire',
            ],
            'is_migrated' => [
                'type' => 'boolean',
                'description' => 'Filtrer par migration',
            ],
            'motif_type' => [
                'type' => 'string',
                'description' => 'Filtrer par type de motif de traitement',
                'enum' => ['traitement', 'rejet', 'annulation'],
            ],
            'has_motifs' => [
                'type' => 'boolean',
                'description' => 'Filtrer par présence de motifs (true = avec motifs, false = sans motifs)',
            ],
            'automatic_only' => [
                'type' => 'boolean',
                'description' => 'Filtrer par motifs automatiques uniquement',
            ],
            'manual_only' => [
                'type' => 'boolean',
                'description' => 'Filtrer par motifs manuels uniquement',
            ],
            'date' => [
                'type' => 'date',
                'description' => 'Filtrer par date spécifique (created_at)',
            ],
            'date_debut' => [
                'type' => 'date',
                'description' => 'Date de début de la plage de dates',
            ],
            'date_fin' => [
                'type' => 'date',
                'description' => 'Date de fin de la plage de dates (doit être >= date_debut)',
            ],
            'sort_by' => [
                'type' => 'string',
                'description' => 'Champ de tri',
                'enum' => ['created_at', 'date_transmission', 'date_traitement', 'status', 'code'],
            ],
            'sort_order' => [
                'type' => 'string',
                'description' => 'Ordre de tri',
                'enum' => ['asc', 'desc'],
            ],
            'per_page' => [
                'type' => 'integer',
                'description' => 'Nombre par page',
                'min' => 1,
                'max' => 100,
                'default' => 20,
            ],
        ];
    }
}
