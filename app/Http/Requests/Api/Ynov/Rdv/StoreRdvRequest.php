<?php
// app/Http/Requests/Api/Ynov/Rdv/StoreRdvRequest.php

namespace App\Http\Requests\Api\Ynov\Rdv;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class StoreRdvRequest extends FormRequest
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
            'id_contrat' => ['required', 'integer'],
            'code_produit' => ['required', 'string', 'exists:produits,code'],
            'motif_rdv' => ['required', 'string', 'exists:type_prestations,uuid_type_prestation'],
            'demandeur' => ['nullable', 'string', 'max:50'],
            'agence_uuid' => ['required', 'string', 'exists:agences,uuid_agence'],
            'date_rdv' => ['required', 'date', 'after_or_equal:today'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'id_contrat.required' => 'L\'identifiant du contrat est obligatoire.',
            'id_contrat.integer' => 'L\'identifiant du contrat doit être un nombre entier.',
            'code_produit.required' => 'Le code du produit est obligatoire.',
            'code_produit.exists' => 'Le code du produit n\'existe pas.',
            'motif_rdv.required' => 'Le motif du rendez-vous est obligatoire.',
            'motif_rdv.exists' => 'Le motif du rendez-vous n\'existe pas.',
            'demandeur.max' => 'Le demandeur ne doit pas dépasser 50 caractères.',
            'agence_uuid.required' => 'L\'UUID de l\'agence est obligatoire.',
            'agence_uuid.exists' => 'L\'agence n\'existe pas.',
            'date_rdv.required' => 'La date du rendez-vous est obligatoire.',
            'date_rdv.date' => 'La date du rendez-vous doit être une date valide.',
            'date_rdv.after_or_equal' => 'La date du rendez-vous doit être aujourd\'hui ou une date future.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'id_contrat' => 'identifiant du contrat',
            'code_produit' => 'code du produit',
            'motif_rdv' => 'motif du rendez-vous',
            'demandeur' => 'demandeur',
            'agence_uuid' => 'UUID de l\'agence',
            'date_rdv' => 'date du rendez-vous',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('date_rdv')) {
            $this->merge([
                'date_rdv' => Carbon::parse($this->date_rdv)->format('Y-m-d'),
            ]);
        }
    }

    /**
     * Get the validated data for creating a RDV.
     */
    public function getRdvData(): array
    {
        return [
            'id_contrat' => $this->id_contrat,
            'code_produit' => $this->code_produit,
            'motif_rdv' => $this->motif_rdv,
            'demandeur' => $this->demandeur ?? 'Souscripteur',
            'agence_uuid' => $this->agence_uuid,
            'date_rdv' => $this->date_rdv,
        ];
    }
}