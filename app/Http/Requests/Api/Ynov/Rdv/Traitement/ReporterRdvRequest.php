<?php

namespace App\Http\Requests\Api\Ynov\Rdv\Traitement;

use Illuminate\Foundation\Http\FormRequest;

class ReporterRdvRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nouvelle_date' => ['required', 'date', 'after:today'],
            'nouvelle_agence_uuid' => ['nullable', 'exists:agences,uuid_agence'],
            'motif_report' => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'nouvelle_date.required' => 'La nouvelle date est requise.',
            'nouvelle_date.date' => 'La nouvelle date doit être une date valide.',
            'nouvelle_date.after' => 'La nouvelle date doit être dans le futur.',
            'nouvelle_agence_uuid.exists' => 'L\'agence n\'existe pas.',
            'motif_report.required' => 'Le motif du report est requis.',
            'motif_report.max' => 'Le motif du report ne peut pas dépasser 500 caractères.',
        ];
    }
}
