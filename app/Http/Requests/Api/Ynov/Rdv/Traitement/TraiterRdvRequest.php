<?php

namespace App\Http\Requests\Api\Ynov\Rdv\Traitement;

use Illuminate\Foundation\Http\FormRequest;



class TraiterRdvRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_uuid' => ['required', 'exists:users,uuid_user'],
            'id_contrat' => ['required', 'string', 'max:55'],
            'type_prestation_uuid' => ['required', 'exists:type_prestations,uuid_type_prestation'], // uuid motif du rdv
            'rdv_uuid' => ['required', 'exists:rdvs,uuid_rdvs'], // uuid du rdv
            'montant' => ['required', 'numeric', 'min:0'], // montant de la prestation

            'is_permitted' => ['required', 'boolean'], // false = conservation, true = sortie de portefeuille
            'motif_traitements' => ['required', 'array', 'min:1'],
            'motif_traitements.*' => ['required', 'string', 'exists:motif_traitements,uuid_motif_traitements'],
            'observation' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'client_uuid.required' => 'Le client est obligatoire.',
            'client_uuid.exists' => 'Le client sélectionné est introuvable.',
            'type_prestation_uuid.required' => 'Le type de prestation est obligatoire.',
            'type_prestation_uuid.exists' => 'Le type de prestation sélectionné est introuvable.',
            'rdv_uuid.required' => 'Le rendez-vous auquel est lié cette prestation est obligatoire.',
            'rdv_uuid.exists' => 'Le rendez-vous auquel est lié cette prestation est introuvable.',
            'id_contrat.required' => 'L\'identifiant du contrat est obligatoire.',
            'montant.numeric' => 'Le montant doit être un nombre valide.',
            'montant.min' => 'Le montant ne peut pas être négatif.',
            
            'motif_traitements.required' => 'Au moins un motif de traitement est requis.',
            'motif_traitements.array' => 'Les motifs de traitement doivent être un tableau.',
            'motif_traitements.min' => 'Au moins un motif de traitement est requis.',
            'motif_traitements.*.required' => 'Chaque motif de traitement est requis.',
            'motif_traitements.*.exists' => 'Un ou plusieurs motifs de traitement sont invalides.',
            'observation.max' => 'L\'observation ne peut pas dépasser 1000 caractères.',
        ];
    }
}
