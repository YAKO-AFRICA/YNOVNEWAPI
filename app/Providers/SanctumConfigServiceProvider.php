<?php

namespace App\Providers;

use App\Models\Api\Ynov\parameter\PersonalAccessToken;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class SanctumConfigServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // CORRECTION : Utiliser le modèle de token sur mysql4
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
    }
}