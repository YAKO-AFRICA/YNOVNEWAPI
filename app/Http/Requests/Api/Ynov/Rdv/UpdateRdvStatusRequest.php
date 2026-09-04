<?php
// app/Http/Requests/Api/Ynov/Rdv/UpdateRdvStatusRequest.php

namespace App\Http\Requests\Api\Ynov\Rdv;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRdvStatusRequest extends FormRequest
{
    /**
     * Les statuts autorisés pour un rendez-vous
     */
    public const STATUS_ALLOWED = [
        'en_attente',
        'confirme',
        'rejete',
        'traite',
        'termine',
        'annule',
        'reporte',
    ];

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
            'status' => ['required', 'string', Rule::in(self::STATUS_ALLOWED)],
            'observation' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Le statut est obligatoire.',
            'status.in' => 'Le statut doit être l\'un des suivants : ' . implode(', ', self::STATUS_ALLOWED) . '.',
            'observation.string' => 'L\'observation doit être une chaîne de caractères.',
            'observation.max' => 'L\'observation ne doit pas dépasser 500 caractères.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'status' => 'statut',
            'observation' => 'observation',
        ];
    }

    /**
     * Get the status value.
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Get the observation value.
     */
    public function getObservation(): ?string
    {
        return $this->observation;
    }

    /**
     * Get the update data array.
     */
    public function getUpdateData(): array
    {
        return [
            'observation' => $this->observation,
            'admin_action' => true,
        ];
    }

    /**
     * Get the label for the status.
     */
    public function getStatusLabel(): string
    {
        $labels = [
            'en_attente' => 'En attente',
            'confirme' => 'Confirmé',
            'rejete' => 'Rejeté',
            'traite' => 'Traité',
            'termine' => 'Terminé',
            'annule' => 'Annulé',
            'reporte' => 'Reporté',
        ];

        return $labels[$this->status] ?? $this->status;
    }
}

