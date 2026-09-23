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
            'motif_reports' => ['required', 'array', 'min:1'],
            'motif_reports.*' => ['required', 'string', 'exists:motif_traitements,uuid_motif_traitements'],
             'observation' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'nouvelle_date.required' => 'La nouvelle date est requise.',
            'nouvelle_date.date' => 'La nouvelle date doit être une date valide.',
            'nouvelle_date.after' => 'La nouvelle date doit être dans le futur.',
            'motif_reports.required' => 'Au moins un motif de report est requis.',
            'motif_reports.array' => 'Les motifs de report doivent être un tableau.',
            'motif_reports.min' => 'Au moins un motif de report est requis.',
            'motif_reports.*.required' => 'Chaque motif de report est requis.',
            'motif_reports.*.exists' => 'Un ou plusieurs motifs de report sont invalides.',
            'observation.max' => 'L\'observation ne peut pas dépasser 1000 caractères.',
        ];
    }
}
