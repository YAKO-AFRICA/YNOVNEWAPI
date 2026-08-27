<?php
// app/Http/Requests/Api/Ynov/UpdateGroupNotifRequest.php
namespace App\Http\Requests\Api\Ynov;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGroupNotifRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('group_notifs.modifier') ?? false;
    }

    public function rules(): array
    {
        $group = $this->route('group_notif');
        $uuid = $group ? $group->uuid_group_notif : null;

        return [
            'libelle' => ['sometimes', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:55', "unique:group_notifs,code,{$uuid},uuid_group_notif"],
            'description' => ['nullable', 'string'],
            'channels' => ['nullable', 'array'],
            'channels.*' => ['string', 'in:database,email,sms,push,whatsapp'],
            'preferences' => ['nullable', 'array'],
            'status' => ['nullable', 'string', 'in:actif,inactif'],
        ];
    }
}