<?php

namespace App\Models\Api\Ynov\parameter;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

/**
 * CORRECTION : Modèle de token Sanctum sur la connexion mysql4
 * pour correspondre à la connexion des utilisateurs YNOV.
 */
class PersonalAccessToken extends SanctumPersonalAccessToken
{
    /**
     * La connexion de base de données à utiliser.
     *
     * @var string
     */
    
    protected $table = 'personal_access_tokens';
    protected $fillable = [
        'tokenable_id',
        'tokenable_type',
        'name',
        'token',
        'abilities',
        'last_used_at',
        'expires_at',
    ];

}
