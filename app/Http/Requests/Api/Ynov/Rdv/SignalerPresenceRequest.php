<?php
// app/Http/Requests/Api/Ynov/Rdv/SignalerPresenceRequest.php

namespace App\Http\Requests\Api\Ynov\Rdv;

use Illuminate\Foundation\Http\FormRequest;

class SignalerPresenceRequest extends FormRequest
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
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'latitude.numeric' => 'La latitude doit être un nombre valide.',
            'latitude.between' => 'La latitude doit être comprise entre -90 et 90 degrés.',
            'longitude.numeric' => 'La longitude doit être un nombre valide.',
            'longitude.between' => 'La longitude doit être comprise entre -180 et 180 degrés.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'latitude' => 'latitude',
            'longitude' => 'longitude',
        ];
    }

    /**
     * Get the GPS coordinates from the request.
     */
    public function getCoordinates(): array
    {
        return $this->only(['latitude', 'longitude']);
    }

    /**
     * Check if GPS coordinates are provided.
     */
    public function hasCoordinates(): bool
    {
        return $this->has('latitude') && $this->has('longitude');
    }
}