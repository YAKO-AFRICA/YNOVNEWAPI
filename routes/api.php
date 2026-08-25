<?php

use App\Http\Controllers\Api\Ynov\AgenceController;
use App\Http\Controllers\Api\Ynov\AuditLogController;
use App\Http\Controllers\Api\Ynov\AuthController;
use App\Http\Controllers\Api\Ynov\DeviceController;
use App\Http\Controllers\Api\Ynov\EmailVerificationController;
use App\Http\Controllers\Api\Ynov\EspaceClient\CustomerController;
use App\Http\Controllers\Api\Ynov\FaqCategoryController;
use App\Http\Controllers\Api\Ynov\FaqController;
use App\Http\Controllers\Api\Ynov\FreezeController;
use App\Http\Controllers\Api\Ynov\IpRestrictionController;
use App\Http\Controllers\Api\Ynov\LoginAttemptController;
use App\Http\Controllers\Api\Ynov\OtpController;
use App\Http\Controllers\Api\Ynov\PartnerController;
use App\Http\Controllers\Api\Ynov\PasswordController;
use App\Http\Controllers\Api\Ynov\PermissionController;
use App\Http\Controllers\Api\Ynov\PermissionGroupController;
use App\Http\Controllers\Api\Ynov\ProfileController;
use App\Http\Controllers\Api\Ynov\ReseauController;
use App\Http\Controllers\Api\Ynov\RoleController;
use App\Http\Controllers\Api\Ynov\SecurityQuestionController;
use App\Http\Controllers\Api\Ynov\SessionController;
use App\Http\Controllers\Api\Ynov\TwoFactorController;
use App\Http\Controllers\Api\Ynov\UserController;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

/*
|--------------------------------------------------------------------------
| Routes Publiques (sans auth)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::post('auth/get-register-data', [AuthController::class, 'getRegisterData'])->middleware('throttle:6,1');
    Route::post('auth/register', [AuthController::class, 'register'])->middleware('throttle:6,1');

    Route::post('auth/forgot-password', [PasswordController::class, 'forgot'])->middleware('throttle:login');
    Route::post('auth/reset-password', [PasswordController::class, 'reset']);
    Route::post('auth/verify-email', [EmailVerificationController::class, 'verify']);
    Route::post('auth/resend-verification', [EmailVerificationController::class, 'send']);

    Route::post('auth/otp/verify-code', [OtpController::class, 'verifyOtp'])
        ->middleware('throttle:5,10');

    // Routes 2FA/OTP avec token temporaire (auth:sanctum mais pas de check status)
    Route::post('auth/2fa/verify-login', [TwoFactorController::class, 'verifyLogin'])
        ->middleware(['auth:sanctum', 'throttle:5,10']);  // 5 tentatives en 10 minutes
    Route::post('auth/2fa/verify-recovery', [TwoFactorController::class, 'verifyRecovery'])
        ->middleware('throttle:5,30');

    // Route::post('auth/otp/verify-login', [TwoFactorController::class, 'verifyOtp'])
    //     ->middleware('auth:sanctum', 'throttle:5,10');



    Route::prefix('security')->group(function () {
        Route::get('questions/suggested', [SecurityQuestionController::class, 'suggestedQuestions']);
        Route::post('verify-answer', [SecurityQuestionController::class, 'verifyAnswer'])->middleware('throttle:5,15');
        Route::get('questions', [SecurityQuestionController::class, 'getAvailableQuestions']);
        Route::post('verify-email', [SecurityQuestionController::class, 'verifyEmail'])->middleware('throttle:5,15');
    });


    Route::get('auth/freeze-check/{login}', [AuthController::class, 'freezeCheck'])
        ->middleware('throttle:30,1');


    // ============================================================
    // FAQ - Publiques
    // ============================================================
    Route::prefix('faq')->group(function () {
        // Liste des FAQs avec filtres
        Route::get('/', [FaqController::class, 'index']);
        
        // Rechercher dans les FAQs
        Route::get('search', [FaqController::class, 'search']);
        
        // Catégories de FAQs
        Route::get('categories', [FaqCategoryController::class, 'index']);
        
        // Détails d'une FAQ (incrémente les vues)
        Route::get('{uuid_faq}', [FaqController::class, 'show']);
    });
});

/*
|--------------------------------------------------------------------------
| Routes Protégées (auth + vérifications)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->middleware([
    'auth:sanctum',
    'check.account.status',
    'ip.restriction',
    'update.last.activity',
])->group(function () {

    Route::get('auth/me', [AuthController::class, 'me']);
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::post('auth/logout-all', [AuthController::class, 'logoutAll']);
    Route::post('auth/refresh', [AuthController::class, 'refresh']);
    Route::post('auth/change-password', [PasswordController::class, 'change'])
        ->middleware('permission:auth.change_password');

    // first-login avec ability:password-change
    // Le token temporaire a l'ability 'password-change', pas '*'
    Route::post('auth/first-login', [PasswordController::class, 'firstLogin'])
        ->middleware('ability:password-change');

    Route::group(['middleware' => 'permission:auth.2fa'], function () {
        // Activation 2FA
        Route::get('auth/2fa/qrcode', [TwoFactorController::class, 'enable']);
        Route::post('auth/2fa/confirm', [TwoFactorController::class, 'confirm']);
        Route::post('auth/2fa/disable', [TwoFactorController::class, 'disable']);

        // Gestion 2FA
        Route::get('auth/2fa/status', [TwoFactorController::class, 'status']);
        Route::get('auth/2fa/methods', [TwoFactorController::class, 'methods']);
        Route::post('auth/2fa/recovery-codes', [TwoFactorController::class, 'recoveryCodes']);
    });

    Route::post('auth/otp/verify', [TwoFactorController::class, 'verifyOtp'])
        ->middleware(['throttle:5,10', 'permission:auth.2fa']);

    Route::group(['middleware' => 'permission:auth.devices'], function () {
        Route::get('auth/devices', [DeviceController::class, 'index']);
        Route::post('auth/devices/{uuidDevice}/trust', [DeviceController::class, 'trust']);
        Route::delete('auth/devices/{uuidDevice}', [DeviceController::class, 'revoke']);
    });

    Route::group(['middleware' => 'permission:auth.sessions'], function () {
        Route::get('auth/sessions', [SessionController::class, 'index']);
        Route::delete('auth/sessions/{tokenId}', [SessionController::class, 'revoke']);
    });

    Route::group(['middleware' => 'permission:auth.login_attempts'], function () {
        Route::get('auth/login-attempts', [LoginAttemptController::class, 'index']);
    });

    Route::get('profile', [ProfileController::class, 'show']);
    Route::put('profile', [ProfileController::class, 'update']);
    Route::delete('profile/photo', [ProfileController::class, 'deletePhoto']);

    Route::group(['middleware' => 'permission:users.afficher'], function () {
        Route::get('users', [UserController::class, 'index']);
        Route::get('users/{uuid_user}', [UserController::class, 'show']);
    });

    Route::prefix('users/{uuid}')->group(function () {
        Route::post('freeze', [FreezeController::class, 'freeze'])->middleware('permission:users.geler');
        Route::group(['middleware' => 'permission:users.degeler'], function () {
            Route::post('unfreeze', [FreezeController::class, 'unfreeze']);
            Route::get('freeze-status', [FreezeController::class, 'status']);
        });
    });



    Route::post('users', [UserController::class, 'store'])->middleware('permission:users.creer');
    Route::put('users/{uuid_user}', [UserController::class, 'update'])->middleware('permission:users.modifier');
    Route::delete('users/{uuid_user}', [UserController::class, 'destroy'])->middleware('permission:users.supprimer');

    Route::group(['middleware' => 'permission:users.bloquer'], function () {
        Route::post('users/{uuid_user}/block', [UserController::class, 'block']);
        Route::post('users/{uuid_user}/unblock', [UserController::class, 'unblock']);
    });

    Route::group(['middleware' => 'permission:roles.afficher'], function () {
        Route::get('roles', [RoleController::class, 'index']);
        Route::get('roles/{uuid_role}', [RoleController::class, 'show']);
        Route::get('roles/{uuid_role}/users', [RoleController::class, 'users']);
    });

    Route::post('roles', [RoleController::class, 'store'])->middleware('permission:roles.creer');
    Route::put('roles/{uuid_role}', [RoleController::class, 'update'])->middleware('permission:roles.modifier');
    Route::delete('roles/{uuid_role}', [RoleController::class, 'destroy'])->middleware('permission:roles.supprimer');
    Route::post('roles/{uuid_role}/permissions', [RoleController::class, 'assignPermissions'])->middleware('permission:roles.gerer_permissions');

    Route::get('permissions/suggested-actions', [PermissionController::class, 'suggestedActions'])->middleware('permission:permissions.afficher');

    Route::group(['middleware' => 'permission:permission_groups.afficher'], function () {
        Route::get('permission-groups', [PermissionGroupController::class, 'index']);
        Route::get('permission-groups/{uuid_permissionGroup}', [PermissionGroupController::class, 'show']);
    });

    Route::post('permission-groups', [PermissionGroupController::class, 'store'])->middleware('permission:permission_groups.creer');
    Route::put('permission-groups/{uuid_permissionGroup}', [PermissionGroupController::class, 'update'])->middleware('permission:permission_groups.modifier');
    Route::delete('permission-groups/{uuid_permissionGroup}', [PermissionGroupController::class, 'destroy'])->middleware('permission:permission_groups.supprimer');

    Route::group(['middleware' => 'permission:permissions.afficher'], function () {
        Route::get('permissions', [PermissionController::class, 'index']);
        Route::get('permissions/{uuid_permission}', [PermissionController::class, 'show']);
    });

    Route::post('permissions', [PermissionController::class, 'store'])->middleware('permission:permissions.creer');
    Route::put('permissions/{uuid_permission}', [PermissionController::class, 'update'])->middleware('permission:permissions.modifier');
    Route::delete('permissions/{uuid_permission}', [PermissionController::class, 'destroy'])->middleware('permission:permissions.supprimer');

    Route::group(['middleware' => 'permission:ip_restrictions.afficher'], function () {
        Route::get('ip-restrictions', [IpRestrictionController::class, 'index']);
    });

    Route::post('ip-restrictions', [IpRestrictionController::class, 'store'])->middleware('permission:ip_restrictions.creer');
    Route::delete('ip-restrictions/{uuid_restriction}', [IpRestrictionController::class, 'destroy'])->middleware('permission:ip_restrictions.supprimer');

    Route::group(['middleware' => 'permission.any:users.afficher,users.creer,users.modifier'], function () {
        Route::get('users/search', [UserController::class, 'search']);
        Route::get('users/export', [UserController::class, 'export']);
    });

    Route::group(['middleware' => 'permission.all:users.creer,users.modifier,users.afficher'], function () {
        Route::post('users/bulk', [UserController::class, 'bulkCreate']);
        Route::put('users/bulk', [UserController::class, 'bulkUpdate']);
    });

    //================================================================
    // NOUVEAU : Routes de questions de sécurité (authentifiées)
    // ================================================================
    Route::prefix('security')->group(function () {
        // Route::get('questions', [SecurityQuestionController::class, 'getAvailableQuestions']);
        Route::get('user-questions', [SecurityQuestionController::class, 'getUserQuestions']);
        Route::post('user-questions', [SecurityQuestionController::class, 'setUserQuestions']);
    });

    // ================================================================
    // NOUVEAU : Routes admin des questions de sécurité
    // ================================================================
    Route::prefix('admin/security')->middleware('permission:security_questions.gerer')->group(function () {
        Route::post('questions', [SecurityQuestionController::class, 'createQuestion']);
        Route::put('questions/{uuid}', [SecurityQuestionController::class, 'updateQuestion']);
        Route::delete('questions/{uuid}', [SecurityQuestionController::class, 'deleteQuestion']);
    });


    Route::prefix('audit')->group(function () {
        // Mes logs personnels
        Route::get('my-activity', [AuditLogController::class, 'getMyActivityLogs']);
        Route::get('my-activity/stats', [AuditLogController::class, 'getActivityStats']);

        // Admin : logs des utilisateurs
        Route::middleware('permission:audit.consulter_les_logs')->group(function () {
            Route::get('activity', [AuditLogController::class, 'getAllActivityLogs']);
            Route::get('activity/user/{uuid_user}', [AuditLogController::class, 'getUserActivityLogs']);
            Route::get('freeze-logs', [AuditLogController::class, 'getFreezeLogs']);
            Route::get('stats', [AuditLogController::class, 'getActivityStats']);
        });
    });

    // ============================================================
    // PARTENAIRES
    // ============================================================
    Route::group(['middleware' => 'permission:partners.afficher'], function () {
        Route::get('partners', [PartnerController::class, 'index']);
        Route::get('partners/{uuid_partner}', [PartnerController::class, 'show']);
        Route::get('partners/{uuid_partner}/reseaux', [PartnerController::class, 'reseaux']);
    });

    Route::post('partners', [PartnerController::class, 'store'])->middleware('permission:partners.creer');
    Route::put('partners/{uuid_partner}', [PartnerController::class, 'update'])->middleware('permission:partners.modifier');
    Route::delete('partners/{uuid_partner}', [PartnerController::class, 'destroy'])->middleware('permission:partners.supprimer');

    // ============================================================
    // RESEAUX
    // ============================================================
    Route::group(['middleware' => 'permission:reseaux.afficher'], function () {
        Route::get('reseaux', [ReseauController::class, 'index']);
        Route::get('reseaux/{uuid_reseau}', [ReseauController::class, 'show']);
        Route::get('reseaux/{uuid_reseau}/agences', [ReseauController::class, 'agences']);
    });

    Route::post('reseaux', [ReseauController::class, 'store'])->middleware('permission:reseaux.creer');
    Route::put('reseaux/{uuid_reseau}', [ReseauController::class, 'update'])->middleware('permission:reseaux.modifier');
    Route::delete('reseaux/{uuid_reseau}', [ReseauController::class, 'destroy'])->middleware('permission:reseaux.supprimer');

    // ============================================================
    // AGENCES
    // ============================================================
    Route::group(['middleware' => 'permission:agences.afficher'], function () {
        Route::get('agences', [AgenceController::class, 'index']);
        Route::get('agences/{uuid_agence}', [AgenceController::class, 'show']);
        // nearby : Récupérer les agences proches (géolocalisation)
        Route::get('agences/nearby', [AgenceController::class, 'nearby']);
        Route::get('agences/{uuid_agence}/horaires', [AgenceController::class, 'horaires']);
    });

    Route::post('agences', [AgenceController::class, 'store'])->middleware('permission:agences.creer');
    Route::put('agences/{uuid_agence}', [AgenceController::class, 'update'])->middleware('permission:agences.modifier');
    Route::delete('agences/{uuid_agence}', [AgenceController::class, 'destroy'])->middleware('permission:agences.supprimer');

    Route::group(['middleware' => 'permission:agences.assigner_utilisateurs'], function () {
        Route::post('agences/{uuid_agence}/users', [AgenceController::class, 'assignUsers']);
        Route::delete('agences/{uuid_agence}/users/{uuid_user}', [AgenceController::class, 'removeUser']);
    });


    // ============================================================
    // FAQ - Admin
    // ============================================================
    Route::prefix('admin/faq')->group(function () {
        
        // ============================================================
        // Gestion des FAQs
        // ============================================================
        Route::post('/', [FaqController::class, 'store'])
            ->middleware('permission:faqs.creer');
        
        Route::put('{uuid_faq}', [FaqController::class, 'update'])
            ->middleware('permission:faqs.modifier');
        
        Route::delete('{uuid_faq}', [FaqController::class, 'destroy'])
            ->middleware('permission:faqs.supprimer');
        
        Route::post('{uuid_faq}/toggle', [FaqController::class, 'toggle'])
            ->middleware('permission:faqs.modifier');
        
        // ============================================================
        // Gestion des Catégories
        // ============================================================
        // Liste complète des catégories (admin)
        Route::get('categories', [FaqCategoryController::class, 'index'])
            ->middleware('permission:faq_categories.afficher');
        
        // Catégories pour select (dropdown)
        Route::get('categories/select', [FaqCategoryController::class, 'forSelect'])
            ->middleware('permission:faq_categories.afficher');
        
        // Statistiques des catégories
        Route::get('categories/stats', [FaqCategoryController::class, 'stats'])
            ->middleware('permission:faq_categories.afficher');
        
        // Détails d'une catégorie
        Route::get('categories/{uuid_faq_category}', [FaqCategoryController::class, 'show'])
            ->middleware('permission:faq_categories.afficher');
        
        // Créer une catégorie
        Route::post('categories', [FaqCategoryController::class, 'store'])
            ->middleware('permission:faq_categories.creer');
        
        // Mettre à jour une catégorie
        Route::put('categories/{uuid_faq_category}', [FaqCategoryController::class, 'update'])
            ->middleware('permission:faq_categories.modifier');
        
        // Supprimer une catégorie
        Route::delete('categories/{uuid_faq_category}', [FaqCategoryController::class, 'destroy'])
            ->middleware('permission:faq_categories.supprimer');
        
        // Activer/Désactiver une catégorie
        Route::post('categories/{uuid_faq_category}/toggle', [FaqCategoryController::class, 'toggle'])
            ->middleware('permission:faq_categories.modifier');
        
        // Réordonner les catégories
        Route::post('categories/reorder', [FaqCategoryController::class, 'reorder'])
            ->middleware('permission:faq_categories.modifier');
        
        // Dupliquer une catégorie
        Route::post('categories/{uuid_faq_category}/duplicate', [FaqCategoryController::class, 'duplicate'])
            ->middleware('permission:faq_categories.creer');
    });


    // Route protégée espaces client
    Route::prefix('espaces-client')->group(function () {
        Route::get('dashboard', [CustomerController::class, 'index']);
    });
});

// use App\Http\Controllers\Api\Ynov\AuditLogController;
// use App\Http\Controllers\Api\Ynov\AuthController;
// use App\Http\Controllers\Api\Ynov\DeviceController;
// use App\Http\Controllers\Api\Ynov\EmailVerificationController;
// use App\Http\Controllers\Api\Ynov\FreezeController;
// use App\Http\Controllers\Api\Ynov\IpRestrictionController;
// use App\Http\Controllers\Api\Ynov\LoginAttemptController;
// use App\Http\Controllers\Api\Ynov\PasswordController;
// use App\Http\Controllers\Api\Ynov\PermissionController;
// use App\Http\Controllers\Api\Ynov\PermissionGroupController;
// use App\Http\Controllers\Api\Ynov\ProfileController;
// use App\Http\Controllers\Api\Ynov\RoleController;
// use App\Http\Controllers\Api\Ynov\SecurityQuestionController;
// use App\Http\Controllers\Api\Ynov\SessionController;
// use App\Http\Controllers\Api\Ynov\TwoFactorController;
// use App\Http\Controllers\Api\Ynov\UserController;
// use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| Routes Publiques (sans auth)
|--------------------------------------------------------------------------
*/

// Route::prefix('v1')->group(function () {
//     Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:login');
//     Route::post('auth/register', [AuthController::class, 'register'])->middleware('throttle:6,1');

//     Route::get('security/questions/suggested', [SecurityQuestionController::class, 'suggestedQuestions']);
//     Route::post('security/verify-answer', [SecurityQuestionController::class, 'verifyAnswer'])
//         ->middleware('throttle:5,15');

//     Route::post('security/verify-email', [SecurityQuestionController::class, 'verifyEmail'])->middleware('throttle:5,15');

//     // ================================================================
//     // CORRECTION #8 : Rate limiting spécifique sur forgot-password
//     // ================================================================
//     Route::post('auth/forgot-password', [PasswordController::class, 'forgot'])->middleware('throttle:5,15');
//     Route::post('auth/reset-password', [PasswordController::class, 'reset']);
//     Route::post('auth/verify-email', [EmailVerificationController::class, 'verify']);
//     Route::post('auth/resend-verification', [EmailVerificationController::class, 'send']);

//     // ================================================================
//     // CORRECTION #4 : Ajout des middlewares de statut et IP sur les routes 2FA/OTP
//     // ================================================================
//     Route::post('auth/2fa/verify-login', [TwoFactorController::class, 'verifyLogin'])
//         ->middleware(['auth:sanctum', 'check.account.status', 'ip.restriction', 'throttle:5,10']);

//     Route::post('auth/otp/verify-login', [TwoFactorController::class, 'verifyOtp'])
//         ->middleware(['auth:sanctum', 'check.account.status', 'ip.restriction', 'throttle:5,10']);

//     Route::get('auth/freeze-check/{login}', [AuthController::class, 'freezeCheck'])
//         ->middleware('throttle:30,1');
// });

// /*
// |--------------------------------------------------------------------------
// | Routes Protégées (auth + vérifications)
// |--------------------------------------------------------------------------
// */

// Route::prefix('v1')->middleware([
//     'auth:sanctum',
//     'check.account.status',
//     'ip.restriction',
//     'update.last.activity',
//     'check.password.expiration',
// ])->group(function () {

//     // ================================================================
//     // Routes d'authentification de base
//     // ================================================================
//     Route::get('auth/me', [AuthController::class, 'me']);
//     Route::post('auth/logout', [AuthController::class, 'logout']);
//     Route::post('auth/logout-all', [AuthController::class, 'logoutAll']);
//     Route::post('auth/refresh', [AuthController::class, 'refresh']);

//     // ================================================================
//     // Routes de gestion des mots de passe
//     // ================================================================
//     Route::post('auth/change-password', [PasswordController::class, 'change'])
//         ->middleware('permission:auth.change_password');

//     Route::post('auth/first-login', [PasswordController::class, 'firstLogin'])
//         ->middleware('ability:password-change');

//     // ================================================================
//     // Routes 2FA
//     // ================================================================
//     Route::group(['middleware' => 'permission:auth.2fa'], function () {
//         Route::get('auth/2fa/qrcode', [TwoFactorController::class, 'enable']);
//         Route::post('auth/2fa/confirm', [TwoFactorController::class, 'confirm']);
//         Route::post('auth/2fa/disable', [TwoFactorController::class, 'disable']);
//         Route::post('auth/2fa/verify', [TwoFactorController::class, 'verifyLogin']);
//         Route::post('auth/otp/send', [TwoFactorController::class, 'sendOtp']);
//         Route::post('auth/otp/verify', [TwoFactorController::class, 'verifyOtp']);
//     });

//     // ================================================================
//     // Routes de gestion des appareils
//     // ================================================================
//     Route::group(['middleware' => 'permission:auth.devices'], function () {
//         Route::get('auth/devices', [DeviceController::class, 'index']);
//         Route::post('auth/devices/{uuidDevice}/trust', [DeviceController::class, 'trust']);
//         Route::delete('auth/devices/{uuidDevice}', [DeviceController::class, 'revoke']);
//     });

//     // ================================================================
//     // Routes de gestion des sessions
//     // ================================================================
//     Route::group(['middleware' => 'permission:auth.sessions'], function () {
//         Route::get('auth/sessions', [SessionController::class, 'index']);
//         Route::delete('auth/sessions/{tokenId}', [SessionController::class, 'revoke']);
//     });

//     // ================================================================
//     // Routes de journalisation des tentatives
//     // ================================================================
//     Route::group(['middleware' => 'permission:auth.login_attempts'], function () {
//         Route::get('auth/login-attempts', [LoginAttemptController::class, 'index']);
//     });

//     // ================================================================
//     // Routes de profil
//     // ================================================================
//     Route::get('profile', [ProfileController::class, 'show']);
//     Route::put('profile', [ProfileController::class, 'update']);

//     // ================================================================
//     // Routes utilisateurs (CRUD complet avec middlewares de permission)
//     // ================================================================
//     Route::group(['middleware' => 'permission:users.afficher'], function () {
//         Route::get('users', [UserController::class, 'index']);
//         Route::get('users/{uuid_user}', [UserController::class, 'show']);
//     });

//     Route::post('users', [UserController::class, 'store'])->middleware('permission:users.creer');
//     Route::put('users/{uuid_user}', [UserController::class, 'update'])->middleware('permission:users.modifier');
//     Route::delete('users/{uuid_user}', [UserController::class, 'destroy'])->middleware('permission:users.supprimer');

//     // ================================================================
//     // Routes de blocage/déblocage
//     // ================================================================
//     Route::group(['middleware' => 'permission:users.bloquer'], function () {
//         Route::post('users/{uuid_user}/block', [UserController::class, 'block']);
//         Route::post('users/{uuid_user}/unblock', [UserController::class, 'unblock']);
//     });

//     // ================================================================
//     // Routes de gel/dégel
//     // ================================================================
//     Route::prefix('users/{uuid}')->group(function () {
//         Route::post('freeze', [FreezeController::class, 'freeze'])->middleware('permission:users.geler');
//         Route::group(['middleware' => 'permission:users.degeler'], function () {
//             Route::post('unfreeze', [FreezeController::class, 'unfreeze']);
//             Route::get('freeze-status', [FreezeController::class, 'status']);
//         });
//     });

//     // ================================================================
//     // Routes de recherche et export
//     // ================================================================
//     Route::group(['middleware' => 'permission.any:users.afficher,users.creer,users.modifier'], function () {
//         Route::get('users/search', [UserController::class, 'search']);
//         Route::get('users/export', [UserController::class, 'export']);
//     });

//     // ================================================================
//     // Routes de création/mise à jour en masse
//     // ================================================================
//     Route::group(['middleware' => 'permission.all:users.creer,users.modifier,users.afficher'], function () {
//         Route::post('users/bulk', [UserController::class, 'bulkCreate']);
//         Route::put('users/bulk', [UserController::class, 'bulkUpdate']);
//     });

//     // ================================================================
//     // Routes Rôles
//     // ================================================================
//     Route::group(['middleware' => 'permission:roles.afficher'], function () {
//         Route::get('roles', [RoleController::class, 'index']);
//         Route::get('roles/{uuid_role}', [RoleController::class, 'show']);
//         Route::get('roles/{uuid_role}/users', [RoleController::class, 'users']);
//     });

//     Route::post('roles', [RoleController::class, 'store'])->middleware('permission:roles.creer');
//     Route::put('roles/{uuid_role}', [RoleController::class, 'update'])->middleware('permission:roles.modifier');
//     Route::delete('roles/{uuid_role}', [RoleController::class, 'destroy'])->middleware('permission:roles.supprimer');
//     Route::post('roles/{uuid_role}/permissions', [RoleController::class, 'assignPermissions'])->middleware('permission:roles.gerer_permissions');

//     // ================================================================
//     // Routes Permissions
//     // ================================================================
//     Route::get('permissions/suggested-actions', [PermissionController::class, 'suggestedActions'])
//         ->middleware('permission:permissions.afficher');

//     Route::group(['middleware' => 'permission:permissions.afficher'], function () {
//         Route::get('permissions', [PermissionController::class, 'index']);
//         Route::get('permissions/{uuid_permission}', [PermissionController::class, 'show']);
//         Route::get('permissions/all-with-groups', [PermissionController::class, 'allWithGroups']);
//         Route::get('permissions/user-permissions', [PermissionController::class, 'userPermissions']);
//     });

//     Route::post('permissions', [PermissionController::class, 'store'])->middleware('permission:permissions.creer');
//     Route::put('permissions/{uuid_permission}', [PermissionController::class, 'update'])->middleware('permission:permissions.modifier');
//     Route::delete('permissions/{uuid_permission}', [PermissionController::class, 'destroy'])->middleware('permission:permissions.supprimer');

//     // ================================================================
//     // Routes Groupes de Permissions
//     // ================================================================
//     Route::group(['middleware' => 'permission:permission_groups.afficher'], function () {
//         Route::get('permission-groups', [PermissionGroupController::class, 'index']);
//         Route::get('permission-groups/{uuid_permissionGroup}', [PermissionGroupController::class, 'show']);
//     });

//     Route::post('permission-groups', [PermissionGroupController::class, 'store'])->middleware('permission:permission_groups.creer');
//     Route::put('permission-groups/{uuid_permissionGroup}', [PermissionGroupController::class, 'update'])->middleware('permission:permission_groups.modifier');
//     Route::delete('permission-groups/{uuid_permissionGroup}', [PermissionGroupController::class, 'destroy'])->middleware('permission:permission_groups.supprimer');

//     // ================================================================
//     // Routes IP Restrictions
//     // ================================================================
//     Route::group(['middleware' => 'permission:ip_restrictions.afficher'], function () {
//         Route::get('ip-restrictions', [IpRestrictionController::class, 'index']);
//     });

//     Route::post('ip-restrictions', [IpRestrictionController::class, 'store'])->middleware('permission:ip_restrictions.creer');
//     Route::delete('ip-restrictions/{uuid_restriction}', [IpRestrictionController::class, 'destroy'])->middleware('permission:ip_restrictions.supprimer');

//     // ================================================================
//     // NOUVEAU : Routes de questions de sécurité (authentifiées)
//     // ================================================================
//     Route::prefix('security')->group(function () {
//         Route::get('questions', [SecurityQuestionController::class, 'getAvailableQuestions']);
//         Route::get('user-questions', [SecurityQuestionController::class, 'getUserQuestions']);
//         Route::post('user-questions', [SecurityQuestionController::class, 'setUserQuestions']);
//     });

//     // ================================================================
//     // NOUVEAU : Routes admin des questions de sécurité
//     // ================================================================
//     Route::prefix('admin/security')->middleware('permission:security_questions.gerer')->group(function () {
//         Route::post('questions', [SecurityQuestionController::class, 'createQuestion']);
//         Route::put('questions/{uuid}', [SecurityQuestionController::class, 'updateQuestion']);
//         Route::delete('questions/{uuid}', [SecurityQuestionController::class, 'deleteQuestion']);
//     });
    

//     Route::prefix('audit')->group(function () {
//         // Mes logs personnels
//         Route::get('my-activity', [AuditLogController::class, 'getMyActivityLogs']);
//         Route::get('my-activity/stats', [AuditLogController::class, 'getActivityStats']);
        
//         // Admin : logs des utilisateurs
//         Route::middleware('permission:audit.consulter_les_logs')->group(function () {
//             Route::get('activity', [AuditLogController::class, 'getAllActivityLogs']);
//             Route::get('activity/user/{uuid_user}', [AuditLogController::class, 'getUserActivityLogs']);
//             Route::get('freeze-logs', [AuditLogController::class, 'getFreezeLogs']);
//             Route::get('stats', [AuditLogController::class, 'getActivityStats']);
//         });
//     });
// });