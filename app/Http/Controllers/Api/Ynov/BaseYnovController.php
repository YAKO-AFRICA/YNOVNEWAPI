<?php

namespace App\Http\Controllers\Api\Ynov;

use App\Http\Controllers\Controller;

/**

 * 
 * @OA\Info(
 *     title="YNOV API",
 *     version="1.0.0",
 *     description="API REST du Front-Office de YAKO AFRICA Assurances Vie Côte d'Ivoire. Cette API permet la gestion sécurisée de l'authentification, des utilisateurs, des accès (rôles/permissions) et des différentes fonctionnalités exposées par la plateforme YNOV. L'API est versionnée (v1) afin de garantir l'évolutivité et la compatibilité avec les applications clientes (Web, Mobile).",
 *     @OA\Contact(
 *         name="Équipe Technique YAKO AFRICA",
 *         email="Non vérifiable avec les informations actuellement fournies."
 *     )
 * )
 *
 *
 * 
 *  @OA\Server(
 *     url="/api/v1",
 *     description="Serveur API v1 — URL de base exacte non vérifiable avec les informations actuellement fournies (dépend de l'environnement de déploiement)."
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="Sanctum Personal Access Token",
 *     description="Authentification via Laravel Sanctum. Envoyer le token obtenu lors du login dans le header : `Authorization: Bearer {token}`. Le token est également renvoyé dans le header de réponse `Authorization` lors du login réussi."
 * )
 *
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     description="Représentation d'un utilisateur",
 *     @OA\Property(property="uuid_user", type="string", format="uuid", example="9c858901-8a57-4791-81fe-4c455b099050"),
 *     @OA\Property(property="login", type="string", nullable=true, example="jdupont"),
 *     @OA\Property(property="email", type="string", format="email", example="jean.dupont@yako-africa.ci"),
 *     @OA\Property(property="user_type", type="string", enum={"client","user_interne","user_partner","admin"}, example="user_interne"),
 *     @OA\Property(property="status", type="string", enum={"actif","inactif","gele","bloque","suspendu"}, example="actif"),
 *     @OA\Property(property="is_first_login", type="boolean", example=false),
 *     @OA\Property(property="is_online", type="boolean", example=true),
 *     @OA\Property(property="is_locked", type="boolean", example=false),
 *     @OA\Property(property="last_login_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="email_verified_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="two_factor_enabled", type="boolean", example=false),
 *     @OA\Property(
 *         property="role",
 *         type="object",
 *         nullable=true,
 *         @OA\Property(property="uuid_role", type="string", format="uuid"),
 *         @OA\Property(property="libelle", type="string", example="Agent Commercial"),
 *         @OA\Property(property="is_super_admin", type="boolean")
 *     ),
 *     @OA\Property(property="details", ref="#/components/schemas/userDetail", nullable=true),
 *     @OA\Property(
 *         property="permissions",
 *         type="array",
 *         @OA\Items(type="string"),
 *         nullable=true,
 *         example={"users.afficher","users.creer"},
 *         description="'*' si super admin, sinon liste des codes de permission"
 *     )
 * )
 *
 **
 * @OA\Schema(
 *     schema="userDetail",
 *     type="object",
 *     description="Détails d'un utilisateur",
 *     @OA\Property(property="uuid_user_details", type="string", format="uuid"),
 *     @OA\Property(property="code_agent", type="string", nullable=true),
 *     @OA\Property(property="matricule", type="string", nullable=true),
 *     @OA\Property(property="nom", type="string", example="Dupont"),
 *     @OA\Property(property="prenoms", type="string", example="Jean"),
 *     @OA\Property(property="full_name", type="string", example="Jean Dupont"),
 *     @OA\Property(property="fonction", type="string", nullable=true, example="Agent Commercial"),
 *     @OA\Property(property="service", type="string", nullable=true),
 *     @OA\Property(property="departement", type="string", nullable=true),
 *     @OA\Property(property="mobile_1", type="string", nullable=true, example="0708091011"),
 *     @OA\Property(property="mobile_2", type="string", nullable=true),
 *     @OA\Property(property="telephone_fixe", type="string", nullable=true),
 *     @OA\Property(property="email_pro", type="string", nullable=true, example="jean.dupont@yako-africa.ci"),
 *     @OA\Property(property="photo", type="string", nullable=true),
 *     @OA\Property(property="date_naissance", type="string", format="date", nullable=true),
 *     @OA\Property(property="lieu_naissance", type="string", nullable=true),
 *     @OA\Property(property="ville", type="string", nullable=true, example="Abidjan"),
 *     @OA\Property(property="pays", type="string", nullable=true, example="Côte d'Ivoire"),
 *     @OA\Property(property="genre", type="string", enum={"M","F"}, nullable=true),
 *     @OA\Property(property="civilite", type="string", enum={"M.","Mme","Mlle","Dr","Pr"}, nullable=true)
 * )
 *
 *
 * @OA\Schema(
 *     schema="ApiSuccessResponse",
 *     type="object",
 *     description="Enveloppe standard de réponse en cas de succès. Tous les endpoints authentifiés et la plupart des endpoints publics de l'API YNOV suivent ce format.",
 *     @OA\Property(property="success", type="boolean", example=true, description="Indique si l'opération a réussi."),
 *     @OA\Property(property="message", type="string", example="Opération réussie.", description="Message lisible destiné à l'affichage utilisateur."),
 *     @OA\Property(property="code", type="string", example="OPERATION_SUCCESS", nullable=true, description="Code applicatif machine-readable. Présent sur la majorité des endpoints CRUD (ex: ROLE_CREATED, PERMISSION_UPDATED) mais absent sur certains endpoints plus anciens (ex: /auth/login, /profile). Voir l'endpoint concerné."),
 *     @OA\Property(property="data", type="object", nullable=true, description="Données retournées, structure variable selon l'endpoint.")
 * )
 *
 * @OA\Schema(
 *     schema="ApiErrorResponse",
 *     type="object",
 *     description="Enveloppe standard de réponse en cas d'échec.",
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="Une erreur est survenue."),
 *     @OA\Property(property="code", type="string", example="ERROR_CODE", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="ValidationErrorResponse",
 *     type="object",
 *     description="Réponse standard Laravel en cas d'échec de validation (422). Structure native du FormRequest, non enveloppée dans success/message/code/data.",
 *     @OA\Property(property="message", type="string", example="The email field is required. (and 1 more error)"),
 *     @OA\Property(
 *         property="errors",
 *         type="object",
 *         description="Dictionnaire champ => liste des messages d'erreur associés.",
 *         example={"email": {"L'email est requis."}, "password": {"Le mot de passe doit comporter au moins 12 caractères."}}
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="UnauthorizedErrorResponse",
 *     type="object",
 *     description="Réponse 401 — absence d'authentification ou token invalide/expiré.",
 *     @OA\Property(property="message", type="string", example="Unauthenticated.")
 * )
 *
 * @OA\Schema(
 *     schema="AccountFrozenResponse",
 *     type="object",
 *     description="Réponse 423 (Locked) retournée lors d'une tentative de connexion ou d'action sur un compte actuellement gelé. Structure exacte dépendant de AccountFrozenException::toArray() — non vérifiable avec le code fourni (la classe d'exception elle-même n'est pas incluse), structure ci-dessous déduite de l'usage dans AuthService.",
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="Compte temporairement gelé. Réessayez dans 2 min 30 s."),
 *     @OA\Property(property="freeze_level", type="integer", example=2),
 *     @OA\Property(property="freeze_label", type="string", example="Modéré"),
 *     @OA\Property(property="remaining_seconds", type="integer", example=150),
 *     @OA\Property(property="frozen_until", type="string", format="date-time", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="LoginRequest",
 *     type="object",
 *     required={"login", "password"},
 *     @OA\Property(property="login", type="string", maxLength=100, example="jean.dupont@yako-africa.ci"),
 *     @OA\Property(property="password", type="string", format="password", minLength=8, example="MotDePasse#2024"),
 *     @OA\Property(property="device_name", type="string", maxLength=255, nullable=true, example="Chrome - Windows 11")
 * )
 *
 * @OA\Schema(
 *     schema="LoginResponse",
 *     type="object",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="user", ref="#/components/schemas/User"),
 *         @OA\Property(property="expires_at", type="string", format="date-time"),
 *         @OA\Property(property="requires_2fa", type="boolean", example=false),
 *         @OA\Property(property="must_change_password", type="boolean", example=false),
 *         @OA\Property(property="trusted_device", type="boolean"),
 *         @OA\Property(property="access_token", type="string", example="1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="RegisterRequest",
 *     type="object",
 *     required={"prenoms", "nom", "email", "password", "password_confirmation"},
 *     @OA\Property(property="prenoms", type="string", maxLength=255, example="Jean"),
 *     @OA\Property(property="nom", type="string", maxLength=55, example="Dupont"),
 *     @OA\Property(property="email", type="string", format="email", maxLength=100, example="jean.dupont@example.com"),
 *     @OA\Property(property="login", type="string", maxLength=100, nullable=true, example="jdupont"),
 *     @OA\Property(property="mobile_1", type="string", maxLength=25, nullable=true, example="0708091011"),
 *     @OA\Property(property="password", type="string", format="password", minLength=8, example="Passw0rd123"),
 *     @OA\Property(property="password_confirmation", type="string", format="password", example="Passw0rd123")
 * )
 *
 * @OA\Schema(
 *     schema="TwoFactorRequiredResponse",
 *     type="object",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="code", type="string", example="2FA_REQUIRED"),
 *     @OA\Property(property="message", type="string", example="Vérification 2FA requise."),
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="two_factor_token", type="string"),
 *         @OA\Property(property="user", ref="#/components/schemas/User")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="PasswordChangeRequiredResponse",
 *     type="object",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="code", type="string", example="PASSWORD_CHANGE_REQUIRED"),
 *     @OA\Property(property="message", type="string", example="Vous devez changer votre mot de passe."),
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="change_password_token", type="string"),
 *         @OA\Property(property="user", ref="#/components/schemas/User")
 *     )
 * )
 * @OA\Schema(
 *     schema="Device",
 *     type="object",
 *     description="Représentation d'un appareil telle que retournée par DeviceResource.",
 *     @OA\Property(property="uuid_device", type="string", format="uuid"),
 *     @OA\Property(property="device_name", type="string", nullable=true, example="Chrome - Windows 11"),
 *     @OA\Property(property="device_type", type="string", nullable=true, example="desktop"),
 *     @OA\Property(property="os", type="string", nullable=true, example="Windows 11"),
 *     @OA\Property(property="browser", type="string", nullable=true, example="Chrome 128"),
 *     @OA\Property(property="ip_address", type="string", nullable=true, example="41.207.xxx.xxx"),
 *     @OA\Property(property="location", type="string", nullable=true),
 *     @OA\Property(property="is_trusted", type="boolean"),
 *     @OA\Property(property="last_used_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time")
 * )
 * 
 * @OA\Schema(
 *     schema="TokenSession",
 *     type="object",
 *     description="Représentation d'un token Sanctum telle que retournée par TokenResource.",
 *     @OA\Property(property="id", type="integer", example=42),
 *     @OA\Property(property="name", type="string", example="Chrome - Windows 11"),
 *     @OA\Property(property="abilities", type="array", @OA\Items(type="string"), example={"*"}),
 *     @OA\Property(property="last_used_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="expires_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time")
 * )
 * 
 *
 * @OA\Schema(
 *     schema="LoginAttempt",
 *     type="object",
 *     description="Représentation d'une tentative de connexion telle que retournée par LoginAttemptResource.",
 *     @OA\Property(property="login_attempted", type="string", example="jean.dupont@yako-africa.ci"),
 *     @OA\Property(property="ip_address", type="string", example="41.207.xxx.xxx"),
 *     @OA\Property(property="location", type="string", nullable=true),
 *     @OA\Property(property="is_successful", type="boolean"),
 *     @OA\Property(property="failure_reason", type="string", nullable=true, enum={"IP_BLOCKED","INVALID_PASSWORD","USER_NOT_FOUND","USER_FROZEN","ACCOUNT_INACTIVE","ACCOUNT_BLOCKED", null}, description="Motif d'échec, null si is_successful=true. Valeurs déduites de AuthService::login() et AuthService::logAttempt()."),
 *     @OA\Property(property="attempted_at", type="string", format="date-time")
 * )
 * 
 * @OA\Schema(
 *     schema="FreezeStatus",
 *     type="object",
 *     description="Structure retournée par FreezeService::getCurrentLevel().",
 *     @OA\Property(property="level", type="integer", example=2, description="0=aucun, 1-3=automatique (léger/modéré/sévère), 4=manuel (admin)."),
 *     @OA\Property(property="label", type="string", example="Modéré"),
 *     @OA\Property(property="remaining_seconds", type="integer", example=45),
 *     @OA\Property(property="remaining_formatted", type="string", example="0m 45s"),
 *     @OA\Property(property="is_frozen", type="boolean"),
 *     @OA\Property(property="is_manual", type="boolean", description="true si level=4."),
 *     @OA\Property(property="can_be_frozen", type="boolean"),
 *     @OA\Property(property="can_be_unfrozen", type="boolean"),
 *     @OA\Property(property="unfrozen_at", type="string", format="date-time", nullable=true, description="Nom de champ trompeur dans le code source (FreezeService::getCurrentLevel) : correspond en réalité à user.frozen_until (date de FIN du gel), pas à une date de dégel effectif déjà survenue. À signaler comme confusion de nommage dans le code.")
 * )
 * 
 * @OA\Schema(
 *     schema="PermissionDeniedResponse",
 *     type="object",
 *     description="Réponse 403 retournée par les middlewares de permission (permission:, permission.any:, permission.all:).",
 *     @OA\Property(property="status", type="string", example="error"),
 *     @OA\Property(property="message", type="string", example="Vous n'avez pas la permission nécessaire pour effectuer cette action"),
 *     @OA\Property(property="code", type="string", example="PERMISSION_DENIED"),
 *     @OA\Property(property="required_permission", type="string", example="users.creer", nullable=true, description="Présent uniquement avec le middleware permission: (permission unique)."),
 *     @OA\Property(property="required_permissions", type="array", @OA\Items(type="string"), nullable=true, description="Présent avec permission.any: ou permission.all: (plusieurs permissions attendues)."),
 *     @OA\Property(property="missing_permissions", type="array", @OA\Items(type="string"), nullable=true, description="Présent uniquement avec permission.all: — liste des permissions manquantes.")
 * )
 *
 * @OA\Tag(
 *     name="Authentication",
 *     description="Inscription, connexion, déconnexion, rafraîchissement de token, et vérification de statut de gel de compte."
 * )
 * @OA\Tag(
 *     name="Password Management",
 *     description="Mot de passe oublié, réinitialisation, changement, et initialisation lors de la première connexion."
 * )
 * @OA\Tag(
 *     name="Email Verification",
 *     description="Vérification d'adresse email et renvoi du lien de vérification."
 * )
 * @OA\Tag(
 *     name="Two-Factor Authentication",
 *     description="Activation, confirmation, désactivation et vérification de la double authentification (2FA/OTP)."
 * )
 * @OA\Tag(
 *     name="User Devices",
 *     description="Gestion des appareils de confiance liés à un compte utilisateur."
 * )
 * @OA\Tag(
 *     name="Sessions",
 *     description="Gestion des sessions actives (tokens Sanctum)."
 * )
 * @OA\Tag(
 *     name="Login Attempts",
 *     description="Historique des tentatives de connexion de l'utilisateur authentifié."
 * )
 * @OA\Tag(
 *     name="User Profile",
 *     description="Consultation et mise à jour du profil de l'utilisateur connecté."
 * )
 * @OA\Tag(
 *     name="Users",
 *     description="Gestion administrative des comptes utilisateurs (CRUD, blocage/déblocage)."
 * )
 * @OA\Tag(
 *     name="Account Freeze",
 *     description="Gel et dégel manuel de comptes par un administrateur."
 * )
 * @OA\Tag(
 *     name="Roles",
 *     description="Gestion des rôles et attribution des permissions."
 * )
 * @OA\Tag(
 *     name="Permissions",
 *     description="Gestion des permissions unitaires."
 * )
 * @OA\Tag(
 *     name="Permission Groups",
 *     description="Gestion des groupes (modules) de permissions."
 * )
 * @OA\Tag(
 *     name="IP Restrictions",
 *     description="Gestion des listes blanches et noires d'adresses IP."
 * )
 * @OA\Tag(
 *     name="Security Questions",
 *     description="Configuration et vérification des questions de sécurité pour la récupération de compte."
 * )
 * @OA\Tag(
 *     name="Audit Logs",
 *     description="Consultation des journaux d'activité et de gel de compte."
 * )
 */
class BaseYnovController extends Controller
{
    // Ce fichier contient les annotations OpenAPI globales
}