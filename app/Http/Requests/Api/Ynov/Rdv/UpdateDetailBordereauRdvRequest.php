<?php

namespace App\Http\Requests\Api\Ynov\Rdv;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDetailBordereauRdvRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'date_effet' => ['sometimes', 'nullable', 'date'],
            'date_echeance' => ['sometimes', 'nullable', 'date'],
            'duree_contrat' => ['sometimes', 'nullable', 'string', 'max:255'],
            'type_operation' => ['sometimes', 'nullable', 'string', 'max:255'],
            'produit' => ['sometimes', 'nullable', 'string', 'max:255'],
            'cumul_rachats_partiels' => ['sometimes', 'nullable', 'numeric'],
            'cumul_avances' => ['sometimes', 'nullable', 'numeric'],
            'provision_nette' => ['sometimes', 'nullable', 'numeric'],
            'valeur_rachat' => ['sometimes', 'nullable', 'numeric'],
            'valeur_max_rachat' => ['sometimes', 'nullable', 'numeric'],
            'valeur_max_avance' => ['sometimes', 'nullable', 'numeric'],
            'montant_transformation' => ['sometimes', 'nullable', 'numeric'],
            'garantie_surete' => ['sometimes', 'nullable', 'numeric'],
            'conservation_capital' => ['sometimes', 'nullable', 'numeric'],
            'observation' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'gestionnaire_prestation_uuid' => ['sometimes', 'nullable', 'string', 'uuid'],
            'status' => ['sometimes', 'nullable', 'in:en_attente,soumis,traite'],
        ];
    }

    public function messages(): array
    {
        return [
            'date_effet.date' => 'La date d’effet doit être une date valide.',
            'date_echeance.date' => 'La date d’échéance doit être une date valide.',
            'duree_contrat.max' => 'La durée du contrat ne doit pas dépasser 255 caractères.',
            'type_operation.max' => 'Le type d’opération ne doit pas dépasser 255 caractères.',
            'produit.max' => 'Le produit ne doit pas dépasser 255 caractères.',
            'observation.max' => 'L’observation ne doit pas dépasser 2000 caractères.',
            'gestionnaire_prestation_uuid.uuid' => 'L’UUID du gestionnaire prestation est invalide.',
            'status.in' => 'Le statut doit être l’un des suivants : en_attente, soumis, traite.',
        ];
    }

    public function getUpdatableData(): array
    {
        $allowed = [
            'date_effet',
            'date_echeance',
            'duree_contrat',
            'type_operation',
            'produit',
            'cumul_rachats_partiels',
            'cumul_avances',
            'provision_nette',
            'valeur_rachat',
            'valeur_max_rachat',
            'valeur_max_avance',
            'montant_transformation',
            'garantie_surete',
            'conservation_capital',
            'observation',
            'gestionnaire_prestation_uuid',
            'status',
        ];

        $payload = $this->validated();

        return array_intersect_key($payload, array_flip($allowed));
    }
}
