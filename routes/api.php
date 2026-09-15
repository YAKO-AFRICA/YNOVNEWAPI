<?php

use App\Http\Controllers\Api\Ynov\AgenceController;
use App\Http\Controllers\Api\Ynov\AuditLogController;
use App\Http\Controllers\Api\Ynov\AuthController;
use App\Http\Controllers\Api\Ynov\Rdv\BordereauController;
use App\Http\Controllers\Api\Ynov\DeviceController;
use App\Http\Controllers\Api\Ynov\EmailVerificationController;
use App\Http\Controllers\Api\Ynov\EspaceClient\CustomerController;
use App\Http\Controllers\Api\Ynov\FaqCategoryController;
use App\Http\Controllers\Api\Ynov\FaqController;
use App\Http\Controllers\Api\Ynov\FreezeController;
use App\Http\Controllers\Api\Ynov\GroupNotifController;
use App\Http\Controllers\Api\Ynov\IpRestrictionController;
use App\Http\Controllers\Api\Ynov\JourFerieController;
use App\Http\Controllers\Api\Ynov\LoginAttemptController;
use App\Http\Controllers\Api\Ynov\MotifTraitementController;
use App\Http\Controllers\Api\Ynov\NotificationController;
use App\Http\Controllers\Api\Ynov\OtpController;
use App\Http\Controllers\Api\Ynov\PartnerController;
use App\Http\Controllers\Api\Ynov\PasswordController;
use App\Http\Controllers\Api\Ynov\PaymentController;
use App\Http\Controllers\Api\Ynov\PermissionController;
use App\Http\Controllers\Api\Ynov\PermissionGroupController;
use App\Http\Controllers\Api\Ynov\PrestationController;
use App\Http\Controllers\Api\Ynov\ProduitController;
use App\Http\Controllers\Api\Ynov\ProfileController;
use App\Http\Controllers\Api\Ynov\Rdv\CalendrierController;
use App\Http\Controllers\Api\Ynov\Rdv\DashboardController;
use App\Http\Controllers\Api\Ynov\Rdv\RdvController;
use App\Http\Controllers\Api\Ynov\Rdv\RoutingController;
use App\Http\Controllers\Api\Ynov\Rdv\TraitementController;
use App\Http\Controllers\Api\Ynov\ReseauController;
use App\Http\Controllers\Api\Ynov\RoleController;
use App\Http\Controllers\Api\Ynov\SecurityQuestionController;
use App\Http\Controllers\Api\Ynov\SessionController;
use App\Http\Controllers\Api\Ynov\TwoFactorController;
use App\Http\Controllers\Api\Ynov\TypeProduitController;
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

    Route::prefix('paiements/jeko')->group(function () {
        // Widget JS
        Route::get('jeko-payment-widget.js', [PaymentController::class, 'jekoPaymentWidget']);

        // Vérification du contrat
        Route::post('contrat/verifier', [PaymentController::class, 'verifierContrat']);

        // Initialisation du paiement
        Route::post('init', [PaymentController::class, 'initierPaiement']);

        // Webhook
        Route::post('webhook', [PaymentController::class, 'webhook']);
    });

    Route::prefix('/rdvs/auto')->group(function () {
        // Assignation automatique des RDV (appelé par front-end 3 min après création)
        Route::post('assign', [RoutingController::class, 'autoAssign']);
        
        // Gestion des RDV expirés (appelé par front-end tous les jours)
        Route::post('expires', [RoutingController::class, 'gererExpires']);
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

    // Gestion des agences pour un utilisateur
    Route::group(['prefix' => 'users/{uuid_user}/agences', 'middleware' => 'permission:agences.assigner_utilisateurs'], function () {
        Route::get('/', [UserController::class, 'getAgences']);
        Route::post('/', [UserController::class, 'assignAgences']);
        Route::put('/', [UserController::class, 'syncAgences']);
        Route::patch('/primary', [UserController::class, 'setPrimaryAgence']);
        Route::delete('/{uuid_agence}', [UserController::class, 'removeAgence']);
    });

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

    Route::prefix('motif-traitements')->group(function () {
        Route::get('/', [MotifTraitementController::class, 'index'])
            ->middleware('permission:motif_traitements.afficher');

        Route::get('actives', [MotifTraitementController::class, 'actives'])
            ->middleware('permission:motif_traitements.afficher');

        Route::get('types/suggested', [MotifTraitementController::class, 'suggestedTypes'])
            ->middleware('permission:motif_traitements.afficher');

        Route::get('{uuid}', [MotifTraitementController::class, 'show'])
            ->middleware('permission:motif_traitements.afficher');

        Route::post('/', [MotifTraitementController::class, 'store'])
            ->middleware('permission:motif_traitements.creer');

        Route::put('{uuid}', [MotifTraitementController::class, 'update'])
            ->middleware('permission:motif_traitements.modifier');

        Route::patch('{uuid}/toggle', [MotifTraitementController::class, 'toggle'])
            ->middleware('permission:motif_traitements.modifier');

        Route::delete('{uuid}', [MotifTraitementController::class, 'destroy'])
            ->middleware('permission:motif_traitements.supprimer');
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


    // ============================================================
    // GROUPES DE NOTIFICATION
    // ============================================================
    Route::prefix('group-notifs')->group(function () {
        // Mes groupes (utilisateur connecté)
        Route::get('my-groups', [GroupNotifController::class, 'myGroups']);
        
        // Canaux disponibles
        Route::get('channels', [GroupNotifController::class, 'channels']);
        
        // Définir mon groupe principal
        Route::post('{uuid_group_notif}/set-primary', [GroupNotifController::class, 'setPrimaryGroup']);
    });

    // ============================================================
    // ADMIN - GROUPES DE NOTIFICATION
    // ============================================================
    Route::prefix('admin/group-notifs') ->group(function () {
        // Liste et détails
        Route::get('/', [GroupNotifController::class, 'index'])
            ->middleware('permission:group_notifs.afficher');

        Route::get('stats', [GroupNotifController::class, 'stats'])
            ->middleware('permission:group_notifs.afficher');

        Route::get('{uuid_group_notif}', [GroupNotifController::class, 'show'])
            ->middleware('permission:group_notifs.afficher');

        // CRUD
        Route::post('/', [GroupNotifController::class, 'store'])
            ->middleware('permission:group_notifs.creer');

        Route::put('{uuid_group_notif}', [GroupNotifController::class, 'update'])
            ->middleware('permission:group_notifs.modifier');

        Route::delete('{uuid_group_notif}', [GroupNotifController::class, 'destroy'])
            ->middleware('permission:group_notifs.supprimer');

        // Duplication
        Route::post('{uuid_group_notif}/duplicate', [GroupNotifController::class, 'duplicate'])
            ->middleware('permission:group_notifs.creer');

        // Gestion des utilisateurs
        Route::post('{uuid_group_notif}/users', [GroupNotifController::class, 'assignUsers'])
            ->middleware('permission:group_notifs.assigner');

        Route::delete('{uuid_group_notif}/users/{uuid_user}', [GroupNotifController::class, 'removeUser'])
            ->middleware('permission:group_notifs.assigner');
    });
    // ============================================================
    // NOTIFICATIONS
    // ============================================================
    Route::prefix('notifications')->group(function () {
        // Liste des notifications
        Route::get('/', [NotificationController::class, 'index']);
        
        // Nombre de notifications non lues
        Route::get('unread-count', [NotificationController::class, 'unreadCount']);
        
        // Marquer toutes comme lues
        Route::post('mark-all-read', [NotificationController::class, 'markAllAsRead']);
        
        // Actions sur une notification spécifique
        Route::prefix('{uuid_notification}')->group(function () {
            Route::post('read', [NotificationController::class, 'markAsRead']);
            Route::post('important', [NotificationController::class, 'markAsImportant']);
            Route::post('unimportant', [NotificationController::class, 'unmarkImportant']);
            Route::delete('/', [NotificationController::class, 'destroy']);
        });
    });

    // ============================================================
    // ADMIN - NOTIFICATIONS
    // ============================================================
    Route::prefix('admin/notifications')->group(function () {
            Route::post('/', [NotificationController::class, 'create'])
                ->middleware('permission:notifications.creer');
            
            Route::post('group', [NotificationController::class, 'createForGroup'])
                ->middleware('permission:notifications.creer');
    });


    // ============================================================
    // TYPES DE PRODUITS
    // ============================================================
    Route::prefix('type-produits')->group(function () {
        Route::get('/', [TypeProduitController::class, 'index'])
            ->middleware('permission:produits.afficher');
        
        Route::get('select', [TypeProduitController::class, 'select'])
            ->middleware('permission:produits.afficher');
        
        Route::get('{uuid_type_produit}', [TypeProduitController::class, 'show'])
            ->middleware('permission:produits.afficher');
        
        Route::post('/', [TypeProduitController::class, 'store'])
            ->middleware('permission:produits.creer');
        
        Route::put('{uuid_type_produit}', [TypeProduitController::class, 'update'])
            ->middleware('permission:produits.modifier');
        
        Route::delete('{uuid_type_produit}', [TypeProduitController::class, 'destroy'])
            ->middleware('permission:produits.supprimer');
    });

    // ============================================================
    // PRODUITS
    // ============================================================
    Route::prefix('produits')->group(function () {
        // Liste et détails
        Route::get('/', [ProduitController::class, 'index'])
            ->middleware('permission:produits.afficher');
        
        Route::get('stats', [ProduitController::class, 'stats'])
            ->middleware('permission:produits.afficher');
        
        Route::get('{uuid_produit}', [ProduitController::class, 'show'])
            ->middleware('permission:produits.afficher');
        
        // CRUD
        Route::post('/', [ProduitController::class, 'store'])
            ->middleware('permission:produits.creer');
        
        Route::put('{uuid_produit}', [ProduitController::class, 'update'])
            ->middleware('permission:produits.modifier');
        
        Route::delete('{uuid_produit}', [ProduitController::class, 'destroy'])
            ->middleware('permission:produits.supprimer');
        
        // Formules
        Route::get('{uuid_produit}/formules', [ProduitController::class, 'getFormules'])
            ->middleware('permission:produits.afficher');
        
        Route::post('{uuid_produit}/formules', [ProduitController::class, 'storeFormule'])
            ->middleware('permission:produits.creer');
        
        Route::put('formules/{uuid_formule}', [ProduitController::class, 'updateFormule'])
            ->middleware('permission:produits.modifier');
        
        Route::delete('formules/{uuid_formule}', [ProduitController::class, 'destroyFormule'])
            ->middleware('permission:produits.supprimer');
        
        // Prestations du produit
        Route::get('{uuid_produit}/prestations', [ProduitController::class, 'getPrestations']);
            // ->middleware('permission:produits.afficher');
        
        // Types de prestations disponibles pour le produit
        Route::get('{uuid_produit}/prestations/available', [ProduitController::class, 'availablePrestations']);
            // ->middleware('permission:produits.afficher');
        
        Route::post('{uuid_produit}/prestations', [ProduitController::class, 'assignPrestation'])
            ->middleware('permission:produits.modifier');
        
        Route::delete('prestations/{uuid_association}', [ProduitController::class, 'removePrestation'])
            ->middleware('permission:produits.modifier');
        
        // Garanties du produit
        Route::get('{uuid_produit}/garanties', [ProduitController::class, 'getGaranties'])
            ->middleware('permission:produits.afficher');
        
        Route::post('{uuid_produit}/garanties', [ProduitController::class, 'storeGarantie'])
            ->middleware('permission:produits.creer');
        
        Route::put('garanties/{uuid_produit_garantie}', [ProduitController::class, 'updateGarantie'])
            ->middleware('permission:produits.modifier');
        
        Route::delete('garanties/{uuid_produit_garantie}', [ProduitController::class, 'destroyGarantie'])
            ->middleware('permission:produits.supprimer');
    });

    // ============================================================
    // PRESTATIONS
    // ============================================================
    Route::prefix('prestations')->group(function () {
        // Catégories
        Route::get('categories', [PrestationController::class, 'categories']);
            // ->middleware('permission:prestations.afficher');
        
        Route::post('categories', [PrestationController::class, 'storeCategory'])
            ->middleware('permission:prestations.creer');
        
        Route::get('categories/{uuid_category}', [PrestationController::class, 'showCategory'])
            ->middleware('permission:prestations.afficher');
        
        Route::put('categories/{uuid_category}', [PrestationController::class, 'updateCategory'])
            ->middleware('permission:prestations.modifier');
        
        Route::delete('categories/{uuid_category}', [PrestationController::class, 'deleteCategory'])
            ->middleware('permission:prestations.supprimer');
        
        // Types de prestations
        Route::get('types', [PrestationController::class, 'types'])
            ->middleware('permission:prestations.afficher');
        
        Route::post('types', [PrestationController::class, 'storeType'])
            ->middleware('permission:prestations.creer');
        
        Route::get('types/{uuid_type}', [PrestationController::class, 'showType'])
            ->middleware('permission:prestations.afficher');
        
        Route::put('types/{uuid_type}', [PrestationController::class, 'updateType'])
            ->middleware('permission:prestations.modifier');
        
        Route::delete('types/{uuid_type}', [PrestationController::class, 'deleteType'])
            ->middleware('permission:prestations.supprimer');
        
        // Statistiques
        Route::get('stats', [PrestationController::class, 'stats'])
            ->middleware('permission:prestations.afficher');
    });

    // ============================================================
    // RENDEZ-VOUS (RDV) - CLIENT
    // ============================================================
    Route::prefix('bordereaux')->group(function () {
        Route::get('lots', [BordereauController::class, 'indexLots'])
            ->middleware('permission:rdvs.afficher');

        Route::get('details', [BordereauController::class, 'indexDetails'])
            ->middleware('permission:rdvs.afficher');

        Route::post('details/import', [BordereauController::class, 'importDetails'])
                ->middleware('permission:rdvs.import_bordereau_final');

        // Transmettre des RDV par email avec fichier Excel
        Route::post('transmettre-email-gest-prestation', [BordereauController::class, 'transmettreParEmail'])
            ->middleware('permission:rdvs.transmettre_bordereau_gest_prestation');

        // Récupérer les gestionnaires prestation
        Route::get('gestionnaires-prestation', [BordereauController::class, 'getGestionnairesPrestation'])
            ->middleware('permission:rdvs.afficher');
    });

    Route::prefix('rdvs')->group(function () {
        // Motifs disponibles
        Route::get('motifs', [RdvController::class, 'motifs'])
            ->middleware('permission:rdvs.creer');

        // Agences disponibles
        Route::get('agences', [RdvController::class, 'agences'])
            ->middleware('permission:rdvs.creer');

        // Dates disponibles
        Route::get('dates-disponibles', [RdvController::class, 'datesDisponibles'])
            ->middleware('permission:rdvs.creer');

        // Vérifier une date
        Route::post('verifier-date', [RdvController::class, 'verifierDate'])
            ->middleware('permission:rdvs.creer');

        // Liste globale des RDV avec filtres (DOIT ÊTRE AVANT LA ROUTE AVEC PARAMÈTRE)
        Route::get('list', [RdvController::class, 'getList'])->middleware('permission:rdvs.afficher');

        // Liste globale des RDV avec filtres (gestionnaire auto-appliqué si besoin)
        Route::get('clients-arrives', [RdvController::class, 'clientsArrives'])->middleware('permission:rdvs.afficher');

        // Mes rendez-vous CLIENT (connecté)
        Route::get('/', [RdvController::class, 'index']);
        Route::get('stats', [RdvController::class, 'stats']);

        // Calendrier des RDV Gestionnaire (connecté) ou Admin (tous les RDV)
        Route::get('calendrier', [CalendrierController::class, 'calendrier'])
            ->middleware('permission:rdvs.calendrier');
        Route::get('calendrier/stats', [CalendrierController::class, 'stats'])
            ->middleware('permission:rdvs.calendrier');

        // Détails d'un rendez-vous connecté (client)
        Route::get('{uuid_rdvs}', [RdvController::class, 'show']);

        // Détails d'un rendez-vous connecté (admin ou gestionnaire)
        Route::get('{uuid_rdvs}/detail-rdv', [RdvController::class, 'showDetailAdmin'])
            ->middleware('permission:rdvs.afficher');

        // Créer un rendez-vous
        Route::post('/', [RdvController::class, 'store'])
            ->middleware('permission:rdvs.creer');

        // Signaler sa présence
        Route::post('{uuid_rdvs}/signaler-presence', [RdvController::class, 'signalerPresence']);

        // Annuler un rendez-vous
        Route::post('{uuid_rdvs}/cancel', [RdvController::class, 'cancel'])->middleware('permission:rdvs.annuler');

        // Liste des rendez-vous d'une agence
        Route::get('agence/{uuid_agence}', [RdvController::class, 'agenceRdvs']);
    });

    // ============================================================
    // RENDEZ-VOUS (RDV) - TRAITEMENT
    // ============================================================
    Route::prefix('rdvs/traitement')->group(function () {
        // Transmettre/Assigner un RDV à un gestionnaire (passe automatiquement en transmis)
        // Route::post('{uuid_rdvs}/transmettre', [TraitementController::class, 'assignGestionnaire']);
        
        Route::get('get-motifs-traitement/', [MotifTraitementController::class, 'index']);

        // Recuperer les produits de tranformation pour un RDV
        Route::get('{uuid_rdvs}/produits-transformation', [RdvController::class, 'getProduitsTransformation']);

        // Rééquilibrer la charge des gestionnaires
        Route::post('reequilibrer', [RoutingController::class, 'reequilibrer']);
        
        // Réassigner un RDV manuellement
        Route::post('{uuid_rdvs}/reassigner', [RoutingController::class, 'reassigner'])->middleware('permission:rdvs.retransmettre');
        
        // Traiter un RDV (effectuer le traitement)
        Route::post('{uuid_rdvs}/traiter', [TraitementController::class, 'traiter'])->middleware('permission:rdvs.traiter');
        
        // Reporter un RDV (client n'est pas venu)
        Route::post('{uuid_rdvs}/reporter', [TraitementController::class, 'reporter'])->middleware('permission:rdvs.reporter');
        
        // Rejeter un RDV
        Route::post('{uuid_rdvs}/rejeter', [TraitementController::class, 'rejeter'])->middleware('permission:rdvs.rejeter');
        
        // Annuler un RDV (admin)
        Route::post('{uuid_rdvs}/annuler', [TraitementController::class, 'annuler'])->middleware('permission:rdvs.annuler');
        
        // Ajouter une observation/commentaire
        Route::post('{uuid_rdvs}/observation', [TraitementController::class, 'addObservation']);
        
        // Historique des traitements d'un RDV
        Route::get('{uuid_rdvs}/historique', [TraitementController::class, 'historique']);
        
        // Marquer comme expiré
        Route::post('{uuid_rdvs}/expirer', [TraitementController::class, 'expirer'])->middleware('permission:rdvs.expirer');

        // Liste des garanties d'un produit (pour le traitement des RDV)
        Route::get('produits/{uuid_produit}/garanties', [ProduitController::class, 'getGaranties']);
    });


    // Tableau de bord
    Route::prefix('dashboard')->group(function () {
        Route::get('/', [DashboardController::class, 'dashboard']);
        Route::get('stats', [DashboardController::class, 'stats']);
        Route::get('stats/motif', [DashboardController::class, 'statsByMotif']);
        Route::get('stats/gestionnaire', [DashboardController::class, 'statsByGestionnaire']);
        Route::get('stats/agence', [DashboardController::class, 'statsByAgence']);
        
        // Dashboard global uniquement (statistiques, file d'attente...)
    });

    // // ============================================================
    // // JOURS FÉRIÉS
    // // ============================================================
    // Route::prefix('jour-feries')->group(function () {
    //     // Liste et détails
    //     Route::get('/', [JourFerieController::class, 'index'])
    //         ->middleware('permission:jour_feries.afficher');
        
    //     Route::get('stats', [JourFerieController::class, 'stats'])
    //         ->middleware('permission:jour_feries.afficher');
        
    //     Route::get('{uuid_jour_ferie}', [JourFerieController::class, 'show'])
    //         ->middleware('permission:jour_feries.afficher');
        
    //     // CRUD
    //     Route::post('/', [JourFerieController::class, 'store'])
    //         ->middleware('permission:jour_feries.creer');
        
    //     Route::put('{uuid_jour_ferie}', [JourFerieController::class, 'update'])
    //         ->middleware('permission:jour_feries.modifier');
        
    //     Route::delete('{uuid_jour_ferie}', [JourFerieController::class, 'destroy'])
    //         ->middleware('permission:jour_feries.supprimer');
        
    //     // Utilitaires
    //     Route::post('verifier', [JourFerieController::class, 'verifier'])
    //         ->middleware('permission:jour_feries.afficher');
        
    //     Route::get('annee/{year}', [JourFerieController::class, 'annee'])
    //         ->middleware('permission:jour_feries.afficher');
        
    //     Route::get('prochains-jours-ouvres', [JourFerieController::class, 'prochainsJoursOuvres'])
    //         ->middleware('permission:jour_feries.afficher');
    // });

    // ============================================================
    // JOURS FÉRIÉS
    // ============================================================
    Route::prefix('jour-feries')->group(function () {
        
        // ============================================================
        // ROUTES SPÉCIFIQUES (DOIVENT ÊTRE AVANT LA ROUTE AVEC PARAMÈTRE)
        // ============================================================
        
        // Utilitaires - routes sans paramètre
        Route::get('stats', [JourFerieController::class, 'stats'])
            ->middleware('permission:jour_feries.afficher');
        
        Route::post('verifier', [JourFerieController::class, 'verifier'])
            ->middleware('permission:jour_feries.afficher');
        
        Route::get('prochains-jours-ouvres', [JourFerieController::class, 'prochainsJoursOuvres'])
            ->middleware('permission:jour_feries.afficher');
        
        // Route avec paramètre dans l'URL
        Route::get('annee/{year}', [JourFerieController::class, 'annee'])
            ->middleware('permission:jour_feries.afficher')
            ->where('year', '[0-9]{4}');
        
        // ============================================================
        // ROUTES AVEC PARAMÈTRE UUID (DOIVENT ÊTRE APRÈS LES ROUTES SPÉCIFIQUES)
        // ============================================================
        
        // Liste (sans paramètre)
        Route::get('/', [JourFerieController::class, 'index'])
            ->middleware('permission:jour_feries.afficher');
        
        // Routes avec UUID - doivent être en dernier
        Route::get('{uuid_jour_ferie}', [JourFerieController::class, 'show'])
            ->middleware('permission:jour_feries.afficher');
        
        Route::put('{uuid_jour_ferie}', [JourFerieController::class, 'update'])
            ->middleware('permission:jour_feries.modifier');
        
        Route::delete('{uuid_jour_ferie}', [JourFerieController::class, 'destroy'])
            ->middleware('permission:jour_feries.supprimer');
        
        // CRUD - Création (sans paramètre)
        Route::post('/', [JourFerieController::class, 'store'])
            ->middleware('permission:jour_feries.creer');
    });


    // Route protégée espaces client
    Route::prefix('espaces-client')->group(function () {
        Route::get('dashboard', [CustomerController::class, 'index']);
        Route::get('contrats/', [CustomerController::class, 'getAllContrat']);
        Route::post('add-new-contrats/', [CustomerController::class, 'addNewContrat']);
        Route::get('contrat-details/{contrat_id}', [CustomerController::class, 'getContratDetails']);
        Route::get('contrats-factures/', [CustomerController::class, 'getContratsFactures']);
        Route::get('contrat-etat-cotisation/{contrat_id}', [CustomerController::class, 'getContratEtatCotisation']);
    });
});