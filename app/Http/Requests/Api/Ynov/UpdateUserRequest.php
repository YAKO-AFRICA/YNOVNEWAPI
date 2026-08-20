<?php
namespace App\Http\Requests\Api\Ynov;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('users.modifier') ?? false;
    }

    public function rules(): array
    {
        $userUuid = $this->route('user')?->uuid_user;
        return [
            'email' => ['sometimes', 'email', "unique:users,email,{$userUuid},uuid_user"],
            'login' => ['sometimes', 'nullable', 'string', 'max:100', "unique:users,login,{$userUuid},uuid_user"],
            'role_uuid' => ['sometimes', 'exists:roles,uuid_role'],
            'user_type' => ['sometimes', 'in:client,user_interne,user_partner,admin'],
            'partner_uuid' => ['nullable', 'exists:partners,uuid_partner'],
            'reseau_uuid' => ['nullable', 'exists:reseaux,uuid_reseau'],
            'status' => ['sometimes', 'in:actif,inactif,gele,bloque'],
            'nom' => ['sometimes', 'string', 'max:55'],
            'prenoms' => ['sometimes', 'string', 'max:255'],
            'fonction' => ['nullable', 'string', 'max:55'],
            'mobile_1' => ['nullable', 'string', 'max:25'],
        ];
    }
    
}