<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use App\Models\Api\Ynov\parameter\Permission;
use App\Models\Api\Ynov\parameter\Role;
use App\Models\Api\Ynov\parameter\SecurityQuestion;
use App\Models\Api\Ynov\parameter\User;
use App\Policies\AuditLogPolicy;
use App\Policies\PermissionPolicy;
use App\Policies\RolePolicy;
use App\Policies\SecurityQuestionPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
         User::class => UserPolicy::class,
         Role::class => RolePolicy::class,
         Permission::class => PermissionPolicy::class,
         SecurityQuestion::class => SecurityQuestionPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // ================================================================
        // Gate supplémentaire pour les permissions sensibles
        // ================================================================
        // Gestion des permissions sensibles
        Gate::define('manage-guard-permissions', function ($user) {
            return $user->hasPermission('permissions.gérer_sensibles');
        });

        // Consultation des logs d'audit
        Gate::define('view-activity-logs', function ($user, $targetUser = null) {
            if ($user->isSuperAdmin()) {
                return true;
            }

            if ($targetUser && $user->uuid_user !== $targetUser->uuid_user) {
                return (new AuditLogPolicy())->viewOther($user, $targetUser);
            }

            return $user->hasPermission('audit.consulter');
        });

        // Gestion des questions de sécurité
        Gate::define('manage-security-questions', function ($user) {
            return $user->hasPermission('security_questions.gerer');
        });

        // Réinitialisation des questions de sécurité d'un autre utilisateur
        Gate::define('reset-security-questions', function ($user, $targetUser) {
            return (new SecurityQuestionPolicy())->resetUserQuestions($user, $targetUser);
        });

        // Impersonnation
        Gate::define('impersonate', function ($user) {
            return $user->isSuperAdmin() || $user->hasPermission('users.impersonate');
        });

        // Export des logs
        Gate::define('export-audit-logs', function ($user) {
            return $user->isSuperAdmin() || $user->hasPermission('audit.exporter');
        });

        // Purge des logs
        Gate::define('purge-audit-logs', function ($user) {
            return $user->isSuperAdmin() || $user->hasPermission('audit.purger');
        });

        // Accès à l'interface d'administration de sécurité
        Gate::define('access-security-admin', function ($user) {
            return $user->isSuperAdmin() || 
                   $user->hasPermission('security_questions.gerer') ||
                   $user->hasPermission('audit.consulter');
        });
    

        //
    }
}
