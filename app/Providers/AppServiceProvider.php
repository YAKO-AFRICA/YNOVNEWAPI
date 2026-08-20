<?php

namespace App\Providers;

use App\Models\Api\Ynov\parameter\Role;
use App\Services\Api\Ynov\Auth\AuthService;
use App\Services\Api\Ynov\Auth\DeviceService;
use App\Services\Api\Ynov\Auth\FreezeService;
use App\Services\Api\Ynov\Auth\IpRestrictionService;
use App\Services\Api\Ynov\Auth\OtpService;
use App\Services\Api\Ynov\Auth\PasswordService;
use App\Services\Api\Ynov\Auth\TwoFactorService;
use App\Services\Api\Ynov\PermissionGroupService;
use App\Services\Api\Ynov\PermissionService;
use App\Services\Api\Ynov\RoleService;
use App\Services\Api\Ynov\UserService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Enregistrement des services métier en singleton
        $this->app->singleton(AuthService::class);
        $this->app->singleton(DeviceService::class);
        $this->app->singleton(FreezeService::class);
        $this->app->singleton(IpRestrictionService::class);
        $this->app->singleton(OtpService::class);
        $this->app->singleton(PasswordService::class);
        $this->app->singleton(TwoFactorService::class);
        $this->app->singleton(PermissionService::class);
        $this->app->singleton(PermissionGroupService::class);       
        $this->app->singleton(RoleService::class);       
        $this->app->singleton(UserService::class);       


    }
    
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Rate limiting personnalisé pour le login
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });
        Route::model('role', Role::class);
    }
}
