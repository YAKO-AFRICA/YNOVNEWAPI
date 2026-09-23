<?php

namespace App\Http\Requests\Api\Ynov\Prestation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePrestationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        // $prestationId = $this->route('uuid_prestation');

        return [
            'client_uuid' => ['sometimes', 'exists:users,uuid_user'],
            'id_contrat' => ['sometimes', 'nullable', 'string', 'max:55'],
            'type_prestation_uuid' => ['sometimes', 'exists:type_prestations,uuid_type_prestation'],
            'rdv_uuid' => ['sometimes', 'nullable', 'exists:rdvs,uuid_rdvs'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'montant' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'mode_paiement' => ['sometimes', 'nullable', 'string', 'max:55'],
            'operateur_mobile' => ['sometimes', 'nullable', 'string', 'max:55'],
            'tel_paiement_1' => ['sometimes', 'nullable', 'string', 'max:55'],
            'tel_paiement_2' => ['sometimes', 'nullable', 'string', 'max:55'],
            'code_banque' => ['sometimes', 'nullable', 'string', 'max:10'],
            'code_guichet' => ['sometimes', 'nullable', 'string', 'max:10'],
            'numero_compte' => ['sometimes', 'nullable', 'string', 'max:55'],
            'cle_rib' => ['sometimes', 'nullable', 'string', 'max:10'],
            'ville_declaration' => ['sometimes', 'nullable', 'string', 'max:55'],
            'partner_uuid' => ['sometimes', 'nullable', 'exists:partners,uuid_partner'],
            'gestionnaire_uuid' => ['sometimes', 'nullable', 'exists:users,uuid_user'],
            'traiter_par' => ['sometimes', 'nullable', 'exists:users,uuid_user'],
            'status' => ['sometimes', 'nullable', 'string', Rule::in(['inacheve', 'en_attente', 'transmis', 'accepte', 'rejete', 'annule'])],
            'motif_traitement' => ['sometimes', 'nullable', 'array'],
            'motif_traitement.*' => ['nullable', 'string'],
            'observation' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'client_uuid.uuid' => 'Le client doit être un UUID valide.',
            'client_uuid.exists' => 'Le client sélectionné est introuvable.',
            'type_prestation_uuid.uuid' => 'Le type de prestation doit être un UUID valide.',
            'type_prestation_uuid.exists' => 'Le type de prestation sélectionné est introuvable.',
            'rdv_uuid.uuid' => 'Le rendez-vous doit être un UUID valide.',
            'rdv_uuid.exists' => 'Le rendez-vous sélectionné est introuvable.',
            'montant.numeric' => 'Le montant doit être un nombre valide.',
            'montant.min' => 'Le montant ne peut pas être négatif.',
            'mode_paiement.in' => 'Le mode de paiement sélectionné est invalide.',
            'status.in' => 'Le statut doit être l’un des suivants : inacheve, en_attente, transmis, accepte, rejete, annule.',
            'motif_traitement.array' => 'Les motifs de traitement doivent être sous forme de tableau.',
            'observation.max' => 'L’observation ne peut pas dépasser 2000 caractères.',
        ];
    }
}
