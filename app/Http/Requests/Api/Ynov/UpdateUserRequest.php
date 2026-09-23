<?php
namespace App\Http\Requests\Api\Ynov;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// class UpdateUserRequest extends FormRequest
// {
//     public function authorize(): bool
//     {
//         return $this->user()?->hasPermission('users.modifier') ?? false;
//     }

//     public function rules(): array
//     {
//         $userUuid = $this->route('user')?->uuid_user;
//         return [
//             'email' => ['sometimes', 'email', "unique:users,email,{$userUuid},uuid_user"],
//             'login' => ['sometimes', 'nullable', 'string', 'max:100', "unique:users,login,{$userUuid},uuid_user"],
//             'role_uuid' => ['sometimes', 'exists:roles,uuid_role'],
//             'user_type' => ['sometimes', 'in:client,user_interne,user_partner,admin'],
//             'partner_uuid' => ['nullable', 'exists:partners,uuid_partner'],
//             'reseau_uuid' => ['nullable', 'exists:reseaux,uuid_reseau'],
//             'status' => ['sometimes', 'in:actif,inactif,gele,bloque'],
//             'nom' => ['sometimes', 'string', 'max:55'],
//             'prenoms' => ['sometimes', 'string', 'max:255'],
//             'fonction' => ['nullable', 'string', 'max:55'],
//             'mobile_1' => ['nullable', 'string', 'max:25'],
//         ];
//     }
    
// }

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('users.modifier') ?? false;
    }

    public function rules(): array
    {
        $user = $this->route('uuid_user');
        
        return [
            'email' => ['nullable', 'email', Rule::unique('users', 'email')->ignore($user, 'uuid_user')],
            'login' => ['nullable', 'string', 'max:100', Rule::unique('users', 'login')->ignore($user, 'uuid_user')],
            'role_uuid' => ['nullable', 'exists:roles,uuid_role'],
            'user_type' => ['nullable', Rule::in(['client', 'user_interne', 'user_partner', 'admin'])],
            'partner_uuid' => ['nullable', 'exists:partners,uuid_partner'],
            'reseau_uuid' => ['nullable', 'exists:reseaux,uuid_reseau'],
            'status' => ['nullable', Rule::in(['actif', 'inactif', 'gele', 'bloque'])],
            'agence_uuids' => ['nullable', 'array', 'min:1'],
            'agence_uuids.*' => ['exists:agences,uuid_agence'],
            'nom' => ['nullable', 'string', 'max:55'],
            'prenoms' => ['nullable', 'string', 'max:255'],
            'fonction' => ['nullable', 'string', 'max:55'],
            'service' => ['nullable', 'string', 'max:100'],
            'departement' => ['nullable', 'string', 'max:100'],
            'mobile_1' => ['nullable', 'string', 'max:25'],
            'mobile_2' => ['nullable', 'string', 'max:25'],
            'email_pro' => ['nullable', 'email', 'max:255'],
            'date_naissance' => ['nullable', 'date'],
            'lieu_naissance' => ['nullable', 'string', 'max:255'],
            'genre' => ['nullable', Rule::in(['M', 'F'])],
            'civilite' => ['nullable', Rule::in(['M.', 'Mme', 'Mlle'])],
            'ville' => ['nullable', 'string', 'max:100'],
            'pays' => ['nullable', 'string', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Si une seule agence est fournie via agence_uuid, la convertir en tableau
        if ($this->has('agence_uuid') && !$this->has('agence_uuids')) {
            $this->merge([
                'agence_uuids' => [$this->input('agence_uuid')]
            ]);
        }
    }
}