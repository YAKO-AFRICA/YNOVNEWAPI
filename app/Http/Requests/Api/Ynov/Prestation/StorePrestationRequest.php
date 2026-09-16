<?php

namespace App\Http\Requests\Api\Ynov\Prestation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePrestationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'client_uuid' => ['required', 'exists:users,uuid_user'],
            'id_contrat' => ['nullable', 'string', 'max:55'],
            'type_prestation_uuid' => ['required', 'exists:type_prestations,uuid_type_prestation'],
            'rdv_uuid' => ['nullable', 'exists:rdvs,uuid_rdvs'],
            'notes' => ['nullable', 'string'],
            'montant' => ['nullable', 'numeric', 'min:0'],
            'mode_paiement' => ['nullable', 'string', 'max:55'],
            'operateur_mobile' => ['nullable', 'string', 'max:55'],
            'tel_paiement_1' => ['nullable', 'string', 'max:55'],
            'tel_paiement_2' => ['nullable', 'string', 'max:55'],
            'code_banque' => ['nullable', 'string', 'max:10'],
            'code_guichet' => ['nullable', 'string', 'max:10'],
            'numero_compte' => ['nullable', 'string', 'max:55'],
            'cle_rib' => ['nullable', 'string', 'max:10'],
            'ville_declaration' => ['nullable', 'string', 'max:55'],
            'status' => ['nullable', 'string', Rule::in(['inacheve', 'en_attente', 'transmis', 'accepte', 'rejete', 'annule'])],
        ];
    }

    public function messages(): array
    {
        return [
            'client_uuid.required' => 'Le client est obligatoire.',
            'client_uuid.exists' => 'Le client sélectionné est introuvable.',
            'type_prestation_uuid.required' => 'Le type de prestation est obligatoire.',
            'type_prestation_uuid.exists' => 'Le type de prestation sélectionné est introuvable.',
            'rdv_uuid.exists' => 'Le rendez-vous auquel est lie cette prestation est introuvable.',
            'montant.numeric' => 'Le montant doit être un nombre valide.',
            'montant.min' => 'Le montant ne peut pas être négatif.',
            'status.in' => 'Le statut doit être l’un des suivants : inacheve, en_attente, transmis, accepte, rejete, annule.',
        ];
    }
}
