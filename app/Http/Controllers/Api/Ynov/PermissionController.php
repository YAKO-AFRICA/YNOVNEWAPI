<?php
// app/Http/Controllers/Api/Ynov/PermissionController.php

namespace App\Http\Controllers\Api\Ynov;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Ynov\StorePermissionRequest;
use App\Http\Requests\Api\Ynov\UpdatePermissionRequest;
use App\Models\Api\Ynov\parameter\ActivityLog;
use App\Models\Api\Ynov\parameter\Permission;
use App\Services\Api\Ynov\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function __construct(private PermissionService $permissionService) {}

    /**
     * ================================================================
     * Suggérer des actions pour les permissions (structuré par catégorie)
     * ================================================================
     */
    // public function suggestedActions(): JsonResponse
    // {
    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Actions standards par catégorie.',
    //         'code' => 'ACTIONS_SUGGESTED',
    //         'data' => [
    //             // ============================================
    //             // ACTIONS CRUD (Créer, Lire, Modifier, Supprimer)
    //             // ============================================
    //             [
    //                 'category' => 'CRUD',
    //                 'icon' => 'bi-database',
    //                 'color' => '#0d6efd',
    //                 'actions' => [
    //                     'Créer',
    //                     'Afficher',
    //                     'Lister',
    //                     'Modifier',
    //                     'Supprimer',
    //                     'Restaurer',
    //                     'Archiver',
    //                 ]
    //             ],
    //             // ============================================
    //             // ACTIONS DE WORKFLOW (Validation, Approbation)
    //             // ============================================
    //             [
    //                 'category' => 'Workflow',
    //                 'icon' => 'bi-diagram-3',
    //                 'color' => '#6f42c1',
    //                 'actions' => [
    //                     'Valider',
    //                     'Rejeter',
    //                     'Approuver',
    //                     'Refuser',
    //                     'Transmettre',
    //                     'Soumettre',
    //                     'Clôturer',
    //                     'Réouvrir',
    //                     'Annuler',
    //                 ]
    //             ],
    //             // ============================================
    //             // ACTIONS D'ADMINISTRATION
    //             // ============================================
    //             [
    //                 'category' => 'Administration',
    //                 'icon' => 'bi-gear',
    //                 'color' => '#dc3545',
    //                 'actions' => [
    //                     'Activer',
    //                     'Désactiver',
    //                     'Bloquer',
    //                     'Débloquer',
    //                     'Geler',
    //                     'Dégeler',
    //                     'Suspendre',
    //                     'Réactiver',
    //                     'Configurer',
    //                     'Paramétrer',
    //                 ]
    //             ],
    //             // ============================================
    //             // ACTIONS D'EXPORT/IMPORT
    //             // ============================================
    //             [
    //                 'category' => 'Export & Import',
    //                 'icon' => 'bi-file-arrow-up',
    //                 'color' => '#198754',
    //                 'actions' => [
    //                     'Exporter',
    //                     'Importer',
    //                     'Télécharger',
    //                     'Téléverser',
    //                     'Imprimer',
    //                     'Générer',
    //                     'Exporter en PDF',
    //                     'Exporter en Excel',
    //                     'Exporter en CSV',
    //                     'Importer en JSON',
    //                 ]
    //             ],
    //             // ============================================
    //             // ACTIONS DE COMMUNICATION
    //             // ============================================
    //             [
    //                 'category' => 'Communication',
    //                 'icon' => 'bi-chat',
    //                 'color' => '#0dcaf0',
    //                 'actions' => [
    //                     'Notifier',
    //                     'Envoyer',
    //                     'Partager',
    //                     'Publier',
    //                     'Commenter',
    //                     'Répondre',
    //                     'Transférer',
    //                     'Informer',
    //                     'Alerter',
    //                 ]
    //             ],
    //             // ============================================
    //             // ACTIONS DE RECHERCHE ET FILTRAGE
    //             // ============================================
    //             [
    //                 'category' => 'Recherche et Filtrage',
    //                 'icon' => 'bi-search',
    //                 'color' => '#fd7e14',
    //                 'actions' => [
    //                     'Rechercher',
    //                     'Filtrer',
    //                     'Trier',
    //                     'Consulter',
    //                     'Explorer',
    //                     'Naviguer',
    //                 ]
    //             ],
    //             // ============================================
    //             // ACTIONS DE SÉCURITÉ
    //             // ============================================
    //             [
    //                 'category' => 'Sécurité',
    //                 'icon' => 'bi-shield-lock',
    //                 'color' => '#dc3545',
    //                 'actions' => [
    //                     'Restreindre',
    //                     'Autoriser',
    //                     'Protéger',
    //                     'Déprotéger',
    //                     'Vérifier',
    //                     'Auditer',
    //                     'Journaliser',
    //                     'Chiffrer',
    //                     'Déchiffrer',
    //                 ]
    //             ],
    //             // ============================================
    //             // ACTIONS DE GESTION DES UTILISATEURS
    //             // ============================================
    //             [
    //                 'category' => 'Gestion des utilisateurs',
    //                 'icon' => 'bi-people',
    //                 'color' => '#0d6efd',
    //                 'actions' => [
    //                     'Assigner',
    //                     'Désassigner',
    //                     'Promouvoir',
    //                     'Rétrograder',
    //                     'Impersonner',
    //                     'Déléguer',
    //                     'Inscrire',
    //                     'Désinscrire',
    //                     'Inviter',
    //                     'Révoquer',
    //                 ]
    //             ],
    //             // ============================================
    //             // ACTIONS DE GESTION DES RÔLES ET PERMISSIONS
    //             // ============================================
    //             [
    //                 'category' => 'Gestion des rôles et permissions',
    //                 'icon' => 'bi-shield',
    //                 'color' => '#6f42c1',
    //                 'actions' => [
    //                     'Attribuer',
    //                     'Retirer',
    //                     'Définir',
    //                     'Modifier',
    //                     'Consulter',
    //                     'Dupliquer',
    //                     'Cloner',
    //                 ]
    //             ],
    //             // ============================================
    //             // ACTIONS DE GESTION DES DOCUMENTS
    //             // ============================================
    //             [
    //                 'category' => 'Gestion des documents',
    //                 'icon' => 'bi-folder',
    //                 'color' => '#fd7e14',
    //                 'actions' => [
    //                     'Créer',
    //                     'Modifier',
    //                     'Supprimer',
    //                     'Partager',
    //                     'Classer',
    //                     'Déclasser',
    //                     'Versionner',
    //                     'Signer',
    //                     'Certifier',
    //                     'Numériser',
    //                 ]
    //             ],
    //             // ============================================
    //             // ACTIONS DE GESTION FINANCIÈRE
    //             // ============================================
    //             [
    //                 'category' => 'Gestion financière',
    //                 'icon' => 'bi-coin',
    //                 'color' => '#198754',
    //                 'actions' => [
    //                     'Facturer',
    //                     'Payer',
    //                     'Rembourser',
    //                     'Approuver',
    //                     'Rejeter',
    //                     'Valider',
    //                     'Annuler',
    //                     'Imputer',
    //                     'Décaisser',
    //                     'Encaisser',
    //                 ]
    //             ],
    //             // ============================================
    //             // ACTIONS DE GESTION DES CLIENTS
    //             // ============================================
    //             [
    //                 'category' => 'Gestion des clients',
    //                 'icon' => 'bi-person-badge',
    //                 'color' => '#0d6efd',
    //                 'actions' => [
    //                     'Accueillir',
    //                     'Orienter',
    //                     'Conseiller',
    //                     'Suivre',
    //                     'Relancer',
    //                     'Fidéliser',
    //                     'Prospecter',
    //                     'Qualifier',
    //                     'Convertir',
    //                 ]
    //             ],
    //             // ============================================
    //             // ACTIONS DE RAPPORT ET ANALYSE
    //             // ============================================
    //             [
    //                 'category' => 'Rapport & Analyse',
    //                 'icon' => 'bi-graph-up',
    //                 'color' => '#6f42c1',
    //                 'actions' => [
    //                     'Analyser',
    //                     'Rapporter',
    //                     'Statistiquer',
    //                     'Visualiser',
    //                     'Comparer',
    //                     'Évaluer',
    //                     'Mesurer',
    //                     'Prévoir',
    //                     'Synthétiser',
    //                 ]
    //             ],
    //             // ============================================
    //             // ACTIONS SYSTÈME
    //             // ============================================
    //             [
    //                 'category' => 'Système',
    //                 'icon' => 'bi-server',
    //                 'color' => '#6c757d',
    //                 'actions' => [
    //                     'Installer',
    //                     'Désinstaller',
    //                     'Mettre à jour',
    //                     'Migrer',
    //                     'Sauvegarder',
    //                     'Restaurer',
    //                     'Nettoyer',
    //                     'Optimiser',
    //                     'Diagnostiquer',
    //                     'Réparer',
    //                 ]
    //             ],
    //             // ============================================
    //             // ACTIONS API
    //             // ============================================
    //             [
    //                 'category' => 'API',
    //                 'icon' => 'bi-code-square',
    //                 'color' => '#0dcaf0',
    //                 'actions' => [
    //                     'Créer un endpoint',
    //                     'Modifier un endpoint',
    //                     'Supprimer un endpoint',
    //                     'Tester',
    //                     'Documenter',
    //                     'Versionner',
    //                     'Sécuriser',
    //                     'Authentifier',
    //                 ]
    //             ],
    //             // ============================================
    //             // ACTIONS DE NOTIFICATION
    //             // ============================================
    //             [
    //                 'category' => 'Notification',
    //                 'icon' => 'bi-bell',
    //                 'color' => '#fd7e14',
    //                 'actions' => [
    //                     'Envoyer une notification',
    //                     'Souscrire',
    //                     'Se désabonner',
    //                     'Configurer',
    //                     'Envoyer un email',
    //                     'Envoyer un SMS',
    //                     'Envoyer un push',
    //                 ]
    //             ],
    //             // ============================================
    //             // ACTIONS DE GESTION DES SESSIONS
    //             // ============================================
    //             [
    //                 'category' => 'Gestion des sessions',
    //                 'icon' => 'bi-laptop',
    //                 'color' => '#0d6efd',
    //                 'actions' => [
    //                     'Voir les sessions',
    //                     'Révoquer une session',
    //                     'Révoquer toutes les sessions',
    //                     'Forcer la déconnexion',
    //                     'Limiter',
    //                 ]
    //             ],
    //             // ============================================
    //             // ACTIONS DE GESTION DES APPAREILS
    //             // ============================================
    //             [
    //                 'category' => 'Gestion des appareils',
    //                 'icon' => 'bi-phone',
    //                 'color' => '#198754',
    //                 'actions' => [
    //                     'Voir les appareils',
    //                     'Approuver un appareil',
    //                     'Révoquer un appareil',
    //                     'Désapprouver',
    //                     'Limiter',
    //                 ]
    //             ],
    //             // ============================================
    //             // ACTIONS DE GESTION IP
    //             // ============================================
    //             [
    //                 'category' => 'Gestion IP',
    //                 'icon' => 'bi-globe2',
    //                 'color' => '#dc3545',
    //                 'actions' => [
    //                     'Bloquer une IP',
    //                     'Débloquer une IP',
    //                     'Ajouter à la whitelist',
    //                     'Ajouter à la blacklist',
    //                     'Voir les restrictions',
    //                     'Supprimer une restriction',
    //                 ]
    //             ],
    //             // ============================================
    //             // ACTIONS DE LOGS ET AUDIT
    //             // ============================================
    //             [
    //                 'category' => 'Logs & Audit',
    //                 'icon' => 'bi-clipboard-data',
    //                 'color' => '#6c757d',
    //                 'actions' => [
    //                     'Consulter les logs',
    //                     'Exporter les logs',
    //                     'Purger les logs',
    //                     'Analyser les logs',
    //                     'Auditer',
    //                     'Voir les tentatives de connexion',
    //                 ]
    //             ],
    //         ],
    //     ]);
    // }

    public function suggestedActions(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Actions standards par catégorie.',
            'code' => 'ACTIONS_SUGGESTED',
            'data' => [
                // ============================================
                // ACTIONS CRUD (Créer, Lire, Modifier, Supprimer)
                // ============================================
                [
                    'category' => 'CRUD',
                    'icon' => 'bi-database',
                    'color' => '#0d6efd',
                    'actions' => [
                        'Créer',
                        'Afficher',
                        'Lister',
                        'Modifier',
                        'Supprimer',
                        'Restaurer',
                        'Archiver',
                    ]
                ],
                // ============================================
                // ACTIONS DE WORKFLOW (Validation, Approbation)
                // ============================================
                [
                    'category' => 'Workflow',
                    'icon' => 'bi-diagram-3',
                    'color' => '#6f42c1',
                    'actions' => [
                        'Valider',
                        'Rejeter',
                        'Approuver',
                        'Refuser',
                        'Transmettre',
                        'Soumettre',
                        'Clôturer',
                        'Réouvrir',
                        'Annuler',
                    ]
                ],
                // ============================================
                // ACTIONS D'ADMINISTRATION
                // ============================================
                [
                    'category' => 'Administration',
                    'icon' => 'bi-gear',
                    'color' => '#dc3545',
                    'actions' => [
                        'Activer',
                        'Désactiver',
                        'Bloquer',
                        'Débloquer',
                        'Geler',
                        'Dégeler',
                        'Suspendre',
                        'Réactiver',
                        'Configurer',
                        'Paramétrer',
                    ]
                ],
                // ============================================
                // ACTIONS D'EXPORT/IMPORT
                // ============================================
                [
                    'category' => 'Export & Import',
                    'icon' => 'bi-file-arrow-up',
                    'color' => '#198754',
                    'actions' => [
                        'Exporter',
                        'Importer',
                        'Télécharger',
                        'Téléverser',
                        'Imprimer',
                        'Générer',
                        'Exporter en PDF',
                        'Exporter en Excel',
                        'Exporter en CSV',
                        'Importer en JSON',
                    ]
                ],
                // ============================================
                // ACTIONS DE COMMUNICATION
                // ============================================
                [
                    'category' => 'Communication',
                    'icon' => 'bi-chat',
                    'color' => '#0dcaf0',
                    'actions' => [
                        'Notifier',
                        'Envoyer',
                        'Partager',
                        'Publier',
                        'Commenter',
                        'Répondre',
                        'Transférer',
                        'Informer',
                        'Alerter',
                    ]
                ],
                // ============================================
                // ACTIONS DE RECHERCHE ET FILTRAGE
                // ============================================
                [
                    'category' => 'Recherche et Filtrage',
                    'icon' => 'bi-search',
                    'color' => '#fd7e14',
                    'actions' => [
                        'Rechercher',
                        'Filtrer',
                        'Trier',
                        'Consulter',
                        'Explorer',
                        'Naviguer',
                    ]
                ],
                // ============================================
                // ACTIONS DE SÉCURITÉ
                // ============================================
                [
                    'category' => 'Sécurité',
                    'icon' => 'bi-shield-lock',
                    'color' => '#dc3545',
                    'actions' => [
                        'Restreindre',
                        'Autoriser',
                        'Protéger',
                        'Déprotéger',
                        'Vérifier',
                        'Auditer',
                        'Journaliser',
                        'Chiffrer',
                        'Déchiffrer',
                    ]
                ],
                // ============================================
                // ACTIONS DE GESTION DES UTILISATEURS
                // ============================================
                [
                    'category' => 'Gestion des utilisateurs',
                    'icon' => 'bi-people',
                    'color' => '#0d6efd',
                    'actions' => [
                        'Assigner',
                        'Désassigner',
                        'Promouvoir',
                        'Rétrograder',
                        'Impersonner',
                        'Déléguer',
                        'Inscrire',
                        'Désinscrire',
                        'Inviter',
                        'Révoquer',
                    ]
                ],
                // ============================================
                // ACTIONS DE GESTION DES RÔLES ET PERMISSIONS
                // ============================================
                [
                    'category' => 'Gestion des rôles et permissions',
                    'icon' => 'bi-shield',
                    'color' => '#6f42c1',
                    'actions' => [
                        'Attribuer',
                        'Retirer',
                        'Définir',
                        'Modifier',
                        'Consulter',
                        'Dupliquer',
                        'Cloner',
                    ]
                ],
                // ============================================
                // ACTIONS DE GESTION DES DOCUMENTS
                // ============================================
                [
                    'category' => 'Gestion des documents',
                    'icon' => 'bi-folder',
                    'color' => '#fd7e14',
                    'actions' => [
                        'Créer',
                        'Modifier',
                        'Supprimer',
                        'Partager',
                        'Classer',
                        'Déclasser',
                        'Versionner',
                        'Signer',
                        'Certifier',
                        'Numériser',
                    ]
                ],
                // ============================================
                // ACTIONS DE GESTION FINANCIÈRE
                // ============================================
                [
                    'category' => 'Gestion financière',
                    'icon' => 'bi-coin',
                    'color' => '#198754',
                    'actions' => [
                        'Facturer',
                        'Payer',
                        'Rembourser',
                        'Approuver',
                        'Rejeter',
                        'Valider',
                        'Annuler',
                        'Imputer',
                        'Décaisser',
                        'Encaisser',
                    ]
                ],
                // ============================================
                // ACTIONS DE GESTION DES CLIENTS
                // ============================================
                [
                    'category' => 'Gestion des clients',
                    'icon' => 'bi-person-badge',
                    'color' => '#0d6efd',
                    'actions' => [
                        'Accueillir',
                        'Orienter',
                        'Conseiller',
                        'Suivre',
                        'Relancer',
                        'Fidéliser',
                        'Prospecter',
                        'Qualifier',
                        'Convertir',
                    ]
                ],
                // ============================================
                // ACTIONS DE RAPPORT ET ANALYSE
                // ============================================
                [
                    'category' => 'Rapport & Analyse',
                    'icon' => 'bi-graph-up',
                    'color' => '#6f42c1',
                    'actions' => [
                        'Analyser',
                        'Rapporter',
                        'Statistiquer',
                        'Visualiser',
                        'Comparer',
                        'Évaluer',
                        'Mesurer',
                        'Prévoir',
                        'Synthétiser',
                    ]
                ],
                // ============================================
                // ACTIONS SYSTÈME
                // ============================================
                [
                    'category' => 'Système',
                    'icon' => 'bi-server',
                    'color' => '#6c757d',
                    'actions' => [
                        'Installer',
                        'Désinstaller',
                        'Mettre à jour',
                        'Migrer',
                        'Sauvegarder',
                        'Restaurer',
                        'Nettoyer',
                        'Optimiser',
                        'Diagnostiquer',
                        'Réparer',
                    ]
                ],
                // ============================================
                // ACTIONS API
                // ============================================
                [
                    'category' => 'API',
                    'icon' => 'bi-code-square',
                    'color' => '#0dcaf0',
                    'actions' => [
                        'Créer un endpoint',
                        'Modifier un endpoint',
                        'Supprimer un endpoint',
                        'Tester',
                        'Documenter',
                        'Versionner',
                        'Sécuriser',
                        'Authentifier',
                    ]
                ],
                // ============================================
                // ACTIONS DE NOTIFICATION
                // ============================================
                [
                    'category' => 'Notification',
                    'icon' => 'bi-bell',
                    'color' => '#fd7e14',
                    'actions' => [
                        'Envoyer une notification',
                        'Souscrire',
                        'Se désabonner',
                        'Configurer',
                        'Envoyer un email',
                        'Envoyer un SMS',
                        'Envoyer un push',
                    ]
                ],
                // ============================================
                // ACTIONS DE GESTION DES SESSIONS
                // ============================================
                [
                    'category' => 'Gestion des sessions',
                    'icon' => 'bi-laptop',
                    'color' => '#0d6efd',
                    'actions' => [
                        'Voir les sessions',
                        'Révoquer une session',
                        'Révoquer toutes les sessions',
                        'Forcer la déconnexion',
                        'Limiter',
                    ]
                ],
                // ============================================
                // ACTIONS DE GESTION DES APPAREILS
                // ============================================
                [
                    'category' => 'Gestion des appareils',
                    'icon' => 'bi-phone',
                    'color' => '#198754',
                    'actions' => [
                        'Voir les appareils',
                        'Approuver un appareil',
                        'Révoquer un appareil',
                        'Désapprouver',
                        'Limiter',
                    ]
                ],
                // ============================================
                // ACTIONS DE GESTION IP
                // ============================================
                [
                    'category' => 'Gestion IP',
                    'icon' => 'bi-globe2',
                    'color' => '#dc3545',
                    'actions' => [
                        'Bloquer une IP',
                        'Débloquer une IP',
                        'Ajouter à la whitelist',
                        'Ajouter à la blacklist',
                        'Voir les restrictions',
                        'Supprimer une restriction',
                    ]
                ],
                // ============================================
                // ACTIONS DE LOGS ET AUDIT
                // ============================================
                [
                    'category' => 'Logs & Audit',
                    'icon' => 'bi-clipboard-data',
                    'color' => '#6c757d',
                    'actions' => [
                        'Consulter les logs',
                        'Exporter les logs',
                        'Purger les logs',
                        'Analyser les logs',
                        'Auditer',
                        'Voir les tentatives de connexion',
                    ]
                ],
                // ============================================
                // ACTIONS DE GESTION DES PARTENAIRES
                // ============================================
                [
                    'category' => 'Gestion des partenaires',
                    'icon' => 'bi-handshake',
                    'color' => '#2c3e50',
                    'actions' => [
                        'Afficher les partenaires',
                        'Créer un partenaire',
                        'Modifier un partenaire',
                        'Supprimer un partenaire',
                        'Activer un partenaire',
                        'Désactiver un partenaire',
                        'Voir les réseaux d\'un partenaire',
                    ]
                ],
                // ============================================
                // ACTIONS DE GESTION DES RÉSEAUX
                // ============================================
                [
                    'category' => 'Gestion des réseaux',
                    'icon' => 'bi-diagram-3',
                    'color' => '#2980b9',
                    'actions' => [
                        'Afficher les réseaux',
                        'Créer un réseau',
                        'Modifier un réseau',
                        'Supprimer un réseau',
                        'Activer un réseau',
                        'Désactiver un réseau',
                        'Voir les agences d\'un réseau',
                    ]
                ],
                // ============================================
                // ACTIONS DE GESTION DES AGENCES
                // ============================================
                [
                    'category' => 'Gestion des agences',
                    'icon' => 'bi-building',
                    'color' => '#27ae60',
                    'actions' => [
                        'Afficher les agences',
                        'Créer une agence',
                        'Modifier une agence',
                        'Supprimer une agence',
                        'Activer une agence',
                        'Désactiver une agence',
                        'Assigner des utilisateurs',
                        'Retirer des utilisateurs',
                        'Voir les horaires d\'une agence',
                        'Modifier les horaires d\'une agence',
                        'Voir les agences à proximité',
                    ]
                ],
                // ============================================
                // ACTIONS DE GESTION DES QUESTIONS DE SÉCURITÉ
                // ============================================
                [
                    'category' => 'Questions de sécurité',
                    'icon' => 'bi-question-circle',
                    'color' => '#8e44ad',
                    'actions' => [
                        'Afficher les questions',
                        'Créer une question',
                        'Modifier une question',
                        'Supprimer une question',
                        'Activer une question',
                        'Désactiver une question',
                        'Voir les réponses des utilisateurs',
                    ]
                ],
            ],
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $permissions = Permission::query()
            ->with('group')
            ->when(
                $request->permission_group_uuid,
                fn($q) =>
                $q->where('permission_group_uuid', $request->permission_group_uuid)
            )
            ->when(
                $request->status,
                fn($q) =>
                $q->where('status', $request->status)
            )
            ->when(
                $request->search,
                fn($q) =>
                $q->where(function ($query) use ($request) {
                    $query->where('code', 'LIKE', "%{$request->search}%")
                        ->orWhere('libelle', 'LIKE', "%{$request->search}%")
                        ->orWhere('action', 'LIKE', "%{$request->search}%");
                })
            )
            ->orderBy('permission_group_uuid')
            ->orderBy('action')
            ->paginate($request->integer('per_page', 20));

        return response()->json([
            'success' => true,
            'message' => 'Permissions récupérées.',
            'code' => 'PERMISSIONS_LISTED',
            'data' => $permissions,
        ]);
    }


    public function store(StorePermissionRequest $request): JsonResponse
    {
        // Log::info($request->all());
        $permission = $this->permissionService->create(
            $request->validated(),
            $request->user()->details?->uuid_user_details
        );

        // Log l'action
        ActivityLog::log([
            'user_uuid' => $request->user()->uuid_user,
            'action' => 'create',
            'action_type' => 'crud',
            'module' => 'permissions',
            'description' => "Création de la permission : {$permission->code}",
            'resource_type' => 'permission',
            'resource_id' => $permission->uuid_permission,
            'new_values' => $permission->toArray(),
            'level' => 'info',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Permission créée avec success.',
            'code' => 'PERMISSION_CREATED',
            'data' => $permission,
        ], 201);
    }

    public function show($uuid_permission): JsonResponse
    {
        $permission = Permission::where('uuid_permission', $uuid_permission)->firstOrFail();
        ActivityLog::log([
            'user_uuid' => request()->user()->uuid_user,
            'action' => 'read',
            'action_type' => 'crud',
            'module' => 'permissions',
            'description' => "Affichage des details de la permission : {$permission->code}",
            'resource_type' => 'permission',
            'resource_id' => $permission->uuid_permission,
            'level' => 'info',
        ]);
        return response()->json([
            'success' => true,
            'message' => 'Details de la permission récupérée avec success.',
            'code' => 'PERMISSION_FOUND',
            'data' => $permission->load('group'),
        ]);
    }

    public function update(UpdatePermissionRequest $request, $uuid_permission): JsonResponse
    {
        $permission = Permission::where('uuid_permission', $uuid_permission)->firstOrFail();
        $oldValues = $permission->toArray();
        $updated = $this->permissionService->update(
            $permission,
            $request->validated(),
            $request->user()->details?->uuid_user_details
        );

        // Log l'action
        ActivityLog::log([
            'user_uuid' => $request->user()->uuid_user,
            'action' => 'update',
            'action_type' => 'crud',
            'module' => 'permissions',
            'description' => "Mise à jour de la permission : {$permission->code}",
            'resource_type' => 'permission',
            'resource_id' => $permission->uuid_permission,
            'old_values' => $oldValues,
            'new_values' => $updated->toArray(),
            'level' => 'info',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Permission mise à jour.',
            'code' => 'PERMISSION_UPDATED',
            'data' => $updated,
        ]);
    }

    public function destroy(Request $request, $uuid_permission): JsonResponse
    {
        $permission = Permission::where('uuid_permission', $uuid_permission)->firstOrFail();
        // Vérifier si la permission est utilisée par un rôle
        if ($permission->roles()->count() > 0) {
            ActivityLog::log([
                'user_uuid' => $request->user()->uuid_user,
                'action' => 'delete',
                'action_type' => 'crud',
                'module' => 'permissions',
                'description' => "Tentative de suppression de la permission : {$permission->code}, mais cette permission est attribuée à un ou plusieurs rôles et ne peut donc pas etre supprimée.",
                'resource_type' => 'permission',
                'resource_id' => $permission->uuid_permission,
                'level' => 'warning',
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Cette permission est attribuée à un ou plusieurs rôles et ne peut donc pas être supprimée.',
                'code' => 'PERMISSION_IN_USE',
            ], 422);
        }

        $permission->update([
            'status' => 'inactif',
            'deleted_by' => $request->user()->details?->uuid_user_details
        ]);
        $permission->delete();

        // Log l'action
        ActivityLog::log([
            'user_uuid' => $request->user()->uuid_user,
            'action' => 'delete',
            'action_type' => 'crud',
            'module' => 'permissions',
            'description' => "Suppression de la permission : {$permission->code}",
            'resource_type' => 'permission',
            'resource_id' => $permission->uuid_permission,
            'level' => 'warning',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Permission supprimée.',
            'code' => 'PERMISSION_DELETED'
        ]);
    }

    /**
     * Récupérer toutes les permissions avec leurs groupes
     */
    public function allWithGroups(Request $request): JsonResponse
    {
        $data = $this->permissionService->getAllPermissionsWithGroups();

        ActivityLog::log([
            'user_uuid' => $request->user()->uuid_user,
            'action' => 'read',
            'action_type' => 'crud',
            'module' => 'permissions',
            'description' => 'Récupération de toutes les permissions avec leurs groupes',
            'resource_type' => 'permission',
            'level' => 'info',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Permissions et groupes récupérés.',
            'code' => 'PERMISSIONS_GROUPS_LISTED',
            'data' => $data,
        ]);
    }

    /**
     * Récupérer les permissions d'un utilisateur
     */

    public function userPermissions(Request $request): JsonResponse
    {
        $user = $request->user();
        $permissions = $this->permissionService->getUserPermissions($user);

        ActivityLog::log([
            'user_uuid' => $request->user()->uuid_user,
            'action' => 'read',
            'action_type' => 'crud',
            'module' => 'permissions',
            'description' => 'Récupération des permissions de l\'utilisateur, ' . $user->uuid_user,
            'resource_type' => 'permission',
            'level' => 'info',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Permissions de l\'utilisateur récupérées.',
            'code' => 'USER_PERMISSIONS_LISTED',
            'data' => [
                'user_uuid' => $user->uuid_user,
                'permissions' => $permissions,
                'is_super_admin' => $user->isSuperAdmin(),
            ],
        ]);
    }
}
