<?php
// app/Http/Requests/Api/Ynov/StoreGroupNotifRequest.php
namespace App\Http\Requests\Api\Ynov;

use Illuminate\Foundation\Http\FormRequest;

class StoreGroupNotifRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('group_notifs.creer') ?? false;
    }

    public function rules(): array
    {
        return [
            'libelle' => ['required', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:55', 'unique:group_notifs,code'],
            'description' => ['nullable', 'string'],
            'channels' => ['nullable', 'array'],
            'channels.*' => ['string', 'in:database,email,sms,push,whatsapp'],
            'preferences' => ['nullable', 'array'],
            'status' => ['nullable', 'string', 'in:actif,inactif'],
        ];
    }

    public function messages(): array
    {
        return [
            'libelle.required' => 'Le libellé est obligatoire.',
            'libelle.max' => 'Le libellé ne doit pas dépasser 100 caractères.',
            'code.unique' => 'Ce code est déjà utilisé.',
        ];
    }
}