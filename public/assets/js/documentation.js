(function() {
    'use strict';
        /* ================================================================
        YNOV API DOCUMENTATION - JAVASCRIPT PRINCIPAL
        Version organisée par modules pour une meilleure maintenabilité
        ================================================================ */

        console.warn = function() {
            const args = Array.from(arguments);
            if (args.some(arg => typeof arg === 'string' && arg.includes('Cookie'))) {
                return;
            }
            console.warn.apply(console, args);
        };

        // ================================================================
        // 1. CONFIGURATION DES ENVIRONNEMENTS
        // ================================================================
        const API_DATA = {
            environments: {
                local: {
                    url: 'http://localhost:8000/api/v1',
                    label: 'Local'
                },
                dev: {
                    url: 'https://apidev.yakoafricassur.com/api/v1',
                    label: 'Development'
                },
                test: {
                    url: 'https://apidev.yakoafricassur.com/api/v1',
                    label: 'Test'
                },
                staging: {
                    url: 'https://apidev.yakoafricassur.com/api/v1',
                    label: 'Staging'
                },
                production: {
                    url: 'https://api.ynov.ci/api/v1',
                    label: 'Production',
                    protected: true
                }
            },

            // ================================================================
            // 2. MODULES DE LA SIDEBAR
            // ================================================================
            modules: {
                home: {
                    label: 'Accueil',
                    icon: 'fa-house'
                },
                auth: {
                    label: 'Authentification',
                    icon: 'fa-right-to-bracket'
                },
                password: {
                    label: 'Mots de Passe',
                    icon: 'fa-key'
                },
                '2fa': {
                    label: 'Double Authentification',
                    icon: 'fa-shield-halved'
                },
                security: {
                    label: 'Questions de Sécurité',
                    icon: 'fa-question-circle'
                },
                profile: {
                    label: 'Profil & Sessions',
                    icon: 'fa-user'
                },
                users: {
                    label: 'Gestion des Utilisateurs',
                    icon: 'fa-users'
                },
                freeze: {
                    label: 'Gel / Dégel',
                    icon: 'fa-snowflake'
                },
                roles: {
                    label: 'Rôles',
                    icon: 'fa-user-shield'
                },
                permissions: {
                    label: 'Permissions',
                    icon: 'fa-key'
                },
                permGroups: {
                    label: 'Groupes de Permissions',
                    icon: 'fa-layer-group'
                },
                ip: {
                    label: 'Restrictions IP',
                    icon: 'fa-network-wired'
                },
                audit: {
                    label: 'Logs & Audit',
                    icon: 'fa-clipboard-list'
                },
                partners: {
                    label: 'Partenaires',
                    icon: 'fa-handshake'
                },
                reseaux: {
                    label: 'Réseaux',
                    icon: 'fa-network-wired'
                },
                agences: {
                    label: 'Agences',
                    icon: 'fa-building'
                },
                contrats: {
                    label: 'Contrats',
                    icon: 'fa-file-contract'
                },
                faq: {
                    label: 'FAQ',
                    icon: 'fa-circle-question'
                },

                group_notifs: {
                    label: 'Groupes de Notifications',
                    icon: 'fa-layer-group'
                },

                notifications: {
                    label: 'Notifications',
                    icon: 'fa-bell'
                },

                jour_feries: {
                    label: 'Jours Fériés',
                    icon: 'fa-calendar-day'
                },

                type_produits: {
                    label: 'Types de Produits',
                    icon: 'fa-tags'
                },
                produits: {
                    label: 'Gestion des Produits',
                    icon: 'fa-boxes'
                },

                prestations: {
                    label: 'Prestations & Catégories',
                    icon: 'fa-clipboard-list'
                },
                

                rdvs: {
                    label: 'Rendez-vous',
                    icon: 'fa-calendar-check'
                },

                espaces_client: {
                    label: 'Espace Client',
                    icon: 'fa-user-tie'
                },

                jeko_widget: {
                    label: 'Widget Jeko Payment',
                    icon: 'fa-credit-card'
                },
                errors: {
                    label: 'Codes HTTP & Erreurs',
                    icon: 'fa-bug'
                }
            },

            // ================================================================
            // 3. ENDPOINTS PAR MODULE
            // ================================================================
            endpoints: [
                // ============================================================
                // 3.1 ACCUEIL
                // ============================================================
                {
                    id: 'home',
                    module: 'home',
                    name: 'Présentation de l\'API',
                    description: 'Bienvenue sur la documentation interactive de l\'API YNOV.',
                    isHome: true
                },

                // ============================================================
                // 3.2 AUTHENTIFICATION — PUBLIQUES
                // ============================================================
                {
                    id: 'auth-login',
                    module: 'auth',
                    name: 'Connexion',
                    description: 'Authentifie un utilisateur par email OU login. Gère IP restriction, gel de compte (3/4/5 tentatives échouées), blocage automatique (6e tentative), 2FA, changement de mot de passe. Message d\'erreur unique pour éviter l\'énumération des utilisateurs.',
                    method: 'POST',
                    path: '/auth/login',
                    isProtected: false,
                    rateLimit: 'throttle:login',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        body: {
                            login: {
                                type: 'string',
                                required: true,
                                max: 100,
                                description: 'Email ou login de l\'utilisateur'
                            },
                            password: {
                                type: 'string',
                                required: true,
                                min: 8,
                                description: 'Mot de passe'
                            },
                            device_name: {
                                type: 'string',
                                required: false,
                                max: 255,
                                description: 'Nom de l\'appareil (défaut: "Appareil inconnu")'
                            }
                        }
                    },
                    exampleRequest: {
                        login: 'admin@ynov.ci',
                        password: 'MonPassword123!',
                        device_name: 'Documentation'
                    },
                    responses: [{
                            status: 200,
                            description: 'Connexion réussie — token retourné dans le body ET le header Authorization',
                            example: {
                                success: true,
                                data: {
                                    user: {},
                                    access_token: '...',
                                    expires_at: '...',
                                    requires_2fa: false,
                                    must_change_password: false,
                                    trusted_device: true
                                }
                            }
                        },
                        {
                            status: 200,
                            description: '2FA requise (code 2FA_REQUIRED)',
                            example: {
                                success: true,
                                code: '2FA_REQUIRED',
                                message: 'Vérification 2FA requise.',
                                data: {
                                    two_factor_token: '...',
                                    user: {}
                                }
                            }
                        },
                        {
                            status: 200,
                            description: 'Changement de mot de passe requis (code PASSWORD_CHANGE_REQUIRED)',
                            example: {
                                success: true,
                                code: 'PASSWORD_CHANGE_REQUIRED',
                                message: 'Vous devez changer votre mot de passe.',
                                data: {
                                    change_password_token: '...',
                                    user: {}
                                }
                            }
                        },
                        {
                            status: 401,
                            description: 'Identifiants incorrects',
                            example: {
                                success: false,
                                code: 'AUTH_ERROR',
                                message: 'Identifiants incorrects.'
                            }
                        },
                        {
                            status: 403,
                            description: 'IP bloquée, compte bloqué/inactif/suspendu',
                            example: {
                                success: false,
                                code: 'AUTH_ERROR',
                                message: 'Accès refusé depuis cette adresse IP.'
                            }
                        },
                        {
                            status: 423,
                            description: 'Compte temporairement gelé',
                            example: {
                                success: false,
                                message: 'Compte temporairement gelé. Réessayez dans 3 min 0 s.',
                                freeze_level: 2,
                                remaining_seconds: 180
                            }
                        },
                        {
                            status: 500,
                            description: 'Erreur interne',
                            example: {
                                success: false,
                                code: 'SERVER_ERROR',
                                message: 'Une erreur interne est survenue. Veuillez réessayer.'
                            }
                        }
                    ]
                },

                {
                    id: 'auth-get-register-data',
                    module: 'auth',
                    name: 'Vérifier un contrat avant inscription',
                    description: 'Permet de vérifier les informations d\'un contrat avant l\'inscription d\'un client. Vérifie que le contrat existe, que la date de naissance correspond, et que le contrat n\'est pas arrêté. Retourne les informations complètes du contrat.',
                    method: 'POST',
                    path: '/auth/get-register-data',
                    isProtected: false,
                    rateLimit: 'throttle:6,1 (6 tentatives / minute)',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        body: {
                            idcontrat: {
                                type: 'string',
                                required: true,
                                description: 'Identifiant du contrat (IdProposition)'
                            },
                            datenaissance: {
                                type: 'date',
                                required: true,
                                format: 'Y-m-d',
                                description: 'Date de naissance du titulaire'
                            }
                        }
                    },
                    exampleRequest: {
                        idcontrat: 'PROP2024001',
                        datenaissance: '1990-05-15'
                    },
                    responses: [{
                            status: 200,
                            description: 'Contrat trouvé et valide',
                            example: {
                                success: true,
                                message: 'Contrat trouvé.',
                                data: {}
                            }
                        },
                        {
                            status: 422,
                            description: 'Contrat arrêté',
                            example: {
                                success: false,
                                code: 'CONTRACT_FROZEN',
                                message: 'Ce contrat est arreté.'
                            }
                        },
                        {
                            status: 422,
                            description: 'Date de naissance incorrecte',
                            example: {
                                success: false,
                                code: 'DATE_OF_BIRTH_MISMATCH',
                                message: 'La date de naissance saisie ne correspond pas à celle enregistrée dans le contrat.'
                            }
                        }
                    ]
                },

                {
                    id: 'auth-register-client',
                    module: 'auth',
                    name: 'Inscription client avec contrat',
                    description: 'Permet à un client de s\'inscrire après avoir vérifié son contrat. Le mot de passe est généré automatiquement (12 caractères aléatoires) et envoyé par email ou SMS. Crée l\'utilisateur (rôle "client"), ses détails et associe les contrats.',
                    method: 'POST',
                    path: '/auth/register',
                    isProtected: false,
                    rateLimit: 'throttle:6,1 (6 tentatives / minute)',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        body: {
                            prenoms: {
                                type: 'string',
                                required: true,
                                max: 255,
                                description: 'Prénoms du client'
                            },
                            nom: {
                                type: 'string',
                                required: true,
                                max: 55,
                                description: 'Nom du client'
                            },
                            date_naissance: {
                                type: 'date',
                                required: false,
                                format: 'Y-m-d',
                                description: 'Date de naissance'
                            },
                            lieu_naissance: {
                                type: 'string',
                                required: false,
                                max: 55,
                                description: 'Lieu de naissance'
                            },
                            genre: {
                                type: 'string',
                                required: false,
                                enum: ['M', 'F'],
                                description: 'Genre'
                            },
                            civilite: {
                                type: 'string',
                                required: false,
                                max: 20,
                                description: 'Civilité'
                            },
                            nationalite: {
                                type: 'string',
                                required: false,
                                max: 55,
                                description: 'Nationalité'
                            },
                            email: {
                                type: 'email',
                                required: true,
                                max: 100,
                                description: 'Email'
                            },
                            login: {
                                type: 'string',
                                required: true,
                                max: 100,
                                description: 'Identifiant de connexion'
                            },
                            mobile_1: {
                                type: 'string',
                                required: true,
                                max: 25,
                                description: 'Téléphone principal'
                            },
                            ville: {
                                type: 'string',
                                required: false,
                                max: 55,
                                description: 'Ville'
                            },
                            code_postal: {
                                type: 'string',
                                required: false,
                                max: 20,
                                description: 'Code postal'
                            },
                            lieu_residence: {
                                type: 'string',
                                required: false,
                                max: 55,
                                description: 'Lieu de résidence'
                            },
                            pays: {
                                type: 'string',
                                required: false,
                                max: 55,
                                description: 'Pays'
                            },
                            fonction: {
                                type: 'string',
                                required: false,
                                max: 55,
                                description: 'Fonction professionnelle'
                            },
                            contrats: {
                                type: 'array',
                                required: true,
                                description: 'Liste des contrats à associer'
                            },
                            numero_client: {
                                type: 'string',
                                required: false,
                                description: 'Numéro client existant'
                            },
                            client_number: {
                                type: 'string',
                                required: false,
                                description: 'Numéro client pour les contrats'
                            }
                        }
                    },
                    exampleRequest: {
                        prenoms: 'Jean',
                        nom: 'Dupont',
                        email: 'jean.dupont@example.com',
                        login: 'jdupont',
                        mobile_1: '+2250708091011',
                        contrats: [{
                            IdProposition: 'PROP2024001',
                            produit: 'Assurance Vie Premium'
                        }]
                    },
                    responses: [{
                            status: 201,
                            description: 'Inscription réussie',
                            example: {
                                success: true,
                                code: 'USER_CREATED',
                                message: 'Inscription réussie. Vos paramètres de connexion ont été envoyés.',
                                data: {}
                            }
                        },
                        {
                            status: 422,
                            description: 'Erreur de validation',
                            example: {
                                success: false,
                                message: 'Données invalides.',
                                errors: {}
                            }
                        }
                    ]
                },

                {
                    id: 'auth-freeze-check',
                    module: 'auth',
                    name: 'Vérifier le gel d\'un compte',
                    description: 'Endpoint public pour vérifier si un compte (par login) est actuellement gelé.',
                    method: 'GET',
                    path: '/auth/freeze-check/{login}',
                    isProtected: false,
                    rateLimit: 'throttle:30,1',
                    headers: {
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            login: {
                                type: 'string',
                                required: true,
                                description: 'Login de l\'utilisateur'
                            }
                        }
                    },
                    responses: [{
                            status: 200,
                            description: 'Compte non gelé',
                            example: {
                                success: true,
                                data: {
                                    is_frozen: false
                                }
                            }
                        },
                        {
                            status: 200,
                            description: 'Compte gelé',
                            example: {
                                success: true,
                                data: {
                                    is_frozen: true,
                                    remaining_seconds: 120,
                                    freeze_level: 2
                                }
                            }
                        }
                    ]
                },

                // ============================================================
                // 3.3 MOTS DE PASSE
                // ============================================================
                {
                    id: 'password-forgot',
                    module: 'password',
                    name: 'Mot de passe oublié',
                    description: 'Demande de réinitialisation de mot de passe. **NOUVEAU :** Limitation de l\'envoi OTP par SMS à une fois toutes les 24 heures. Support de multiples canaux : sms, email, whatsapp, question_secrete.',
                    method: 'POST',
                    path: '/auth/forgot-password',
                    isProtected: false,
                    rateLimit: 'throttle:login',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        body: {
                            login: {
                                type: 'string',
                                required: true,
                                max: 100,
                                description: 'Login de l\'utilisateur'
                            },
                            option: {
                                type: 'string',
                                required: true,
                                enum: ['sms', 'email', 'whatsapp', 'question_secrete'],
                                description: 'Canal de récupération'
                            }
                        }
                    },
                    exampleRequest: {
                        login: 'jdupont',
                        option: 'email'
                    },
                    responses: [{
                            status: 200,
                            description: 'Questions de sécurité retournées',
                            example: {
                                success: true,
                                data: {
                                    token: '...',
                                    method: 'question_secrete',
                                    questions: []
                                }
                            }
                        },
                        {
                            status: 200,
                            description: 'OTP envoyé avec succès',
                            example: {
                                success: true,
                                message: 'Un code de vérification a été envoyé.',
                                data: {
                                    token: '...',
                                    method: 'email',
                                    expires_in: 5
                                }
                            }
                        },
                        {
                            status: 404,
                            description: 'Utilisateur introuvable',
                            example: {
                                success: false,
                                message: 'Utilisateur introuvable.'
                            }
                        },
                        {
                            status: 429,
                            description: 'Limite OTP SMS atteinte (24h)',
                            example: {
                                success: false,
                                code: 'OTP_SMS_ALREADY_SENT',
                                message: 'Vous avez déjà utilisé la vérification par SMS au cours des dernières 24 heures.',
                                data: {
                                    available_options: [{
                                        code: 'email',
                                        label: 'Recevoir un code par email'
                                    }, {
                                        code: 'question_secrete',
                                        label: 'Répondre aux questions de sécurité'
                                    }]
                                }
                            }
                        }
                    ]
                },

                {
                    id: 'password-reset',
                    module: 'password',
                    name: 'Réinitialiser le mot de passe',
                    description: 'Réinitialise le mot de passe à partir du token. Vérifie l\'historique (5 derniers mots de passe non réutilisables).',
                    method: 'POST',
                    path: '/auth/reset-password',
                    isProtected: false,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        body: {
                            login: {
                                type: 'string',
                                required: true,
                                max: 100,
                                description: 'Login de l\'utilisateur'
                            },
                            token: {
                                type: 'string',
                                required: true,
                                description: 'Token de réinitialisation'
                            },
                            password: {
                                type: 'string',
                                required: true,
                                min: 12,
                                description: 'Nouveau mot de passe'
                            },
                            password_confirmation: {
                                type: 'string',
                                required: true,
                                description: 'Confirmation'
                            }
                        }
                    },
                    exampleRequest: {
                        login: 'jean.dupont@example.com',
                        token: 'abc123def456',
                        password: 'NouveauMdp123!',
                        password_confirmation: 'NouveauMdp123!'
                    },
                    responses: [{
                            status: 200,
                            description: 'Mot de passe réinitialisé',
                            example: {
                                success: true,
                                message: 'Mot de passe réinitialisé avec succès.'
                            }
                        },
                        {
                            status: 422,
                            description: 'Token invalide/expiré',
                            example: {
                                success: false,
                                message: 'Token invalide ou expiré.'
                            }
                        }
                    ]
                },

                {
                    id: 'password-change',
                    module: 'password',
                    name: 'Changer le mot de passe',
                    description: 'Change le mot de passe d\'un utilisateur authentifié après vérification du mot de passe actuel et de l\'historique.',
                    method: 'POST',
                    path: '/auth/change-password',
                    isProtected: true,
                    permissionsRequired: ['auth.change_password'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        body: {
                            current_password: {
                                type: 'string',
                                required: true,
                                description: 'Mot de passe actuel'
                            },
                            password: {
                                type: 'string',
                                required: true,
                                min: 12,
                                description: 'Nouveau mot de passe'
                            },
                            password_confirmation: {
                                type: 'string',
                                required: true,
                                description: 'Confirmation'
                            }
                        }
                    },
                    exampleRequest: {
                        current_password: 'AncienMdp123!',
                        password: 'NouveauMdp456!',
                        password_confirmation: 'NouveauMdp456!'
                    },
                    responses: [{
                            status: 200,
                            description: 'Mot de passe changé',
                            example: {
                                success: true,
                                message: 'Mot de passe changé avec succès.'
                            }
                        },
                        {
                            status: 422,
                            description: 'Mot de passe actuel incorrect',
                            example: {
                                success: false,
                                message: 'Mot de passe actuel incorrect.'
                            }
                        }
                    ]
                },

                {
                    id: 'password-first-login',
                    module: 'password',
                    name: 'Première connexion — définir le mot de passe',
                    description: 'Définit le mot de passe initial lors de la première connexion. Nécessite le token temporaire avec ability password-change.',
                    method: 'POST',
                    path: '/auth/first-login',
                    isProtected: true,
                    abilityRequired: 'password-change',
                    headers: {
                        'Authorization': 'Bearer {change_password_token}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        body: {
                            password: {
                                type: 'string',
                                required: true,
                                min: 12,
                                description: 'Nouveau mot de passe'
                            },
                            password_confirmation: {
                                type: 'string',
                                required: true,
                                description: 'Confirmation'
                            }
                        }
                    },
                    exampleRequest: {
                        password: 'MonPremierMdp123!',
                        password_confirmation: 'MonPremierMdp123!'
                    },
                    responses: [{
                            status: 200,
                            description: 'Mot de passe initialisé',
                            example: {
                                success: true,
                                message: 'Mot de passe initialisé.',
                                data: {
                                    access_token: '...',
                                    user: {}
                                }
                            }
                        },
                        {
                            status: 422,
                            description: 'Changement non requis',
                            example: {
                                success: false,
                                code: 'PASSWORD_CHANGE_NOT_REQUIRED',
                                message: 'Le changement de mot de passe n\'est pas requis pour ce compte.'
                            }
                        }
                    ]
                },

                // ============================================================
                // 3.4 DOUBLE AUTHENTIFICATION (2FA & OTP)
                // ============================================================
                {
                    id: '2fa-enable',
                    module: '2fa',
                    name: 'Activer 2FA - QR Code',
                    description: 'Génère un secret TOTP pour l\'authenticator et retourne un QR code SVG à scanner. Support de plusieurs méthodes de 2FA : authenticator (TOTP), OTP (email/SMS).',
                    method: 'GET',
                    path: '/auth/2fa/qrcode',
                    isProtected: true,
                    permissionsRequired: ['auth.2fa'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        query: {
                            method: {
                                type: 'string',
                                required: false,
                                enum: ['authenticator', 'otp'],
                                default: 'authenticator',
                                description: 'Méthode de 2FA'
                            }
                        }
                    },
                    responses: [{
                            status: 200,
                            description: 'QR Code généré',
                            example: {
                                success: true,
                                data: {
                                    secret: '...',
                                    qr_code_svg: '<svg>...</svg>',
                                    method: 'authenticator'
                                }
                            }
                        },
                        {
                            status: 200,
                            description: 'OTP prêt',
                            example: {
                                success: true,
                                data: {
                                    method: 'otp',
                                    message: 'Un code OTP de vérification a été envoyé.',
                                    expires_in: 5
                                }
                            }
                        },
                        {
                            status: 422,
                            description: '2FA déjà activé',
                            example: {
                                success: false,
                                message: '2FA déjà activé.'
                            }
                        }
                    ]
                },

                {
                    id: '2fa-confirm',
                    module: '2fa',
                    name: 'Confirmer l\'activation 2FA',
                    description: 'Confirme l\'activation de la 2FA. Supporte plusieurs méthodes de confirmation : code TOTP (authenticator) ou code OTP (email/SMS). Génère 8 codes de récupération.',
                    method: 'POST',
                    path: '/auth/2fa/confirm',
                    isProtected: true,
                    permissionsRequired: ['auth.2fa'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        body: {
                            method: {
                                type: 'string',
                                required: true,
                                enum: ['authenticator', 'otp'],
                                description: 'Méthode de confirmation'
                            },
                            code: {
                                type: 'string',
                                required: true,
                                size: 6,
                                description: 'Code de confirmation'
                            }
                        }
                    },
                    exampleRequest: {
                        method: 'authenticator',
                        code: '123456'
                    },
                    responses: [{
                            status: 200,
                            description: '2FA activé',
                            example: {
                                success: true,
                                message: '2FA activé avec succès.',
                                data: {
                                    recovery_codes: ['abc123', '...'],
                                    method: 'authenticator'
                                }
                            }
                        },
                        {
                            status: 422,
                            description: 'Code invalide',
                            example: {
                                success: false,
                                message: 'Code invalide.'
                            }
                        }
                    ]
                },

                {
                    id: '2fa-disable',
                    module: '2fa',
                    name: 'Désactiver la 2FA',
                    description: 'Désactive la 2FA après vérification du mot de passe.',
                    method: 'POST',
                    path: '/auth/2fa/disable',
                    isProtected: true,
                    permissionsRequired: ['auth.2fa'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        body: {
                            password: {
                                type: 'string',
                                required: true,
                                description: 'Mot de passe actuel'
                            }
                        }
                    },
                    exampleRequest: {
                        password: 'MonPassword123!'
                    },
                    responses: [{
                            status: 200,
                            description: '2FA désactivée',
                            example: {
                                success: true,
                                message: '2FA désactivé.'
                            }
                        },
                        {
                            status: 403,
                            description: 'Mot de passe incorrect',
                            example: {
                                success: false,
                                message: 'Mot de passe incorrect.'
                            }
                        }
                    ]
                },

                {
                    id: '2fa-methods',
                    module: '2fa',
                    name: 'Méthodes 2FA disponibles',
                    description: 'Récupère la liste des méthodes de double authentification disponibles et configurées.',
                    method: 'GET',
                    path: '/auth/2fa/methods',
                    isProtected: true,
                    permissionsRequired: ['auth.2fa'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        body: {}
                    },
                    responses: [{
                        status: 200,
                        description: 'Méthodes disponibles',
                        example: {
                            success: true,
                            data: {
                                available_methods: ['authenticator', 'otp'],
                                configured_methods: ['authenticator'],
                                default_method: 'authenticator',
                                is_enabled: true
                            }
                        }
                    }]
                },

                {
                    id: '2fa-status',
                    module: '2fa',
                    name: 'Statut de la 2FA',
                    description: 'Récupère le statut complet de la double authentification pour l\'utilisateur.',
                    method: 'GET',
                    path: '/auth/2fa/status',
                    isProtected: true,
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        body: {}
                    },
                    responses: [{
                        status: 200,
                        description: 'Statut 2FA',
                        example: {
                            success: true,
                            data: {
                                enabled: true,
                                method: 'authenticator',
                                recovery_codes_count: 6
                            }
                        }
                    }]
                },

                {
                    id: '2fa-verify-login-public',
                    module: '2fa',
                    name: 'Vérifier 2FA (post-login)',
                    description: 'Vérifie le code de double authentification après une connexion. Supporte les deux méthodes : authenticator (TOTP) et OTP (email/SMS). Gestion des tentatives : après 5 échecs, verrouillage 30 min.',
                    method: 'POST',
                    path: '/auth/2fa/verify-login',
                    isProtected: true,
                    abilityRequired: '2fa-verify',
                    rateLimit: 'throttle:5,10',
                    headers: {
                        'Authorization': 'Bearer {two_factor_token}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        body: {
                            method: {
                                type: 'string',
                                required: false,
                                enum: ['authenticator', 'otp'],
                                default: 'authenticator',
                                description: 'Méthode de vérification'
                            },
                            code: {
                                type: 'string',
                                required: true,
                                size: 6,
                                description: 'Code de vérification'
                            },
                            trust_device: {
                                type: 'boolean',
                                required: false,
                                default: false,
                                description: 'Marquer l\'appareil comme de confiance'
                            }
                        }
                    },
                    exampleRequest: {
                        method: 'authenticator',
                        code: '123456',
                        trust_device: true
                    },
                    responses: [{
                            status: 200,
                            description: '2FA vérifiée',
                            example: {
                                success: true,
                                data: {
                                    user: {},
                                    access_token: '...',
                                    trusted_device: true
                                }
                            }
                        },
                        {
                            status: 401,
                            description: 'Token invalide',
                            example: {
                                success: false,
                                message: 'Token invalide.'
                            }
                        },
                        {
                            status: 422,
                            description: 'Code invalide',
                            example: {
                                success: false,
                                message: 'Code 2FA invalide.'
                            }
                        },
                        {
                            status: 423,
                            description: 'Compte verrouillé',
                            example: {
                                success: false,
                                message: 'Trop de tentatives. Réessayez dans 1800 secondes.',
                                is_locked: true
                            }
                        }
                    ]
                },

                {
                    id: '2fa-recovery-codes',
                    module: '2fa',
                    name: 'Gérer les codes de récupération 2FA',
                    description: 'Permet de régénérer ou de consulter les codes de récupération de la 2FA.',
                    method: 'POST',
                    path: '/auth/2fa/recovery-codes',
                    isProtected: true,
                    permissionsRequired: ['auth.2fa'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        body: {
                            action: {
                                type: 'string',
                                required: true,
                                enum: ['regenerate', 'send'],
                                description: 'Action : "regenerate" ou "send"'
                            }
                        }
                    },
                    exampleRequest: {
                        action: 'regenerate'
                    },
                    responses: [{
                            status: 200,
                            description: 'Codes régénérés',
                            example: {
                                success: true,
                                message: 'Nouveaux codes de récupération générés.',
                                data: {
                                    recovery_codes: ['abc123', '...'],
                                    count: 8
                                }
                            }
                        },
                        {
                            status: 200,
                            description: 'Codes envoyés',
                            example: {
                                success: true,
                                message: 'Codes de récupération envoyés par email.'
                            }
                        }
                    ]
                },

                {
                    id: '2fa-verify-recovery',
                    module: '2fa',
                    name: 'Vérifier un code de récupération 2FA',
                    description: 'Permet de vérifier un code de récupération de la 2FA lors de la perte d\'accès à l\'authenticator.',
                    method: 'POST',
                    path: '/auth/2fa/verify-recovery',
                    isProtected: false,
                    rateLimit: 'throttle:5,30',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        body: {
                            login: {
                                type: 'string',
                                required: true,
                                description: 'Login de l\'utilisateur'
                            },
                            code: {
                                type: 'string',
                                required: true,
                                description: 'Code de récupération à 10 caractères'
                            }
                        }
                    },
                    exampleRequest: {
                        login: 'jdupont',
                        code: 'abc123def4'
                    },
                    responses: [{
                            status: 200,
                            description: 'Code de récupération valide',
                            example: {
                                success: true,
                                message: 'Code de récupération valide.',
                                data: {
                                    user_uuid: '...',
                                    reset_token: '...'
                                }
                            }
                        },
                        {
                            status: 422,
                            description: 'Code invalide',
                            example: {
                                success: false,
                                message: 'Code de récupération invalide ou déjà utilisé.'
                            }
                        },
                        {
                            status: 429,
                            description: 'Trop de tentatives',
                            example: {
                                success: false,
                                message: 'Trop de tentatives. Veuillez patienter.'
                            }
                        }
                    ]
                },

                {
                    id: 'otp-send',
                    module: '2fa',
                    name: 'Envoyer un code OTP',
                    description: 'Génère et envoie un code OTP à 6 chiffres par email, SMS ou WhatsApp.',
                    method: 'POST',
                    path: '/auth/otp/send',
                    isProtected: true,
                    permissionsRequired: ['auth.2fa'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        body: {
                            channel: {
                                type: 'string',
                                required: true,
                                enum: ['email', 'sms', 'whatsapp'],
                                description: 'Canal d\'envoi'
                            },
                            purpose: {
                                type: 'string',
                                required: true,
                                enum: ['login', '2fa', 'reset'],
                                description: 'Usage du code'
                            },
                            login: {
                                type: 'string',
                                required: false,
                                description: 'Login de l\'utilisateur'
                            }
                        }
                    },
                    exampleRequest: {
                        channel: 'email',
                        purpose: '2fa'
                    },
                    responses: [{
                            status: 200,
                            description: 'OTP envoyé',
                            example: {
                                success: true,
                                code: 'OTP_SENT',
                                message: 'Code OTP envoyé par email.',
                                data: {
                                    channel: 'email',
                                    purpose: '2fa',
                                    expires_in: 5
                                }
                            }
                        },
                        {
                            status: 422,
                            description: 'Canal invalide',
                            example: {
                                success: false,
                                code: 'CHANNEL_INVALID',
                                message: 'Le canal WhatsApp n\'est pas encore configuré.'
                            }
                        },
                        {
                            status: 422,
                            description: 'Téléphone invalide',
                            example: {
                                success: false,
                                code: 'TELEPHONE_INVALID',
                                message: 'Numéro de téléphone invalide.'
                            }
                        }
                    ]
                },

                {
                    id: 'otp-verify-code',
                    module: '2fa',
                    name: 'Vérifier un OTP pour une opération',
                    description: 'Vérifie un code OTP précédemment envoyé. Si le `purpose` est "reset", génère un token de réinitialisation.',
                    method: 'POST',
                    path: '/auth/otp/verify-code',
                    isProtected: false,
                    rateLimit: 'throttle:5,10',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        body: {
                            login: {
                                type: 'string',
                                required: true,
                                description: 'Login de l\'utilisateur'
                            },
                            code: {
                                type: 'string',
                                required: true,
                                size: 6,
                                pattern: '^[0-9]{6}$',
                                description: 'Code OTP'
                            },
                            purpose: {
                                type: 'string',
                                required: true,
                                enum: ['login', '2fa', 'reset'],
                                description: 'Usage du code'
                            }
                        }
                    },
                    exampleRequest: {
                        login: 'jdupont',
                        code: '123456',
                        purpose: 'reset'
                    },
                    responses: [{
                            status: 200,
                            description: 'OTP vérifié avec token',
                            example: {
                                success: true,
                                code: 'OTP_VERIFIED',
                                message: 'Code OTP vérifié.',
                                data: {
                                    user_uuid: '...',
                                    reset_token: '...'
                                }
                            }
                        },
                        {
                            status: 422,
                            description: 'OTP invalide',
                            example: {
                                success: false,
                                code: 'OTP_INVALID',
                                message: 'Code OTP invalide ou expiré.'
                            }
                        },
                        {
                            status: 429,
                            description: 'Trop de tentatives',
                            example: {
                                success: false,
                                message: 'Trop de tentatives. Veuillez patienter.'
                            }
                        }
                    ]
                },

                // ============================================================
                // 3.5 QUESTIONS DE SÉCURITÉ
                // ============================================================
                {
                    id: 'security-suggested',
                    module: 'security',
                    name: 'Questions suggérées (publiques)',
                    description: 'Retourne une liste statique de questions de sécurité prédéfinies, groupées par catégorie.',
                    method: 'GET',
                    path: '/security/questions/suggested',
                    isProtected: false,
                    headers: {
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        body: {}
                    },
                    responses: [{
                        status: 200,
                        description: 'Questions suggérées',
                        example: {
                            success: true,
                            code: 'QUESTIONS_SUGGESTED',
                            data: [{
                                category: 'Personnelle',
                                questions: ['...']
                            }]
                        }
                    }]
                },

                {
                    id: 'security-questions-available',
                    module: 'security',
                    name: 'Questions disponibles',
                    description: 'Récupère toutes les questions de sécurité actives disponibles dans le système.',
                    method: 'GET',
                    path: '/security/questions',
                    isProtected: false,
                    headers: {
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        body: {}
                    },
                    responses: [{
                        status: 200,
                        description: 'Liste des questions',
                        example: {
                            success: true,
                            data: [{
                                uuid: '...',
                                question_text: '...',
                                category: '...'
                            }]
                        }
                    }]
                },

                {
                    id: 'security-verify-email',
                    module: 'security',
                    name: 'Vérifier les questions (par login)',
                    description: 'Vérifie si un compte (par login) a configuré des questions de sécurité. Rate-limité par IP.',
                    method: 'POST',
                    path: '/security/verify-email',
                    isProtected: false,
                    rateLimit: 'throttle:5,15 + ThrottleService (5/300)',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        body: {
                            login: {
                                type: 'string',
                                required: true,
                                max: 100,
                                description: 'Login de l\'utilisateur'
                            }
                        }
                    },
                    exampleRequest: {
                        login: 'jdupont'
                    },
                    responses: [{
                            status: 200,
                            description: 'Compte trouvé',
                            example: {
                                success: true,
                                data: {
                                    user_uuid: '...',
                                    has_questions: true,
                                    questions: []
                                }
                            }
                        },
                        {
                            status: 404,
                            description: 'Login non trouvé',
                            example: {
                                success: false,
                                message: 'Login non trouvé.'
                            }
                        },
                        {
                            status: 429,
                            description: 'Trop de tentatives',
                            example: {
                                success: false,
                                message: 'Trop de tentatives. Veuillez patienter.',
                                code: 'TOO_MANY_ATTEMPTS'
                            }
                        }
                    ]
                },

                {
                    id: 'security-verify-answer',
                    module: 'security',
                    name: 'Vérifier les réponses (multi-questions)',
                    description: 'Vérifie une ou plusieurs réponses aux questions de sécurité. Retourne un token de réinitialisation si toutes les réponses sont correctes.',
                    method: 'POST',
                    path: '/security/verify-answer',
                    isProtected: false,
                    rateLimit: 'throttle:5,15 + ThrottleService (5/300 par user)',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        body: {
                            login: {
                                type: 'string',
                                required: true,
                                max: 100,
                                description: 'Login de l\'utilisateur'
                            },
                            questions: {
                                type: 'array',
                                required: true,
                                min: 1,
                                description: 'Tableau des questions/réponses'
                            },
                            'questions.*.question_uuid': {
                                type: 'uuid',
                                required: true,
                                description: 'UUID de la question'
                            },
                            'questions.*.answer': {
                                type: 'string',
                                required: true,
                                min: 1,
                                max: 255,
                                description: 'Réponse à vérifier'
                            }
                        }
                    },
                    exampleRequest: {
                        login: 'jdupont',
                        questions: [{
                            question_uuid: 'q1-uuid',
                            answer: 'Rex'
                        }]
                    },
                    responses: [{
                            status: 200,
                            description: 'Réponses correctes',
                            example: {
                                success: true,
                                message: 'Toutes les réponses sont correctes.',
                                data: {
                                    verified: true,
                                    user_uuid: '...',
                                    reset_token: '...',
                                    results: [{
                                        question_uuid: '...',
                                        verified: true
                                    }]
                                }
                            }
                        },
                        {
                            status: 422,
                            description: 'Réponses incorrectes',
                            example: {
                                success: false,
                                message: 'Une ou plusieurs réponses sont incorrectes.',
                                remaining_attempts: 3
                            }
                        },
                        {
                            status: 429,
                            description: 'Trop de tentatives',
                            example: {
                                success: false,
                                message: 'Trop de tentatives...',
                                code: 'TOO_MANY_ATTEMPTS'
                            }
                        }
                    ]
                },

                {
                    id: 'security-user-questions-get',
                    module: 'security',
                    name: 'Mes questions configurées',
                    description: 'Récupère les questions de sécurité déjà configurées par l\'utilisateur (sans les réponses).',
                    method: 'GET',
                    path: '/security/user-questions',
                    isProtected: true,
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        body: {}
                    },
                    responses: [{
                        status: 200,
                        description: 'Questions configurées',
                        example: {
                            success: true,
                            data: {
                                user_uuid: '...',
                                has_configured: true,
                                questions: []
                            }
                        }
                    }]
                },

                {
                    id: 'security-user-questions-set',
                    module: 'security',
                    name: 'Configurer mes questions',
                    description: 'Définit (remplace) les réponses de sécurité de l\'utilisateur. Entre 3 et 5 questions distinctes. Nécessite le mot de passe actuel.',
                    method: 'POST',
                    path: '/security/user-questions',
                    isProtected: true,
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        body: {
                            answers: {
                                type: 'array',
                                required: true,
                                min: 3,
                                max: 5,
                                description: 'Tableau { question_uuid, answer }'
                            },
                            password: {
                                type: 'string',
                                required: true,
                                description: 'Mot de passe actuel'
                            }
                        }
                    },
                    exampleRequest: {
                        answers: [{
                            question_uuid: 'q1-uuid',
                            answer: 'Rex'
                        }, {
                            question_uuid: 'q2-uuid',
                            answer: 'Bouaké'
                        }, {
                            question_uuid: 'q3-uuid',
                            answer: 'Aya'
                        }],
                        password: 'MonPassword123!'
                    },
                    responses: [{
                            status: 200,
                            description: 'Questions configurées',
                            example: {
                                success: true,
                                message: 'Questions de sécurité configurées avec succès.'
                            }
                        },
                        {
                            status: 403,
                            description: 'Mot de passe incorrect',
                            example: {
                                success: false,
                                message: 'Mot de passe incorrect.',
                                code: 'INVALID_PASSWORD'
                            }
                        },
                        {
                            status: 422,
                            description: 'Validation échouée',
                            example: {
                                success: false,
                                message: 'Vous ne pouvez pas sélectionner deux fois la même question.'
                            }
                        }
                    ]
                },

                // ============================================================
                // 3.6 ADMIN — QUESTIONS DE SÉCURITÉ
                // ============================================================
                {
                    id: 'security-questions-create-admin',
                    module: 'security',
                    name: '[Admin] Créer une question',
                    description: 'Crée une nouvelle question de sécurité. Réservé aux utilisateurs avec la permission security_questions.gerer.',
                    method: 'POST',
                    path: '/admin/security/questions',
                    isProtected: true,
                    permissionsRequired: ['security_questions.gerer'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        body: {
                            question_text: {
                                type: 'string',
                                required: true,
                                max: 255,
                                description: 'Texte de la question (unique)'
                            },
                            category: {
                                type: 'string',
                                required: false,
                                max: 50,
                                description: 'Catégorie'
                            },
                            is_active: {
                                type: 'boolean',
                                required: false,
                                default: true,
                                description: 'Question active'
                            },
                            is_system: {
                                type: 'boolean',
                                required: false,
                                default: false,
                                description: 'Question système protégée'
                            }
                        }
                    },
                    exampleRequest: {
                        question_text: 'Quel est le nom de votre premier employeur ?',
                        category: 'Professionnelle'
                    },
                    responses: [{
                            status: 201,
                            description: 'Question créée',
                            example: {
                                success: true,
                                message: 'Question de sécurité créée avec succès.',
                                data: {}
                            }
                        },
                        {
                            status: 422,
                            description: 'Erreur de validation',
                            example: {
                                success: false,
                                message: 'Données invalides.'
                            }
                        }
                    ]
                },

                {
                    id: 'security-questions-update-admin',
                    module: 'security',
                    name: '[Admin] Modifier une question',
                    description: 'Met à jour une question existante. Les questions système sont protégées (sauf Super Admin).',
                    method: 'PUT',
                    path: '/admin/security/questions/{uuid}',
                    isProtected: true,
                    permissionsRequired: ['security_questions.gerer'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID de la question'
                            }
                        },
                        body: {
                            question_text: {
                                type: 'string',
                                required: false,
                                max: 255,
                                description: 'Texte de la question'
                            },
                            category: {
                                type: 'string',
                                required: false,
                                max: 50,
                                description: 'Catégorie'
                            },
                            is_active: {
                                type: 'boolean',
                                required: false,
                                description: 'Question active'
                            }
                        }
                    },
                    responses: [{
                            status: 200,
                            description: 'Question mise à jour',
                            example: {
                                success: true,
                                message: 'Question de sécurité mise à jour avec succès.',
                                data: {}
                            }
                        },
                        {
                            status: 403,
                            description: 'Question système protégée',
                            example: {
                                success: false,
                                message: 'Les questions système ne peuvent pas être modifiées.'
                            }
                        }
                    ]
                },

                {
                    id: 'security-questions-delete-admin',
                    module: 'security',
                    name: '[Admin] Supprimer une question',
                    description: 'Supprime (soft delete) une question si elle n\'est pas utilisée et n\'est pas système.',
                    method: 'DELETE',
                    path: '/admin/security/questions/{uuid}',
                    isProtected: true,
                    isDestructive: true,
                    permissionsRequired: ['security_questions.gerer'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID de la question'
                            }
                        },
                        body: {}
                    },
                    responses: [{
                            status: 200,
                            description: 'Question supprimée',
                            example: {
                                success: true,
                                message: 'Question de sécurité supprimée avec succès.'
                            }
                        },
                        {
                            status: 422,
                            description: 'Question utilisée ou protégée',
                            example: {
                                success: false,
                                message: 'Cette question est utilisée par des utilisateurs et ne peut donc pas être supprimée.'
                            }
                        }
                    ]
                },

                // ============================================================
                // 3.7 PROFIL, APPAREILS & SESSIONS
                // ============================================================
                {
                    id: 'auth-me',
                    module: 'profile',
                    name: 'Utilisateur connecté',
                    description: 'Récupère les informations complètes de l\'utilisateur authentifié (rôle, permissions, détails, partenaire, réseau, agences, contrats).',
                    method: 'GET',
                    path: '/auth/me',
                    isProtected: true,
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        body: {}
                    },
                    responses: [{
                        status: 200,
                        description: 'Informations utilisateur',
                        example: {
                            success: true,
                            data: {
                                uuid_user: '...',
                                email: '...',
                                role: {},
                                permissions: []
                            }
                        }
                    }]
                },

                {
                    id: 'auth-logout',
                    module: 'profile',
                    name: 'Déconnexion',
                    description: 'Révoque uniquement le token Sanctum de la session courante.',
                    method: 'POST',
                    path: '/auth/logout',
                    isProtected: true,
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        body: {}
                    },
                    responses: [{
                        status: 200,
                        description: 'Déconnexion réussie',
                        example: {
                            success: true,
                            message: 'Déconnexion réussie.'
                        }
                    }]
                },

                {
                    id: 'auth-logout-all',
                    module: 'profile',
                    name: 'Déconnexion de tous les appareils',
                    description: 'Révoque tous les tokens Sanctum de l\'utilisateur et envoie un email de notification.',
                    method: 'POST',
                    path: '/auth/logout-all',
                    isProtected: true,
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        body: {}
                    },
                    responses: [{
                        status: 200,
                        description: 'Toutes les sessions révoquées',
                        example: {
                            success: true,
                            message: 'Déconnexion de tous les appareils.'
                        }
                    }]
                },

                {
                    id: 'auth-refresh',
                    module: 'profile',
                    name: 'Rafraîchir le token',
                    description: 'Génère un nouveau token (24h) et supprime l\'ancien.',
                    method: 'POST',
                    path: '/auth/refresh',
                    isProtected: true,
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'X-Device-Name': 'API Token',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        body: {}
                    },
                    responses: [{
                        status: 200,
                        description: 'Nouveau token généré',
                        example: {
                            success: true,
                            data: {
                                access_token: '...',
                                token_type: 'Bearer',
                                expires_at: '...'
                            }
                        }
                    }]
                },

                {
                    id: 'profile-show',
                    module: 'profile',
                    name: 'Mon profil',
                    description: 'Récupère les informations complètes du profil de l\'utilisateur connecté. Retourne les données utilisateur, ses détails (nom, prénoms, contact, photo, etc.), son rôle, ses permissions, ses partenaires, réseaux, agences et contrats.',
                    method: 'GET',
                    path: '/profile',
                    isProtected: true,
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        body: {}
                    },
                    responses: [{
                            status: 200,
                            description: 'Profil utilisateur récupéré avec succès',
                            example: {
                                success: true,
                                message: 'Profil récupéré avec succès.',
                                code: 'PROFILE_FOUND',
                                data: {
                                    uuid_user: '550e8400-e29b-41d4-a716-446655440000',
                                    login: 'jean.dupont',
                                    email: 'jean.dupont@ynov.ci',
                                    user_type: 'user_interne',
                                    status: 'actif',
                                    is_first_login: false,
                                    is_online: true,
                                    is_locked: false,
                                    last_login_at: '2025-01-15T10:30:00.000000Z',
                                    email_verified_at: '2025-01-15T10:00:00.000000Z',
                                    two_factor_enabled: true,
                                    role: {
                                        uuid_role: '550e8400-e29b-41d4-a716-446655440001',
                                        libelle: 'Administrateur',
                                        is_super_admin: false
                                    },
                                    details: {
                                        uuid_user_details: '550e8400-e29b-41d4-a716-446655440002',
                                        code_agent: 'AG2025001',
                                        matricule: 'MAT2025001',
                                        numero_client: 'CLT2025001',
                                        nom: 'Dupont',
                                        prenoms: 'Jean-Marc',
                                        full_name: 'Jean-Marc Dupont',
                                        fonction: 'Directeur Commercial',
                                        service: 'Commercial',
                                        departement: 'Ventes',
                                        mobile_1: '+2250708091011',
                                        mobile_2: '+2250708091012',
                                        telephone_fixe: '+2252720304050',
                                        email_pro: 'jean.dupont@yako.ci',
                                        photo: null,
                                        photo_path: 'profiles/550e8400-e29b-41d4-a716-446655440000/profile_550e8400-e29b-41d4-a716-446655440000_1698765432.jpg',
                                        photo_url: 'https://api.ynov.ci/storage/documents/profiles/550e8400-e29b-41d4-a716-446655440000/profile_550e8400-e29b-41d4-a716-446655440000_1698765432.jpg',
                                        date_naissance: '1985-06-15',
                                        lieu_naissance: 'Abidjan',
                                        lieu_residence: 'Cocody',
                                        nationalite: 'Ivoirienne',
                                        genre: 'M',
                                        civilite: 'M.',
                                        adresse_complete: 'Cocody, Abidjan',
                                        ville: 'Abidjan',
                                        code_postal: '01 BP 1234',
                                        pays: 'Côte d\'Ivoire',
                                        date_embauche: '2020-01-15',
                                        statut_employe: 'CDI',
                                        type_contrat: 'Permanent',
                                        created_at: '2025-01-15T10:00:00.000000Z',
                                        updated_at: '2025-01-15T14:30:00.000000Z'
                                    },
                                    partner: {
                                        uuid_partner: '...',
                                        designation: 'YAKO AFRICA'
                                    },
                                    reseau: {
                                        uuid_reseau: '...',
                                        libelle: 'Réseau Abidjan'
                                    },
                                    agences: [{
                                        uuid_agence: '...',
                                        libelle: 'YAKO Plateau',
                                        is_primary: true
                                    }],
                                    permissions_grouped: {
                                        'Utilisateurs': ['users.afficher', 'users.creer', 'users.modifier'],
                                        'Rôles': ['roles.afficher', 'roles.creer']
                                    }
                                }
                            }
                        },
                        {
                            status: 401,
                            description: 'Non authentifié',
                            example: {
                                success: false,
                                message: 'Non authentifié.'
                            }
                        }
                    ]
                },

                {
                    id: 'profile-update',
                    module: 'profile',
                    name: 'Mettre à jour mon profil',
                    description: 'Modifie les informations personnelles de l\'utilisateur connecté. **NOUVEAU :** Support de l\'upload de photo de profil (image) et des URLs externes. Permet de mettre à jour le login, l\'email, les coordonnées, la photo, et les informations professionnelles.',
                    method: 'PUT',
                    path: '/profile',
                    isProtected: true,
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Content-Type': 'multipart/form-data (pour upload photo) ou application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        body: {
                            login: {
                                type: 'string',
                                required: false,
                                max: 100,
                                description: 'Identifiant de connexion (unique)'
                            },
                            email: {
                                type: 'email',
                                required: false,
                                max: 100,
                                description: 'Email principal (unique)'
                            },
                            nom: {
                                type: 'string',
                                required: false,
                                max: 55,
                                description: 'Nom de famille'
                            },
                            prenoms: {
                                type: 'string',
                                required: false,
                                max: 255,
                                description: 'Prénoms'
                            },
                            civilite: {
                                type: 'string',
                                required: false,
                                enum: ['M.', 'Mme', 'Mlle', 'Dr', 'Pr'],
                                description: 'Civilité'
                            },
                            genre: {
                                type: 'string',
                                required: false,
                                enum: ['M', 'F'],
                                description: 'Genre'
                            },
                            date_naissance: {
                                type: 'date',
                                required: false,
                                description: 'Date de naissance (avant aujourd\'hui)'
                            },
                            lieu_naissance: {
                                type: 'string',
                                required: false,
                                max: 55,
                                description: 'Lieu de naissance'
                            },
                            nationalite: {
                                type: 'string',
                                required: false,
                                max: 55,
                                description: 'Nationalité'
                            },
                            mobile_1: {
                                type: 'string',
                                required: false,
                                max: 25,
                                description: 'Téléphone principal'
                            },
                            mobile_2: {
                                type: 'string',
                                required: false,
                                max: 25,
                                description: 'Téléphone secondaire'
                            },
                            telephone_fixe: {
                                type: 'string',
                                required: false,
                                max: 25,
                                description: 'Téléphone fixe'
                            },
                            email_pro: {
                                type: 'email',
                                required: false,
                                max: 100,
                                description: 'Email professionnel (unique)'
                            },
                            adresse_complete: {
                                type: 'string',
                                required: false,
                                max: 255,
                                description: 'Adresse complète'
                            },
                            ville: {
                                type: 'string',
                                required: false,
                                max: 100,
                                description: 'Ville'
                            },
                            pays: {
                                type: 'string',
                                required: false,
                                max: 100,
                                description: 'Pays'
                            },
                            code_postal: {
                                type: 'string',
                                required: false,
                                max: 20,
                                description: 'Code postal'
                            },
                            lieu_residence: {
                                type: 'string',
                                required: false,
                                max: 55,
                                description: 'Lieu de résidence'
                            },
                            fonction: {
                                type: 'string',
                                required: false,
                                max: 55,
                                description: 'Fonction'
                            },
                            service: {
                                type: 'string',
                                required: false,
                                max: 55,
                                description: 'Service'
                            },
                            departement: {
                                type: 'string',
                                required: false,
                                max: 55,
                                description: 'Département'
                            },
                            date_embauche: {
                                type: 'date',
                                required: false,
                                description: 'Date d\'embauche'
                            },
                            statut_employe: {
                                type: 'string',
                                required: false,
                                max: 50,
                                description: 'Statut employé'
                            },
                            type_contrat: {
                                type: 'string',
                                required: false,
                                max: 50,
                                description: 'Type de contrat'
                            },
                            code_agent: {
                                type: 'string',
                                required: false,
                                max: 35,
                                description: 'Code agent'
                            },
                            matricule: {
                                type: 'string',
                                required: false,
                                max: 35,
                                description: 'Matricule'
                            },
                            numero_client: {
                                type: 'string',
                                required: false,
                                max: 35,
                                description: 'Numéro client'
                            },
                            photo: {
                                type: 'file (image)',
                                required: false,
                                mimes: 'jpeg,png,jpg,gif,webp',
                                max: '2048 Ko',
                                description: 'Photo de profil (upload)'
                            },
                            photo_url: {
                                type: 'string (url)',
                                required: false,
                                max: 255,
                                description: 'URL externe de la photo'
                            },
                            remove_photo: {
                                type: 'boolean',
                                required: false,
                                description: 'Supprimer la photo actuelle'
                            },
                            preferences: {
                                type: 'object',
                                required: false,
                                description: 'Préférences utilisateur (JSON)'
                            }
                        }
                    },
                    exampleRequest: {
                        nom: 'Dupont',
                        prenoms: 'Jean-Marc',
                        fonction: 'Directeur Commercial',
                        mobile_1: '+2250708091011',
                        ville: 'Abidjan',
                        pays: 'Côte d\'Ivoire',
                        photo: '[Fichier image]'
                    },
                    exampleRequestJson: {
                        nom: 'Dupont',
                        prenoms: 'Jean-Marc',
                        fonction: 'Directeur Commercial',
                        mobile_1: '+2250708091011',
                        ville: 'Abidjan',
                        photo_url: 'https://example.com/photos/jean.jpg'
                    },
                    responses: [{
                            status: 200,
                            description: 'Profil mis à jour avec succès',
                            example: {
                                success: true,
                                message: 'Profil mis à jour avec succès.',
                                code: 'PROFILE_UPDATED',
                                data: {}
                            }
                        },
                        {
                            status: 422,
                            description: 'Erreur de validation',
                            example: {
                                success: false,
                                message: 'Données invalides.',
                                errors: {
                                    email: ['Cet email est déjà utilisé.'],
                                    photo: ['La photo ne doit pas dépasser 2 Mo.']
                                }
                            }
                        },
                        {
                            status: 401,
                            description: 'Non authentifié',
                            example: {
                                success: false,
                                message: 'Non authentifié.'
                            }
                        }
                    ]
                },

                {
                    id: 'profile-delete-photo',
                    module: 'profile',
                    name: 'Supprimer la photo de profil',
                    description: 'Supprime la photo de profil de l\'utilisateur connecté. La photo est supprimée du serveur et les champs `photo` et `photo_path` sont vidés.',
                    method: 'DELETE',
                    path: '/profile/photo',
                    isProtected: true,
                    isDestructive: true,
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        body: {}
                    },
                    responses: [{
                            status: 200,
                            description: 'Photo supprimée avec succès',
                            example: {
                                success: true,
                                message: 'Photo de profil supprimée avec succès.',
                                code: 'PHOTO_DELETED'
                            }
                        },
                        {
                            status: 404,
                            description: 'Aucune photo à supprimer',
                            example: {
                                success: false,
                                message: 'Aucune photo de profil à supprimer.',
                                code: 'NO_PHOTO_FOUND'
                            }
                        },
                        {
                            status: 401,
                            description: 'Non authentifié',
                            example: {
                                success: false,
                                message: 'Non authentifié.'
                            }
                        }
                    ]
                },

                {
                    id: 'devices-list',
                    module: 'profile',
                    name: 'Liste des appareils',
                    description: 'Récupère la liste des appareils enregistrés pour l\'utilisateur.',
                    method: 'GET',
                    path: '/auth/devices',
                    isProtected: true,
                    permissionsRequired: ['auth.devices'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        body: {}
                    },
                    responses: [{
                        status: 200,
                        description: 'Liste des appareils',
                        example: {
                            success: true,
                            data: [{
                                uuid_device: '...',
                                device_name: 'iPhone de Jean',
                                is_trusted: false
                            }]
                        }
                    }]
                },

                {
                    id: 'devices-trust',
                    module: 'profile',
                    name: 'Approuver un appareil',
                    description: 'Marque un appareil comme "de confiance", évitant la 2FA lors des prochaines connexions.',
                    method: 'POST',
                    path: '/auth/devices/{uuidDevice}/trust',
                    isProtected: true,
                    permissionsRequired: ['auth.devices'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuidDevice: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID de l\'appareil'
                            }
                        },
                        body: {}
                    },
                    responses: [{
                        status: 200,
                        description: 'Appareil approuvé',
                        example: {
                            success: true,
                            message: 'Appareil approuvé.'
                        }
                    }]
                },

                {
                    id: 'devices-revoke',
                    module: 'profile',
                    name: 'Révoquer un appareil',
                    description: 'Supprime un appareil de la liste des appareils connus.',
                    method: 'DELETE',
                    path: '/auth/devices/{uuidDevice}',
                    isProtected: true,
                    isDestructive: true,
                    permissionsRequired: ['auth.devices'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuidDevice: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID de l\'appareil'
                            }
                        },
                        body: {}
                    },
                    responses: [{
                            status: 200,
                            description: 'Appareil révoqué',
                            example: {
                                success: true,
                                message: 'Appareil révoqué.'
                            }
                        },
                        {
                            status: 404,
                            description: 'Appareil non trouvé',
                            example: {
                                success: false,
                                message: 'Appareil non trouvé.'
                            }
                        }
                    ]
                },

                {
                    id: 'sessions-list',
                    module: 'profile',
                    name: 'Liste des sessions',
                    description: 'Récupère la liste des tokens Sanctum actifs (sessions) de l\'utilisateur.',
                    method: 'GET',
                    path: '/auth/sessions',
                    isProtected: true,
                    permissionsRequired: ['auth.sessions'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        body: {}
                    },
                    responses: [{
                        status: 200,
                        description: 'Liste des sessions',
                        example: {
                            success: true,
                            data: [{
                                id: 1,
                                name: 'API Token',
                                last_used_at: '...'
                            }]
                        }
                    }]
                },

                {
                    id: 'sessions-revoke',
                    module: 'profile',
                    name: 'Révoquer une session',
                    description: 'Révoque un token spécifique (autre appareil).',
                    method: 'DELETE',
                    path: '/auth/sessions/{tokenId}',
                    isProtected: true,
                    isDestructive: true,
                    permissionsRequired: ['auth.sessions'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            tokenId: {
                                type: 'integer',
                                required: true,
                                description: 'ID du token Sanctum'
                            }
                        },
                        body: {}
                    },
                    responses: [{
                            status: 200,
                            description: 'Session révoquée',
                            example: {
                                success: true,
                                message: 'Session révoquée.'
                            }
                        },
                        {
                            status: 404,
                            description: 'Session non trouvée',
                            example: {
                                success: false,
                                message: 'Session non trouvée.'
                            }
                        }
                    ]
                },

                {
                    id: 'login-attempts-list',
                    module: 'profile',
                    name: 'Historique de connexion',
                    description: 'Liste paginée des tentatives de connexion (réussies et échouées) de l\'utilisateur.',
                    method: 'GET',
                    path: '/auth/login-attempts',
                    isProtected: true,
                    permissionsRequired: ['auth.login_attempts'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        query: {
                            per_page: {
                                type: 'integer',
                                required: false,
                                default: 20,
                                description: 'Nombre par page'
                            }
                        }
                    },
                    responses: [{
                        status: 200,
                        description: 'Historique des tentatives',
                        example: {
                            success: true,
                            data: [{
                                login_attempted: '...',
                                is_successful: true
                            }]
                        }
                    }]
                },

                // ============================================================
                // 3.8 GESTION DES UTILISATEURS
                // ============================================================
                {
                    id: 'users-list',
                    module: 'users',
                    name: 'Liste des utilisateurs',
                    description: 'Liste paginée des utilisateurs. Filtrée selon la portée de l\'utilisateur connecté.',
                    method: 'GET',
                    path: '/users',
                    isProtected: true,
                    permissionsRequired: ['users.afficher'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        query: {
                            per_page: {
                                type: 'integer',
                                required: false,
                                default: 20,
                                description: 'Nombre par page'
                            }
                        }
                    },
                    responses: [{
                        status: 200,
                        description: 'Liste des utilisateurs',
                        example: {
                            success: true,
                            data: []
                        }
                    }]
                },

                {
                    id: 'users-show',
                    module: 'users',
                    name: 'Détails d\'un utilisateur',
                    description: 'Récupère les informations complètes d\'un utilisateur (rôle, permissions, détails, partenaire, réseau, agences).',
                    method: 'GET',
                    path: '/users/{uuid_user}',
                    isProtected: true,
                    permissionsRequired: ['users.afficher'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_user: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID de l\'utilisateur'
                            }
                        }
                    },
                    responses: [{
                        status: 200,
                        description: 'Détails de l\'utilisateur',
                        example: {
                            success: true,
                            data: {}
                        }
                    }]
                },

                {
                    id: 'users-create',
                    module: 'users',
                    name: 'Créer un utilisateur',
                    description: 'Crée un nouvel utilisateur interne/partenaire/admin avec ses détails. Envoie un email de bienvenue.',
                    method: 'POST',
                    path: '/users',
                    isProtected: true,
                    permissionsRequired: ['users.creer'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        body: {
                            email: {
                                type: 'email',
                                required: true,
                                description: 'Email (unique)'
                            },
                            login: {
                                type: 'string',
                                required: false,
                                max: 100,
                                description: 'Identifiant (unique)'
                            },
                            password: {
                                type: 'string',
                                required: true,
                                min: 12,
                                description: 'Mot de passe'
                            },
                            role_uuid: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID du rôle'
                            },
                            user_type: {
                                type: 'string',
                                required: true,
                                enum: ['client', 'user_interne', 'user_partner', 'admin'],
                                description: 'Type'
                            },
                            partner_uuid: {
                                type: 'uuid',
                                required: false,
                                description: 'UUID du partenaire'
                            },
                            reseau_uuid: {
                                type: 'uuid',
                                required: false,
                                description: 'UUID du réseau'
                            },
                            agence_uuid: {
                                type: 'uuid',
                                required: false,
                                description: 'UUID de l\'agence'
                            },
                            nom: {
                                type: 'string',
                                required: true,
                                max: 55,
                                description: 'Nom'
                            },
                            prenoms: {
                                type: 'string',
                                required: true,
                                max: 255,
                                description: 'Prénoms'
                            },
                            fonction: {
                                type: 'string',
                                required: false,
                                max: 55,
                                description: 'Fonction'
                            },
                            mobile_1: {
                                type: 'string',
                                required: false,
                                max: 25,
                                description: 'Téléphone'
                            }
                        }
                    },
                    exampleRequest: {
                        email: 'nouveau@ynov.ci',
                        login: 'nouveau',
                        password: 'Password123!',
                        role_uuid: 'role-uuid',
                        user_type: 'user_interne',
                        nom: 'Dupont',
                        prenoms: 'Jean'
                    },
                    responses: [{
                        status: 201,
                        description: 'Utilisateur créé',
                        example: {
                            success: true,
                            message: 'Utilisateur créé.',
                            data: {}
                        }
                    }]
                },

                {
                    id: 'users-update',
                    module: 'users',
                    name: 'Modifier un utilisateur',
                    description: 'Met à jour les informations d\'un utilisateur.',
                    method: 'PUT',
                    path: '/users/{uuid_user}',
                    isProtected: true,
                    permissionsRequired: ['users.modifier'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_user: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID de l\'utilisateur'
                            }
                        },
                        body: {
                            email: {
                                type: 'email',
                                required: false,
                                description: 'Email (unique)'
                            },
                            login: {
                                type: 'string',
                                required: false,
                                max: 100,
                                description: 'Identifiant'
                            },
                            role_uuid: {
                                type: 'uuid',
                                required: false,
                                description: 'UUID du rôle'
                            },
                            user_type: {
                                type: 'string',
                                required: false,
                                enum: ['client', 'user_interne', 'user_partner', 'admin'],
                                description: 'Type'
                            },
                            status: {
                                type: 'string',
                                required: false,
                                enum: ['actif', 'inactif', 'gele', 'bloque'],
                                description: 'Statut'
                            },
                            nom: {
                                type: 'string',
                                required: false,
                                max: 55,
                                description: 'Nom'
                            },
                            prenoms: {
                                type: 'string',
                                required: false,
                                max: 255,
                                description: 'Prénoms'
                            }
                        }
                    },
                    exampleRequest: {
                        email: 'jean.updated@ynov.ci',
                        status: 'actif'
                    },
                    responses: [{
                        status: 200,
                        description: 'Utilisateur mis à jour',
                        example: {
                            success: true,
                            message: 'Utilisateur mis à jour.',
                            data: {}
                        }
                    }]
                },

                {
                    id: 'users-destroy',
                    module: 'users',
                    name: 'Supprimer un utilisateur',
                    description: 'Suppression logique (soft delete) : passe le statut à "inactif", supprime les tokens.',
                    method: 'DELETE',
                    path: '/users/{uuid_user}',
                    isProtected: true,
                    isDestructive: true,
                    permissionsRequired: ['users.supprimer'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_user: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID de l\'utilisateur'
                            }
                        },
                        body: {}
                    },
                    responses: [{
                        status: 200,
                        description: 'Utilisateur supprimé',
                        example: {
                            success: true,
                            message: 'Utilisateur supprimé.'
                        }
                    }]
                },

                {
                    id: 'users-block',
                    module: 'users',
                    name: 'Bloquer un utilisateur',
                    description: 'Bloque manuellement un compte (statut = bloque, révocation des tokens). Envoie un email de notification.',
                    method: 'POST',
                    path: '/users/{uuid_user}/block',
                    isProtected: true,
                    isDestructive: true,
                    permissionsRequired: ['users.bloquer'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_user: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID de l\'utilisateur'
                            }
                        },
                        body: {
                            reason: {
                                type: 'string',
                                required: true,
                                max: 500,
                                description: 'Motif du blocage'
                            }
                        }
                    },
                    exampleRequest: {
                        reason: 'Activité suspecte détectée'
                    },
                    responses: [{
                        status: 200,
                        description: 'Utilisateur bloqué',
                        example: {
                            success: true,
                            message: 'Utilisateur bloqué.'
                        }
                    }]
                },

                {
                    id: 'users-unblock',
                    module: 'users',
                    name: 'Débloquer un utilisateur',
                    description: 'Débloque un compte précédemment bloqué (statut = actif, réinitialise les compteurs).',
                    method: 'POST',
                    path: '/users/{uuid_user}/unblock',
                    isProtected: true,
                    permissionsRequired: ['users.bloquer'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_user: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID de l\'utilisateur'
                            }
                        },
                        body: {}
                    },
                    responses: [{
                        status: 200,
                        description: 'Utilisateur débloqué',
                        example: {
                            success: true,
                            message: 'Utilisateur débloqué.'
                        }
                    }]
                },

                // ============================================================
                // 3.9 GEL / DÉGEL
                // ============================================================
                {
                    id: 'users-freeze',
                    module: 'freeze',
                    name: 'Geler un compte (manuel)',
                    description: 'Gèle manuellement un compte pour une durée définie (10s à 24h), avec motif obligatoire. Impossible de geler son propre compte.',
                    method: 'POST',
                    path: '/users/{uuid}/freeze',
                    isProtected: true,
                    isDestructive: true,
                    permissionsRequired: ['users.geler'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID de l\'utilisateur'
                            }
                        },
                        body: {
                            duration: {
                                type: 'integer',
                                required: true,
                                min: 10,
                                max: 86400,
                                description: 'Durée en secondes'
                            },
                            reason: {
                                type: 'string',
                                required: true,
                                min: 3,
                                max: 255,
                                description: 'Motif du gel'
                            }
                        }
                    },
                    exampleRequest: {
                        duration: 300,
                        reason: 'Comportement suspect détecté'
                    },
                    responses: [{
                            status: 200,
                            description: 'Compte gelé',
                            example: {
                                message: 'Compte gelé avec succès.',
                                data: {
                                    level: 4,
                                    is_frozen: true
                                }
                            }
                        },
                        {
                            status: 409,
                            description: 'Compte non gelable',
                            example: {
                                message: 'Ce compte ne peut pas être gelé actuellement.'
                            }
                        },
                        {
                            status: 422,
                            description: 'Données invalides',
                            example: {
                                message: 'Données invalides.',
                                errors: {}
                            }
                        }
                    ]
                },

                {
                    id: 'users-unfreeze',
                    module: 'freeze',
                    name: 'Dégeler un compte (manuel)',
                    description: 'Dégèle manuellement un compte, réinitialise les compteurs de tentatives échouées et notifie l\'utilisateur.',
                    method: 'POST',
                    path: '/users/{uuid}/unfreeze',
                    isProtected: true,
                    permissionsRequired: ['users.degeler'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID de l\'utilisateur'
                            }
                        },
                        body: {
                            reason: {
                                type: 'string',
                                required: false,
                                max: 255,
                                description: 'Motif du dégel'
                            }
                        }
                    },
                    exampleRequest: {
                        reason: 'Vérification effectuée'
                    },
                    responses: [{
                            status: 200,
                            description: 'Compte dégelé',
                            example: {
                                message: 'Compte dégelé avec succès.',
                                data: {
                                    level: 0,
                                    is_frozen: false
                                }
                            }
                        },
                        {
                            status: 409,
                            description: 'Non gelé',
                            example: {
                                message: 'Ce compte n\'est pas gelé ou ne peut pas être dégelé manuellement.'
                            }
                        }
                    ]
                },

                {
                    id: 'users-freeze-status',
                    module: 'freeze',
                    name: 'Statut de gel',
                    description: 'Récupère l\'état actuel de gel d\'un compte (niveau, durée restante, possibilité de geler/dégeler).',
                    method: 'GET',
                    path: '/users/{uuid}/freeze-status',
                    isProtected: true,
                    permissionsRequired: ['users.degeler'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID de l\'utilisateur'
                            }
                        }
                    },
                    responses: [{
                        status: 200,
                        description: 'Statut de gel',
                        example: {
                            data: {
                                user: {},
                                freeze: {
                                    level: 2,
                                    is_frozen: true,
                                    remaining_seconds: 45
                                },
                                can_be_frozen: false,
                                can_be_unfrozen: true
                            }
                        }
                    }]
                },

                // ============================================================
                // 3.10 RÔLES
                // ============================================================
                {
                    id: 'roles-list',
                    module: 'roles',
                    name: 'Liste des rôles',
                    description: 'Liste paginée des rôles avec leurs utilisateurs et permissions.',
                    method: 'GET',
                    path: '/roles',
                    isProtected: true,
                    permissionsRequired: ['roles.afficher'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        query: {
                            per_page: {
                                type: 'integer',
                                required: false,
                                default: 20,
                                description: 'Nombre par page'
                            },
                            status: {
                                type: 'string',
                                required: false,
                                enum: ['actif', 'inactif'],
                                description: 'Filtrer par statut'
                            }
                        }
                    },
                    responses: [{
                        status: 200,
                        description: 'Liste des rôles',
                        example: {
                            success: true,
                            message: 'Liste des rôles récupérée.',
                            code: 'ROLES_LISTED',
                            data: []
                        }
                    }]
                },

                {
                    id: 'roles-show',
                    module: 'roles',
                    name: 'Détails d\'un rôle',
                    description: 'Récupère un rôle avec toutes ses permissions groupées par module.',
                    method: 'GET',
                    path: '/roles/{uuid_role}',
                    isProtected: true,
                    permissionsRequired: ['roles.afficher'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_role: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID du rôle'
                            }
                        }
                    },
                    responses: [{
                        status: 200,
                        description: 'Détails du rôle',
                        example: {
                            success: true,
                            code: 'ROLE_FOUND',
                            data: {}
                        }
                    }]
                },

                {
                    id: 'roles-users',
                    module: 'roles',
                    name: 'Utilisateurs d\'un rôle',
                    description: 'Liste paginée des utilisateurs ayant ce rôle.',
                    method: 'GET',
                    path: '/roles/{uuid_role}/users',
                    isProtected: true,
                    permissionsRequired: ['roles.afficher'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_role: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID du rôle'
                            }
                        },
                        query: {
                            per_page: {
                                type: 'integer',
                                required: false,
                                default: 20,
                                description: 'Nombre par page'
                            }
                        }
                    },
                    responses: [{
                        status: 200,
                        description: 'Utilisateurs du rôle',
                        example: {
                            success: true,
                            code: 'ROLE_USERS_LISTED',
                            data: []
                        }
                    }]
                },

                {
                    id: 'roles-create',
                    module: 'roles',
                    name: 'Créer un rôle',
                    description: 'Crée un nouveau rôle personnalisé (non-système).',
                    method: 'POST',
                    path: '/roles',
                    isProtected: true,
                    permissionsRequired: ['roles.creer'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        body: {
                            libelle: {
                                type: 'string',
                                required: true,
                                max: 100,
                                description: 'Libellé (unique)'
                            },
                            description: {
                                type: 'string',
                                required: false,
                                description: 'Description'
                            },
                            level: {
                                type: 'integer',
                                required: false,
                                min: 0,
                                description: 'Niveau hiérarchique'
                            },
                            priority: {
                                type: 'integer',
                                required: false,
                                min: 0,
                                description: 'Priorité'
                            }
                        }
                    },
                    exampleRequest: {
                        libelle: 'Gestionnaire Agence',
                        description: 'Gère les opérations d\'une agence',
                        level: 2
                    },
                    responses: [{
                        status: 201,
                        description: 'Rôle créé',
                        example: {
                            success: true,
                            code: 'ROLE_CREATED',
                            data: {}
                        }
                    }]
                },

                {
                    id: 'roles-update',
                    module: 'roles',
                    name: 'Modifier un rôle',
                    description: 'Met à jour un rôle. Les rôles système (is_system = true) sont protégés.',
                    method: 'PUT',
                    path: '/roles/{uuid_role}',
                    isProtected: true,
                    permissionsRequired: ['roles.modifier'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_role: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID du rôle'
                            }
                        },
                        body: {
                            description: {
                                type: 'string',
                                required: false,
                                description: 'Description'
                            },
                            level: {
                                type: 'integer',
                                required: false,
                                min: 0,
                                description: 'Niveau'
                            },
                            priority: {
                                type: 'integer',
                                required: false,
                                min: 0,
                                description: 'Priorité'
                            },
                            status: {
                                type: 'string',
                                required: false,
                                enum: ['actif', 'inactif'],
                                description: 'Statut'
                            }
                        }
                    },
                    exampleRequest: {
                        description: 'Description mise à jour',
                        priority: 10
                    },
                    responses: [{
                            status: 200,
                            description: 'Rôle mis à jour',
                            example: {
                                success: true,
                                code: 'ROLE_UPDATED',
                                data: {}
                            }
                        },
                        {
                            status: 403,
                            description: 'Rôle système protégé',
                            example: {
                                success: false,
                                message: 'Rôle système protégé.',
                                code: 'ROLE_PROTECTED'
                            }
                        }
                    ]
                },

                {
                    id: 'roles-destroy',
                    module: 'roles',
                    name: 'Supprimer un rôle',
                    description: 'Suppression logique (soft delete) d\'un rôle. Les rôles système sont protégés.',
                    method: 'DELETE',
                    path: '/roles/{uuid_role}',
                    isProtected: true,
                    isDestructive: true,
                    permissionsRequired: ['roles.supprimer'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_role: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID du rôle'
                            }
                        },
                        body: {}
                    },
                    responses: [{
                            status: 200,
                            description: 'Rôle supprimé',
                            example: {
                                success: true,
                                message: 'Rôle supprimé.',
                                code: 'ROLE_DELETED'
                            }
                        },
                        {
                            status: 403,
                            description: 'Rôle système protégé',
                            example: {
                                success: false,
                                message: 'Rôle système protégé.',
                                code: 'ROLE_PROTECTED'
                            }
                        }
                    ]
                },

                {
                    id: 'roles-assign-permissions',
                    module: 'roles',
                    name: 'Attribuer des permissions',
                    description: 'Remplace (sync) l\'ensemble des permissions attribuées à un rôle. Le rôle Super Admin n\'est pas assignable.',
                    method: 'POST',
                    path: '/roles/{uuid_role}/permissions',
                    isProtected: true,
                    permissionsRequired: ['roles.gerer_permissions'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_role: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID du rôle'
                            }
                        },
                        body: {
                            permission_uuids: {
                                type: 'array',
                                required: true,
                                min: 1,
                                description: 'Tableau d\'UUIDs de permissions'
                            }
                        }
                    },
                    exampleRequest: {
                        permission_uuids: ['perm-uuid-1', 'perm-uuid-2']
                    },
                    responses: [{
                            status: 200,
                            description: 'Permissions attribuées',
                            example: {
                                success: true,
                                code: 'ROLE_PERMISSIONS_ASSIGNED',
                                data: {}
                            }
                        },
                        {
                            status: 422,
                            description: 'Super Admin non assignable',
                            example: {
                                success: false,
                                code: 'ROLE_SUPER_ADMIN_NOT_ASSIGNABLE'
                            }
                        }
                    ]
                },

                // ============================================================
                // 3.11 PERMISSIONS
                // ============================================================
                {
                    id: 'permissions-suggested-actions',
                    module: 'permissions',
                    name: 'Actions suggérées',
                    description: 'Liste des actions standards suggérées pour la création de permissions.',
                    method: 'GET',
                    path: '/permissions/suggested-actions',
                    isProtected: true,
                    permissionsRequired: ['permissions.afficher'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        body: {}
                    },
                    responses: [{
                        status: 200,
                        description: 'Actions suggérées',
                        example: {
                            success: true,
                            code: 'ACTIONS_SUGGESTED',
                            data: ['Créer', 'Afficher', 'Modifier', 'Supprimer', 'Geler', 'Dégeler', 'Bloquer', 'Débloquer']
                        }
                    }]
                },

                {
                    id: 'permissions-list',
                    module: 'permissions',
                    name: 'Liste des permissions',
                    description: 'Liste paginée des permissions, filtrable par groupe, statut, recherche.',
                    method: 'GET',
                    path: '/permissions',
                    isProtected: true,
                    permissionsRequired: ['permissions.afficher'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        query: {
                            permission_group_uuid: {
                                type: 'uuid',
                                required: false,
                                description: 'Filtrer par groupe'
                            },
                            status: {
                                type: 'string',
                                required: false,
                                description: 'Filtrer par statut'
                            },
                            search: {
                                type: 'string',
                                required: false,
                                description: 'Recherche libre'
                            },
                            per_page: {
                                type: 'integer',
                                required: false,
                                default: 20,
                                description: 'Nombre par page'
                            }
                        }
                    },
                    responses: [{
                        status: 200,
                        description: 'Liste des permissions',
                        example: {
                            success: true,
                            code: 'PERMISSIONS_LISTED',
                            data: []
                        }
                    }]
                },

                {
                    id: 'permissions-show',
                    module: 'permissions',
                    name: 'Détails d\'une permission',
                    description: 'Récupère une permission avec son groupe.',
                    method: 'GET',
                    path: '/permissions/{uuid_permission}',
                    isProtected: true,
                    permissionsRequired: ['permissions.afficher'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_permission: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID de la permission'
                            }
                        }
                    },
                    responses: [{
                        status: 200,
                        description: 'Détails de la permission',
                        example: {
                            success: true,
                            code: 'PERMISSION_FOUND',
                            data: {}
                        }
                    }]
                },

                {
                    id: 'permissions-create',
                    module: 'permissions',
                    name: 'Créer une permission',
                    description: 'Crée une nouvelle permission. Le code est généré automatiquement (module.action).',
                    method: 'POST',
                    path: '/permissions',
                    isProtected: true,
                    permissionsRequired: ['permissions.creer'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        body: {
                            permission_group_uuid: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID du groupe'
                            },
                            libelle: {
                                type: 'string',
                                required: true,
                                max: 100,
                                description: 'Libellé (unique)'
                            },
                            action: {
                                type: 'string',
                                required: true,
                                max: 100,
                                description: 'Action'
                            },
                            description: {
                                type: 'string',
                                required: false,
                                description: 'Description'
                            },
                            category: {
                                type: 'string',
                                required: false,
                                max: 100,
                                description: 'Catégorie'
                            }
                        }
                    },
                    exampleRequest: {
                        permission_group_uuid: 'group-uuid',
                        libelle: 'Créer un utilisateur',
                        action: 'creer'
                    },
                    responses: [{
                        status: 201,
                        description: 'Permission créée',
                        example: {
                            success: true,
                            code: 'PERMISSION_CREATED',
                            data: {}
                        }
                    }]
                },

                {
                    id: 'permissions-update',
                    module: 'permissions',
                    name: 'Modifier une permission',
                    description: 'Met à jour une permission existante.',
                    method: 'PUT',
                    path: '/permissions/{uuid_permission}',
                    isProtected: true,
                    permissionsRequired: ['permissions.modifier'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_permission: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID de la permission'
                            }
                        },
                        body: {
                            permission_group_uuid: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID du groupe'
                            },
                            libelle: {
                                type: 'string',
                                required: true,
                                max: 100,
                                description: 'Libellé'
                            },
                            action: {
                                type: 'string',
                                required: true,
                                max: 100,
                                description: 'Action'
                            },
                            description: {
                                type: 'string',
                                required: false,
                                description: 'Description'
                            },
                            category: {
                                type: 'string',
                                required: false,
                                max: 100,
                                description: 'Catégorie'
                            }
                        }
                    },
                    exampleRequest: {
                        permission_group_uuid: 'group-uuid',
                        libelle: 'Créer un utilisateur (v2)',
                        action: 'creer'
                    },
                    responses: [{
                        status: 200,
                        description: 'Permission mise à jour',
                        example: {
                            success: true,
                            code: 'PERMISSION_UPDATED',
                            data: {}
                        }
                    }]
                },

                {
                    id: 'permissions-destroy',
                    module: 'permissions',
                    name: 'Supprimer une permission',
                    description: 'Suppression logique d\'une permission, refusée si elle est encore attribuée à un rôle.',
                    method: 'DELETE',
                    path: '/permissions/{uuid_permission}',
                    isProtected: true,
                    isDestructive: true,
                    permissionsRequired: ['permissions.supprimer'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_permission: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID de la permission'
                            }
                        },
                        body: {}
                    },
                    responses: [{
                            status: 200,
                            description: 'Permission supprimée',
                            example: {
                                success: true,
                                message: 'Permission supprimée.',
                                code: 'PERMISSION_DELETED'
                            }
                        },
                        {
                            status: 422,
                            description: 'Permission utilisée',
                            example: {
                                success: false,
                                message: 'Cette permission est attribuée à un ou plusieurs rôles.',
                                code: 'PERMISSION_IN_USE'
                            }
                        }
                    ]
                },

                // ============================================================
                // 3.12 GROUPES DE PERMISSIONS
                // ============================================================
                {
                    id: 'perm-groups-list',
                    module: 'permGroups',
                    name: 'Liste des groupes',
                    description: 'Liste des groupes (modules) de permissions avec leurs permissions.',
                    method: 'GET',
                    path: '/permission-groups',
                    isProtected: true,
                    permissionsRequired: ['permission_groups.afficher'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        query: {
                            status: {
                                type: 'string',
                                required: false,
                                description: 'Filtrer par statut'
                            },
                            search: {
                                type: 'string',
                                required: false,
                                description: 'Recherche libre'
                            }
                        }
                    },
                    responses: [{
                        status: 200,
                        description: 'Liste des groupes',
                        example: {
                            success: true,
                            code: 'PERMISSION_GROUPS_LISTED',
                            data: []
                        }
                    }]
                },

                {
                    id: 'perm-groups-show',
                    module: 'permGroups',
                    name: 'Détails d\'un groupe',
                    description: 'Récupère un groupe avec toutes ses permissions.',
                    method: 'GET',
                    path: '/permission-groups/{uuid_permissionGroup}',
                    isProtected: true,
                    permissionsRequired: ['permission_groups.afficher'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_permissionGroup: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID du groupe'
                            }
                        }
                    },
                    responses: [{
                        status: 200,
                        description: 'Détails du groupe',
                        example: {
                            success: true,
                            code: 'PERMISSION_GROUP_FOUND',
                            data: {}
                        }
                    }]
                },

                {
                    id: 'perm-groups-create',
                    module: 'permGroups',
                    name: 'Créer un groupe',
                    description: 'Crée un nouveau module/groupe de permissions.',
                    method: 'POST',
                    path: '/permission-groups',
                    isProtected: true,
                    permissionsRequired: ['permission_groups.creer'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        body: {
                            libelle: {
                                type: 'string',
                                required: true,
                                max: 100,
                                description: 'Libellé (unique)'
                            },
                            description: {
                                type: 'string',
                                required: false,
                                description: 'Description'
                            },
                            icone: {
                                type: 'string',
                                required: false,
                                max: 100,
                                description: 'Icône'
                            },
                            color: {
                                type: 'string',
                                required: false,
                                max: 50,
                                description: 'Couleur'
                            },
                            ordre_affichage: {
                                type: 'integer',
                                required: false,
                                min: 0,
                                description: 'Ordre d\'affichage'
                            },
                            parent_uuid: {
                                type: 'uuid',
                                required: false,
                                description: 'Groupe parent'
                            },
                            route_prefix: {
                                type: 'string',
                                required: false,
                                max: 100,
                                description: 'Préfixe de route'
                            }
                        }
                    },
                    exampleRequest: {
                        libelle: 'Gestion des Sinistres',
                        icone: 'fa-file-medical',
                        ordre_affichage: 5
                    },
                    responses: [{
                        status: 201,
                        description: 'Groupe créé',
                        example: {
                            success: true,
                            code: 'PERMISSION_GROUP_CREATED',
                            data: {}
                        }
                    }]
                },

                {
                    id: 'perm-groups-update',
                    module: 'permGroups',
                    name: 'Modifier un groupe',
                    description: 'Met à jour un groupe de permissions existant.',
                    method: 'PUT',
                    path: '/permission-groups/{uuid_permissionGroup}',
                    isProtected: true,
                    permissionsRequired: ['permission_groups.modifier'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_permissionGroup: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID du groupe'
                            }
                        },
                        body: {
                            libelle: {
                                type: 'string',
                                required: true,
                                max: 100,
                                description: 'Libellé'
                            },
                            description: {
                                type: 'string',
                                required: false,
                                description: 'Description'
                            },
                            icone: {
                                type: 'string',
                                required: false,
                                max: 100,
                                description: 'Icône'
                            },
                            color: {
                                type: 'string',
                                required: false,
                                max: 50,
                                description: 'Couleur'
                            },
                            ordre_affichage: {
                                type: 'integer',
                                required: false,
                                min: 0,
                                description: 'Ordre d\'affichage'
                            }
                        }
                    },
                    exampleRequest: {
                        libelle: 'Gestion des Sinistres (v2)',
                        color: '#F7A400'
                    },
                    responses: [{
                        status: 200,
                        description: 'Groupe mis à jour',
                        example: {
                            success: true,
                            code: 'PERMISSION_GROUP_UPDATED',
                            data: {}
                        }
                    }]
                },

                {
                    id: 'perm-groups-destroy',
                    module: 'permGroups',
                    name: 'Supprimer un groupe',
                    description: 'Suppression logique d\'un groupe, refusée s\'il contient encore des permissions.',
                    method: 'DELETE',
                    path: '/permission-groups/{uuid_permissionGroup}',
                    isProtected: true,
                    isDestructive: true,
                    permissionsRequired: ['permission_groups.supprimer'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_permissionGroup: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID du groupe'
                            }
                        },
                        body: {}
                    },
                    responses: [{
                            status: 200,
                            description: 'Groupe supprimé',
                            example: {
                                success: true,
                                message: 'Module supprimé.',
                                code: 'PERMISSION_GROUP_DELETED'
                            }
                        },
                        {
                            status: 422,
                            description: 'Groupe non vide',
                            example: {
                                success: false,
                                message: 'Le groupe contient des permissions.',
                                code: 'PERMISSION_GROUP_NOT_EMPTY'
                            }
                        }
                    ]
                },

                // ============================================================
                // 3.13 RESTRICTIONS IP
                // ============================================================
                {
                    id: 'ip-restrictions-list',
                    module: 'ip',
                    name: 'Liste des restrictions IP',
                    description: 'Récupère toutes les règles de restriction IP (whitelist/blacklist).',
                    method: 'GET',
                    path: '/ip-restrictions',
                    isProtected: true,
                    permissionsRequired: ['ip_restrictions.afficher'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        body: {}
                    },
                    responses: [{
                        status: 200,
                        description: 'Restrictions IP',
                        example: {
                            success: true,
                            data: [{
                                ip_address: '10.0.0.5',
                                type: 'blacklist'
                            }]
                        }
                    }]
                },

                {
                    id: 'ip-restrictions-create',
                    module: 'ip',
                    name: 'Créer une restriction IP',
                    description: 'Ajoute une règle de restriction IP (whitelist ou blacklist).',
                    method: 'POST',
                    path: '/ip-restrictions',
                    isProtected: true,
                    permissionsRequired: ['ip_restrictions.creer'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        body: {
                            ip_address: {
                                type: 'string',
                                required: true,
                                max: 45,
                                description: 'Adresse IP'
                            },
                            type: {
                                type: 'string',
                                required: true,
                                enum: ['whitelist', 'blacklist'],
                                description: 'Type'
                            },
                            reason: {
                                type: 'string',
                                required: false,
                                max: 255,
                                description: 'Motif'
                            },
                            expires_at: {
                                type: 'date',
                                required: false,
                                description: 'Date d\'expiration'
                            }
                        }
                    },
                    exampleRequest: {
                        ip_address: '41.83.12.7',
                        type: 'blacklist',
                        reason: 'Tentatives de brute-force'
                    },
                    responses: [{
                        status: 201,
                        description: 'Restriction créée',
                        example: {
                            success: true,
                            data: {}
                        }
                    }]
                },

                {
                    id: 'ip-restrictions-destroy',
                    module: 'ip',
                    name: 'Supprimer une restriction IP',
                    description: 'Supprime définitivement une règle de restriction IP.',
                    method: 'DELETE',
                    path: '/ip-restrictions/{uuid_restriction}',
                    isProtected: true,
                    isDestructive: true,
                    permissionsRequired: ['ip_restrictions.supprimer'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_restriction: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID de la restriction'
                            }
                        },
                        body: {}
                    },
                    responses: [{
                        status: 200,
                        description: 'Restriction supprimée',
                        example: {
                            success: true,
                            message: 'Restriction supprimée.'
                        }
                    }]
                },

                // ============================================================
                // 3.14 LOGS & AUDIT
                // ============================================================
                {
                    id: 'audit-my-activity',
                    module: 'audit',
                    name: 'Mes logs d\'activité',
                    description: 'Récupère les logs d\'activité (ActivityLog) de l\'utilisateur connecté.',
                    method: 'GET',
                    path: '/audit/my-activity',
                    isProtected: true,
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        query: {
                            per_page: {
                                type: 'integer',
                                required: false,
                                default: 10,
                                description: 'Nombre par page'
                            }
                        }
                    },
                    responses: [{
                        status: 200,
                        description: 'Logs d\'activité personnels',
                        example: {
                            success: true,
                            data: []
                        }
                    }]
                },

                {
                    id: 'audit-my-activity-stats',
                    module: 'audit',
                    name: 'Mes statistiques d\'activité',
                    description: 'Statistiques d\'activité (aujourd\'hui, cette semaine, ce mois, par action, par niveau).',
                    method: 'GET',
                    path: '/audit/my-activity/stats',
                    isProtected: true,
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        body: {}
                    },
                    responses: [{
                        status: 200,
                        description: 'Statistiques',
                        example: {
                            success: true,
                            data: {
                                today: 4,
                                this_week: 20,
                                this_month: 87
                            }
                        }
                    }]
                },

                {
                    id: 'audit-all-logs',
                    module: 'audit',
                    name: '[Admin] Tous les logs',
                    description: 'Liste paginée de tous les logs du système, avec filtres.',
                    method: 'GET',
                    path: '/audit/activity',
                    isProtected: true,
                    permissionsRequired: ['audit.consulter_les_logs'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        query: {
                            action: {
                                type: 'string',
                                required: false,
                                description: 'Filtrer par action'
                            },
                            level: {
                                type: 'string',
                                required: false,
                                enum: ['info', 'warning', 'error', 'critical'],
                                description: 'Niveau'
                            },
                            module: {
                                type: 'string',
                                required: false,
                                description: 'Filtrer par module'
                            },
                            user_uuid: {
                                type: 'uuid',
                                required: false,
                                description: 'Filtrer par utilisateur'
                            },
                            per_page: {
                                type: 'integer',
                                required: false,
                                default: 50,
                                description: 'Nombre par page'
                            }
                        }
                    },
                    responses: [{
                        status: 200,
                        description: 'Tous les logs',
                        example: {
                            success: true,
                            data: []
                        }
                    }]
                },

                {
                    id: 'audit-user-activity',
                    module: 'audit',
                    name: '[Admin] Logs d\'un utilisateur',
                    description: 'Liste paginée des logs d\'activité d\'un utilisateur spécifique.',
                    method: 'GET',
                    path: '/audit/activity/user/{uuid_user}',
                    isProtected: true,
                    permissionsRequired: ['audit.consulter_les_logs'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_user: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID de l\'utilisateur'
                            }
                        },
                        query: {
                            per_page: {
                                type: 'integer',
                                required: false,
                                default: 20,
                                description: 'Nombre par page'
                            }
                        }
                    },
                    responses: [{
                            status: 200,
                            description: 'Logs de l\'utilisateur',
                            example: {
                                success: true,
                                data: []
                            }
                        },
                        {
                            status: 403,
                            description: 'Non autorisé',
                            example: {
                                success: false,
                                message: 'Vous n\'avez pas le droit de consulter ces logs.'
                            }
                        }
                    ]
                },

                {
                    id: 'audit-freeze-logs',
                    module: 'audit',
                    name: '[Admin] Logs de gel/dégel',
                    description: 'Historique paginé de tous les gels et dégels de comptes (table account_freezes).',
                    method: 'GET',
                    path: '/audit/freeze-logs',
                    isProtected: true,
                    permissionsRequired: ['audit.consulter_les_logs'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        query: {
                            per_page: {
                                type: 'integer',
                                required: false,
                                default: 20,
                                description: 'Nombre par page'
                            }
                        }
                    },
                    responses: [{
                        status: 200,
                        description: 'Logs de gel/dégel',
                        example: {
                            success: true,
                            data: []
                        }
                    }]
                },

                {
                    id: 'audit-stats',
                    module: 'audit',
                    name: '[Admin] Statistiques globales',
                    description: 'Statistiques d\'activité globales du système.',
                    method: 'GET',
                    path: '/audit/stats',
                    isProtected: true,
                    permissionsRequired: ['audit.consulter_les_logs'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        body: {}
                    },
                    responses: [{
                        status: 200,
                        description: 'Statistiques globales',
                        example: {
                            success: true,
                            data: {
                                today: 120,
                                this_week: 640,
                                this_month: 2500
                            }
                        }
                    }]
                },

                // ============================================================
                // 3.15 PARTENAIRES
                // ============================================================
                {
                    id: 'partners-list',
                    module: 'partners',
                    name: 'Liste des partenaires',
                    description: 'Récupère la liste des partenaires avec filtres (statut, type, catégorie, recherche textuelle).',
                    method: 'GET',
                    path: '/partners',
                    isProtected: true,
                    permissionsRequired: ['partners.afficher'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        query: {
                            per_page: {
                                type: 'integer',
                                required: false,
                                default: 20,
                                description: 'Nombre par page'
                            },
                            status: {
                                type: 'string',
                                required: false,
                                enum: ['actif', 'inactif', 'suspendu'],
                                description: 'Filtrer par statut'
                            },
                            is_active: {
                                type: 'boolean',
                                required: false,
                                description: 'Filtrer par statut actif/inactif'
                            },
                            type: {
                                type: 'string',
                                required: false,
                                description: 'Filtrer par type'
                            },
                            categorie: {
                                type: 'string',
                                required: false,
                                description: 'Filtrer par catégorie'
                            },
                            code_branche: {
                                type: 'string',
                                required: false,
                                description: 'Filtrer par code branche'
                            },
                            search: {
                                type: 'string',
                                required: false,
                                description: 'Recherche textuelle'
                            },
                            not_expired: {
                                type: 'boolean',
                                required: false,
                                description: 'Partenaires non expirés'
                            }
                        }
                    },
                    responses: [{
                        status: 200,
                        description: 'Liste des partenaires',
                        example: {
                            success: true,
                            data: []
                        }
                    }]
                },

                {
                    id: 'partners-create',
                    module: 'partners',
                    name: 'Créer un partenaire',
                    description: 'Crée un nouveau partenaire avec toutes ses informations.',
                    method: 'POST',
                    path: '/partners',
                    isProtected: true,
                    permissionsRequired: ['partners.creer'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        body: {
                            code: {
                                type: 'string',
                                required: true,
                                max: 55,
                                description: 'Code unique'
                            },
                            designation: {
                                type: 'string',
                                required: true,
                                max: 100,
                                description: 'Nom'
                            },
                            sigle: {
                                type: 'string',
                                required: false,
                                max: 20,
                                description: 'Sigle'
                            },
                            description: {
                                type: 'string',
                                required: false,
                                description: 'Description'
                            },
                            logo: {
                                type: 'string',
                                required: false,
                                max: 255,
                                description: 'URL du logo'
                            },
                            code_branche: {
                                type: 'string',
                                required: false,
                                max: 35,
                                description: 'Code branche'
                            },
                            email: {
                                type: 'email',
                                required: false,
                                max: 100,
                                description: 'Email'
                            },
                            email_2: {
                                type: 'email',
                                required: false,
                                max: 100,
                                description: 'Email secondaire'
                            },
                            telephone: {
                                type: 'string',
                                required: false,
                                max: 25,
                                description: 'Téléphone'
                            },
                            telephone_2: {
                                type: 'string',
                                required: false,
                                max: 25,
                                description: 'Téléphone secondaire'
                            },
                            adresse: {
                                type: 'string',
                                required: false,
                                max: 255,
                                description: 'Adresse'
                            },
                            ville: {
                                type: 'string',
                                required: false,
                                max: 100,
                                description: 'Ville'
                            },
                            pays: {
                                type: 'string',
                                required: false,
                                max: 100,
                                description: 'Pays'
                            },
                            site_web: {
                                type: 'url',
                                required: false,
                                max: 255,
                                description: 'Site web'
                            },
                            latitude: {
                                type: 'number',
                                required: false,
                                between: [-90, 90],
                                description: 'Latitude'
                            },
                            longitude: {
                                type: 'number',
                                required: false,
                                between: [-180, 180],
                                description: 'Longitude'
                            },
                            type: {
                                type: 'string',
                                required: false,
                                max: 50,
                                description: 'Type de partenaire'
                            },
                            secteur_activite: {
                                type: 'string',
                                required: false,
                                max: 100,
                                description: 'Secteur d\'activité'
                            },
                            categorie: {
                                type: 'string',
                                required: false,
                                max: 50,
                                description: 'Catégorie'
                            },
                            is_active: {
                                type: 'boolean',
                                required: false,
                                default: true,
                                description: 'Actif'
                            },
                            status: {
                                type: 'string',
                                required: false,
                                enum: ['actif', 'inactif', 'suspendu'],
                                default: 'actif',
                                description: 'Statut'
                            },
                            date_agrement: {
                                type: 'date',
                                required: false,
                                description: 'Date d\'agrément'
                            },
                            date_expiration: {
                                type: 'date',
                                required: false,
                                description: 'Date d\'expiration'
                            }
                        }
                    },
                    exampleRequest: {
                        code: 'PART001',
                        designation: 'YAKO AFRICA Assurance',
                        sigle: 'YAKO',
                        email: 'contact@yako.ci',
                        telephone: '+2252720304050',
                        ville: 'Abidjan',
                        pays: 'Côte d\'Ivoire',
                        type: 'institutionnel',
                        categorie: 'A'
                    },
                    responses: [{
                        status: 201,
                        description: 'Partenaire créé',
                        example: {
                            success: true,
                            message: 'Partenaire créé avec succès.',
                            code: 'PARTNER_CREATED',
                            data: {}
                        }
                    }]
                },

                {
                    id: 'partners-show',
                    module: 'partners',
                    name: 'Détails d\'un partenaire',
                    description: 'Récupère les informations complètes d\'un partenaire avec ses réseaux et agences.',
                    method: 'GET',
                    path: '/partners/{uuid_partner}',
                    isProtected: true,
                    permissionsRequired: ['partners.afficher'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_partner: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID du partenaire'
                            }
                        }
                    },
                    responses: [{
                        status: 200,
                        description: 'Détails du partenaire',
                        example: {
                            success: true,
                            data: {}
                        }
                    }]
                },

                {
                    id: 'partners-update',
                    module: 'partners',
                    name: 'Mettre à jour un partenaire',
                    description: 'Modifie les informations d\'un partenaire existant.',
                    method: 'PUT',
                    path: '/partners/{uuid_partner}',
                    isProtected: true,
                    permissionsRequired: ['partners.modifier'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_partner: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID du partenaire'
                            }
                        },
                        body: {
                            /* Mêmes champs que la création, tous optionnels */
                        }
                    },
                    responses: [{
                        status: 200,
                        description: 'Partenaire mis à jour',
                        example: {
                            success: true,
                            message: 'Partenaire mis à jour avec succès.',
                            code: 'PARTNER_UPDATED',
                            data: {}
                        }
                    }]
                },

                {
                    id: 'partners-delete',
                    module: 'partners',
                    name: 'Supprimer un partenaire',
                    description: 'Supprime un partenaire. Refusé s\'il a des réseaux associés.',
                    method: 'DELETE',
                    path: '/partners/{uuid_partner}',
                    isProtected: true,
                    isDestructive: true,
                    permissionsRequired: ['partners.supprimer'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_partner: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID du partenaire'
                            }
                        },
                        body: {}
                    },
                    responses: [{
                            status: 200,
                            description: 'Partenaire supprimé',
                            example: {
                                success: true,
                                message: 'Partenaire supprimé avec succès.',
                                code: 'PARTNER_DELETED'
                            }
                        },
                        {
                            status: 422,
                            description: 'Partenaire a des réseaux',
                            example: {
                                success: false,
                                message: 'Ce partenaire a des réseaux associés.',
                                code: 'PARTNER_HAS_RESEAVX'
                            }
                        }
                    ]
                },

                {
                    id: 'partners-reseaux',
                    module: 'partners',
                    name: 'Réseaux d\'un partenaire',
                    description: 'Récupère tous les réseaux associés à un partenaire.',
                    method: 'GET',
                    path: '/partners/{uuid_partner}/reseaux',
                    isProtected: true,
                    permissionsRequired: ['partners.afficher'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_partner: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID du partenaire'
                            }
                        }
                    },
                    responses: [{
                        status: 200,
                        description: 'Réseaux du partenaire',
                        example: {
                            success: true,
                            data: []
                        }
                    }]
                },

                // ============================================================
                // 3.16 RESEAUX
                // ============================================================
                {
                    id: 'reseaux-list',
                    module: 'reseaux',
                    name: 'Liste des réseaux',
                    description: 'Récupère la liste des réseaux avec filtres (statut, partenaire, recherche).',
                    method: 'GET',
                    path: '/reseaux',
                    isProtected: true,
                    permissionsRequired: ['reseaux.afficher'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        query: {
                            per_page: {
                                type: 'integer',
                                required: false,
                                default: 20,
                                description: 'Nombre par page'
                            },
                            status: {
                                type: 'string',
                                required: false,
                                enum: ['actif', 'inactif'],
                                description: 'Filtrer par statut'
                            },
                            partner_uuid: {
                                type: 'uuid',
                                required: false,
                                description: 'Filtrer par partenaire'
                            },
                            search: {
                                type: 'string',
                                required: false,
                                description: 'Recherche textuelle'
                            }
                        }
                    },
                    responses: [{
                        status: 200,
                        description: 'Liste des réseaux',
                        example: {
                            success: true,
                            data: []
                        }
                    }]
                },

                {
                    id: 'reseaux-create',
                    module: 'reseaux',
                    name: 'Créer un réseau',
                    description: 'Crée un nouveau réseau pour un partenaire.',
                    method: 'POST',
                    path: '/reseaux',
                    isProtected: true,
                    permissionsRequired: ['reseaux.creer'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        body: {
                            code: {
                                type: 'string',
                                required: true,
                                max: 55,
                                description: 'Code unique'
                            },
                            libelle: {
                                type: 'string',
                                required: true,
                                max: 255,
                                description: 'Nom du réseau'
                            },
                            description: {
                                type: 'string',
                                required: false,
                                description: 'Description'
                            },
                            partner_uuid: {
                                type: 'uuid',
                                required: false,
                                description: 'UUID du partenaire'
                            },
                            email: {
                                type: 'email',
                                required: false,
                                max: 100,
                                description: 'Email'
                            },
                            telephone: {
                                type: 'string',
                                required: false,
                                max: 25,
                                description: 'Téléphone'
                            },
                            status: {
                                type: 'string',
                                required: false,
                                enum: ['actif', 'inactif'],
                                default: 'actif',
                                description: 'Statut'
                            }
                        }
                    },
                    exampleRequest: {
                        code: 'RES001',
                        libelle: 'Réseau Abidjan',
                        partner_uuid: 'partner-uuid',
                        email: 'abidjan@yako.ci'
                    },
                    responses: [{
                        status: 201,
                        description: 'Réseau créé',
                        example: {
                            success: true,
                            message: 'Réseau créé avec succès.',
                            code: 'RESEAU_CREATED',
                            data: {}
                        }
                    }]
                },

                {
                    id: 'reseaux-show',
                    module: 'reseaux',
                    name: 'Détails d\'un réseau',
                    description: 'Récupère les informations complètes d\'un réseau avec ses agences.',
                    method: 'GET',
                    path: '/reseaux/{uuid_reseau}',
                    isProtected: true,
                    permissionsRequired: ['reseaux.afficher'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_reseau: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID du réseau'
                            }
                        }
                    },
                    responses: [{
                        status: 200,
                        description: 'Détails du réseau',
                        example: {
                            success: true,
                            data: {}
                        }
                    }]
                },

                {
                    id: 'reseaux-update',
                    module: 'reseaux',
                    name: 'Mettre à jour un réseau',
                    description: 'Modifie les informations d\'un réseau existant.',
                    method: 'PUT',
                    path: '/reseaux/{uuid_reseau}',
                    isProtected: true,
                    permissionsRequired: ['reseaux.modifier'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_reseau: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID du réseau'
                            }
                        },
                        body: {
                            /* Mêmes champs que la création, tous optionnels */
                        }
                    },
                    responses: [{
                        status: 200,
                        description: 'Réseau mis à jour',
                        example: {
                            success: true,
                            message: 'Réseau mis à jour avec succès.',
                            code: 'RESEAU_UPDATED',
                            data: {}
                        }
                    }]
                },

                {
                    id: 'reseaux-delete',
                    module: 'reseaux',
                    name: 'Supprimer un réseau',
                    description: 'Supprime un réseau. Refusé s\'il a des agences associées.',
                    method: 'DELETE',
                    path: '/reseaux/{uuid_reseau}',
                    isProtected: true,
                    isDestructive: true,
                    permissionsRequired: ['reseaux.supprimer'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_reseau: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID du réseau'
                            }
                        },
                        body: {}
                    },
                    responses: [{
                            status: 200,
                            description: 'Réseau supprimé',
                            example: {
                                success: true,
                                message: 'Réseau supprimé avec succès.',
                                code: 'RESEAU_DELETED'
                            }
                        },
                        {
                            status: 422,
                            description: 'Réseau a des agences',
                            example: {
                                success: false,
                                message: 'Ce réseau a des agences associées.',
                                code: 'RESEAU_HAS_AGENCES'
                            }
                        }
                    ]
                },

                {
                    id: 'reseaux-agences',
                    module: 'reseaux',
                    name: 'Agences d\'un réseau',
                    description: 'Récupère toutes les agences associées à un réseau.',
                    method: 'GET',
                    path: '/reseaux/{uuid_reseau}/agences',
                    isProtected: true,
                    permissionsRequired: ['reseaux.afficher'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_reseau: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID du réseau'
                            }
                        }
                    },
                    responses: [{
                        status: 200,
                        description: 'Agences du réseau',
                        example: {
                            success: true,
                            data: []
                        }
                    }]
                },

                // ============================================================
                // 3.17 AGENCES
                // ============================================================
                // {
                //     id: 'agences-list',
                //     module: 'agences',
                //     name: 'Liste des agences',
                //     description: 'Récupère la liste des agences avec filtres (ville, quartier, statut, réseau, recherche textuelle, agences ouvertes, géolocalisation). Retourne également les horaires avec la configuration des rendez-vous par jour.',
                //     method: 'GET',
                //     path: '/agences',
                //     isProtected: true,
                //     permissionsRequired: ['agences.afficher'],
                //     headers: {
                //         'Authorization': 'Bearer {token}',
                //         'Accept': 'application/json'
                //     },
                //     requestParams: {
                //         query: {
                //             per_page: {
                //                 type: 'integer',
                //                 required: false,
                //                 default: 20,
                //                 description: 'Nombre par page'
                //             },
                //             status: {
                //                 type: 'string',
                //                 required: false,
                //                 enum: ['actif', 'inactif'],
                //                 description: 'Filtrer par statut'
                //             },
                //             reseau_uuid: {
                //                 type: 'uuid',
                //                 required: false,
                //                 description: 'Filtrer par réseau'
                //             },
                //             ville: {
                //                 type: 'string',
                //                 required: false,
                //                 description: 'Filtrer par ville'
                //             },
                //             quartier: {
                //                 type: 'string',
                //                 required: false,
                //                 description: 'Filtrer par quartier'
                //             },
                //             search: {
                //                 type: 'string',
                //                 required: false,
                //                 description: 'Recherche textuelle (libellé, description, adresse)'
                //             },
                //             open_now: {
                //                 type: 'boolean',
                //                 required: false,
                //                 description: 'Filtrer les agences ouvertes actuellement'
                //             },
                //             latitude: {
                //                 type: 'number',
                //                 required: false,
                //                 description: 'Latitude pour recherche à proximité'
                //             },
                //             longitude: {
                //                 type: 'number',
                //                 required: false,
                //                 description: 'Longitude pour recherche à proximité'
                //             },
                //             radius: {
                //                 type: 'number',
                //                 required: false,
                //                 default: 10,
                //                 description: 'Rayon en kilomètres (1-100)'
                //             }
                //         }
                //     },
                //     responses: [{
                //         status: 200,
                //         description: 'Liste des agences avec horaires et configuration rendez-vous',
                //         example: {
                //             success: true,
                //             code: 'AGENCES_LISTED',
                //             message: 'Liste des agences récupérée.',
                //             data: [{
                //                 uuid_agence: '550e8400-e29b-41d4-a716-446655440001',
                //                 code: 'AG001',
                //                 libelle: 'YAKO Plateau',
                //                 description: 'Agence principale du Plateau',
                //                 adresse: 'Av. Chardy, Imm. Alpha 2000',
                //                 ville: 'Abidjan',
                //                 quartier: 'Plateau',
                //                 telephone: '+2252720304050',
                //                 email: 'plateau@yako.ci',
                //                 latitude: 5.3364,
                //                 longitude: -4.0271,
                //                 status: 'actif',
                //                 is_open: true,
                //                 is_rdv_actif_aujourdhui: true,
                //                 horaires: {
                //                     lundi: {
                //                         jour: 'lundi',
                //                         jour_label: 'Lundi',
                //                         heure_ouverture: '08:00',
                //                         heure_fermeture: '17:30',
                //                         heure_ouverture_midi: null,
                //                         heure_fermeture_midi: null,
                //                         ferme: false,
                //                         commentaire: null,
                //                         rendez_vous_actif: true,
                //                         capacite_rendez_vous: 10
                //                     },
                //                     mardi: {
                //                         jour: 'mardi',
                //                         jour_label: 'Mardi',
                //                         heure_ouverture: '08:00',
                //                         heure_fermeture: '17:30',
                //                         ferme: false,
                //                         rendez_vous_actif: true,
                //                         capacite_rendez_vous: 10
                //                     },
                //                     mercredi: {
                //                         jour: 'mercredi',
                //                         jour_label: 'Mercredi',
                //                         heure_ouverture: '08:00',
                //                         heure_fermeture: '17:30',
                //                         ferme: false,
                //                         rendez_vous_actif: true,
                //                         capacite_rendez_vous: 8
                //                     },
                //                     samedi: {
                //                         jour: 'samedi',
                //                         jour_label: 'Samedi',
                //                         heure_ouverture: '08:00',
                //                         heure_fermeture: '12:00',
                //                         ferme: false,
                //                         rendez_vous_actif: false,
                //                         capacite_rendez_vous: null
                //                     },
                //                     dimanche: {
                //                         jour: 'dimanche',
                //                         jour_label: 'Dimanche',
                //                         heure_ouverture: null,
                //                         heure_fermeture: null,
                //                         ferme: true,
                //                         rendez_vous_actif: false,
                //                         capacite_rendez_vous: null
                //                     }
                //                 },
                //                 reseau: {
                //                     uuid_reseau: '...',
                //                     libelle: 'Réseau Abidjan'
                //                 }
                //             }],
                //             meta: {
                //                 current_page: 1,
                //                 per_page: 20,
                //                 total: 1,
                //                 last_page: 1
                //             }
                //         }
                //     }]
                // },
                // ============================================================

                // 3.17 AGENCES
                // ============================================================
                {
                    id: 'agences-list',
                    module: 'agences',
                    name: 'Liste des agences',
                    description: 'Récupère la liste des agences avec filtres (ville, quartier, statut, réseau, recherche textuelle, agences ouvertes, géolocalisation). Retourne également les horaires avec la configuration des rendez-vous par jour.',
                    method: 'GET',
                    path: '/agences',
                    isProtected: true,
                    permissionsRequired: ['agences.afficher'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        query: {
                            per_page: {
                                type: 'integer',
                                required: false,
                                default: 20,
                                description: 'Nombre par page'
                            },
                            status: {
                                type: 'string',
                                required: false,
                                enum: ['actif', 'inactif'],
                                description: 'Filtrer par statut'
                            },
                            reseau_uuid: {
                                type: 'uuid',
                                required: false,
                                description: 'Filtrer par réseau'
                            },
                            ville: {
                                type: 'string',
                                required: false,
                                description: 'Filtrer par ville'
                            },
                            quartier: {
                                type: 'string',
                                required: false,
                                description: 'Filtrer par quartier'
                            },
                            search: {
                                type: 'string',
                                required: false,
                                description: 'Recherche textuelle (libellé, description, adresse)'
                            },
                            open_now: {
                                type: 'boolean',
                                required: false,
                                description: 'Filtrer les agences ouvertes actuellement'
                            },
                            latitude: {
                                type: 'number',
                                required: false,
                                description: 'Latitude pour recherche à proximité'
                            },
                            longitude: {
                                type: 'number',
                                required: false,
                                description: 'Longitude pour recherche à proximité'
                            },
                            radius: {
                                type: 'number',
                                required: false,
                                default: 10,
                                description: 'Rayon en kilomètres (1-100)'
                            }
                        }
                    },
                    responses: [{
                        status: 200,
                        description: 'Liste des agences avec horaires et configuration rendez-vous',
                        example: {
                            success: true,
                            message: 'Liste des agences récupérée.',
                            code: 'AGENCES_LISTED',
                            data: [{
                                uuid_agence: '550e8400-e29b-41d4-a716-446655440001',
                                code: 'AG001',
                                libelle: 'YAKO Plateau',
                                adresse: 'Av. Chardy, Imm. Alpha 2000',
                                ville: 'Abidjan',
                                quartier: 'Plateau',
                                telephone: '+2252720304050',
                                email: 'plateau@yako.ci',
                                latitude: 5.3364,
                                longitude: -4.0271,
                                status: 'actif',
                                is_open: true,
                                is_rdv_actif_aujourdhui: true,
                                horaires: {
                                    lundi: {
                                        jour: 'lundi',
                                        jour_label: 'Lundi',
                                        heure_ouverture: '08:00',
                                        heure_fermeture: '17:30',
                                        ferme: false,
                                        rendez_vous_actif: true,
                                        capacite_rendez_vous: 10
                                    },
                                    dimanche: {
                                        jour: 'dimanche',
                                        jour_label: 'Dimanche',
                                        ferme: true,
                                        rendez_vous_actif: false,
                                        capacite_rendez_vous: null
                                    }
                                },
                                reseau: {
                                    uuid_reseau: '...',
                                    libelle: 'Réseau Abidjan'
                                }
                            }],
                            meta: {
                                current_page: 1,
                                per_page: 20,
                                total: 1,
                                last_page: 1
                            }
                        }
                    }]
                },

                {
                    id: 'agences-create',
                    module: 'agences',
                    name: 'Créer une agence',
                    description: 'Crée une nouvelle agence avec ses informations (contact, horaires, géolocalisation). Chaque horaire peut être configuré pour les rendez-vous avec `rendez_vous_actif` et `capacite_rendez_vous`.',
                    method: 'POST',
                    path: '/agences',
                    isProtected: true,
                    permissionsRequired: ['agences.creer'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        body: {
                            code: {
                                type: 'string',
                                required: true,
                                max: 55,
                                description: 'Code unique de l\'agence'
                            },
                            libelle: {
                                type: 'string',
                                required: true,
                                max: 255,
                                description: 'Nom de l\'agence'
                            },
                            description: {
                                type: 'string',
                                required: false,
                                description: 'Description'
                            },
                            reseau_uuid: {
                                type: 'uuid',
                                required: false,
                                description: 'UUID du réseau'
                            },
                            email: {
                                type: 'email',
                                required: false,
                                max: 100,
                                description: 'Email'
                            },
                            telephone: {
                                type: 'string',
                                required: false,
                                max: 25,
                                description: 'Téléphone principal'
                            },
                            telephone_2: {
                                type: 'string',
                                required: false,
                                max: 25,
                                description: 'Téléphone secondaire'
                            },
                            adresse: {
                                type: 'string',
                                required: false,
                                max: 255,
                                description: 'Adresse'
                            },
                            ville: {
                                type: 'string',
                                required: false,
                                max: 100,
                                description: 'Ville'
                            },
                            quartier: {
                                type: 'string',
                                required: false,
                                max: 100,
                                description: 'Quartier'
                            },
                            code_postal: {
                                type: 'string',
                                required: false,
                                max: 20,
                                description: 'Code postal'
                            },
                            pays: {
                                type: 'string',
                                required: false,
                                max: 100,
                                default: 'Côte d\'Ivoire',
                                description: 'Pays'
                            },
                            latitude: {
                                type: 'number',
                                required: false,
                                between: [-90, 90],
                                description: 'Latitude'
                            },
                            longitude: {
                                type: 'number',
                                required: false,
                                between: [-180, 180],
                                description: 'Longitude'
                            },
                            photo: {
                                type: 'string',
                                required: false,
                                max: 255,
                                description: 'Photo principale'
                            },
                            photos: {
                                type: 'array',
                                required: false,
                                description: 'Galerie photos'
                            },
                            responsable: {
                                type: 'string',
                                required: false,
                                max: 255,
                                description: 'Nom du responsable'
                            },
                            site_web: {
                                type: 'url',
                                required: false,
                                max: 255,
                                description: 'Site web'
                            },
                            status: {
                                type: 'string',
                                required: false,
                                enum: ['actif', 'inactif'],
                                default: 'actif',
                                description: 'Statut'
                            },
                            horaires: {
                                type: 'array',
                                required: false,
                                description: 'Horaires d\'ouverture par jour'
                            },
                            'horaires.*.jour': {
                                type: 'string',
                                required: true,
                                enum: ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'],
                                description: 'Jour de la semaine'
                            },
                            'horaires.*.heure_ouverture': {
                                type: 'string',
                                format: 'H:i',
                                required: false,
                                description: 'Heure d\'ouverture'
                            },
                            'horaires.*.heure_fermeture': {
                                type: 'string',
                                format: 'H:i',
                                required: false,
                                description: 'Heure de fermeture'
                            },
                            'horaires.*.heure_ouverture_midi': {
                                type: 'string',
                                format: 'H:i',
                                required: false,
                                description: 'Heure d\'ouverture après la pause midi'
                            },
                            'horaires.*.heure_fermeture_midi': {
                                type: 'string',
                                format: 'H:i',
                                required: false,
                                description: 'Heure de fermeture pour la pause midi'
                            },
                            'horaires.*.ferme': {
                                type: 'boolean',
                                required: false,
                                default: false,
                                description: 'Fermé ce jour'
                            },
                            'horaires.*.commentaire': {
                                type: 'string',
                                required: false,
                                max: 255,
                                description: 'Commentaire'
                            },
                            'horaires.*.rendez_vous_actif': {
                                type: 'boolean',
                                required: false,
                                default: false,
                                description: 'L\'agence reçoit sur rendez-vous ce jour'
                            },
                            'horaires.*.capacite_rendez_vous': {
                                type: 'integer',
                                required: false,
                                min: 0,
                                description: 'Capacité maximale de rendez-vous pour ce jour'
                            }
                        }
                    },
                    exampleRequest: {
                        code: 'AG001',
                        libelle: 'YAKO Plateau',
                        email: 'plateau@yako.ci',
                        telephone: '+2252720304050',
                        adresse: 'Av. Chardy, Imm. Alpha 2000',
                        ville: 'Abidjan',
                        quartier: 'Plateau',
                        latitude: 5.3364,
                        longitude: -4.0271,
                        horaires: [{
                            jour: 'lundi',
                            heure_ouverture: '08:00',
                            heure_fermeture: '17:30',
                            rendez_vous_actif: true,
                            capacite_rendez_vous: 10
                        }, {
                            jour: 'mardi',
                            heure_ouverture: '08:00',
                            heure_fermeture: '17:30',
                            rendez_vous_actif: true,
                            capacite_rendez_vous: 10
                        }, {
                            jour: 'mercredi',
                            heure_ouverture: '08:00',
                            heure_fermeture: '17:30',
                            rendez_vous_actif: true,
                            capacite_rendez_vous: 8
                        }, {
                            jour: 'samedi',
                            heure_ouverture: '08:00',
                            heure_fermeture: '12:00',
                            rendez_vous_actif: false
                        }, {
                            jour: 'dimanche',
                            ferme: true
                        }]
                    },
                    responses: [{
                        status: 201,
                        description: 'Agence créée avec succès',
                        example: {
                            success: true,
                            message: 'Agence créée avec succès.',
                            code: 'AGENCE_CREATED',
                            data: {
                                uuid_agence: '550e8400-e29b-41d4-a716-446655440001',
                                code: 'AG001',
                                libelle: 'YAKO Plateau',
                                horaires: {
                                    lundi: {
                                        rendez_vous_actif: true,
                                        capacite_rendez_vous: 10
                                    }
                                }
                            }
                        }
                    }, {
                        status: 422,
                        description: 'Erreur de validation',
                        example: {
                            success: false,
                            message: 'Données invalides.',
                            errors: {
                                code: ['Le code est déjà utilisé.'],
                                'horaires.0.capacite_rendez_vous': ['La capacité doit être un nombre positif.']
                            }
                        }
                    }]
                },

                {
                    id: 'agences-show',
                    module: 'agences',
                    name: 'Détails d\'une agence',
                    description: 'Récupère les informations complètes d\'une agence (avec les horaires, la configuration rendez-vous, le réseau, les utilisateurs).',
                    method: 'GET',
                    path: '/agences/{uuid_agence}',
                    isProtected: true,
                    permissionsRequired: ['agences.afficher'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_agence: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID de l\'agence'
                            }
                        }
                    },
                    responses: [{
                        status: 200,
                        description: 'Détails de l\'agence avec configuration rendez-vous',
                        example: {
                            success: true,
                            code: 'AGENCE_FOUND',
                            message: 'Détails de l\'agence.',
                            data: {
                                uuid_agence: '550e8400-e29b-41d4-a716-446655440001',
                                code: 'AG001',
                                libelle: 'YAKO Plateau',
                                description: 'Agence principale du Plateau',
                                adresse: 'Av. Chardy, Imm. Alpha 2000',
                                ville: 'Abidjan',
                                quartier: 'Plateau',
                                telephone: '+2252720304050',
                                telephone_2: null,
                                email: 'plateau@yako.ci',
                                latitude: 5.3364,
                                longitude: -4.0271,
                                photo: null,
                                photos: [],
                                responsable: 'M. Koffi Serge',
                                site_web: 'https://www.yako.ci',
                                status: 'actif',
                                is_open: true,
                                is_rdv_actif_aujourdhui: true,
                                horaires: {
                                    lundi: {
                                        jour: 'lundi',
                                        jour_label: 'Lundi',
                                        heure_ouverture: '08:00',
                                        heure_fermeture: '17:30',
                                        heure_ouverture_midi: null,
                                        heure_fermeture_midi: null,
                                        ferme: false,
                                        commentaire: null,
                                        rendez_vous_actif: true,
                                        capacite_rendez_vous: 10
                                    },
                                    mardi: {
                                        jour: 'mardi',
                                        jour_label: 'Mardi',
                                        heure_ouverture: '08:00',
                                        heure_fermeture: '17:30',
                                        ferme: false,
                                        rendez_vous_actif: true,
                                        capacite_rendez_vous: 10
                                    },
                                    mercredi: {
                                        jour: 'mercredi',
                                        jour_label: 'Mercredi',
                                        heure_ouverture: '08:00',
                                        heure_fermeture: '17:30',
                                        ferme: false,
                                        rendez_vous_actif: true,
                                        capacite_rendez_vous: 8
                                    },
                                    jeudi: {
                                        jour: 'jeudi',
                                        jour_label: 'Jeudi',
                                        heure_ouverture: '08:00',
                                        heure_fermeture: '17:30',
                                        ferme: false,
                                        rendez_vous_actif: true,
                                        capacite_rendez_vous: 10
                                    },
                                    vendredi: {
                                        jour: 'vendredi',
                                        jour_label: 'Vendredi',
                                        heure_ouverture: '08:00',
                                        heure_fermeture: '17:30',
                                        ferme: false,
                                        rendez_vous_actif: true,
                                        capacite_rendez_vous: 10
                                    },
                                    samedi: {
                                        jour: 'samedi',
                                        jour_label: 'Samedi',
                                        heure_ouverture: '08:00',
                                        heure_fermeture: '12:00',
                                        ferme: false,
                                        rendez_vous_actif: false,
                                        capacite_rendez_vous: null
                                    },
                                    dimanche: {
                                        jour: 'dimanche',
                                        jour_label: 'Dimanche',
                                        heure_ouverture: null,
                                        heure_fermeture: null,
                                        ferme: true,
                                        rendez_vous_actif: false,
                                        capacite_rendez_vous: null
                                    }
                                },
                                reseau: {
                                    uuid_reseau: '...',
                                    libelle: 'Réseau Abidjan'
                                },
                                users_count: 5
                            }
                        }
                    }, {
                        status: 404,
                        description: 'Agence non trouvée'
                    }]
                },

                {
                    id: 'agences-update',
                    module: 'agences',
                    name: 'Mettre à jour une agence',
                    description: 'Modifie les informations d\'une agence existante. Permet de mettre à jour la configuration des rendez-vous par jour. Vous pouvez modifier un ou plusieurs horaires sans fournir tous les horaires existants.',
                    method: 'PUT',
                    path: '/agences/{uuid_agence}',
                    isProtected: true,
                    permissionsRequired: ['agences.modifier'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_agence: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID de l\'agence'
                            }
                        },
                        body: {
                            code: {
                                type: 'string',
                                required: false,
                                max: 55,
                                description: 'Code unique de l\'agence'
                            },
                            libelle: {
                                type: 'string',
                                required: false,
                                max: 255,
                                description: 'Nom de l\'agence'
                            },
                            description: {
                                type: 'string',
                                required: false,
                                description: 'Description'
                            },
                            reseau_uuid: {
                                type: 'uuid',
                                required: false,
                                description: 'UUID du réseau'
                            },
                            email: {
                                type: 'email',
                                required: false,
                                max: 100,
                                description: 'Email'
                            },
                            telephone: {
                                type: 'string',
                                required: false,
                                max: 25,
                                description: 'Téléphone principal'
                            },
                            telephone_2: {
                                type: 'string',
                                required: false,
                                max: 25,
                                description: 'Téléphone secondaire'
                            },
                            adresse: {
                                type: 'string',
                                required: false,
                                max: 255,
                                description: 'Adresse'
                            },
                            ville: {
                                type: 'string',
                                required: false,
                                max: 100,
                                description: 'Ville'
                            },
                            quartier: {
                                type: 'string',
                                required: false,
                                max: 100,
                                description: 'Quartier'
                            },
                            code_postal: {
                                type: 'string',
                                required: false,
                                max: 20,
                                description: 'Code postal'
                            },
                            pays: {
                                type: 'string',
                                required: false,
                                max: 100,
                                description: 'Pays'
                            },
                            latitude: {
                                type: 'number',
                                required: false,
                                between: [-90, 90],
                                description: 'Latitude'
                            },
                            longitude: {
                                type: 'number',
                                required: false,
                                between: [-180, 180],
                                description: 'Longitude'
                            },
                            photo: {
                                type: 'string',
                                required: false,
                                max: 255,
                                description: 'Photo principale'
                            },
                            photos: {
                                type: 'array',
                                required: false,
                                description: 'Galerie photos'
                            },
                            responsable: {
                                type: 'string',
                                required: false,
                                max: 255,
                                description: 'Nom du responsable'
                            },
                            site_web: {
                                type: 'url',
                                required: false,
                                max: 255,
                                description: 'Site web'
                            },
                            status: {
                                type: 'string',
                                required: false,
                                enum: ['actif', 'inactif'],
                                description: 'Statut'
                            },
                            horaires: {
                                type: 'array',
                                required: false,
                                description: 'Horaires d\'ouverture par jour (partiel ou total)'
                            },
                            'horaires.*.jour': {
                                type: 'string',
                                required: true,
                                enum: ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'],
                                description: 'Jour de la semaine'
                            },
                            'horaires.*.heure_ouverture': {
                                type: 'string',
                                format: 'H:i',
                                required: false,
                                description: 'Heure d\'ouverture'
                            },
                            'horaires.*.heure_fermeture': {
                                type: 'string',
                                format: 'H:i',
                                required: false,
                                description: 'Heure de fermeture'
                            },
                            'horaires.*.heure_ouverture_midi': {
                                type: 'string',
                                format: 'H:i',
                                required: false,
                                description: 'Heure d\'ouverture après la pause midi'
                            },
                            'horaires.*.heure_fermeture_midi': {
                                type: 'string',
                                format: 'H:i',
                                required: false,
                                description: 'Heure de fermeture pour la pause midi'
                            },
                            'horaires.*.ferme': {
                                type: 'boolean',
                                required: false,
                                description: 'Fermé ce jour'
                            },
                            'horaires.*.commentaire': {
                                type: 'string',
                                required: false,
                                max: 255,
                                description: 'Commentaire'
                            },
                            'horaires.*.rendez_vous_actif': {
                                type: 'boolean',
                                required: false,
                                description: 'L\'agence reçoit sur rendez-vous ce jour'
                            },
                            'horaires.*.capacite_rendez_vous': {
                                type: 'integer',
                                required: false,
                                min: 0,
                                description: 'Capacité maximale de rendez-vous pour ce jour'
                            }
                        }
                    },
                    exampleRequest: {
                        libelle: 'YAKO Plateau (mise à jour)',
                        horaires: [
                            {
                                jour: 'lundi',
                                rendez_vous_actif: true,
                                capacite_rendez_vous: 15
                            },
                            {
                                jour: 'mardi',
                                rendez_vous_actif: true,
                                capacite_rendez_vous: 12
                            },
                            {
                                jour: 'samedi',
                                ferme: true
                            }
                        ]
                    },
                    exampleRequestPartial: {
                        // Mise à jour partielle - seul l'horaire du lundi est modifié
                        horaires: [
                            {
                                jour: 'lundi',
                                rendez_vous_actif: true,
                                capacite_rendez_vous: 20
                            }
                        ]
                    },
                    responses: [{
                        status: 200,
                        description: 'Agence mise à jour',
                        example: {
                            success: true,
                            message: 'Agence mise à jour avec succès.',
                            code: 'AGENCE_UPDATED',
                            data: {
                                uuid_agence: '550e8400-e29b-41d4-a716-446655440001',
                                code: 'AG001',
                                libelle: 'YAKO Plateau (mise à jour)',
                                horaires: {
                                    lundi: {
                                        jour: 'lundi',
                                        jour_label: 'Lundi',
                                        heure_ouverture: '08:00',
                                        heure_fermeture: '17:30',
                                        ferme: false,
                                        rendez_vous_actif: true,
                                        capacite_rendez_vous: 15
                                    },
                                    mardi: {
                                        jour: 'mardi',
                                        jour_label: 'Mardi',
                                        heure_ouverture: '08:00',
                                        heure_fermeture: '17:30',
                                        ferme: false,
                                        rendez_vous_actif: true,
                                        capacite_rendez_vous: 12
                                    },
                                    samedi: {
                                        jour: 'samedi',
                                        jour_label: 'Samedi',
                                        ferme: true,
                                        rendez_vous_actif: false,
                                        capacite_rendez_vous: null
                                    }
                                }
                            }
                        }
                    }]
                },

                {
                    id: 'agences-delete',
                    module: 'agences',
                    name: 'Supprimer une agence',
                    description: 'Supprime une agence (soft delete). Refusée si des utilisateurs sont associés.',
                    method: 'DELETE',
                    path: '/agences/{uuid_agence}',
                    isProtected: true,
                    isDestructive: true,
                    permissionsRequired: ['agences.supprimer'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_agence: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID de l\'agence'
                            }
                        },
                        body: {}
                    },
                    responses: [{
                        status: 200,
                        description: 'Agence supprimée',
                        example: {
                            success: true,
                            message: 'Agence supprimée avec succès.',
                            code: 'AGENCE_DELETED'
                        }
                    }, {
                        status: 422,
                        description: 'Agence associée à des utilisateurs',
                        example: {
                            success: false,
                            message: 'Cette agence est associée à des utilisateurs.',
                            code: 'AGENCE_HAS_USERS'
                        }
                    }]
                },

                {
                    id: 'agences-nearby',
                    module: 'agences',
                    name: 'Agences à proximité',
                    description: 'Récupère les agences les plus proches d\'une position géographique donnée.',
                    method: 'GET',
                    path: '/agences/nearby',
                    isProtected: true,
                    permissionsRequired: ['agences.afficher'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        query: {
                            latitude: {
                                type: 'number',
                                required: true,
                                between: [-90, 90],
                                description: 'Latitude'
                            },
                            longitude: {
                                type: 'number',
                                required: true,
                                between: [-180, 180],
                                description: 'Longitude'
                            },
                            radius: {
                                type: 'number',
                                required: false,
                                min: 1,
                                max: 100,
                                default: 10,
                                description: 'Rayon en kilomètres'
                            },
                            limit: {
                                type: 'integer',
                                required: false,
                                min: 1,
                                max: 50,
                                default: 20,
                                description: 'Nombre d\'agences'
                            }
                        }
                    },
                    exampleRequest: {
                        latitude: 5.3364,
                        longitude: -4.0271,
                        radius: 10,
                        limit: 10
                    },
                    responses: [{
                        status: 200,
                        description: 'Agences à proximité',
                        example: {
                            success: true,
                            code: 'NEARBY_AGENCES',
                            message: 'Agences à proximité récupérées.',
                            data: [{
                                uuid_agence: '...',
                                libelle: 'YAKO Plateau',
                                adresse: 'Av. Chardy, Imm. Alpha 2000',
                                ville: 'Abidjan',
                                telephone: '+2252720304050',
                                distance: 1.2
                            }]
                        }
                    }]
                },

                {
                    id: 'agences-horaires',
                    module: 'agences',
                    name: 'Horaires d\'une agence',
                    description: 'Récupère les horaires d\'ouverture d\'une agence avec la configuration des rendez-vous par jour.',
                    method: 'GET',
                    path: '/agences/{uuid_agence}/horaires',
                    isProtected: true,
                    permissionsRequired: ['agences.afficher'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_agence: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID de l\'agence'
                            }
                        }
                    },
                    responses: [{
                        status: 200,
                        description: 'Horaires de l\'agence avec configuration rendez-vous',
                        example: {
                            success: true,
                            code: 'AGENCE_HORAIRES_LISTED',
                            message: 'Horaires de l\'agence récupérés.',
                            data: {
                                lundi: {
                                    jour: 'lundi',
                                    jour_label: 'Lundi',
                                    heure_ouverture: '08:00',
                                    heure_fermeture: '17:30',
                                    heure_ouverture_midi: null,
                                    heure_fermeture_midi: null,
                                    ferme: false,
                                    commentaire: null,
                                    rendez_vous_actif: true,
                                    capacite_rendez_vous: 10
                                },
                                mardi: {
                                    jour: 'mardi',
                                    jour_label: 'Mardi',
                                    heure_ouverture: '08:00',
                                    heure_fermeture: '17:30',
                                    ferme: false,
                                    rendez_vous_actif: true,
                                    capacite_rendez_vous: 10
                                },
                                dimanche: {
                                    jour: 'dimanche',
                                    jour_label: 'Dimanche',
                                    heure_ouverture: null,
                                    heure_fermeture: null,
                                    ferme: true,
                                    rendez_vous_actif: false,
                                    capacite_rendez_vous: null
                                }
                            }
                        }
                    }]
                },

                {
                    id: 'agences-assign-users',
                    module: 'agences',
                    name: 'Assigner des utilisateurs',
                    description: 'Assigne un ou plusieurs utilisateurs à une agence.',
                    method: 'POST',
                    path: '/agences/{uuid_agence}/users',
                    isProtected: true,
                    permissionsRequired: ['agences.assigner_utilisateurs'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_agence: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID de l\'agence'
                            }
                        },
                        body: {
                            user_uuids: {
                                type: 'array',
                                required: true,
                                min: 1,
                                description: 'UUIDs des utilisateurs'
                            },
                            is_primary: {
                                type: 'boolean',
                                required: false,
                                default: false,
                                description: 'Utilisateur principal'
                            }
                        }
                    },
                    exampleRequest: {
                        user_uuids: ['uuid1', 'uuid2'],
                        is_primary: true
                    },
                    responses: [{
                        status: 200,
                        description: 'Utilisateurs assignés',
                        example: {
                            success: true,
                            message: 'Utilisateurs assignés avec succès.',
                            code: 'USERS_ASSIGNED',
                            data: {
                                agence: {
                                    uuid_agence: '...',
                                    libelle: 'YAKO Plateau',
                                    users_count: 7
                                },
                                assigned: ['uuid1', 'uuid2']
                            }
                        }
                    }]
                },

                {
                    id: 'agences-remove-user',
                    module: 'agences',
                    name: 'Retirer un utilisateur',
                    description: 'Retire un utilisateur d\'une agence.',
                    method: 'DELETE',
                    path: '/agences/{uuid_agence}/users/{uuid_user}',
                    isProtected: true,
                    permissionsRequired: ['agences.assigner_utilisateurs'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_agence: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID de l\'agence'
                            },
                            uuid_user: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID de l\'utilisateur'
                            }
                        },
                        body: {}
                    },
                    responses: [{
                        status: 200,
                        description: 'Utilisateur retiré',
                        example: {
                            success: true,
                            message: 'Utilisateur retiré de l\'agence avec succès.',
                            code: 'USER_REMOVED',
                            data: {
                                agence: {
                                    uuid_agence: '...',
                                    libelle: 'YAKO Plateau'
                                },
                                user_uuid: 'uuid_user'
                            }
                        }
                    }]
                },

                // ============================================================
                // FAQ - Public
                // ============================================================

                {
                    id: 'faq-list',
                    module: 'faq',
                    name: 'Liste des FAQs',
                    description: 'Récupère la liste des FAQs actives avec possibilité de filtrage par catégorie, recherche textuelle, et tri. Accessible publiquement sans authentification.',
                    method: 'GET',
                    path: '/faq',
                    isProtected: false,
                    headers: {
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        query: {
                            faq_category_uuid: {
                                type: 'uuid',
                                required: false,
                                description: 'Filtrer par catégorie (UUID de la catégorie)'
                            },
                            category: {
                                type: 'string',
                                required: false,
                                enum: ['compte', 'souscription', 'paiement', 'sinistre', 'securite', 'assistance', 'rendez-vous'],
                                description: 'Filtrer par catégorie (legacy)'
                            },
                            search: {
                                type: 'string',
                                required: false,
                                description: 'Recherche textuelle dans les questions, réponses et tags'
                            },
                            is_featured: {
                                type: 'boolean',
                                required: false,
                                description: 'Filtrer les FAQs en vedette'
                            },
                            per_page: {
                                type: 'integer',
                                required: false,
                                default: 20,
                                description: 'Nombre d\'éléments par page'
                            }
                        }
                    },
                    exampleRequest: {
                        category: 'compte',
                        per_page: 10
                    },
                    responses: [{
                        status: 200,
                        description: 'Liste des FAQs récupérée avec succès',
                        example: {
                            success: true,
                            message: 'Liste des FAQs récupérée.',
                            code: 'FAQS_LISTED',
                            data: [{
                                uuid_faq: '550e8400-e29b-41d4-a716-446655440000',
                                faq_category: {
                                    uuid: '550e8400-e29b-41d4-a716-446655440001',
                                    code: 'compte',
                                    label: 'Compte & connexion',
                                    icon: 'bi-person-circle',
                                    color: '#3490dc'
                                },
                                category: 'compte',
                                category_label: 'Compte & connexion',
                                question: 'Comment créer un compte sur YNOV ?',
                                answer: '<p>Pour créer votre compte YNOV, suivez ces étapes...</p>',
                                order: 1,
                                is_active: true,
                                is_featured: true,
                                tags: ['inscription', 'compte', 'création'],
                                views: 150,
                                created_at: '2025-01-15T10:00:00.000000Z',
                                updated_at: '2025-01-15T14:30:00.000000Z'
                            }],
                            meta: {
                                current_page: 1,
                                per_page: 20,
                                total: 5,
                                last_page: 1
                            }
                        }
                    }]
                },

                {
                    id: 'faq-search',
                    module: 'faq',
                    name: 'Rechercher dans les FAQs',
                    description: 'Effectue une recherche textuelle dans les questions, réponses et tags des FAQs actives. Accessible publiquement.',
                    method: 'GET',
                    path: '/faq/search',
                    isProtected: false,
                    headers: {
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        query: {
                            q: {
                                type: 'string',
                                required: true,
                                min: 2,
                                description: 'Terme de recherche (minimum 2 caractères)'
                            }
                        }
                    },
                    exampleRequest: {
                        q: 'mot de passe oublié'
                    },
                    responses: [{
                            status: 200,
                            description: 'Résultats de recherche',
                            example: {
                                success: true,
                                message: 'Résultats de recherche.',
                                code: 'FAQ_SEARCH_RESULTS',
                                data: [{
                                    uuid_faq: '...',
                                    question: 'J\'ai oublié mon mot de passe. Que faire ?',
                                    answer: '<p>Si vous avez oublié votre mot de passe...</p>',
                                    views: 89
                                }]
                            }
                        },
                        {
                            status: 422,
                            description: 'Terme de recherche trop court',
                            example: {
                                success: false,
                                message: 'Le terme de recherche doit contenir au moins 2 caractères.',
                                errors: {
                                    q: ['Le champ q doit contenir au moins 2 caractères.']
                                }
                            }
                        }
                    ]
                },

                {
                    id: 'faq-categories-list',
                    module: 'faq',
                    name: 'Liste des catégories de FAQ',
                    description: 'Récupère toutes les catégories de FAQ avec leurs compteurs de questions. Accessible publiquement.',
                    method: 'GET',
                    path: '/faq/categories',
                    isProtected: false,
                    headers: {
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        query: {
                            only_active: {
                                type: 'boolean',
                                required: false,
                                default: true,
                                description: 'Récupérer uniquement les catégories actives'
                            }
                        }
                    },
                    responses: [{
                        status: 200,
                        description: 'Catégories récupérées avec succès',
                        example: {
                            success: true,
                            message: 'Catégories de FAQs récupérées.',
                            code: 'FAQ_CATEGORIES_LISTED',
                            data: [{
                                    uuid: '550e8400-e29b-41d4-a716-446655440001',
                                    code: 'compte',
                                    label: 'Compte & connexion',
                                    icon: 'bi-person-circle',
                                    color: '#3490dc',
                                    description: 'Questions relatives à la création de compte, connexion, gestion du profil.',
                                    count: 5,
                                    is_default: true,
                                    is_active: true
                                },
                                {
                                    uuid: '550e8400-e29b-41d4-a716-446655440002',
                                    code: 'souscription',
                                    label: 'Souscription & contrats',
                                    icon: 'bi-file-earmark-text',
                                    color: '#2ecc71',
                                    description: 'Questions sur les souscriptions, les contrats et les garanties.',
                                    count: 8,
                                    is_default: true,
                                    is_active: true
                                }
                            ]
                        }
                    }]
                },

                {
                    id: 'faq-show',
                    module: 'faq',
                    name: 'Détails d\'une FAQ',
                    description: 'Récupère les détails d\'une FAQ spécifique. Incrémente automatiquement le compteur de vues à chaque consultation.',
                    method: 'GET',
                    path: '/faq/{uuid_faq}',
                    isProtected: false,
                    headers: {
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_faq: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID de la FAQ à consulter'
                            }
                        }
                    },
                    responses: [{
                            status: 200,
                            description: 'Détails de la FAQ récupérés avec succès',
                            example: {
                                success: true,
                                message: 'Détails de la FAQ.',
                                code: 'FAQ_FOUND',
                                data: {
                                    uuid_faq: '550e8400-e29b-41d4-a716-446655440000',
                                    faq_category: {
                                        uuid: '550e8400-e29b-41d4-a716-446655440001',
                                        code: 'compte',
                                        label: 'Compte & connexion',
                                        icon: 'bi-person-circle',
                                        color: '#3490dc'
                                    },
                                    category: 'compte',
                                    category_label: 'Compte & connexion',
                                    question: 'Comment créer un compte sur YNOV ?',
                                    answer: '<p>Pour créer votre compte YNOV, suivez ces étapes :</p><ol><li>Rendez-vous sur la page d\'inscription</li><li>Remplissez le formulaire...</li></ol>',
                                    order: 1,
                                    is_active: true,
                                    is_featured: true,
                                    tags: ['inscription', 'compte', 'création'],
                                    views: 151,
                                    created_at: '2025-01-15T10:00:00.000000Z',
                                    updated_at: '2025-01-15T14:30:00.000000Z'
                                }
                            }
                        },
                        {
                            status: 404,
                            description: 'FAQ non trouvée',
                            example: {
                                success: false,
                                message: 'FAQ non trouvée.'
                            }
                        }
                    ]
                },

                // ============================================================
                // 2. FAQ ADMIN - GESTION DES FAQs
                // ============================================================

                {
                    id: 'faq-create-admin',
                    module: 'faq',
                    name: '[Admin] Créer une FAQ',
                    description: 'Crée une nouvelle question/réponse pour la FAQ. Nécessite la permission `faqs.creer`.',
                    method: 'POST',
                    path: '/admin/faq',
                    isProtected: true,
                    permissionsRequired: ['faqs.creer'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        body: {
                            faq_category_uuid: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID de la catégorie (exists:faq_categories,uuid_faq_category)'
                            },
                            category: {
                                type: 'string',
                                required: false,
                                max: 50,
                                description: 'Catégorie (legacy - optionnel)'
                            },
                            category_label: {
                                type: 'string',
                                required: false,
                                max: 100,
                                description: 'Libellé personnalisé de la catégorie'
                            },
                            question: {
                                type: 'string',
                                required: true,
                                max: 255,
                                description: 'Question (titre de la FAQ)'
                            },
                            answer: {
                                type: 'string',
                                required: true,
                                description: 'Réponse (support HTML)'
                            },
                            order: {
                                type: 'integer',
                                required: false,
                                min: 0,
                                default: 0,
                                description: 'Ordre d\'affichage'
                            },
                            is_active: {
                                type: 'boolean',
                                required: false,
                                default: true,
                                description: 'FAQ active (visible publiquement)'
                            },
                            is_featured: {
                                type: 'boolean',
                                required: false,
                                default: false,
                                description: 'Mettre en avant dans la section "Questions fréquentes"'
                            },
                            tags: {
                                type: 'array',
                                required: false,
                                description: 'Tags pour la recherche'
                            },
                            'tags.*': {
                                type: 'string',
                                max: 50,
                                description: 'Tag individuel'
                            }
                        }
                    },
                    exampleRequest: {
                        faq_category_uuid: '550e8400-e29b-41d4-a716-446655440001',
                        question: 'Comment créer un compte sur YNOV ?',
                        answer: '<p>Pour créer votre compte YNOV, suivez ces étapes :</p><ol><li>Rendez-vous sur la page d\'inscription</li><li>Remplissez le formulaire...</li></ol>',
                        order: 1,
                        is_active: true,
                        is_featured: true,
                        tags: ['inscription', 'compte', 'création']
                    },
                    responses: [{
                            status: 201,
                            description: 'FAQ créée avec succès',
                            example: {
                                success: true,
                                message: 'FAQ créée avec succès.',
                                code: 'FAQ_CREATED',
                                data: {
                                    uuid_faq: '550e8400-e29b-41d4-a716-446655440000',
                                    faq_category: {
                                        uuid: '550e8400-e29b-41d4-a716-446655440001',
                                        code: 'compte',
                                        label: 'Compte & connexion'
                                    },
                                    question: 'Comment créer un compte sur YNOV ?',
                                    is_active: true,
                                    is_featured: true
                                }
                            }
                        },
                        {
                            status: 422,
                            description: 'Erreur de validation',
                            example: {
                                success: false,
                                message: 'Données invalides.',
                                errors: {
                                    faq_category_uuid: ['La catégorie est obligatoire.'],
                                    question: ['La question est obligatoire.'],
                                    answer: ['La réponse est obligatoire.']
                                }
                            }
                        },
                        {
                            status: 403,
                            description: 'Permission manquante',
                            example: {
                                success: false,
                                message: 'Vous n\'avez pas la permission nécessaire pour effectuer cette action.',
                                code: 'PERMISSION_DENIED'
                            }
                        }
                    ]
                },

                {
                    id: 'faq-update-admin',
                    module: 'faq',
                    name: '[Admin] Mettre à jour une FAQ',
                    description: 'Modifie une FAQ existante. Nécessite la permission `faqs.modifier`.',
                    method: 'PUT',
                    path: '/admin/faq/{uuid_faq}',
                    isProtected: true,
                    permissionsRequired: ['faqs.modifier'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_faq: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID de la FAQ à modifier'
                            }
                        },
                        body: {
                            faq_category_uuid: {
                                type: 'uuid',
                                required: false,
                                description: 'UUID de la catégorie'
                            },
                            category: {
                                type: 'string',
                                required: false,
                                max: 50,
                                description: 'Catégorie (legacy)'
                            },
                            category_label: {
                                type: 'string',
                                required: false,
                                max: 100,
                                description: 'Libellé personnalisé'
                            },
                            question: {
                                type: 'string',
                                required: false,
                                max: 255,
                                description: 'Question'
                            },
                            answer: {
                                type: 'string',
                                required: false,
                                description: 'Réponse'
                            },
                            order: {
                                type: 'integer',
                                required: false,
                                min: 0,
                                description: 'Ordre d\'affichage'
                            },
                            is_active: {
                                type: 'boolean',
                                required: false,
                                description: 'FAQ active'
                            },
                            is_featured: {
                                type: 'boolean',
                                required: false,
                                description: 'Mettre en avant'
                            },
                            tags: {
                                type: 'array',
                                required: false,
                                description: 'Tags pour la recherche'
                            }
                        }
                    },
                    exampleRequest: {
                        question: 'Comment créer un compte sur YNOV ? (mis à jour)',
                        answer: '<p>Pour créer votre compte YNOV, suivez ces étapes mises à jour...</p>',
                        is_featured: true
                    },
                    responses: [{
                            status: 200,
                            description: 'FAQ mise à jour avec succès',
                            example: {
                                success: true,
                                message: 'FAQ mise à jour avec succès.',
                                code: 'FAQ_UPDATED',
                                data: {}
                            }
                        },
                        {
                            status: 404,
                            description: 'FAQ non trouvée',
                            example: {
                                success: false,
                                message: 'FAQ non trouvée.'
                            }
                        }
                    ]
                },

                {
                    id: 'faq-delete-admin',
                    module: 'faq',
                    name: '[Admin] Supprimer une FAQ',
                    description: 'Supprime une FAQ (soft delete). Nécessite la permission `faqs.supprimer`.',
                    method: 'DELETE',
                    path: '/admin/faq/{uuid_faq}',
                    isProtected: true,
                    isDestructive: true,
                    permissionsRequired: ['faqs.supprimer'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_faq: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID de la FAQ à supprimer'
                            }
                        },
                        body: {}
                    },
                    responses: [{
                            status: 200,
                            description: 'FAQ supprimée avec succès',
                            example: {
                                success: true,
                                message: 'FAQ supprimée avec succès.',
                                code: 'FAQ_DELETED'
                            }
                        },
                        {
                            status: 404,
                            description: 'FAQ non trouvée',
                            example: {
                                success: false,
                                message: 'FAQ non trouvée.'
                            }
                        }
                    ]
                },

                {
                    id: 'faq-toggle-admin',
                    module: 'faq',
                    name: '[Admin] Activer/Désactiver une FAQ',
                    description: 'Active ou désactive une FAQ. Les FAQs désactivées ne sont pas visibles publiquement. Nécessite la permission `faqs.modifier`.',
                    method: 'POST',
                    path: '/admin/faq/{uuid_faq}/toggle',
                    isProtected: true,
                    permissionsRequired: ['faqs.modifier'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_faq: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID de la FAQ'
                            }
                        },
                        body: {}
                    },
                    responses: [{
                            status: 200,
                            description: 'FAQ activée',
                            example: {
                                success: true,
                                message: 'FAQ activée.',
                                code: 'FAQ_TOGGLED',
                                data: {
                                    uuid_faq: '...',
                                    is_active: true
                                }
                            }
                        },
                        {
                            status: 200,
                            description: 'FAQ désactivée',
                            example: {
                                success: true,
                                message: 'FAQ désactivée.',
                                code: 'FAQ_TOGGLED',
                                data: {
                                    uuid_faq: '...',
                                    is_active: false
                                }
                            }
                        }
                    ]
                },

                // ============================================================
                // 3. FAQ ADMIN - GESTION DES CATÉGORIES
                // ============================================================

                {
                    id: 'faq-category-create-admin',
                    module: 'faq',
                    name: '[Admin] Créer une catégorie de FAQ',
                    description: 'Crée une nouvelle catégorie de FAQ personnalisée. Nécessite la permission `faq_categories.creer`. Les catégories par défaut sont protégées.',
                    method: 'POST',
                    path: '/admin/faq/categories',
                    isProtected: true,
                    permissionsRequired: ['faq_categories.creer'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        body: {
                            label: {
                                type: 'string',
                                required: true,
                                max: 100,
                                description: 'Libellé de la catégorie'
                            },
                            code: {
                                type: 'string',
                                required: false,
                                max: 50,
                                description: 'Code unique (généré automatiquement si non fourni)'
                            },
                            icon: {
                                type: 'string',
                                required: false,
                                max: 50,
                                description: 'Icône (Bootstrap Icons, FontAwesome)'
                            },
                            color: {
                                type: 'string',
                                required: false,
                                max: 20,
                                description: 'Couleur (hexadécimal ou nom CSS)'
                            },
                            description: {
                                type: 'string',
                                required: false,
                                max: 500,
                                description: 'Description de la catégorie'
                            },
                            order: {
                                type: 'integer',
                                required: false,
                                min: 0,
                                description: 'Ordre d\'affichage (auto si non fourni)'
                            },
                            is_active: {
                                type: 'boolean',
                                required: false,
                                default: true,
                                description: 'Catégorie active'
                            },
                            metadata: {
                                type: 'object',
                                required: false,
                                description: 'Métadonnées supplémentaires'
                            }
                        }
                    },
                    exampleRequest: {
                        label: 'Questions générales',
                        icon: 'bi-question-circle',
                        color: '#6c757d',
                        description: 'Questions générales sur la plateforme',
                        order: 8,
                        is_active: true
                    },
                    responses: [{
                            status: 201,
                            description: 'Catégorie créée avec succès',
                            example: {
                                success: true,
                                message: 'Catégorie créée avec succès.',
                                code: 'FAQ_CATEGORY_CREATED',
                                data: {
                                    uuid_faq_category: '...',
                                    code: 'questions_generales',
                                    label: 'Questions générales',
                                    icon: 'bi-question-circle',
                                    color: '#6c757d',
                                    is_active: true,
                                    is_default: false
                                }
                            }
                        },
                        {
                            status: 422,
                            description: 'Erreur de validation',
                            example: {
                                success: false,
                                message: 'Erreur de validation.',
                                errors: {
                                    label: ['Le libellé est obligatoire.'],
                                    code: ['Ce code est déjà utilisé.']
                                }
                            }
                        }
                    ]
                },

                {
                    id: 'faq-category-update-admin',
                    module: 'faq',
                    name: '[Admin] Mettre à jour une catégorie de FAQ',
                    description: 'Modifie une catégorie de FAQ existante. Les catégories par défaut ne peuvent pas être modifiées. Nécessite la permission `faq_categories.modifier`.',
                    method: 'PUT',
                    path: '/admin/faq/categories/{uuid_faq_category}',
                    isProtected: true,
                    permissionsRequired: ['faq_categories.modifier'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_faq_category: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID de la catégorie à modifier'
                            }
                        },
                        body: {
                            label: {
                                type: 'string',
                                required: false,
                                max: 100,
                                description: 'Libellé'
                            },
                            icon: {
                                type: 'string',
                                required: false,
                                max: 50,
                                description: 'Icône'
                            },
                            color: {
                                type: 'string',
                                required: false,
                                max: 20,
                                description: 'Couleur'
                            },
                            description: {
                                type: 'string',
                                required: false,
                                max: 500,
                                description: 'Description'
                            },
                            order: {
                                type: 'integer',
                                required: false,
                                min: 0,
                                description: 'Ordre d\'affichage'
                            },
                            is_active: {
                                type: 'boolean',
                                required: false,
                                description: 'Catégorie active'
                            },
                            metadata: {
                                type: 'object',
                                required: false,
                                description: 'Métadonnées'
                            }
                        }
                    },
                    exampleRequest: {
                        label: 'Questions générales (mise à jour)',
                        icon: 'bi-question-circle-fill',
                        color: '#6c757d',
                        description: 'Questions générales mises à jour',
                        is_active: true
                    },
                    responses: [{
                            status: 200,
                            description: 'Catégorie mise à jour avec succès',
                            example: {
                                success: true,
                                message: 'Catégorie mise à jour avec succès.',
                                code: 'FAQ_CATEGORY_UPDATED',
                                data: {}
                            }
                        },
                        {
                            status: 422,
                            description: 'Erreur de validation',
                            example: {
                                success: false,
                                message: 'Erreur de validation.',
                                errors: {
                                    category: ['Les catégories par défaut ne peuvent pas être modifiées.']
                                }
                            }
                        },
                        {
                            status: 404,
                            description: 'Catégorie non trouvée',
                            example: {
                                success: false,
                                message: 'Catégorie non trouvée.',
                                code: 'FAQ_CATEGORY_NOT_FOUND'
                            }
                        }
                    ]
                },

                {
                    id: 'faq-category-delete-admin',
                    module: 'faq',
                    name: '[Admin] Supprimer une catégorie de FAQ',
                    description: 'Supprime une catégorie de FAQ. Les catégories par défaut ne peuvent pas être supprimées. Une catégorie contenant des FAQs ne peut pas être supprimée. Nécessite la permission `faq_categories.supprimer`.',
                    method: 'DELETE',
                    path: '/admin/faq/categories/{uuid_faq_category}',
                    isProtected: true,
                    isDestructive: true,
                    permissionsRequired: ['faq_categories.supprimer'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_faq_category: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID de la catégorie à supprimer'
                            }
                        },
                        body: {}
                    },
                    responses: [{
                            status: 200,
                            description: 'Catégorie supprimée avec succès',
                            example: {
                                success: true,
                                message: 'Catégorie supprimée avec succès.',
                                code: 'FAQ_CATEGORY_DELETED'
                            }
                        },
                        {
                            status: 422,
                            description: 'Erreur de validation',
                            example: {
                                success: false,
                                message: 'Erreur de validation.',
                                errors: {
                                    category: ['Les catégories par défaut ne peuvent pas être supprimées.']
                                }
                            }
                        },
                        {
                            status: 422,
                            description: 'Catégorie contient des FAQs',
                            example: {
                                success: false,
                                message: 'Erreur de validation.',
                                errors: {
                                    category: ['Cette catégorie contient des FAQs et ne peut pas être supprimée.']
                                }
                            }
                        }
                    ]
                },

                {
                    id: 'faq-category-toggle-admin',
                    module: 'faq',
                    name: '[Admin] Activer/Désactiver une catégorie de FAQ',
                    description: 'Active ou désactive une catégorie de FAQ. Les catégories désactivées ne sont pas affichées publiquement. Nécessite la permission `faq_categories.modifier`.',
                    method: 'POST',
                    path: '/admin/faq/categories/{uuid_faq_category}/toggle',
                    isProtected: true,
                    permissionsRequired: ['faq_categories.modifier'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_faq_category: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID de la catégorie'
                            }
                        },
                        body: {}
                    },
                    responses: [{
                        status: 200,
                        description: 'Catégorie activée/désactivée',
                        example: {
                            success: true,
                            message: 'Catégorie activée avec succès.',
                            code: 'FAQ_CATEGORY_TOGGLED',
                            data: {
                                uuid_faq_category: '...',
                                is_active: true
                            }
                        }
                    }]
                },

                {
                    id: 'faq-category-reorder-admin',
                    module: 'faq',
                    name: '[Admin] Réordonner les catégories',
                    description: 'Réordonne les catégories de FAQ selon l\'ordre souhaité. Nécessite la permission `faq_categories.modifier`.',
                    method: 'POST',
                    path: '/admin/faq/categories/reorder',
                    isProtected: true,
                    permissionsRequired: ['faq_categories.modifier'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        body: {
                            uuids: {
                                type: 'array',
                                required: true,
                                description: 'Liste des UUIDs dans l\'ordre souhaité'
                            },
                            'uuids.*': {
                                type: 'uuid',
                                description: 'UUID d\'une catégorie (exists:faq_categories,uuid_faq_category)'
                            }
                        }
                    },
                    exampleRequest: {
                        uuids: [
                            'uuid_categorie_1',
                            'uuid_categorie_2',
                            'uuid_categorie_3'
                        ]
                    },
                    responses: [{
                            status: 200,
                            description: 'Catégories réordonnées avec succès',
                            example: {
                                success: true,
                                message: 'Catégories réordonnées avec succès.',
                                code: 'FAQ_CATEGORIES_REORDERED'
                            }
                        },
                        {
                            status: 422,
                            description: 'Erreur de validation',
                            example: {
                                success: false,
                                message: 'Données invalides.',
                                errors: {
                                    uuids: ['Le champ uuids est obligatoire.']
                                }
                            }
                        }
                    ]
                },

                {
                    id: 'faq-category-duplicate-admin',
                    module: 'faq',
                    name: '[Admin] Dupliquer une catégorie de FAQ',
                    description: 'Crée une copie d\'une catégorie existante. La nouvelle catégorie est créée en mode inactif. Nécessite la permission `faq_categories.creer`.',
                    method: 'POST',
                    path: '/admin/faq/categories/{uuid_faq_category}/duplicate',
                    isProtected: true,
                    permissionsRequired: ['faq_categories.creer'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_faq_category: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID de la catégorie à dupliquer'
                            }
                        },
                        body: {}
                    },
                    responses: [{
                        status: 201,
                        description: 'Catégorie dupliquée avec succès',
                        example: {
                            success: true,
                            message: 'Catégorie dupliquée avec succès.',
                            code: 'FAQ_CATEGORY_DUPLICATED',
                            data: {
                                uuid_faq_category: '...',
                                label: 'Questions générales (copie)',
                                is_active: false
                            }
                        }
                    }]
                },

                {
                    id: 'faq-category-stats-admin',
                    module: 'faq',
                    name: '[Admin] Statistiques des catégories',
                    description: 'Récupère les statistiques des catégories de FAQ (total, actives, inactives, par défaut, personnalisées). Nécessite la permission `faq_categories.afficher`.',
                    method: 'GET',
                    path: '/admin/faq/categories/stats',
                    isProtected: true,
                    permissionsRequired: ['faq_categories.afficher'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        body: {}
                    },
                    responses: [{
                        status: 200,
                        description: 'Statistiques récupérées avec succès',
                        example: {
                            success: true,
                            message: 'Statistiques des catégories récupérées.',
                            code: 'FAQ_CATEGORY_STATS',
                            data: {
                                total: 10,
                                active: 8,
                                inactive: 2,
                                default: 7,
                                custom: 3
                            }
                        }
                    }]
                },

                {
                    id: 'faq-category-show-admin',
                    module: 'faq',
                    name: '[Admin] Détails d\'une catégorie de FAQ',
                    description: 'Récupère les détails d\'une catégorie avec ses FAQs associées. Nécessite la permission `faq_categories.afficher`.',
                    method: 'GET',
                    path: '/admin/faq/categories/{uuid_faq_category}',
                    isProtected: true,
                    permissionsRequired: ['faq_categories.afficher'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_faq_category: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID de la catégorie'
                            }
                        }
                    },
                    responses: [{
                            status: 200,
                            description: 'Détails de la catégorie',
                            example: {
                                success: true,
                                message: 'Catégorie récupérée avec succès.',
                                code: 'FAQ_CATEGORY_FOUND',
                                data: {
                                    uuid_faq_category: '...',
                                    code: 'compte',
                                    label: 'Compte & connexion',
                                    icon: 'bi-person-circle',
                                    color: '#3490dc',
                                    description: 'Questions relatives à la création de compte',
                                    is_active: true,
                                    is_default: true,
                                    faqs_count: 5,
                                    active_faqs_count: 5,
                                    faqs: [{
                                        uuid_faq: '...',
                                        question: 'Comment créer un compte ?',
                                        answer: '...',
                                        is_active: true,
                                        views: 150
                                    }]
                                }
                            }
                        },
                        {
                            status: 404,
                            description: 'Catégorie non trouvée',
                            example: {
                                success: false,
                                message: 'Catégorie non trouvée.',
                                code: 'FAQ_CATEGORY_NOT_FOUND'
                            }
                        }
                    ]
                },

                {
                    id: 'faq-category-select-admin',
                    module: 'faq',
                    name: '[Admin] Catégories pour sélection',
                    description: 'Récupère les catégories formatées pour un dropdown/select. Nécessite la permission `faq_categories.afficher`.',
                    method: 'GET',
                    path: '/admin/faq/categories/select',
                    isProtected: true,
                    permissionsRequired: ['faq_categories.afficher'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        query: {
                            only_active: {
                                type: 'boolean',
                                required: false,
                                default: true,
                                description: 'Récupérer uniquement les catégories actives'
                            }
                        }
                    },
                    responses: [{
                        status: 200,
                        description: 'Catégories pour sélection',
                        example: {
                            success: true,
                            message: 'Catégories pour sélection récupérées.',
                            code: 'FAQ_CATEGORIES_SELECT',
                            data: [{
                                    value: 'uuid_categorie_1',
                                    label: 'Compte & connexion',
                                    code: 'compte'
                                },
                                {
                                    value: 'uuid_categorie_2',
                                    label: 'Souscription & contrats',
                                    code: 'souscription'
                                }
                            ]
                        }
                    }]
                },

                // ============================================================
                // GROUPES DE NOTIFICATION
                // ============================================================

                // ============================================================
                // 1. MES GROUPES (Utilisateur connecté)
                // ============================================================
                {
                    id: 'group-notifs-my-groups',
                    module: 'group_notifs',
                    name: 'Mes groupes de notification',
                    description: 'Récupère la liste des groupes de notification auxquels l\'utilisateur connecté appartient. Retourne les informations du groupe et le statut de l\'utilisateur dans le groupe (principal, actif).',
                    method: 'GET',
                    path: '/group-notifs/my-groups',
                    isProtected: true,
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        body: {}
                    },
                    responses: [{
                            status: 200,
                            description: 'Mes groupes récupérés',
                            example: {
                                success: true,
                                message: 'Mes groupes de notification.',
                                code: 'MY_GROUPS_LISTED',
                                data: [{
                                        uuid_group_notif: '550e8400-e29b-41d4-a716-446655440001',
                                        code: 'welcome',
                                        libelle: 'Bienvenue',
                                        description: 'Notifications de bienvenue et d\'inscription',
                                        channels: ['database', 'email'],
                                        status: 'actif',
                                        is_primary: true,
                                        is_active: true,
                                        assigned_at: '2025-01-15T10:00:00.000000Z'
                                    },
                                    {
                                        uuid_group_notif: '550e8400-e29b-41d4-a716-446655440002',
                                        code: 'security',
                                        libelle: 'Sécurité',
                                        description: 'Alertes de sécurité',
                                        channels: ['database', 'email', 'sms'],
                                        status: 'actif',
                                        is_primary: false,
                                        is_active: true,
                                        assigned_at: '2025-01-15T10:00:00.000000Z'
                                    }
                                ]
                            }
                        },
                        {
                            status: 401,
                            description: 'Non authentifié',
                            example: {
                                success: false,
                                message: 'Non authentifié.'
                            }
                        }
                    ]
                },

                // ============================================================
                // 2. CANAUX DISPONIBLES
                // ============================================================
                {
                    id: 'group-notifs-channels',
                    module: 'group_notifs',
                    name: 'Canaux disponibles',
                    description: 'Récupère la liste des canaux de notification disponibles (database, email, sms, push, whatsapp). Utile pour configurer les préférences d\'un groupe.',
                    method: 'GET',
                    path: '/group-notifs/channels',
                    isProtected: true,
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        body: {}
                    },
                    responses: [{
                        status: 200,
                        description: 'Canaux disponibles',
                        example: {
                            success: true,
                            message: 'Canaux disponibles.',
                            code: 'CHANNELS_LISTED',
                            data: [{
                                    code: 'database',
                                    label: 'Base de données',
                                    icon: 'bi-database'
                                },
                                {
                                    code: 'email',
                                    label: 'Email',
                                    icon: 'bi-envelope'
                                },
                                {
                                    code: 'sms',
                                    label: 'SMS',
                                    icon: 'bi-phone'
                                },
                                {
                                    code: 'push',
                                    label: 'Push mobile',
                                    icon: 'bi-bell'
                                },
                                {
                                    code: 'whatsapp',
                                    label: 'WhatsApp',
                                    icon: 'bi-whatsapp'
                                }
                            ]
                        }
                    }]
                },

                // ============================================================
                // 3. DÉFINIR LE GROUPE PRINCIPAL
                // ============================================================
                {
                    id: 'group-notifs-set-primary',
                    module: 'group_notifs',
                    name: 'Définir le groupe principal',
                    description: 'Définit un groupe comme principal pour l\'utilisateur connecté. Un seul groupe peut être principal à la fois.',
                    method: 'POST',
                    path: '/group-notifs/{uuid_group_notif}/set-primary',
                    isProtected: true,
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_group_notif: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID du groupe de notification'
                            }
                        }
                    },
                    responses: [{
                            status: 200,
                            description: 'Groupe principal défini',
                            example: {
                                success: true,
                                message: 'Groupe principal défini.',
                                code: 'PRIMARY_GROUP_SET'
                            }
                        },
                        {
                            status: 404,
                            description: 'Utilisateur non trouvé dans ce groupe',
                            example: {
                                success: false,
                                message: 'Vous n\'appartenez pas à ce groupe.',
                                code: 'USER_NOT_IN_GROUP'
                            }
                        }
                    ]
                },

                // ============================================================
                // 4. ADMIN - LISTE DES GROUPES
                // ============================================================
                {
                    id: 'admin-group-notifs-list',
                    module: 'group_notifs',
                    name: '[Admin] Liste des groupes de notification',
                    description: 'Liste paginée des groupes de notification avec filtres (statut, recherche, canal). Nécessite la permission `group_notifs.afficher`.',
                    method: 'GET',
                    path: '/admin/group-notifs',
                    isProtected: true,
                    permissionsRequired: ['group_notifs.afficher'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        query: {
                            per_page: {
                                type: 'integer',
                                required: false,
                                default: 20,
                                description: 'Nombre par page'
                            },
                            status: {
                                type: 'string',
                                required: false,
                                enum: ['actif', 'inactif'],
                                description: 'Filtrer par statut'
                            },
                            search: {
                                type: 'string',
                                required: false,
                                description: 'Recherche textuelle (libellé, code, description)'
                            },
                            channel: {
                                type: 'string',
                                required: false,
                                enum: ['database', 'email', 'sms', 'push', 'whatsapp'],
                                description: 'Filtrer par canal'
                            }
                        }
                    },
                    responses: [{
                        status: 200,
                        description: 'Liste des groupes',
                        example: {
                            success: true,
                            message: 'Groupes de notification récupérés.',
                            code: 'GROUPS_LISTED',
                            data: [{
                                uuid_group_notif: '...',
                                code: 'welcome',
                                libelle: 'Bienvenue',
                                description: 'Notifications de bienvenue',
                                channels: ['database', 'email'],
                                status: 'actif',
                                users_count: 150,
                                created_at: '2025-01-15T10:00:00.000000Z'
                            }],
                            meta: {
                                current_page: 1,
                                per_page: 20,
                                total: 5,
                                last_page: 1
                            }
                        }
                    }]
                },

                // ============================================================
                // 5. ADMIN - CRÉER UN GROUPE
                // ============================================================
                {
                    id: 'admin-group-notifs-create',
                    module: 'group_notifs',
                    name: '[Admin] Créer un groupe de notification',
                    description: 'Crée un nouveau groupe de notification. Nécessite la permission `group_notifs.creer`.',
                    method: 'POST',
                    path: '/admin/group-notifs',
                    isProtected: true,
                    permissionsRequired: ['group_notifs.creer'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        body: {
                            libelle: {
                                type: 'string',
                                required: true,
                                max: 100,
                                description: 'Libellé du groupe'
                            },
                            code: {
                                type: 'string',
                                required: false,
                                max: 55,
                                description: 'Code unique (généré automatiquement)'
                            },
                            description: {
                                type: 'string',
                                required: false,
                                description: 'Description du groupe'
                            },
                            channels: {
                                type: 'array',
                                required: false,
                                description: 'Canaux de notification'
                            },
                            'channels.*': {
                                type: 'string',
                                enum: ['database', 'email', 'sms', 'push', 'whatsapp'],
                                description: 'Canal'
                            },
                            preferences: {
                                type: 'object',
                                required: false,
                                description: 'Préférences supplémentaires'
                            },
                            status: {
                                type: 'string',
                                required: false,
                                enum: ['actif', 'inactif'],
                                default: 'actif',
                                description: 'Statut'
                            }
                        }
                    },
                    exampleRequest: {
                        libelle: 'Promotions',
                        description: 'Notifications sur les offres promotionnelles',
                        channels: ['database', 'email'],
                        status: 'actif'
                    },
                    responses: [{
                            status: 201,
                            description: 'Groupe créé',
                            example: {
                                success: true,
                                message: 'Groupe de notification créé.',
                                code: 'GROUP_CREATED',
                                data: {
                                    uuid_group_notif: '...',
                                    code: 'promotions',
                                    libelle: 'Promotions',
                                    description: 'Notifications sur les offres promotionnelles',
                                    channels: ['database', 'email'],
                                    status: 'actif'
                                }
                            }
                        },
                        {
                            status: 422,
                            description: 'Erreur de validation',
                            example: {
                                success: false,
                                message: 'Données invalides.',
                                errors: {
                                    libelle: ['Le libellé est obligatoire.'],
                                    code: ['Ce code est déjà utilisé.']
                                }
                            }
                        }
                    ]
                },

                // ============================================================
                // 6. ADMIN - DÉTAILS D'UN GROUPE
                // ============================================================
                {
                    id: 'admin-group-notifs-show',
                    module: 'group_notifs',
                    name: '[Admin] Détails d\'un groupe',
                    description: 'Récupère les détails d\'un groupe de notification avec la liste de ses utilisateurs. Nécessite la permission `group_notifs.afficher`.',
                    method: 'GET',
                    path: '/admin/group-notifs/{uuid_group_notif}',
                    isProtected: true,
                    permissionsRequired: ['group_notifs.afficher'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_group_notif: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID du groupe'
                            }
                        }
                    },
                    responses: [{
                            status: 200,
                            description: 'Détails du groupe',
                            example: {
                                success: true,
                                message: 'Détails du groupe.',
                                code: 'GROUP_FOUND',
                                data: {
                                    uuid_group_notif: '...',
                                    code: 'welcome',
                                    libelle: 'Bienvenue',
                                    description: 'Notifications de bienvenue',
                                    channels: ['database', 'email'],
                                    status: 'actif',
                                    users_count: 150,
                                    users: [{
                                        uuid_user: '...',
                                        email: 'user@example.com',
                                        login: 'jdupont',
                                        details: {
                                            nom: 'Dupont',
                                            prenoms: 'Jean',
                                            full_name: 'Jean Dupont'
                                        },
                                        pivot: {
                                            is_primary: true,
                                            is_active: true,
                                            assigned_at: '2025-01-15T10:00:00.000000Z'
                                        }
                                    }],
                                    created_at: '2025-01-15T10:00:00.000000Z'
                                }
                            }
                        },
                        {
                            status: 404,
                            description: 'Groupe non trouvé',
                            example: {
                                success: false,
                                message: 'Groupe non trouvé.'
                            }
                        }
                    ]
                },

                // ============================================================
                // 7. ADMIN - MODIFIER UN GROUPE
                // ============================================================
                {
                    id: 'admin-group-notifs-update',
                    module: 'group_notifs',
                    name: '[Admin] Modifier un groupe de notification',
                    description: 'Modifie un groupe de notification existant. Nécessite la permission `group_notifs.modifier`.',
                    method: 'PUT',
                    path: '/admin/group-notifs/{uuid_group_notif}',
                    isProtected: true,
                    permissionsRequired: ['group_notifs.modifier'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_group_notif: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID du groupe'
                            }
                        },
                        body: {
                            libelle: {
                                type: 'string',
                                required: false,
                                max: 100,
                                description: 'Libellé'
                            },
                            description: {
                                type: 'string',
                                required: false,
                                description: 'Description'
                            },
                            channels: {
                                type: 'array',
                                required: false,
                                description: 'Canaux'
                            },
                            'channels.*': {
                                type: 'string',
                                enum: ['database', 'email', 'sms', 'push', 'whatsapp'],
                                description: 'Canal'
                            },
                            preferences: {
                                type: 'object',
                                required: false,
                                description: 'Préférences'
                            },
                            status: {
                                type: 'string',
                                required: false,
                                enum: ['actif', 'inactif'],
                                description: 'Statut'
                            }
                        }
                    },
                    exampleRequest: {
                        libelle: 'Promotions (mise à jour)',
                        channels: ['database', 'email', 'push'],
                        status: 'actif'
                    },
                    responses: [{
                        status: 200,
                        description: 'Groupe mis à jour',
                        example: {
                            success: true,
                            message: 'Groupe de notification mis à jour.',
                            code: 'GROUP_UPDATED',
                            data: {}
                        }
                    }]
                },

                // ============================================================
                // 8. ADMIN - SUPPRIMER UN GROUPE
                // ============================================================
                {
                    id: 'admin-group-notifs-delete',
                    module: 'group_notifs',
                    name: '[Admin] Supprimer un groupe de notification',
                    description: 'Supprime un groupe de notification (soft delete). Refusé si le groupe contient des utilisateurs. Nécessite la permission `group_notifs.supprimer`.',
                    method: 'DELETE',
                    path: '/admin/group-notifs/{uuid_group_notif}',
                    isProtected: true,
                    isDestructive: true,
                    permissionsRequired: ['group_notifs.supprimer'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_group_notif: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID du groupe'
                            }
                        },
                        body: {}
                    },
                    responses: [{
                            status: 200,
                            description: 'Groupe supprimé',
                            example: {
                                success: true,
                                message: 'Groupe de notification supprimé.',
                                code: 'GROUP_DELETED'
                            }
                        },
                        {
                            status: 422,
                            description: 'Groupe contient des utilisateurs',
                            example: {
                                success: false,
                                message: 'Erreur de validation.',
                                errors: {
                                    group: ['Ce groupe contient des utilisateurs et ne peut pas être supprimé.']
                                }
                            }
                        }
                    ]
                },

                // ============================================================
                // 9. ADMIN - DUPLIQUER UN GROUPE
                // ============================================================
                {
                    id: 'admin-group-notifs-duplicate',
                    module: 'group_notifs',
                    name: '[Admin] Dupliquer un groupe de notification',
                    description: 'Crée une copie d\'un groupe de notification existant. Le nouveau groupe est créé en mode inactif par défaut. Nécessite la permission `group_notifs.creer`.',
                    method: 'POST',
                    path: '/admin/group-notifs/{uuid_group_notif}/duplicate',
                    isProtected: true,
                    permissionsRequired: ['group_notifs.creer'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_group_notif: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID du groupe à dupliquer'
                            }
                        },
                        body: {}
                    },
                    responses: [{
                        status: 201,
                        description: 'Groupe dupliqué',
                        example: {
                            success: true,
                            message: 'Groupe dupliqué.',
                            code: 'GROUP_DUPLICATED',
                            data: {
                                uuid_group_notif: '...',
                                libelle: 'Promotions (copie)',
                                code: 'promotions_copy_1',
                                status: 'inactif'
                            }
                        }
                    }]
                },

                // ============================================================
                // 10. ADMIN - ASSIGNER DES UTILISATEURS
                // ============================================================
                {
                    id: 'admin-group-notifs-assign-users',
                    module: 'group_notifs',
                    name: '[Admin] Assigner des utilisateurs à un groupe',
                    description: 'Assigne un ou plusieurs utilisateurs à un groupe de notification. Nécessite la permission `group_notifs.assigner`.',
                    method: 'POST',
                    path: '/admin/group-notifs/{uuid_group_notif}/users',
                    isProtected: true,
                    permissionsRequired: ['group_notifs.assigner'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_group_notif: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID du groupe'
                            }
                        },
                        body: {
                            user_uuids: {
                                type: 'array',
                                required: true,
                                min: 1,
                                description: 'UUIDs des utilisateurs'
                            },
                            'user_uuids.*': {
                                type: 'uuid',
                                description: 'UUID d\'un utilisateur'
                            }
                        }
                    },
                    exampleRequest: {
                        user_uuids: ['uuid1', 'uuid2', 'uuid3']
                    },
                    responses: [{
                            status: 200,
                            description: 'Utilisateurs assignés',
                            example: {
                                success: true,
                                message: 'Utilisateurs assignés au groupe.',
                                code: 'USERS_ASSIGNED',
                                data: {
                                    assigned_count: 3,
                                    group: {}
                                }
                            }
                        },
                        {
                            status: 422,
                            description: 'Erreur de validation',
                            example: {
                                success: false,
                                message: 'Données invalides.',
                                errors: {
                                    user_uuids: ['Le champ user_uuids est obligatoire.']
                                }
                            }
                        }
                    ]
                },

                // ============================================================
                // 11. ADMIN - RETIRER UN UTILISATEUR
                // ============================================================
                {
                    id: 'admin-group-notifs-remove-user',
                    module: 'group_notifs',
                    name: '[Admin] Retirer un utilisateur d\'un groupe',
                    description: 'Retire un utilisateur d\'un groupe de notification. Nécessite la permission `group_notifs.assigner`.',
                    method: 'DELETE',
                    path: '/admin/group-notifs/{uuid_group_notif}/users/{uuid_user}',
                    isProtected: true,
                    permissionsRequired: ['group_notifs.assigner'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_group_notif: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID du groupe'
                            },
                            uuid_user: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID de l\'utilisateur'
                            }
                        },
                        body: {}
                    },
                    responses: [{
                            status: 200,
                            description: 'Utilisateur retiré',
                            example: {
                                success: true,
                                message: 'Utilisateur retiré du groupe.',
                                code: 'USER_REMOVED'
                            }
                        },
                        {
                            status: 404,
                            description: 'Utilisateur non trouvé dans ce groupe',
                            example: {
                                success: false,
                                message: 'Utilisateur non trouvé dans ce groupe.',
                                code: 'USER_NOT_IN_GROUP'
                            }
                        }
                    ]
                },

                // ============================================================
                // 12. ADMIN - STATISTIQUES DES GROUPES
                // ============================================================
                {
                    id: 'admin-group-notifs-stats',
                    module: 'group_notifs',
                    name: '[Admin] Statistiques des groupes',
                    description: 'Récupère les statistiques des groupes de notification : total, actifs, inactifs, nombre d\'utilisateurs assignés. Nécessite la permission `group_notifs.afficher`.',
                    method: 'GET',
                    path: '/admin/group-notifs/stats',
                    isProtected: true,
                    permissionsRequired: ['group_notifs.afficher'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        body: {}
                    },
                    responses: [{
                        status: 200,
                        description: 'Statistiques des groupes',
                        example: {
                            success: true,
                            message: 'Statistiques des groupes.',
                            code: 'GROUP_STATS',
                            data: {
                                total: 8,
                                active: 6,
                                inactive: 2,
                                total_users_assigned: 450
                            }
                        }
                    }]
                },

                // ============================================================
                // NOTIFICATIONS
                // ============================================================

                {
                    id: 'notifications-list',
                    module: 'notifications',
                    name: 'Liste des notifications',
                    description: 'Récupère la liste des notifications de l\'utilisateur connecté avec possibilité de filtrage (lues/non lues, importantes, type, groupe, recherche). Retourne également le nombre de notifications non lues.',
                    method: 'GET',
                    path: '/notifications',
                    isProtected: true,
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        query: {
                            per_page: {
                                type: 'integer',
                                required: false,
                                default: 20,
                                description: 'Nombre par page'
                            },
                            read: {
                                type: 'boolean',
                                required: false,
                                description: 'Filtrer par statut de lecture (true: lues, false: non lues)'
                            },
                            important: {
                                type: 'boolean',
                                required: false,
                                description: 'Filtrer les notifications importantes'
                            },
                            type: {
                                type: 'string',
                                required: false,
                                description: 'Filtrer par type'
                            },
                            group_notif_uuid: {
                                type: 'uuid',
                                required: false,
                                description: 'Filtrer par groupe de notification'
                            },
                            search: {
                                type: 'string',
                                required: false,
                                description: 'Recherche dans le titre et le corps'
                            }
                        }
                    },
                    responses: [{
                        status: 200,
                        description: 'Liste des notifications',
                        example: {
                            success: true,
                            message: 'Notifications récupérées.',
                            code: 'NOTIFICATIONS_LISTED',
                            data: [{
                                uuid_notification: '...',
                                title: 'Bienvenue sur YNOV',
                                body: 'Votre compte a été créé avec succès.',
                                type: 'system',
                                action_url: '/profile',
                                action_label: 'Voir mon profil',
                                is_read: false,
                                is_important: false,
                                read_at: null,
                                created_at: '2025-08-27T10:00:00.000000Z',
                                group_notif: {
                                    uuid_group_notif: '...',
                                    code: 'welcome',
                                    libelle: 'Bienvenue'
                                }
                            }],
                            meta: {
                                current_page: 1,
                                per_page: 20,
                                total: 5,
                                last_page: 1,
                                unread_count: 3,
                                important_count: 1
                            }
                        }
                    }]
                },

                {
                    id: 'notifications-unread-count',
                    module: 'notifications',
                    name: 'Nombre de notifications non lues',
                    description: 'Récupère le nombre de notifications non lues de l\'utilisateur connecté. Utile pour afficher un badge sur l\'icône des notifications.',
                    method: 'GET',
                    path: '/notifications/unread-count',
                    isProtected: true,
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    responses: [{
                        status: 200,
                        description: 'Nombre de notifications non lues',
                        example: {
                            success: true,
                            message: 'Nombre de notifications non lues.',
                            code: 'UNREAD_COUNT',
                            data: {
                                unread_count: 3
                            }
                        }
                    }]
                },

                {
                    id: 'notifications-mark-read',
                    module: 'notifications',
                    name: 'Marquer une notification comme lue',
                    description: 'Marque une notification spécifique comme lue.',
                    method: 'POST',
                    path: '/notifications/{uuid_notification}/read',
                    isProtected: true,
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_notification: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID de la notification'
                            }
                        }
                    },
                    responses: [{
                            status: 200,
                            description: 'Notification marquée comme lue',
                            example: {
                                success: true,
                                message: 'Notification marquée comme lue.',
                                code: 'NOTIFICATION_READ',
                                data: {}
                            }
                        },
                        {
                            status: 404,
                            description: 'Notification non trouvée',
                            example: {
                                success: false,
                                message: 'Notification non trouvée.',
                                code: 'NOTIFICATION_NOT_FOUND'
                            }
                        }
                    ]
                },

                {
                    id: 'notifications-mark-all-read',
                    module: 'notifications',
                    name: 'Marquer toutes les notifications comme lues',
                    description: 'Marque toutes les notifications non lues de l\'utilisateur comme lues en une seule action.',
                    method: 'POST',
                    path: '/notifications/mark-all-read',
                    isProtected: true,
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    responses: [{
                        status: 200,
                        description: 'Toutes les notifications marquées comme lues',
                        example: {
                            success: true,
                            message: 'Toutes les notifications ont été marquées comme lues.',
                            code: 'ALL_NOTIFICATIONS_READ',
                            data: {
                                marked_count: 3
                            }
                        }
                    }]
                },

                {
                    id: 'notifications-important',
                    module: 'notifications',
                    name: 'Marquer une notification comme importante',
                    description: 'Marque une notification comme importante pour la mettre en avant.',
                    method: 'POST',
                    path: '/notifications/{uuid_notification}/important',
                    isProtected: true,
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_notification: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID de la notification'
                            }
                        }
                    },
                    responses: [{
                        status: 200,
                        description: 'Notification marquée comme importante',
                        example: {
                            success: true,
                            message: 'Notification marquée comme importante.',
                            code: 'NOTIFICATION_IMPORTANT',
                            data: {}
                        }
                    }]
                },

                {
                    id: 'notifications-unimportant',
                    module: 'notifications',
                    name: 'Retirer le statut important',
                    description: 'Retire le statut important d\'une notification.',
                    method: 'POST',
                    path: '/notifications/{uuid_notification}/unimportant',
                    isProtected: true,
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_notification: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID de la notification'
                            }
                        }
                    },
                    responses: [{
                        status: 200,
                        description: 'Statut important retiré',
                        example: {
                            success: true,
                            message: 'Statut important retiré.',
                            code: 'NOTIFICATION_UNIMPORTANT',
                            data: {}
                        }
                    }]
                },

                {
                    id: 'notifications-delete',
                    module: 'notifications',
                    name: 'Supprimer une notification',
                    description: 'Supprime une notification (soft delete). La notification ne sera plus visible dans la liste.',
                    method: 'DELETE',
                    path: '/notifications/{uuid_notification}',
                    isProtected: true,
                    isDestructive: true,
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_notification: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID de la notification'
                            }
                        }
                    },
                    responses: [{
                            status: 200,
                            description: 'Notification supprimée',
                            example: {
                                success: true,
                                message: 'Notification supprimée.',
                                code: 'NOTIFICATION_DELETED'
                            }
                        },
                        {
                            status: 404,
                            description: 'Notification non trouvée',
                            example: {
                                success: false,
                                message: 'Notification non trouvée.',
                                code: 'NOTIFICATION_NOT_FOUND'
                            }
                        }
                    ]
                },

                // ============================================================
                // ADMIN - NOTIFICATIONS
                // ============================================================

                {
                    id: 'admin-notification-create',
                    module: 'notifications',
                    name: '[Admin] Créer une notification',
                    description: 'Crée une notification pour un utilisateur spécifique.',
                    method: 'POST',
                    path: '/admin/notifications',
                    isProtected: true,
                    permissionsRequired: ['notifications.creer'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        body: {
                            user_uuid: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID de l\'utilisateur destinataire'
                            },
                            group_notif_uuid: {
                                type: 'uuid',
                                required: false,
                                description: 'UUID du groupe de notification'
                            },
                            title: {
                                type: 'string',
                                required: true,
                                max: 255,
                                description: 'Titre de la notification'
                            },
                            body: {
                                type: 'string',
                                required: true,
                                description: 'Corps du message'
                            },
                            type: {
                                type: 'string',
                                required: false,
                                max: 50,
                                description: 'Type de notification'
                            },
                            action_url: {
                                type: 'string',
                                required: false,
                                max: 500,
                                description: 'URL d\'action'
                            },
                            action_label: {
                                type: 'string',
                                required: false,
                                max: 100,
                                description: 'Libellé du bouton d\'action'
                            },
                            channel: {
                                type: 'string',
                                required: false,
                                max: 30,
                                description: 'Canal d\'envoi'
                            },
                            metadata: {
                                type: 'object',
                                required: false,
                                description: 'Métadonnées additionnelles'
                            }
                        }
                    },
                    responses: [{
                        status: 201,
                        description: 'Notification créée',
                        example: {
                            success: true,
                            message: 'Notification créée.',
                            code: 'NOTIFICATION_CREATED',
                            data: {}
                        }
                    }]
                },

                {
                    id: 'admin-notification-create-group',
                    module: 'notifications',
                    name: '[Admin] Créer une notification pour un groupe',
                    description: 'Crée une notification pour tous les utilisateurs d\'un groupe de notification.',
                    method: 'POST',
                    path: '/admin/notifications/group',
                    isProtected: true,
                    permissionsRequired: ['notifications.creer'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        body: {
                            group_notif_uuid: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID du groupe de notification'
                            },
                            title: {
                                type: 'string',
                                required: true,
                                max: 255,
                                description: 'Titre de la notification'
                            },
                            body: {
                                type: 'string',
                                required: true,
                                description: 'Corps du message'
                            },
                            type: {
                                type: 'string',
                                required: false,
                                max: 50,
                                description: 'Type de notification'
                            },
                            action_url: {
                                type: 'string',
                                required: false,
                                max: 500,
                                description: 'URL d\'action'
                            },
                            action_label: {
                                type: 'string',
                                required: false,
                                max: 100,
                                description: 'Libellé du bouton d\'action'
                            },
                            metadata: {
                                type: 'object',
                                required: false,
                                description: 'Métadonnées additionnelles'
                            }
                        }
                    },
                    responses: [{
                        status: 201,
                        description: 'Notifications créées pour le groupe',
                        example: {
                            success: true,
                            message: 'Notifications créées pour le groupe.',
                            code: 'NOTIFICATIONS_CREATED_FOR_GROUP',
                            data: {
                                count: 15,
                                group_notif_uuid: '...'
                            }
                        }
                    }]
                },

                // ============================================================
                // JOURS FÉRIÉS
                // ============================================================
                {
                    id: 'jour-feries-list',
                    module: 'jour_feries',
                    name: 'Liste des jours fériés',
                    description: 'Récupère la liste des jours fériés avec filtres (année, récurrent, recherche).',
                    method: 'GET',
                    path: '/jour-feries',
                    isProtected: true,
                    permissionsRequired: ['jour_feries.afficher'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        query: {
                            per_page: {
                                type: 'integer',
                                required: false,
                                default: 20,
                                description: 'Nombre par page'
                            },
                            year: {
                                type: 'integer',
                                required: false,
                                description: 'Filtrer par année'
                            },
                            est_recurrent: {
                                type: 'boolean',
                                required: false,
                                description: 'Filtrer par récurrence'
                            },
                            search: {
                                type: 'string',
                                required: false,
                                description: 'Recherche textuelle (libellé, code, description)'
                            }
                        }
                    },
                    responses: [{
                        status: 200,
                        description: 'Liste des jours fériés',
                        example: {
                            success: true,
                            message: 'Liste des jours fériés récupérée.',
                            code: 'FERIES_LISTED',
                            data: {
                                current_page: 1,
                                data: [{
                                    uuid_jour_ferie: '...',
                                    date: '2026-01-01',
                                    libelle: 'Jour de l\'an',
                                    est_recurrent: true,
                                    code: 'NOUVEL_AN',
                                    description: null,
                                    created_at: '2026-01-01T00:00:00.000000Z'
                                }],
                                total: 1,
                                per_page: 20,
                                last_page: 1
                            }
                        }
                    }]
                },
                {
                    id: 'jour-feries-create',
                    module: 'jour_feries',
                    name: 'Créer un jour férié',
                    description: 'Crée un nouveau jour férié.',
                    method: 'POST',
                    path: '/jour-feries',
                    isProtected: true,
                    permissionsRequired: ['jour_feries.creer'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        body: {
                            date: {
                                type: 'date',
                                required: true,
                                description: 'Date du jour férié'
                            },
                            libelle: {
                                type: 'string',
                                required: true,
                                max: 255,
                                description: 'Libellé'
                            },
                            est_recurrent: {
                                type: 'boolean',
                                required: false,
                                default: false,
                                description: 'Récurrent chaque année'
                            },
                            code: {
                                type: 'string',
                                required: false,
                                max: 50,
                                description: 'Code de référence'
                            },
                            description: {
                                type: 'string',
                                required: false,
                                description: 'Description'
                            }
                        }
                    },
                    exampleRequest: {
                        date: '2026-08-07',
                        libelle: 'Fête de l\'Indépendance',
                        est_recurrent: true,
                        code: 'INDEPENDANCE'
                    },
                    responses: [{
                        status: 201,
                        description: 'Jour férié créé',
                        example: {
                            success: true,
                            message: 'Jour férié créé avec succès.',
                            code: 'FERIE_CREATED',
                            data: {}
                        }
                    }]
                },
                {
                    id: 'jour-feries-verifier',
                    module: 'jour_feries',
                    name: 'Vérifier une date',
                    description: 'Vérifie si une date donnée est un jour férié.',
                    method: 'POST',
                    path: '/jour-feries/verifier',
                    isProtected: true,
                    permissionsRequired: ['jour_feries.afficher'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        body: {
                            date: {
                                type: 'date',
                                required: true,
                                description: 'Date à vérifier'
                            }
                        }
                    },
                    exampleRequest: {
                        date: '2026-01-01'
                    },
                    responses: [{
                        status: 200,
                        description: 'Date vérifiée',
                        example: {
                            success: true,
                            message: 'Cette date est un jour férié.',
                            code: 'DATE_FERIE',
                            data: {
                                date: '2026-01-01',
                                est_ferie: true
                            }
                        }
                    }]
                },
                // ============================================================
// JOURS FÉRIÉS D'UNE ANNÉE
// ============================================================
{
    id: 'jour-feries-annee',
    module: 'jour_feries',
    name: 'Jours fériés d\'une année',
    description: 'Récupère tous les jours fériés d\'une année donnée (incluant les récurrents).',
    method: 'GET',
    path: '/jour-feries/annee/{year}',
    isProtected: true,
    permissionsRequired: ['jour_feries.afficher'],
    headers: {
        'Authorization': 'Bearer {token}',
        'Accept': 'application/json'
    },
    requestParams: {
        path: {
            year: {
                type: 'integer',
                required: true,
                description: 'Année (2000-2100)',
                example: 2026
            }
        }
    },
    exampleRequest: {
        // Le paramètre est dans l'URL, pas dans le body
    },
    exampleResponse: {
        status: 200,
        description: 'Jours fériés de l\'année',
        example: {
            success: true,
            message: 'Jours fériés de l\'année 2026.',
            code: 'FERIES_YEAR',
            data: [
                {
                    uuid_jour_ferie: '550e8400-e29b-41d4-a716-446655440001',
                    date: '2026-01-01',
                    libelle: 'Jour de l\'an',
                    code: 'NOUVEL_AN',
                    est_recurrent: true,
                    description: 'Premier jour de l\'année'
                },
                {
                    uuid_jour_ferie: '550e8400-e29b-41d4-a716-446655440002',
                    date: '2026-04-06',
                    libelle: 'Lundi de Pâques',
                    code: 'PAQUES',
                    est_recurrent: false,
                    description: 'Lundi suivant le dimanche de Pâques'
                },
                {
                    uuid_jour_ferie: '550e8400-e29b-41d4-a716-446655440003',
                    date: '2026-05-01',
                    libelle: 'Fête du Travail',
                    code: 'FETE_TRAVAIL',
                    est_recurrent: true,
                    description: 'Fête internationale du travail'
                },
                {
                    uuid_jour_ferie: '550e8400-e29b-41d4-a716-446655440004',
                    date: '2026-08-07',
                    libelle: 'Fête de l\'Indépendance',
                    code: 'INDEPENDANCE',
                    est_recurrent: true,
                    description: 'Fête nationale de l\'indépendance'
                },
                {
                    uuid_jour_ferie: '550e8400-e29b-41d4-a716-446655440005',
                    date: '2026-12-25',
                    libelle: 'Noël',
                    code: 'NOEL',
                    est_recurrent: true,
                    description: 'Noël'
                }
            ]
        }
    },
    responses: [
        {
            status: 200,
            description: 'Jours fériés de l\'année récupérés avec succès',
            example: {
                success: true,
                message: 'Jours fériés de l\'année 2026.',
                code: 'FERIES_YEAR',
                data: []
            }
        },
        {
            status: 422,
            description: 'Année invalide',
            example: {
                success: false,
                message: 'L\'année doit être comprise entre 2000 et 2100.',
                code: 'INVALID_YEAR'
            }
        },
        {
            status: 403,
            description: 'Permission manquante',
            example: {
                success: false,
                message: 'Vous n\'avez pas la permission nécessaire.',
                code: 'PERMISSION_DENIED'
            }
        }
    ]
},
                {
                    id: 'jour-feries-prochains',
                    module: 'jour_feries',
                    name: 'Prochains jours ouvrés',
                    description: 'Récupère les prochains jours ouvrés (non fériés et non week-ends).',
                    method: 'GET',
                    path: '/jour-feries/prochains-jours-ouvres',
                    isProtected: true,
                    permissionsRequired: ['jour_feries.afficher'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        query: {
                            nb_jours: {
                                type: 'integer',
                                required: false,
                                default: 30,
                                min: 1,
                                max: 365,
                                description: 'Nombre de jours à retourner'
                            },
                            date_debut: {
                                type: 'date',
                                required: false,
                                description: 'Date de début (aujourd\'hui par défaut)'
                            }
                        }
                    },
                    exampleRequest: {
                        nb_jours: 10,
                        date_debut: '2026-07-01'
                    },
                    responses: [{
                        status: 200,
                        description: 'Prochains jours ouvrés',
                        example: {
                            success: true,
                            message: 'Prochains jours ouvrés.',
                            code: 'PROCHAINS_JOURS_OUVRES',
                            data: ['2026-07-01', '2026-07-02', '2026-07-03', '2026-07-06']
                        }
                    }]
                },


                // ============================================================
                // ESPACE CLIENT - TABLEAU DE BORD
                // ============================================================
                {
                    id: 'customer-dashboard',
                    module: 'espaces_client',
                    name: 'Tableau de bord client',
                    description: 'Récupère les informations de synthèse du client : nombre de contrats, capital total, primes totales, statut global, etc.',
                    method: 'GET',
                    path: '/espaces-client/dashboard',
                    isProtected: true,
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        body: {}
                    },
                    responses: [{
                        status: 200,
                        description: 'Tableau de bord récupéré',
                        example: {
                            success: true,
                            code: 'DASHBOARD_FOUND',
                            message: 'Tableau de bord récupéré avec succès.',
                            data: {
                                total_contrats: 6,
                                total_capital: 45000000,
                                total_prime: 18000000,
                                total_encaisse: 14200000,
                                taux_moyen_paiement: 78.9,
                                contrats_actifs: 4,
                                contrats_en_retard: 2,
                                dernier_contrat: {
                                    IdProposition: 'PROP2024006',
                                    produit: 'PERFORMA Individuel',
                                    date: '2023-06-15'
                                }
                            }
                        }
                    }]
                },
                // ============================================================
                // ESPACE CLIENT - CONTRATS
                // ============================================================
                {
                    id: 'customer-contrats-list',
                    module: 'espaces_client',
                    name: 'Liste des contrats du client',
                    description: 'Récupère la liste de tous les contrats actifs du client authentifié avec pagination. Exclut les contrats arrêtés (OnStdbyOff = 3). Retourne les informations détaillées de chaque contrat : capital, primes, taux de paiement, statut, ancienneté.',
                    method: 'GET',
                    path: '/espaces-client/contrats',
                    isProtected: true,
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        query: {
                            per_page: {
                                type: 'integer',
                                required: false,
                                default: 10,
                                min: 1,
                                max: 100,
                                description: 'Nombre d\'éléments par page (1-100)'
                            },
                            page: {
                                type: 'integer',
                                required: false,
                                default: 1,
                                min: 1,
                                description: 'Numéro de la page'
                            }
                        }
                    },
                    exampleRequest: {
                        per_page: 5,
                        page: 2
                    },
                    responses: [{
                            status: 200,
                            description: 'Liste des contrats récupérée avec succès',
                            example: {
                                success: true,
                                code: 'GET_ALL_CONTRAT_SUCCESS',
                                message: 'Contrats récupérés avec succès.',
                                data: [{
                                    IdProposition: 'PROP2024001',
                                    CapitalSouscrit: 15000000,
                                    TotalPrime: 5400000,
                                    NbreImpayes: 0,
                                    produit: 'PERFORMA Individuel',
                                    EtatAvancementCotisation: 42 + 'En %',
                                }],
                                meta: {
                                    total: 6,
                                    per_page: 10,
                                    current_page: 1,
                                    last_page: 1,
                                    has_errors: false,
                                    errors: []
                                }
                            }
                        },
                        {
                            status: 200,
                            description: 'Aucun contrat actif trouvé',
                            example: {
                                success: true,
                                code: 'NO_CONTRAT_FOUND',
                                message: 'Aucun contrat actif trouvé.',
                                data: [],
                                meta: {
                                    total: 0,
                                    per_page: 10,
                                    current_page: 1,
                                    last_page: 1,
                                    has_errors: false,
                                    errors: []
                                }
                            }
                        },
                        {
                            status: 401,
                            description: 'Non authentifié',
                            example: {
                                success: false,
                                message: 'Non authentifié.'
                            }
                        },
                        {
                            status: 422,
                            description: 'Erreur de récupération',
                            example: {
                                success: false,
                                code: 'GET_CONTRAT_ERROR',
                                message: 'Une erreur est survenue lors de la récupération des contrats.'
                            }
                        }
                    ]
                },

                // ============================================================
                // ESPACE CLIENT - DÉTAILS D'UN CONTRAT
                // ============================================================
                {
                    id: 'customer-contrat-detail',
                    module: 'espaces_client',
                    name: 'Détails d\'un contrat',
                    description: 'Récupère les informations détaillées d\'un contrat spécifique du client. Inclut les informations personnelles (assurés, bénéficiaires), les garanties, les documents contractuels (CP, CG, avenants) et l\'état d\'avancement.',
                    method: 'GET',
                    path: '/espaces-client/contrat-details/{contrat_id}',
                    isProtected: true,
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            contrat_id: {
                                type: 'integer',
                                required: true,
                                description: 'ID du contrat (identifiant numérique)'
                            }
                        }
                    },
                    responses: [{
                            status: 200,
                            description: 'Détails du contrat récupérés avec succès',
                            example: {
                                success: true,
                                code: 'CONTRAT_DETAILS_FOUND',
                                message: 'Détails du contrat récupérés avec succès.',
                                data: {
                                    details: {
                                        IdProposition: 'PROP2024001',
                                        NumBulletin: 'BUL-2024-001',
                                        CodeProposition: 'PROP-2024-001',
                                        CapitalSouscrit: 15000000,
                                        TotalPrime: 5400000,
                                        NbreImpayes: 0,
                                        produit: 'PERFORMA Individuel',
                                        EtatAvancementCotisation: '78.5',
                                        Periodicite: 'Mensuel',
                                        ModePaiement: 'Prélèvement automatique',
                                        DateFinAdhesion: '31/12/2040',
                                        DateEffetAdhesion: '15/01/2021',
                                        Conseiller: 'C12345 - KOFFI Serge',
                                        Adherent: 'YAPO BRUCE BERNADIN EVRARD JUNIOR',
                                        Status: 'En cours'
                                    },
                                    Assures: [{
                                        CodePersonne: 'P001',
                                        Nom: 'YAPO',
                                        Prenoms: 'BRUCE BERNADIN EVRARD JUNIOR',
                                        NomComplet: 'YAPO BRUCE BERNADIN EVRARD JUNIOR',
                                        DateNaissance: '2000-11-20',
                                        LieuNaissance: 'Grand-Lahou',
                                        Profession: 'Informaticien',
                                        CodeFiliation: 'FIL001',
                                        Filiation: 'Fils'
                                    }],
                                    Beneficiaires: [{
                                        CodePersonne: 'P002',
                                        Nom: 'YAPO',
                                        Prenoms: 'MARIE CLAIRE',
                                        NomComplet: 'YAPO MARIE CLAIRE',
                                        DateNaissance: '1975-03-15',
                                        LieuNaissance: 'Abidjan',
                                        Profession: 'Enseignante',
                                        CodeFiliation: 'FIL002',
                                        Filiation: 'Conjoint'
                                    }],
                                    Garanties: [{
                                        CodeGarantie: 'G001',
                                        Libelle: 'Décès',
                                        Capital: 15000000,
                                        Prime: 5400000,
                                        PrimePrincipale: 5400000,
                                        DateEffet: '2021-01-15',
                                        DateEcheance: '2040-12-31',
                                        DureeCouvAns: 20,
                                        DureePrimeAns: 20,
                                        Periodicite: 'M'
                                    }],
                                    Documents: {
                                        CP: {
                                            libelle: "Police d'assurance (Conditions particulières et générales)",
                                            fileName: "CP-CG_3593104.pdf",
                                            docUrl: "https://localhost:8000/get-document-contrat/A2025/M11/DocumentsContractuels_3593104/CP-CG_3593104.pdf"
                                        },
                                        avenantsUrls: [{
                                                libelle: "Avenant de police d'assurance n° 1",
                                                fileName: "AVT_3593104_001.pdf",
                                                docUrl: "http://localhost:8000/get-document-contrat/A2025/M11/DocumentsContractuels_3593104/AVT_3593104_001.pdf"
                                            },
                                            {
                                                libelle: "Avenant de police d'assurance n° 2",
                                                fileName: "AVT_3593104_002.pdf",
                                                docUrl: "http://localhost:8000/get-document-contrat/A2025/M11/DocumentsContractuels_3593104/AVT_3593104_002.pdf"
                                            }
                                        ]
                                    },
                                    Anciennete: {
                                        date_premier_contrat: '2021-01-15',
                                        date_aujourdhui: '2025-08-26',
                                        annees: 4,
                                        mois: 7,
                                        jours: 11,
                                        total_mois: 55,
                                        total_jours: 1685
                                    }
                                }
                            }
                        },
                        {
                            status: 404,
                            description: 'Contrat non trouvé',
                            example: {
                                success: false,
                                code: 'CONTRAT_NOT_FOUND',
                                message: 'Contrat non trouvé ou non associé à cet utilisateur.'
                            }
                        },
                        {
                            status: 422,
                            description: 'Erreur de récupération des détails',
                            example: {
                                success: false,
                                code: 'CONTRACT_DETAILS_ERROR',
                                message: 'Une erreur est survenue lors de la récupération des détails du contrat.'
                            }
                        },
                        {
                            status: 401,
                            description: 'Non authentifié',
                            example: {
                                success: false,
                                message: 'Non authentifié.'
                            }
                        }
                    ]
                },

                // ============================================================
                // ESPACE CLIENT - AJOUTER UN CONTRAT
                // ============================================================
                {
                    id: 'customer-add-contrat',
                    module: 'espaces_client',
                    name: 'Ajouter un contrat',
                    description: 'Permet à un client d\'ajouter un nouveau contrat à son compte. Vérifie que le contrat n\'est pas déjà associé, que la date de naissance correspond et que le contrat n\'est pas arrêté.',
                    method: 'POST',
                    path: '/espaces-client/add-new-contrats',
                    isProtected: true,
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        body: {
                            idcontrat: {
                                type: 'integer',
                                required: true,
                                description: 'ID du contrat à ajouter (identifiant numérique)'
                            }
                        }
                    },
                    exampleRequest: {
                        idcontrat: 3593104
                    },
                    responses: [{
                            status: 200,
                            description: 'Contrat ajouté avec succès',
                            example: {
                                success: true,
                                code: 'CONTRACT_ADDED',
                                message: 'Contract ajouté avec succès.',
                                data: {
                                    uuid_user_contrat: 'd2772910-0f7b-4ca2-8b64-167596d7c9f9',
                                    user_uuid: 'e817f673-c481-4cdc-8aa7-cc6360eca7ae',
                                    contrat_id: 3593104,
                                    client_number: '2025012108505846',
                                    code_produit: 'DOIHOO',
                                    libelle_produit: 'PERFORMA Individuel',
                                    code_produit_formule: 'DOIHOO_2020_IND',
                                    libelle_produit_formule: 'DOIHOO INDIVIDUEL',
                                    updated_at: '2025-08-27T10:00:00.000000Z',
                                    created_at: '2025-08-27T10:00:00.000000Z'
                                }
                            }
                        },
                        {
                            status: 422,
                            description: 'Contrat déjà associé au compte',
                            example: {
                                success: false,
                                code: 'CONTRAT_ALREADY_EXISTS',
                                message: 'Le contrat 3593104 est déjà associé à votre compte.'
                            }
                        },
                        {
                            status: 422,
                            description: 'Date de naissance ne correspond pas',
                            example: {
                                success: false,
                                code: 'DATE_OF_BIRTH_MISMATCH',
                                message: 'La date de naissance ne correspond pas à celle enregistrée dans le contrat.'
                            }
                        },
                        {
                            status: 422,
                            description: 'Contrat arrêté',
                            example: {
                                success: false,
                                code: 'CONTRACT_FROZEN',
                                message: 'Ce contrat est arrêté.'
                            }
                        },
                        {
                            status: 422,
                            description: 'Contrat non trouvé',
                            example: {
                                success: false,
                                message: 'Contrat introuvable ou en erreur.'
                            }
                        },
                        {
                            status: 401,
                            description: 'Non authentifié',
                            example: {
                                success: false,
                                message: 'Non authentifié.'
                            }
                        }
                    ]
                },

                // ============================================================
                // ESPACE CLIENT - CONTRATS AVEC FACTURES IMPRIMÉES
                // ============================================================
                {
                    id: 'customer-contrats-factures',
                    module: 'espaces_client',
                    name: 'Contrats avec factures impayées',
                    description: 'Récupère la liste des contrats du client qui ont des factures impayés. Retourne les informations des contrats avec le détail des factures non réglées. **Possibilité de filtrer par période (aujourd\'hui, semaine, mois, année, personnalisé) et de rechercher par produit ou numéro de contrat.**',
                    method: 'GET',
                    path: '/espaces-client/contrats-factures',
                    isProtected: true,
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        query: {
                            per_page: {
                                type: 'integer',
                                required: false,
                                default: 10,
                                min: 1,
                                max: 100,
                                description: 'Nombre d\'éléments par page (1-100)'
                            },
                            page: {
                                type: 'integer',
                                required: false,
                                default: 1,
                                min: 1,
                                description: 'Numéro de la page'
                            },
                            period: {
                                type: 'string',
                                required: false,
                                enum: ['all', 'today', 'week', 'month', 'year', 'custom'],
                                default: 'all',
                                description: 'Période de filtrage des factures : all (toutes), today (aujourd\'hui), week (cette semaine), month (ce mois), year (cette année), custom (personnalisé)'
                            },
                            date_from: {
                                type: 'date',
                                required: false,
                                format: 'Y-m-d',
                                description: 'Date de début pour le filtrage personnalisé (period=custom)'
                            },
                            date_to: {
                                type: 'date',
                                required: false,
                                format: 'Y-m-d',
                                description: 'Date de fin pour le filtrage personnalisé (period=custom)'
                            },
                            search: {
                                type: 'string',
                                required: false,
                                description: 'Recherche par nom du produit ou numéro de contrat (IdProposition)'
                            }
                        }
                    },
                    exampleRequest: {
                        period: 'month',
                        per_page: 10,
                        page: 1
                    },
                    exampleRequestCustom: {
                        period: 'custom',
                        date_from: '2025-01-01',
                        date_to: '2025-12-31',
                        per_page: 20
                    },
                    responses: [{
                            status: 200,
                            description: 'Contrats avec factures récupérés avec succès',
                            example: {
                                success: true,
                                code: 'FACTURE_FOUND',
                                message: 'Contrats avec factures impayés récupérés',
                                data: [{
                                    details: {
                                        IdProposition: '3593104',
                                        CapitalSouscrit: 1000000,
                                        TotalPrime: 10400,
                                        NbreImpayes: 1,
                                        TotalImpayes: 7500,
                                        produit: 'DOIHOO',
                                        Status: 'En cours'
                                    },
                                    PrimeNonRegles: [{
                                        IdFacture: '31680334',
                                        DateCreation: '30/11/2025',
                                        DateCreationFormatted: '2025-11-30',
                                        MontantARegler: 7500,
                                        TypeFacture: 'F',
                                        TypeFactureLibelle: 'Frais d\'adhésion'
                                    }]
                                }],
                                meta: {
                                    total: 3,
                                    per_page: 10,
                                    current_page: 1,
                                    last_page: 1,
                                    has_errors: false,
                                    errors: [],
                                    filters: {
                                        period: 'month',
                                        date_from: null,
                                        date_to: null,
                                        search: null
                                    }
                                }
                            }
                        },
                        {
                            status: 200,
                            description: 'Aucun contrat avec factures trouvé',
                            example: {
                                success: true,
                                code: 'NO_FACTURE_FOUND',
                                message: 'Aucun contrat trouvé avec facture impayé.',
                                data: [],
                                meta: {
                                    total: 0,
                                    per_page: 10,
                                    current_page: 1,
                                    last_page: 1,
                                    has_errors: false,
                                    errors: [],
                                    filters: {
                                        period: 'all',
                                        date_from: null,
                                        date_to: null,
                                        search: null
                                    }
                                }
                            }
                        },
                        {
                            status: 401,
                            description: 'Non authentifié',
                            example: {
                                success: false,
                                message: 'Non authentifié.'
                            }
                        },
                        {
                            status: 422,
                            description: 'Erreur de récupération',
                            example: {
                                success: false,
                                code: 'GET_CONTRAT_ERROR',
                                message: 'Une erreur est survenue lors de la récupération des contrats.'
                            }
                        }
                    ]
                },


                // ============================================================
                // TYPES DE PRODUITS
                // ============================================================
                {
                    id: 'type-produits-list',
                    module: 'type_produits',
                    name: 'Liste des types de produits',
                    description: 'Récupère la liste paginée des types de produits avec possibilité de recherche.',
                    method: 'GET',
                    path: '/type-produits',
                    isProtected: true,
                    permissionsRequired: ['produits.afficher'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        query: {
                            per_page: {
                                type: 'integer',
                                required: false,
                                default: 20,
                                description: 'Nombre par page'
                            },
                            search: {
                                type: 'string',
                                required: false,
                                description: 'Recherche textuelle (code ou libellé)'
                            }
                        }
                    },
                    responses: [{
                        status: 200,
                        description: 'Liste des types de produits',
                        example: {
                            success: true,
                            message: 'Liste des types de produits récupérée.',
                            code: 'TYPE_PRODUITS_LISTED',
                            data: {
                                current_page: 1,
                                data: [{
                                    uuid_type_produit: '550e8400-e29b-41d4-a716-446655440001',
                                    code: 'individuel',
                                    libelle: 'Individuel',
                                    produits_count: 5,
                                    created_at: '2025-01-15T10:00:00.000000Z'
                                }],
                                total: 10,
                                per_page: 20,
                                last_page: 1
                            }
                        }
                    }]
                },

                {
                    id: 'type-produits-select',
                    module: 'type_produits',
                    name: 'Types de produits pour sélection',
                    description: 'Récupère la liste des types de produits formatée pour un dropdown/select.',
                    method: 'GET',
                    path: '/type-produits/select',
                    isProtected: true,
                    permissionsRequired: ['produits.afficher'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    responses: [{
                        status: 200,
                        description: 'Types de produits pour sélection',
                        example: {
                            success: true,
                            message: 'Types de produits pour sélection.',
                            code: 'TYPE_PRODUITS_SELECT',
                            data: [{
                                value: '550e8400-e29b-41d4-a716-446655440001',
                                label: 'Individuel',
                                code: 'individuel'
                            }]
                        }
                    }]
                },

                {
                    id: 'type-produits-show',
                    module: 'type_produits',
                    name: 'Détails d\'un type de produit',
                    description: 'Récupère les détails d\'un type de produit avec ses produits associés.',
                    method: 'GET',
                    path: '/type-produits/{uuid_type_produit}',
                    isProtected: true,
                    permissionsRequired: ['produits.afficher'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_type_produit: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID du type de produit'
                            }
                        }
                    },
                    responses: [{
                        status: 200,
                        description: 'Détails du type de produit',
                        example: {
                            success: true,
                            message: 'Détails du type de produit.',
                            code: 'TYPE_PRODUIT_FOUND',
                            data: {
                                uuid_type_produit: '550e8400-e29b-41d4-a716-446655440001',
                                code: 'individuel',
                                libelle: 'Individuel',
                                produits: [{
                                    uuid_produit: '...',
                                    code: 'PROD001',
                                    libelle: 'PERFORMA Individuel',
                                    statut: 'actif'
                                }]
                            }
                        }
                    }]
                },

                {
                    id: 'type-produits-create',
                    module: 'type_produits',
                    name: 'Créer un type de produit',
                    description: 'Crée un nouveau type de produit.',
                    method: 'POST',
                    path: '/type-produits',
                    isProtected: true,
                    permissionsRequired: ['produits.creer'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        body: {
                            libelle: {
                                type: 'string',
                                required: true,
                                max: 90,
                                description: 'Libellé du type de produit'
                            },
                            code: {
                                type: 'string',
                                required: false,
                                max: 45,
                                description: 'Code unique (généré automatiquement)'
                            }
                        }
                    },
                    exampleRequest: {
                        libelle: 'Collectif',
                        code: 'collectif'
                    },
                    responses: [{
                        status: 201,
                        description: 'Type de produit créé',
                        example: {
                            success: true,
                            message: 'Type de produit créé avec succès.',
                            code: 'TYPE_PRODUIT_CREATED',
                            data: {
                                uuid_type_produit: '...',
                                code: 'collectif',
                                libelle: 'Collectif'
                            }
                        }
                    }]
                },

                {
                    id: 'type-produits-update',
                    module: 'type_produits',
                    name: 'Modifier un type de produit',
                    description: 'Met à jour un type de produit existant.',
                    method: 'PUT',
                    path: '/type-produits/{uuid_type_produit}',
                    isProtected: true,
                    permissionsRequired: ['produits.modifier'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_type_produit: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID du type de produit'
                            }
                        },
                        body: {
                            libelle: {
                                type: 'string',
                                required: false,
                                max: 90,
                                description: 'Libellé'
                            },
                            code: {
                                type: 'string',
                                required: false,
                                max: 45,
                                description: 'Code unique'
                            }
                        }
                    },
                    exampleRequest: {
                        libelle: 'Collectif (mise à jour)'
                    },
                    responses: [{
                        status: 200,
                        description: 'Type de produit mis à jour',
                        example: {
                            success: true,
                            message: 'Type de produit mis à jour.',
                            code: 'TYPE_PRODUIT_UPDATED',
                            data: {}
                        }
                    }]
                },

                {
                    id: 'type-produits-delete',
                    module: 'type_produits',
                    name: 'Supprimer un type de produit',
                    description: 'Supprime un type de produit. Refusé s\'il est associé à des produits.',
                    method: 'DELETE',
                    path: '/type-produits/{uuid_type_produit}',
                    isProtected: true,
                    isDestructive: true,
                    permissionsRequired: ['produits.supprimer'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_type_produit: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID du type de produit'
                            }
                        },
                        body: {}
                    },
                    responses: [{
                            status: 200,
                            description: 'Type de produit supprimé',
                            example: {
                                success: true,
                                message: 'Type de produit supprimé.',
                                code: 'TYPE_PRODUIT_DELETED'
                            }
                        },
                        {
                            status: 422,
                            description: 'Type utilisé par des produits',
                            example: {
                                success: false,
                                message: 'Ce type de produit est associé à des produits et ne peut pas être supprimé.',
                                code: 'TYPE_PRODUIT_IN_USE'
                            }
                        }
                    ]
                },

                // ============================================================
                // PRODUITS
                // ============================================================
                {
                    id: 'produits-list',
                    module: 'produits',
                    name: 'Liste des produits',
                    description: 'Liste paginée des produits avec filtres (statut, branche, type de produit, recherche).',
                    method: 'GET',
                    path: '/produits',
                    isProtected: true,
                    permissionsRequired: ['produits.afficher'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        query: {
                            per_page: {
                                type: 'integer',
                                required: false,
                                default: 20,
                                description: 'Nombre par page'
                            },
                            statut: {
                                type: 'string',
                                required: false,
                                enum: ['actif', 'inactif'],
                                description: 'Filtrer par statut'
                            },
                            code_branche: {
                                type: 'string',
                                required: false,
                                description: 'Filtrer par code branche'
                            },
                            type_produit_uuid: {
                                type: 'uuid',
                                required: false,
                                description: 'Filtrer par type de produit'
                            },
                            search: {
                                type: 'string',
                                required: false,
                                description: 'Recherche textuelle'
                            }
                        }
                    },
                    responses: [{
                        status: 200,
                        description: 'Liste des produits',
                        example: {
                            success: true,
                            message: 'Liste des produits récupérée.',
                            code: 'PRODUITS_LISTED',
                            data: {
                                current_page: 1,
                                data: [{
                                    uuid_produit: '550e8400-e29b-41d4-a716-446655440001',
                                    code: 'PERF_IND',
                                    libelle: 'PERFORMA Individuel',
                                    code_branche: 'Vie',
                                    statut: 'actif',
                                    type_produit: {
                                        uuid_type_produit: '...',
                                        libelle: 'Individuel'
                                    },
                                    formules_actives_count: 2,
                                    prestations_actives_count: 5,
                                    created_at: '2025-01-15T10:00:00.000000Z'
                                }],
                                total: 10,
                                per_page: 20,
                                last_page: 1
                            }
                        }
                    }]
                },

                {
                    id: 'produits-stats',
                    module: 'produits',
                    name: 'Statistiques des produits',
                    description: 'Récupère les statistiques des produits, formules et prestations.',
                    method: 'GET',
                    path: '/produits/stats',
                    isProtected: true,
                    permissionsRequired: ['produits.afficher'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    responses: [{
                        status: 200,
                        description: 'Statistiques des produits',
                        example: {
                            success: true,
                            message: 'Statistiques des produits.',
                            code: 'PRODUIT_STATS',
                            data: {
                                total: 45,
                                active: 32,
                                inactive: 13,
                                formules_total: 78,
                                formules_active: 56,
                                prestations_total: 120,
                                prestations_active: 95
                            }
                        }
                    }]
                },

                {
                    id: 'produits-show',
                    module: 'produits',
                    name: 'Détails d\'un produit',
                    description: 'Récupère les détails complets d\'un produit avec ses formules et prestations.',
                    method: 'GET',
                    path: '/produits/{uuid_produit}',
                    isProtected: true,
                    permissionsRequired: ['produits.afficher'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_produit: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID du produit'
                            }
                        }
                    },
                    responses: [{
                        status: 200,
                        description: 'Détails du produit',
                        example: {
                            success: true,
                            message: 'Détails du produit.',
                            code: 'PRODUIT_FOUND',
                            data: {
                                uuid_produit: '550e8400-e29b-41d4-a716-446655440001',
                                code: 'PERF_IND',
                                libelle: 'PERFORMA Individuel',
                                code_branche: 'Vie',
                                code_produit_nature: 'Individuel',
                                description: 'Produit d\'assurance vie individuel',
                                statut: 'actif',
                                age_mini_adh: 18,
                                age_maxi_adh: 70,
                                capital: 1000000,
                                vie_entiere: false,
                                type_produit: {
                                    uuid_type_produit: '...',
                                    libelle: 'Individuel'
                                },
                                formules: [{
                                    uuid_produit_formule: '...',
                                    libelle: 'Formule Premium',
                                    est_actif: true,
                                    fa: 1.25,
                                    fg: 0.85,
                                    tx: 0.05,
                                    code_canal_distribution: 'Direct'
                                }],
                                type_prestations: [{
                                    uuid_type_prestation: '...',
                                    code: 'DECES',
                                    libelle: 'Décès',
                                    impact: '1',
                                    pivot: {
                                        status: 'actif',
                                        produit_type: 'Principal'
                                    }
                                }]
                            }
                        }
                    }]
                },

                {
                    id: 'produits-create',
                    module: 'produits',
                    name: 'Créer un produit',
                    description: 'Crée un nouveau produit avec toutes ses informations.',
                    method: 'POST',
                    path: '/produits',
                    isProtected: true,
                    permissionsRequired: ['produits.creer'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        body: {
                            libelle: {
                                type: 'string',
                                required: true,
                                max: 128,
                                description: 'Libellé du produit'
                            },
                            code: {
                                type: 'string',
                                required: false,
                                max: 25,
                                description: 'Code unique'
                            },
                            code_branche: {
                                type: 'string',
                                required: false,
                                max: 25,
                                description: 'Code de la branche'
                            },
                            code_produit_nature: {
                                type: 'string',
                                required: false,
                                max: 25,
                                description: 'Nature du produit'
                            },
                            description: {
                                type: 'string',
                                required: false,
                                description: 'Description'
                            },
                            statut: {
                                type: 'string',
                                required: false,
                                enum: ['actif', 'inactif'],
                                default: 'actif',
                                description: 'Statut'
                            },
                            type_produit_uuid: {
                                type: 'uuid',
                                required: false,
                                description: 'UUID du type de produit'
                            },
                            age_mini_adh: {
                                type: 'integer',
                                required: false,
                                min: 0,
                                max: 127,
                                description: 'Âge minimum d\'adhésion'
                            },
                            age_maxi_adh: {
                                type: 'integer',
                                required: false,
                                min: 0,
                                max: 127,
                                description: 'Âge maximum d\'adhésion'
                            },
                            capital: {
                                type: 'integer',
                                required: false,
                                description: 'Capital souscrit'
                            },
                            vie_entiere: {
                                type: 'boolean',
                                required: false,
                                default: false,
                                description: 'Vie entière'
                            },
                            code_produit_court: {
                                type: 'string',
                                required: false,
                                max: 5,
                                description: 'Code court'
                            },
                            code_marque: {
                                type: 'string',
                                required: false,
                                max: 20,
                                description: 'Code marque'
                            }
                        }
                    },
                    exampleRequest: {
                        libelle: 'PERFORMA Individuel',
                        code_branche: 'Vie',
                        code_produit_nature: 'Individuel',
                        statut: 'actif',
                        type_produit_uuid: '550e8400-e29b-41d4-a716-446655440001',
                        age_mini_adh: 18,
                        age_maxi_adh: 70,
                        capital: 1000000,
                        vie_entiere: false
                    },
                    responses: [{
                        status: 201,
                        description: 'Produit créé',
                        example: {
                            success: true,
                            message: 'Produit créé avec succès.',
                            code: 'PRODUIT_CREATED',
                            data: {}
                        }
                    }]
                },

                {
                    id: 'produits-update',
                    module: 'produits',
                    name: 'Modifier un produit',
                    description: 'Met à jour un produit existant.',
                    method: 'PUT',
                    path: '/produits/{uuid_produit}',
                    isProtected: true,
                    permissionsRequired: ['produits.modifier'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_produit: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID du produit'
                            }
                        },
                        body: {
                            /* Mêmes champs que la création, tous optionnels */
                        }
                    },
                    responses: [{
                        status: 200,
                        description: 'Produit mis à jour',
                        example: {
                            success: true,
                            message: 'Produit mis à jour.',
                            code: 'PRODUIT_UPDATED',
                            data: {}
                        }
                    }]
                },

                {
                    id: 'produits-delete',
                    module: 'produits',
                    name: 'Supprimer un produit',
                    description: 'Supprime un produit. Refusé s\'il a des formules ou prestations associées.',
                    method: 'DELETE',
                    path: '/produits/{uuid_produit}',
                    isProtected: true,
                    isDestructive: true,
                    permissionsRequired: ['produits.supprimer'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_produit: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID du produit'
                            }
                        },
                        body: {}
                    },
                    responses: [{
                            status: 200,
                            description: 'Produit supprimé',
                            example: {
                                success: true,
                                message: 'Produit supprimé.',
                                code: 'PRODUIT_DELETED'
                            }
                        },
                        {
                            status: 422,
                            description: 'Produit a des associations',
                            example: {
                                success: false,
                                message: 'Ce produit a des formules associées et ne peut pas être supprimé.',
                                code: 'PRODUIT_DELETE_ERROR'
                            }
                        }
                    ]
                },

                // ============================================================
                // FORMULES DE PRODUITS
                // ============================================================
                {
                    id: 'produit-formules-list',
                    module: 'produits',
                    name: 'Liste des formules d\'un produit',
                    description: 'Récupère toutes les formules associées à un produit.',
                    method: 'GET',
                    path: '/produits/{uuid_produit}/formules',
                    isProtected: true,
                    permissionsRequired: ['produits.afficher'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_produit: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID du produit'
                            }
                        },
                        query: {
                            est_actif: {
                                type: 'boolean',
                                required: false,
                                description: 'Filtrer par statut actif'
                            },
                            search: {
                                type: 'string',
                                required: false,
                                description: 'Recherche textuelle'
                            }
                        }
                    },
                    responses: [{
                        status: 200,
                        description: 'Formules du produit',
                        example: {
                            success: true,
                            message: 'Formules du produit.',
                            code: 'FORMULES_LISTED',
                            data: [{
                                uuid_produit_formule: '...',
                                code_produit_formule: 'PREMIUM',
                                libelle: 'Formule Premium',
                                est_actif: true,
                                fa: 1.25,
                                fg: 0.85,
                                tx: 0.05,
                                code_canal_distribution: 'Direct',
                                date_debut: '2025-01-01',
                                date_fin: null
                            }]
                        }
                    }]
                },

                {
                    id: 'produit-formules-create',
                    module: 'produits',
                    name: 'Créer une formule',
                    description: 'Crée une nouvelle formule pour un produit.',
                    method: 'POST',
                    path: '/produits/{uuid_produit}/formules',
                    isProtected: true,
                    permissionsRequired: ['produits.creer'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_produit: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID du produit'
                            }
                        },
                        body: {
                            libelle: {
                                type: 'string',
                                required: true,
                                max: 128,
                                description: 'Libellé de la formule'
                            },
                            code_produit_formule: {
                                type: 'string',
                                required: false,
                                max: 25,
                                description: 'Code unique'
                            },
                            code_produit: {
                                type: 'string',
                                required: false,
                                max: 25,
                                description: 'Code du produit associé'
                            },
                            date_debut: {
                                type: 'date',
                                required: false,
                                description: 'Date de début de validité'
                            },
                            date_fin: {
                                type: 'date',
                                required: false,
                                description: 'Date de fin de validité'
                            },
                            est_actif: {
                                type: 'boolean',
                                required: false,
                                default: true,
                                description: 'Formule active'
                            },
                            fa: {
                                type: 'number',
                                required: false,
                                description: 'Facteur A'
                            },
                            fg: {
                                type: 'number',
                                required: false,
                                description: 'Facteur G'
                            },
                            tx: {
                                type: 'number',
                                required: false,
                                description: 'Taux'
                            },
                            code_canal_distribution: {
                                type: 'string',
                                required: false,
                                max: 25,
                                description: 'Canal de distribution'
                            }
                        }
                    },
                    exampleRequest: {
                        libelle: 'Formule Premium Plus',
                        est_actif: true,
                        fa: 1.35,
                        fg: 0.90,
                        tx: 0.055,
                        code_canal_distribution: 'Courtier'
                    },
                    responses: [{
                        status: 201,
                        description: 'Formule créée',
                        example: {
                            success: true,
                            message: 'Formule créée.',
                            code: 'FORMULE_CREATED',
                            data: {}
                        }
                    }]
                },

                {
                    id: 'produit-formules-update',
                    module: 'produits',
                    name: 'Modifier une formule',
                    description: 'Met à jour une formule existante.',
                    method: 'PUT',
                    path: '/produits/formules/{uuid_formule}',
                    isProtected: true,
                    permissionsRequired: ['produits.modifier'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_formule: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID de la formule'
                            }
                        },
                        body: {
                            /* Mêmes champs que la création, tous optionnels */
                        }
                    },
                    responses: [{
                        status: 200,
                        description: 'Formule mise à jour',
                        example: {
                            success: true,
                            message: 'Formule mise à jour.',
                            code: 'FORMULE_UPDATED',
                            data: {}
                        }
                    }]
                },

                {
                    id: 'produit-formules-delete',
                    module: 'produits',
                    name: 'Supprimer une formule',
                    description: 'Supprime une formule.',
                    method: 'DELETE',
                    path: '/produits/formules/{uuid_formule}',
                    isProtected: true,
                    isDestructive: true,
                    permissionsRequired: ['produits.supprimer'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_formule: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID de la formule'
                            }
                        },
                        body: {}
                    },
                    responses: [{
                        status: 200,
                        description: 'Formule supprimée',
                        example: {
                            success: true,
                            message: 'Formule supprimée.',
                            code: 'FORMULE_DELETED'
                        }
                    }]
                },

                // ============================================================
                // PRESTATIONS D'UN PRODUIT
                // ============================================================
                {
                    id: 'produit-prestations-list',
                    module: 'produits',
                    name: 'Prestations d\'un produit',
                    description: 'Récupère toutes les prestations associées à un produit.',
                    method: 'GET',
                    path: '/produits/{uuid_produit}/prestations',
                    isProtected: true,
                    permissionsRequired: ['produits.afficher'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_produit: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID du produit'
                            }
                        }
                    },
                    responses: [{
                        status: 200,
                        description: 'Prestations du produit',
                        example: {
                            success: true,
                            message: 'Prestations du produit.',
                            code: 'PRESTATIONS_LISTED',
                            data: [{
                                uuid_type_prestation: '...',
                                code: 'DECES',
                                libelle: 'Décès',
                                impact: '1',
                                category: {
                                    uuid: '...',
                                    libelle: 'Garanties'
                                },
                                pivot: {
                                    status: 'actif',
                                    produit_type: 'Principal',
                                    uuid_product_prestation: '...'
                                }
                            }]
                        }
                    }]
                },

                {
                    id: 'produit-prestations-available',
                    module: 'produits',
                    name: 'Types de prestations disponibles',
                    description: 'Récupère les types de prestations non encore associés à un produit.',
                    method: 'GET',
                    path: '/produits/{uuid_produit}/prestations/available',
                    isProtected: true,
                    permissionsRequired: ['produits.afficher'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_produit: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID du produit'
                            }
                        },
                        query: {
                            category_uuid: {
                                type: 'uuid',
                                required: false,
                                description: 'Filtrer par catégorie'
                            },
                            search: {
                                type: 'string',
                                required: false,
                                description: 'Recherche textuelle'
                            }
                        }
                    },
                    responses: [{
                        status: 200,
                        description: 'Types de prestations disponibles',
                        example: {
                            success: true,
                            message: 'Types de prestations disponibles.',
                            code: 'AVAILABLE_PRESTATIONS',
                            data: [{
                                uuid_type_prestation: '...',
                                code: 'INVALIDITE',
                                libelle: 'Invalidité',
                                impact: '0',
                                category: {
                                    uuid: '...',
                                    libelle: 'Garanties'
                                }
                            }]
                        }
                    }]
                },


                {
                    id: 'produit-prestations-assign',
                    module: 'produits',
                    name: 'Associer des prestations à un produit',
                    description: 'Associe un ou plusieurs types de prestations à un produit. Permet d\'associer plusieurs prestations en une seule requête.',
                    method: 'POST',
                    path: '/produits/{uuid_produit}/prestations',
                    isProtected: true,
                    permissionsRequired: ['produits.modifier'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_produit: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID du produit'
                            }
                        },
                        body: {
                            type_prestation_uuids: {
                                type: 'array',
                                required: true,
                                min: 1,
                                description: 'Liste des UUIDs des types de prestations à associer'
                            },
                            'type_prestation_uuids.*': {
                                type: 'uuid',
                                required: true,
                                description: 'UUID d\'un type de prestation'
                            },
                            status: {
                                type: 'string',
                                required: false,
                                enum: ['actif', 'inactif'],
                                default: 'actif',
                                description: 'Statut de l\'association'
                            }
                        }
                    },
                    exampleRequest: {
                        type_prestation_uuids: [
                            '550e8400-e29b-41d4-a716-446655440003',
                            '550e8400-e29b-41d4-a716-446655440004',
                            '550e8400-e29b-41d4-a716-446655440005'
                        ],
                        status: 'actif'
                    },
                    responses: [{
                        status: 201,
                        description: 'Prestations associées avec succès',
                        example: {
                            success: true,
                            message: 'Prestations associées au produit avec succès.',
                            code: 'PRESTATIONS_ASSIGNED',
                            data: {
                                associations: [{
                                    uuid_product_prestation: '...',
                                    produit: {
                                        uuid_produit: '...',
                                        libelle: 'PERFORMA INDIVIDUEL'
                                    },
                                    type_prestation: {
                                        uuid_type_prestation: '...',
                                        code: 'DECES',
                                        libelle: 'Décès'
                                    },
                                    status: 'actif'
                                }],
                                assigned_count: 3,
                                skipped_count: 0,
                                already_assigned: []
                            }
                        }
                    }, {
                        status: 200,
                        description: 'Aucune nouvelle association (toutes déjà existantes)',
                        example: {
                            success: true,
                            message: 'Aucune nouvelle prestation associée.',
                            code: 'PRESTATIONS_ASSIGNED',
                            data: {
                                associations: [],
                                assigned_count: 0,
                                skipped_count: 3,
                                already_assigned: [
                                    '550e8400-e29b-41d4-a716-446655440003',
                                    '550e8400-e29b-41d4-a716-446655440004'
                                ]
                            }
                        }
                    }, {
                        status: 422,
                        description: 'Erreur de validation ou prestation déjà associée',
                        example: {
                            success: false,
                            message: 'Toutes les prestations sélectionnées sont déjà associées à ce produit.',
                            code: 'PRESTATION_ASSIGN_ERROR'
                        }
                    }]
                },

                {
                    id: 'produit-prestations-remove',
                    module: 'produits',
                    name: 'Retirer une prestation d\'un produit',
                    description: 'Retire une prestation d\'un produit.',
                    method: 'DELETE',
                    path: '/produits/prestations/{uuid_association}',
                    isProtected: true,
                    isDestructive: true,
                    permissionsRequired: ['produits.modifier'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_association: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID de l\'association (uuid_product_prestation)'
                            }
                        },
                        body: {}
                    },
                    responses: [{
                        status: 200,
                        description: 'Prestation retirée',
                        example: {
                            success: true,
                            message: 'Prestation retirée du produit.',
                            code: 'PRESTATION_REMOVED'
                        }
                    }]
                },

                // ============================================================
                // CATÉGORIES DE PRESTATIONS
                // ============================================================
                {
                    id: 'prestations-categories-list',
                    module: 'prestations',
                    name: 'Liste des catégories de prestations',
                    description: 'Liste paginée des catégories de prestations avec leurs types associés.',
                    method: 'GET',
                    path: '/prestations/categories',
                    isProtected: true,
                    permissionsRequired: ['produits.afficher'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        query: {
                            per_page: {
                                type: 'integer',
                                required: false,
                                default: 20,
                                description: 'Nombre par page'
                            },
                            status: {
                                type: 'string',
                                required: false,
                                enum: ['actif', 'inactif'],
                                description: 'Filtrer par statut'
                            },
                            search: {
                                type: 'string',
                                required: false,
                                description: 'Recherche textuelle'
                            }
                        }
                    },
                    responses: [{
                        status: 200,
                        description: 'Catégories de prestations',
                        example: {
                            success: true,
                            message: 'Liste des catégories de prestations.',
                            code: 'CATEGORIES_LISTED',
                            data: {
                                current_page: 1,
                                data: [{
                                    uuid_category_type_prestations: '...',
                                    code: 'garanties',
                                    libelle: 'Garanties',
                                    description: 'Garanties principales',
                                    status: 'actif',
                                    type_prestations_count: 5,
                                    type_prestations: [{
                                        uuid_type_prestation: '...',
                                        code: 'DECES',
                                        libelle: 'Décès'
                                    }]
                                }],
                                total: 5,
                                per_page: 20,
                                last_page: 1
                            }
                        }
                    }]
                },

                {
                    id: 'prestations-categories-create',
                    module: 'prestations',
                    name: 'Créer une catégorie de prestations',
                    description: 'Crée une nouvelle catégorie de prestations.',
                    method: 'POST',
                    path: '/prestations/categories',
                    isProtected: true,
                    permissionsRequired: ['produits.creer'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        body: {
                            libelle: {
                                type: 'string',
                                required: true,
                                max: 90,
                                description: 'Libellé de la catégorie'
                            },
                            code: {
                                type: 'string',
                                required: false,
                                max: 45,
                                description: 'Code unique'
                            },
                            description: {
                                type: 'string',
                                required: false,
                                description: 'Description'
                            },
                            status: {
                                type: 'string',
                                required: false,
                                enum: ['actif', 'inactif'],
                                default: 'actif',
                                description: 'Statut'
                            }
                        }
                    },
                    exampleRequest: {
                        libelle: 'Garanties Complémentaires',
                        code: 'garanties_complementaires',
                        description: 'Garanties additionnelles',
                        status: 'actif'
                    },
                    responses: [{
                        status: 201,
                        description: 'Catégorie créée',
                        example: {
                            success: true,
                            message: 'Catégorie créée avec succès.',
                            code: 'CATEGORY_CREATED',
                            data: {}
                        }
                    }]
                },

                {
                    id: 'prestations-categories-show',
                    module: 'prestations',
                    name: 'Détails d\'une catégorie de prestations',
                    description: 'Récupère les détails d\'une catégorie avec ses types de prestations.',
                    method: 'GET',
                    path: '/prestations/categories/{uuid_category}',
                    isProtected: true,
                    permissionsRequired: ['produits.afficher'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_category: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID de la catégorie'
                            }
                        }
                    },
                    responses: [{
                        status: 200,
                        description: 'Détails de la catégorie',
                        example: {
                            success: true,
                            message: 'Détails de la catégorie.',
                            code: 'CATEGORY_FOUND',
                            data: {
                                uuid_category_type_prestations: '...',
                                code: 'garanties',
                                libelle: 'Garanties',
                                description: 'Garanties principales',
                                status: 'actif',
                                type_prestations: [{
                                    uuid_type_prestation: '...',
                                    code: 'DECES',
                                    libelle: 'Décès',
                                    impact: '1',
                                    delai_traitement: 30
                                }]
                            }
                        }
                    }]
                },

                {
                    id: 'prestations-categories-update',
                    module: 'prestations',
                    name: 'Modifier une catégorie de prestations',
                    description: 'Met à jour une catégorie de prestations existante.',
                    method: 'PUT',
                    path: '/prestations/categories/{uuid_category}',
                    isProtected: true,
                    permissionsRequired: ['produits.modifier'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_category: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID de la catégorie'
                            }
                        },
                        body: {
                            /* Mêmes champs que la création, tous optionnels */
                        }
                    },
                    responses: [{
                        status: 200,
                        description: 'Catégorie mise à jour',
                        example: {
                            success: true,
                            message: 'Catégorie mise à jour.',
                            code: 'CATEGORY_UPDATED',
                            data: {}
                        }
                    }]
                },

                {
                    id: 'prestations-categories-delete',
                    module: 'prestations',
                    name: 'Supprimer une catégorie de prestations',
                    description: 'Supprime une catégorie. Refusée si elle contient des types de prestations.',
                    method: 'DELETE',
                    path: '/prestations/categories/{uuid_category}',
                    isProtected: true,
                    isDestructive: true,
                    permissionsRequired: ['produits.supprimer'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_category: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID de la catégorie'
                            }
                        },
                        body: {}
                    },
                    responses: [{
                            status: 200,
                            description: 'Catégorie supprimée',
                            example: {
                                success: true,
                                message: 'Catégorie supprimée.',
                                code: 'CATEGORY_DELETED'
                            }
                        },
                        {
                            status: 422,
                            description: 'Catégorie non vide',
                            example: {
                                success: false,
                                message: 'Cette catégorie contient des types de prestations et ne peut pas être supprimée.',
                                code: 'CATEGORY_DELETE_ERROR'
                            }
                        }
                    ]
                },

                // ============================================================
                // TYPES DE PRESTATIONS
                // ============================================================
                {
                    id: 'prestations-types-list',
                    module: 'prestations',
                    name: 'Liste des types de prestations',
                    description: 'Liste paginée des types de prestations avec leur catégorie.',
                    method: 'GET',
                    path: '/prestations/types',
                    isProtected: true,
                    permissionsRequired: ['produits.afficher'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        query: {
                            per_page: {
                                type: 'integer',
                                required: false,
                                default: 20,
                                description: 'Nombre par page'
                            },
                            category_uuid: {
                                type: 'uuid',
                                required: false,
                                description: 'Filtrer par catégorie'
                            },
                            status: {
                                type: 'string',
                                required: false,
                                enum: ['actif', 'inactif'],
                                description: 'Filtrer par statut'
                            },
                            search: {
                                type: 'string',
                                required: false,
                                description: 'Recherche textuelle'
                            }
                        }
                    },
                    responses: [{
                        status: 200,
                        description: 'Types de prestations',
                        example: {
                            success: true,
                            message: 'Liste des types de prestations.',
                            code: 'TYPES_LISTED',
                            data: {
                                current_page: 1,
                                data: [{
                                    uuid_type_prestation: '...',
                                    code: 'DECES',
                                    libelle: 'Décès',
                                    impact: '1',
                                    delai_traitement: 30,
                                    status: 'actif',
                                    category: {
                                        uuid: '...',
                                        libelle: 'Garanties'
                                    },
                                    produits_count: 12
                                }],
                                total: 15,
                                per_page: 20,
                                last_page: 1
                            }
                        }
                    }]
                },

                {
                    id: 'prestations-types-create',
                    module: 'prestations',
                    name: 'Créer un type de prestation',
                    description: 'Crée un nouveau type de prestation.',
                    method: 'POST',
                    path: '/prestations/types',
                    isProtected: true,
                    permissionsRequired: ['produits.creer'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        body: {
                            libelle: {
                                type: 'string',
                                required: true,
                                max: 90,
                                description: 'Libellé'
                            },
                            code: {
                                type: 'string',
                                required: false,
                                max: 45,
                                description: 'Code unique'
                            },
                            description: {
                                type: 'string',
                                required: false,
                                description: 'Description'
                            },
                            category_uuid: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID de la catégorie'
                            },
                            impact: {
                                type: 'string',
                                required: false,
                                enum: ['0', '1'],
                                default: '0',
                                description: 'Impact (1: Sortie portefeuille, 0: Non)'
                            },
                            delai_traitement: {
                                type: 'integer',
                                required: false,
                                min: 0,
                                description: 'Délai de traitement en jours'
                            },
                            status: {
                                type: 'string',
                                required: false,
                                enum: ['actif', 'inactif'],
                                default: 'actif',
                                description: 'Statut'
                            }
                        }
                    },
                    exampleRequest: {
                        libelle: 'Invalidité Permanente',
                        code: 'INVALIDITE',
                        category_uuid: '550e8400-e29b-41d4-a716-446655440001',
                        impact: '1',
                        delai_traitement: 45,
                        status: 'actif'
                    },
                    responses: [{
                        status: 201,
                        description: 'Type de prestation créé',
                        example: {
                            success: true,
                            message: 'Type de prestation créé avec succès.',
                            code: 'TYPE_PRESTATION_CREATED',
                            data: {}
                        }
                    }]
                },

                {
                    id: 'prestations-types-show',
                    module: 'prestations',
                    name: 'Détails d\'un type de prestation',
                    description: 'Récupère les détails d\'un type de prestation avec ses produits associés.',
                    method: 'GET',
                    path: '/prestations/types/{uuid_type}',
                    isProtected: true,
                    permissionsRequired: ['produits.afficher'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_type: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID du type de prestation'
                            }
                        }
                    },
                    responses: [{
                        status: 200,
                        description: 'Détails du type de prestation',
                        example: {
                            success: true,
                            message: 'Détails du type de prestation.',
                            code: 'TYPE_PRESTATION_FOUND',
                            data: {
                                uuid_type_prestation: '...',
                                code: 'DECES',
                                libelle: 'Décès',
                                impact: '1',
                                delai_traitement: 30,
                                status: 'actif',
                                category: {
                                    uuid: '...',
                                    libelle: 'Garanties'
                                },
                                produits: [{
                                    uuid_produit: '...',
                                    code: 'PERF_IND',
                                    libelle: 'PERFORMA Individuel',
                                    pivot: {
                                        status: 'actif',
                                        produit_type: 'Principal'
                                    }
                                }]
                            }
                        }
                    }]
                },

                {
                    id: 'prestations-types-update',
                    module: 'prestations',
                    name: 'Modifier un type de prestation',
                    description: 'Met à jour un type de prestation existant.',
                    method: 'PUT',
                    path: '/prestations/types/{uuid_type}',
                    isProtected: true,
                    permissionsRequired: ['produits.modifier'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_type: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID du type de prestation'
                            }
                        },
                        body: {
                            /* Mêmes champs que la création, tous optionnels */
                        }
                    },
                    responses: [{
                        status: 200,
                        description: 'Type de prestation mis à jour',
                        example: {
                            success: true,
                            message: 'Type de prestation mis à jour.',
                            code: 'TYPE_PRESTATION_UPDATED',
                            data: {}
                        }
                    }]
                },

                {
                    id: 'prestations-types-delete',
                    module: 'prestations',
                    name: 'Supprimer un type de prestation',
                    description: 'Supprime un type de prestation. Refusé s\'il est associé à des produits.',
                    method: 'DELETE',
                    path: '/prestations/types/{uuid_type}',
                    isProtected: true,
                    isDestructive: true,
                    permissionsRequired: ['produits.supprimer'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_type: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID du type de prestation'
                            }
                        },
                        body: {}
                    },
                    responses: [{
                            status: 200,
                            description: 'Type de prestation supprimé',
                            example: {
                                success: true,
                                message: 'Type de prestation supprimé.',
                                code: 'TYPE_PRESTATION_DELETED'
                            }
                        },
                        {
                            status: 422,
                            description: 'Type associé à des produits',
                            example: {
                                success: false,
                                message: 'Ce type de prestation est associé à des produits et ne peut pas être supprimé.',
                                code: 'TYPE_PRESTATION_DELETE_ERROR'
                            }
                        }
                    ]
                },

                {
                    id: 'prestations-stats',
                    module: 'prestations',
                    name: 'Statistiques des prestations',
                    description: 'Récupère les statistiques des catégories, types et associations.',
                    method: 'GET',
                    path: '/prestations/stats',
                    isProtected: true,
                    permissionsRequired: ['produits.afficher'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    responses: [{
                        status: 200,
                        description: 'Statistiques des prestations',
                        example: {
                            success: true,
                            message: 'Statistiques des prestations.',
                            code: 'PRESTATION_STATS',
                            data: {
                                categories_total: 8,
                                categories_active: 6,
                                types_total: 25,
                                types_active: 20,
                                associations_total: 45,
                                associations_active: 38
                            }
                        }
                    }]
                },


                // ============================================================
                // 3.18 RENDEZ-VOUS (RDV)
                // ============================================================

                // ============================================================
                // 1. MOTIFS DISPONIBLES
                // ============================================================
                {
                    id: 'rdv-motifs',
                    module: 'rdvs',
                    name: 'Motifs disponibles pour un contrat',
                    description: 'Récupère la liste des motifs de rendez-vous disponibles pour un produit/contrat donné. Les motifs sont les types de prestations associés au produit. Possibilité de filtrer par impact (sortie/non sortie portefeuille).',
                    method: 'GET',
                    path: '/rdvs/motifs',
                    isProtected: true,
                    permissionsRequired: ['rdvs.creer'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        query: {
                            code_produit: {
                                type: 'string',
                                required: true,
                                description: 'Code du produit (ex: PERFORMA_IND)'
                            },
                            impact: {
                                type: 'string',
                                required: false,
                                enum: ['0', '1'],
                                description: 'Filtrer par impact : 1 = Sortie portefeuille, 0 = Non sortie portefeuille. Omettre pour tous.'
                            }
                        }
                    },
                    exampleRequest: {
                        code_produit: 'PERFORMA_IND',
                        impact: '1' // Seulement les motifs avec sortie portefeuille
                    },
                    exampleRequestWithoutFilter: {
                        code_produit: 'PERFORMA_IND' // Tous les motifs
                    },
                    responses: [{
                        status: 200,
                        description: 'Liste des motifs disponibles',
                        example: {
                            success: true,
                            message: 'Motifs disponibles avec impact sortie portefeuille.',
                            code: 'MOTIFS_LISTED',
                            data: [{
                                uuid_type_prestation: '550e8400-e29b-41d4-a716-446655440003',
                                code: 'DECES',
                                libelle: 'Décès',
                                description: 'Déclaration de décès',
                                impact: '1',
                                impact_label: 'Sortie portefeuille',
                                category: {
                                    uuid: '550e8400-e29b-41d4-a716-446655440002',
                                    libelle: 'Garanties'
                                }
                            }, {
                                uuid_type_prestation: '550e8400-e29b-41d4-a716-446655440004',
                                code: 'INVALIDITE',
                                libelle: 'Invalidité',
                                description: 'Demande d\'invalidité',
                                impact: '1',
                                impact_label: 'Sortie portefeuille',
                                category: {
                                    uuid: '550e8400-e29b-41d4-a716-446655440002',
                                    libelle: 'Garanties'
                                }
                            }],
                            meta: {
                                total: 2,
                                filter_impact: '1',
                                filter_impact_label: 'Sortie portefeuille'
                            }
                        }
                    }, {
                        status: 200,
                        description: 'Liste des motifs sans filtre',
                        example: {
                            success: true,
                            message: 'Motifs disponibles.',
                            code: 'MOTIFS_LISTED',
                            data: [{
                                uuid_type_prestation: '550e8400-e29b-41d4-a716-446655440003',
                                code: 'DECES',
                                libelle: 'Décès',
                                impact: '1',
                                impact_label: 'Sortie portefeuille'
                            }, {
                                uuid_type_prestation: '550e8400-e29b-41d4-a716-446655440006',
                                code: 'PAIEMENT',
                                libelle: 'Paiement',
                                impact: '0',
                                impact_label: 'Non sortie portefeuille'
                            }],
                            meta: {
                                total: 2,
                                filter_impact: null,
                                filter_impact_label: 'Tous'
                            }
                        }
                    }, {
                        status: 422,
                        description: 'Erreur de validation',
                        example: {
                            success: false,
                            message: 'Erreur de validation.',
                            code: 'VALIDATION_ERROR',
                            errors: {
                                code_produit: ['Le code produit est requis.'],
                                impact: ['L\'impact doit être 0 ou 1.']
                            }
                        }
                    }]
                },

                // ============================================================
                // 2. AGENCES DISPONIBLES POUR RENDEZ-VOUS
                // ============================================================
                {
                    id: 'rdv-agences',
                    module: 'rdvs',
                    name: 'Agences disponibles pour rendez-vous',
                    description: 'Récupère la liste des agences qui reçoivent sur rendez-vous. Filtrée par ville ou recherche textuelle.',
                    method: 'GET',
                    path: '/rdvs/agences',
                    isProtected: true,
                    permissionsRequired: ['rdvs.creer'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        query: {
                            ville: {
                                type: 'string',
                                required: false,
                                description: 'Filtrer par ville'
                            },
                            search: {
                                type: 'string',
                                required: false,
                                description: 'Recherche textuelle (libellé, adresse, ville, quartier)'
                            }
                        }
                    },
                    exampleRequest: {
                        ville: 'Abidjan'
                    },
                    responses: [{
                        status: 200,
                        description: 'Liste des agences disponibles',
                        example: {
                            success: true,
                            message: 'Agences disponibles.',
                            code: 'AGENCES_RDV_LISTED',
                            data: [{
                                uuid_agence: '550e8400-e29b-41d4-a716-446655440001',
                                code: 'AG001',
                                libelle: 'YAKO Plateau',
                                adresse: 'Av. Chardy, Imm. Alpha 2000',
                                ville: 'Abidjan',
                                quartier: 'Plateau',
                                telephone: '+2252720304050',
                                email: 'plateau@yako.ci',
                                latitude: 5.3364,
                                longitude: -4.0271,
                                jours_rdv: [{
                                    jour: 'lundi',
                                    jour_label: 'Lundi',
                                    capacite_rendez_vous: 10,
                                }, {
                                    jour: 'mardi',
                                    jour_label: 'Mardi',
                                    capacite_rendez_vous: 10,
                                }, {
                                    jour: 'mercredi',
                                    jour_label: 'Mercredi',
                                    capacite_rendez_vous: 8,
                                }],
                                horaires: {
                                    lundi: {
                                        heure_ouverture: '08:00',
                                        heure_fermeture: '17:30',
                                        ferme: false,
                                        rendez_vous_actif: true
                                    },
                                    dimanche: {
                                        ferme: true,
                                        rendez_vous_actif: false
                                    }
                                }
                            }, {
                                uuid_agence: '550e8400-e29b-41d4-a716-446655440002',
                                code: 'AG002',
                                libelle: 'YAKO Cocody',
                                adresse: 'Bd de la Corniche, Riviera 3',
                                ville: 'Abidjan',
                                quartier: 'Cocody',
                                telephone: '+2252722445566',
                                email: 'cocody@yako.ci',
                                latitude: 5.3364,
                                longitude: -4.0271,
                                jours_rdv: [{
                                    jour: 'lundi',
                                    jour_label: 'Lundi',
                                    capacite_rendez_vous: 8,
                                }, {
                                    jour: 'mercredi',
                                    jour_label: 'Mercredi',
                                    capacite_rendez_vous: 8,
                                }, {
                                    jour: 'vendredi',
                                    jour_label: 'Vendredi',
                                    capacite_rendez_vous: 6,
                                }],
                                horaires: {
                                    lundi: {
                                        heure_ouverture: '08:00',
                                        heure_fermeture: '17:30',
                                        ferme: false,
                                        rendez_vous_actif: true
                                    }
                                }
                            }]
                        }
                    }]
                },

                // ============================================================
                // 3. DATES DISPONIBLES
                // ============================================================
                {
                    id: 'rdv-dates-disponibles',
                    module: 'rdvs',
                    name: 'Dates disponibles pour une agence',
                    description: 'Récupère les dates disponibles pour une agence sur un mois donné. Vérifie automatiquement : jours fériés, week-ends, périodes clôturées, capacité journalière.',
                    method: 'GET',
                    path: '/rdvs/dates-disponibles',
                    isProtected: true,
                    permissionsRequired: ['rdvs.creer'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        query: {
                            agence_uuid: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID de l\'agence'
                            },
                            mois: {
                                type: 'integer',
                                required: true,
                                min: 1,
                                max: 12,
                                description: 'Mois (1-12)'
                            },
                            annee: {
                                type: 'integer',
                                required: true,
                                min: 2020,
                                max: 2100,
                                description: 'Année'
                            },
                            // id_contrat: {
                            //     type: 'integer',
                            //     required: false,
                            //     description: 'ID du contrat (pour vérifier l\'éligibilité)'
                            // }
                        }
                    },
                    exampleRequest: {
                        agence_uuid: '550e8400-e29b-41d4-a716-446655440001',
                        mois: 7,
                        annee: 2026,
                        id_contrat: 12345
                    },
                    responses: [{
                        status: 200,
                        description: 'Dates disponibles avec places restantes',
                        example: {
                            success: true,
                            message: 'Dates disponibles.',
                            code: 'DATES_DISPONIBLES',
                            data: [{
                                date: '2026-07-01',
                                date_formatee: 'Mercredi 1 Juillet 2026',
                                jour_semaine: 'mercredi',
                                places_restantes: 8,
                                disponible: true,
                                capacite_max: 10,
                                heure_ouverture: '08:00',
                                heure_fermeture: '17:30'
                            }, {
                                date: '2026-07-02',
                                date_formatee: 'Jeudi 2 Juillet 2026',
                                jour_semaine: 'jeudi',
                                places_restantes: 3,
                                disponible: true,
                                capacite_max: 10,
                                heure_ouverture: '08:00',
                                heure_fermeture: '17:30'
                            }, {
                                date: '2026-07-03',
                                date_formatee: 'Vendredi 3 Juillet 2026',
                                jour_semaine: 'vendredi',
                                places_restantes: 0,
                                disponible: false,
                                capacite_max: 10,
                                heure_ouverture: '08:00',
                                heure_fermeture: '17:30'
                            }, {
                                date: '2026-07-06',
                                date_formatee: 'Lundi 6 Juillet 2026',
                                jour_semaine: 'lundi',
                                places_restantes: 10,
                                disponible: true,
                                capacite_max: 10,
                                heure_ouverture: '08:00',
                                heure_fermeture: '17:30'
                            }]
                        }
                    }, {
                        status: 422,
                        description: 'Agence ne reçoit pas sur rendez-vous',
                        example: {
                            success: false,
                            message: 'Cette agence ne reçoit pas sur rendez-vous.',
                            code: 'AGENCE_NON_DISPONIBLE'
                        }
                    }]
                },

                // ============================================================
                // 4. VÉRIFIER UNE DATE
                // ============================================================
                {
                    id: 'rdv-verifier-date',
                    module: 'rdvs',
                    name: 'Vérifier une date spécifique',
                    description: 'Vérifie si une date spécifique est disponible pour un rendez-vous dans une agence donnée.',
                    method: 'POST',
                    path: '/rdvs/verifier-date',
                    isProtected: true,
                    permissionsRequired: ['rdvs.creer'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        body: {
                            agence_uuid: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID de l\'agence'
                            },
                            date_rdv: {
                                type: 'date',
                                required: true,
                                format: 'Y-m-d',
                                description: 'Date à vérifier (aujourd\'hui ou plus tard)'
                            }
                        }
                    },
                    exampleRequest: {
                        agence_uuid: '550e8400-e29b-41d4-a716-446655440001',
                        date_rdv: '2026-07-06'
                    },
                    responses: [{
                        status: 200,
                        description: 'Date disponible',
                        example: {
                            success: true,
                            message: 'Date disponible.',
                            code: 'DATE_DISPONIBLE',
                            data: {
                                disponible: true,
                                places_restantes: 10,
                                capacite_max: 10,
                                heure_ouverture: '08:00',
                                heure_fermeture: '17:30'
                            }
                        }
                    }, {
                        status: 200,
                        description: 'Date non disponible',
                        example: {
                            success: false,
                            message: 'Plus de places disponibles pour cette date.',
                            code: 'DATE_NON_DISPONIBLE',
                            data: {
                                disponible: false,
                                places_restantes: 0,
                                message: 'Plus de places disponibles pour cette date.'
                            }
                        }
                    }]
                },

                // ============================================================
                // 5. CRÉER UN RENDEZ-VOUS
                // ============================================================
                {
                    id: 'rdv-create',
                    module: 'rdvs',
                    name: 'Créer un rendez-vous',
                    description: 'Crée un nouveau rendez-vous pour le client connecté. Vérifie automatiquement : éligibilité du client, disponibilité de la date, capacité de l\'agence, jours fériés, périodes clôturées.',
                    method: 'POST',
                    path: '/rdvs',
                    isProtected: true,
                    permissionsRequired: ['rdvs.creer'],
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        body: {
                            id_contrat: {
                                type: 'integer',
                                required: true,
                                description: 'ID du contrat concerné'
                            },
                            code_produit: {
                                type: 'string',
                                required: true,
                                description: 'Code du produit (ex: PERFORMA_IND)'
                            },
                            motif_rdv: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID du motif (type de prestation)'
                            },
                            agence_uuid: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID de l\'agence souhaitée'
                            },
                            date_rdv: {
                                type: 'date',
                                required: true,
                                format: 'Y-m-d',
                                description: 'Date du rendez-vous'
                            },
                            demandeur: {
                                type: 'string',
                                required: false,
                                max: 50,
                                description: 'Titre du demandeur (Souscripteur, Assuré, Bénéficiaire, etc.)'
                            }
                        }
                    },
                    exampleRequest: {
                        id_contrat: 12345,
                        code_produit: 'PERFORMA_IND',
                        motif_rdv: '550e8400-e29b-41d4-a716-446655440001',
                        agence_uuid: '550e8400-e29b-41d4-a716-446655440001',
                        date_rdv: '2026-07-06',
                        demandeur: 'Souscripteur'
                    },
                    responses: [{
                        status: 201,
                        description: 'Rendez-vous créé avec succès',
                        example: {
                            success: true,
                            message: 'Rendez-vous créé avec succès. Code : RDV-20260706-AbC12345',
                            code: 'RDV_CREATED',
                            data: {
                                uuid_rdvs: '550e8400-e29b-41d4-a716-446655440010',
                                code: 'RDV-20260706-AbC12345',
                                client_uuid: '550e8400-e29b-41d4-a716-446655440000',
                                id_contrat: 12345,
                                motif_rdv: {
                                    uuid_type_prestation: '550e8400-e29b-41d4-a716-446655440001',
                                    libelle: 'Décès'
                                },
                                demandeur: 'Souscripteur',
                                date_rdv: '2026-07-06',
                                date_rdv_souhaiter: '2026-07-01T10:30:00.000000Z',
                                agence_souhaiter_uuid: '550e8400-e29b-41d4-a716-446655440001',
                                agence_souhaitee: {
                                    uuid_agence: '550e8400-e29b-41d4-a716-446655440001',
                                    libelle: 'YAKO Plateau',
                                    adresse: 'Av. Chardy, Imm. Alpha 2000',
                                    ville: 'Abidjan'
                                },
                                status: 'en_attente',
                                is_permitted: false,
                                created_at: '2026-07-01T10:30:00.000000Z'
                            }
                        }
                    }, {
                        status: 422,
                        description: 'Erreur de validation',
                        example: {
                            success: false,
                            message: 'Erreur de validation.',
                            code: 'VALIDATION_ERROR',
                            errors: {
                                date_rdv: ['Cette date n\'est plus disponible.'],
                                eligibilite: [
                                    {
                                        code: 'RDV_RECENT',
                                        message: 'Vous avez déjà un rendez-vous sur ce contrat datant de moins de 30 jours.',
                                        rdv_code: 'RDV-20260620-XyZ78910',
                                        rdv_date: '2026-06-20',
                                        rdv_status: 'confirme'
                                    }
                                ]
                            }
                        }
                    }]
                },

                // ============================================================
                // 6. LISTE DES RENDEZ-VOUS DU CLIENT
                // ============================================================
                {
                    id: 'rdv-list',
                    module: 'rdvs',
                    name: 'Mes rendez-vous',
                    description: 'Récupère la liste paginée des rendez-vous du client connecté avec filtres (statut, contrat, recherche).',
                    method: 'GET',
                    path: '/rdvs',
                    isProtected: true,
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        query: {
                            per_page: {
                                type: 'integer',
                                required: false,
                                default: 20,
                                description: 'Nombre par page'
                            },
                            status: {
                                type: 'string',
                                required: false,
                                enum: ['en_attente', 'confirme', 'annule', 'rejete', 'traite', 'present', 'absent', 'reporte'],
                                description: 'Filtrer par statut'
                            },
                            id_contrat: {
                                type: 'integer',
                                required: false,
                                description: 'Filtrer par contrat'
                            },
                            search: {
                                type: 'string',
                                required: false,
                                description: 'Recherche textuelle (code, référence contrat)'
                            }
                        }
                    },
                    exampleRequest: {
                        status: 'confirme',
                        per_page: 10
                    },
                    responses: [{
                        status: 200,
                        description: 'Liste des rendez-vous',
                        example: {
                            success: true,
                            message: 'Liste des rendez-vous.',
                            code: 'RDVS_LISTED',
                            data: [{
                                uuid_rdvs: '550e8400-e29b-41d4-a716-446655440010',
                                code: 'RDV-20260706-AbC12345',
                                motif: {
                                    uuid_type_prestation: '550e8400-e29b-41d4-a716-446655440001',
                                    libelle: 'Décès'
                                },
                                date_rdv: '2026-07-06',
                                agence_souhaitee: {
                                    uuid_agence: '550e8400-e29b-41d4-a716-446655440001',
                                    libelle: 'YAKO Plateau',
                                    ville: 'Abidjan'
                                },
                                status: 'confirme',
                                status_label: 'Confirmé',
                                created_at: '2026-07-01T10:30:00.000000Z'
                            }, {
                                uuid_rdvs: '550e8400-e29b-41d4-a716-446655440011',
                                code: 'RDV-20260628-XyZ78910',
                                motif: {
                                    uuid_type_prestation: '550e8400-e29b-41d4-a716-446655440004',
                                    libelle: 'Paiement'
                                },
                                date_rdv: '2026-06-28',
                                agence_souhaitee: {
                                    uuid_agence: '550e8400-e29b-41d4-a716-446655440002',
                                    libelle: 'YAKO Cocody',
                                    ville: 'Abidjan'
                                },
                                status: 'termine',
                                status_label: 'Terminé',
                                created_at: '2026-06-20T14:00:00.000000Z'
                            }],
                            meta: {
                                current_page: 1,
                                per_page: 20,
                                total: 2,
                                last_page: 1
                            }
                        }
                    }]
                },

                // ============================================================
                // 7. STATISTIQUES DES RENDEZ-VOUS
                // ============================================================
                {
                    id: 'rdv-stats',
                    module: 'rdvs',
                    name: 'Statistiques des rendez-vous',
                    description: 'Récupère les statistiques des rendez-vous du client connecté (total, par statut).',
                    method: 'GET',
                    path: '/rdvs/stats',
                    isProtected: true,
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    responses: [{
                        status: 200,
                        description: 'Statistiques des rendez-vous',
                        example: {
                            success: true,
                            message: 'Statistiques des rendez-vous.',
                            code: 'RDV_STATS',
                            data: {
                                total: 5,
                                en_attente: 1,
                                confirme: 2,
                                traite: 1,
                                termine: 0,
                                annule: 1,
                                rejete: 0,
                                reporte: 0
                            }
                        }
                    }]
                },

                // ============================================================
                // 8. DÉTAILS D'UN RENDEZ-VOUS
                // ============================================================
                {
                    id: 'rdv-show',
                    module: 'rdvs',
                    name: 'Détails d\'un rendez-vous',
                    description: 'Récupère les détails complets d\'un rendez-vous (client, motif, agences, gestionnaire). Le client ne peut voir que ses propres rendez-vous.',
                    method: 'GET',
                    path: '/rdvs/{uuid_rdvs}',
                    isProtected: true,
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_rdvs: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID du rendez-vous'
                            }
                        }
                    },
                    responses: [{
                        status: 200,
                        description: 'Détails du rendez-vous',
                        example: {
                            success: true,
                            message: 'Détails du rendez-vous.',
                            code: 'RDV_FOUND',
                            data: {
                                uuid_rdvs: '550e8400-e29b-41d4-a716-446655440010',
                                code: 'RDV-20260706-AbC12345',
                                client: {
                                    uuid_user: '550e8400-e29b-41d4-a716-446655440000',
                                    email: 'client@example.com',
                                    login: 'jdupont',
                                    details: {
                                        nom: 'Dupont',
                                        prenoms: 'Jean',
                                        full_name: 'Jean Dupont'
                                    }
                                },
                                id_contrat: 12345,
                                contrat_reference: 'YAK-2021-004821',
                                motif: {
                                    uuid_type_prestation: '550e8400-e29b-41d4-a716-446655440001',
                                    code: 'DECES',
                                    libelle: 'Décès',
                                    description: 'Déclaration de décès'
                                },
                                demandeur: 'Souscripteur',
                                date_rdv: '2026-07-06',
                                date_rdv_souhaiter: '2026-07-01T10:30:00.000000Z',
                                date_rdv_effective: null,
                                agence_souhaitee: {
                                    uuid_agence: '550e8400-e29b-41d4-a716-446655440001',
                                    libelle: 'YAKO Plateau',
                                    adresse: 'Av. Chardy, Imm. Alpha 2000',
                                    ville: 'Abidjan',
                                    telephone: '+2252720304050'
                                },
                                agence_effective: null,
                                gestionnaire: null,
                                status: 'confirme',
                                status_label: 'Confirmé',
                                // is_permitted: true,
                                observation: null,
                                created_at: '2026-07-01T10:30:00.000000Z',
                                updated_at: '2026-07-01T10:30:00.000000Z'
                            }
                        }
                    }, {
                        status: 403,
                        description: 'Accès non autorisé',
                        example: {
                            success: false,
                            message: 'Accès non autorisé.',
                            code: 'FORBIDDEN'
                        }
                    }, {
                        status: 404,
                        description: 'Rendez-vous non trouvé',
                        example: {
                            success: false,
                            message: 'Rendez-vous non trouvé.',
                            code: 'RDV_NOT_FOUND'
                        }
                    }]
                },

                // ============================================================
                // 9. ANNULER UN RENDEZ-VOUS
                // ============================================================
                {
                    id: 'rdv-cancel',
                    module: 'rdvs',
                    name: 'Annuler un rendez-vous',
                    description: 'Annule un rendez-vous. Un rendez-vous confirmé ou traité ne peut pas être annulé.',
                    method: 'POST',
                    path: '/rdvs/{uuid_rdvs}/cancel',
                    isProtected: true,
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_rdvs: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID du rendez-vous'
                            }
                        },
                        body: {
                            motif: {
                                type: 'string',
                                required: false,
                                max: 255,
                                description: 'Motif de l\'annulation'
                            }
                        }
                    },
                    exampleRequest: {
                        motif: 'Impossibilité de me déplacer'
                    },
                    responses: [{
                        status: 200,
                        description: 'Rendez-vous annulé',
                        example: {
                            success: true,
                            message: 'Rendez-vous annulé avec succès.',
                            code: 'RDV_CANCELLED',
                            data: {
                                uuid_rdvs: '550e8400-e29b-41d4-a716-446655440010',
                                code: 'RDV-20260706-AbC12345',
                                status: 'annule',
                                status_label: 'Annulé',
                                motif_traitement: {
                                    annulation: 'Impossibilité de me déplacer',
                                    admin_action: true
                                },
                                date_traitement: '2026-07-02T08:00:00.000000Z'
                            }
                        }
                    }, {
                        status: 422,
                        description: 'Rendez-vous déjà traité',
                        example: {
                            success: false,
                            message: 'Ce rendez-vous ne peut plus être annulé car il est déjà confirmé ou traité.',
                            code: 'RDV_DEJA_TRAITE'
                        }
                    }, {
                        status: 403,
                        description: 'Accès non autorisé',
                        example: {
                            success: false,
                            message: 'Accès non autorisé.',
                            code: 'FORBIDDEN'
                        }
                    }]
                },

                // ============================================================
                // 10. SIGNALER SA PRÉSENCE
                // ============================================================
                {
                    id: 'rdv-signaler-presence',
                    module: 'rdvs',
                    name: 'Signaler sa présence',
                    description: 'Permet au client de signaler sa présence à l\'agence le jour du rendez-vous. Vérifie la géolocalisation si configurée.',
                    method: 'POST',
                    path: '/rdvs/{uuid_rdvs}/signaler-presence',
                    isProtected: true,
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_rdvs: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID du rendez-vous'
                            }
                        },
                        body: {
                            latitude: {
                                type: 'number',
                                required: false,
                                between: [-90, 90],
                                description: 'Latitude de la position du client'
                            },
                            longitude: {
                                type: 'number',
                                required: false,
                                between: [-180, 180],
                                description: 'Longitude de la position du client'
                            }
                        }
                    },
                    exampleRequest: {
                        latitude: 5.3364,
                        longitude: -4.0271
                    },
                    responses: [{
                        status: 200,
                        description: 'Présence signalée avec succès',
                        example: {
                            success: true,
                            message: 'Présence signalée avec succès.',
                            code: 'PRESENCE_SIGNALEE',
                            data: {
                                uuid_rdvs: '550e8400-e29b-41d4-a716-446655440010',
                                code: 'RDV-20260706-AbC12345',
                                is_present: true,
                            }
                        }
                    }, {
                        status: 422,
                        description: 'Erreur de validation',
                        example: {
                            success: false,
                            message: 'Erreur de validation.',
                            code: 'VALIDATION_ERROR',
                            errors: {
                                geolocalisation: ['Vous n\'êtes pas à proximité de l\'agence. Veuillez vous rapprocher pour valider votre présence.']
                            }
                        }
                    }, {
                        status: 422,
                        description: 'Rendez-vous non confirmé',
                        example: {
                            success: false,
                            message: 'Ce rendez-vous n\'est pas confirmé.',
                            code: 'RDV_NOT_CONFIRMED'
                        }
                    }, {
                        status: 422,
                        description: 'Rendez-vous pas aujourd\'hui',
                        example: {
                            success: false,
                            message: 'Le rendez-vous n\'est pas prévu aujourd\'hui.',
                            code: 'RDV_NOT_TODAY'
                        }
                    }]
                },

                // ============================================================
                // ADMIN - GESTION DES RENDEZ-VOUS
                // ============================================================

                // ============================================================
                // 11. ADMIN - LISTE DES RENDEZ-VOUS D'UNE AGENCE
                // ============================================================
                {
                    id: 'rdv-admin-agence',
                    module: 'rdvs',
                    name: '[Admin] Rendez-vous d\'une agence',
                    description: 'Liste paginée des rendez-vous d\'une agence spécifique (souhaitée ou effective). Réservé aux administrateurs.',
                    method: 'GET',
                    path: '/admin/rdvs/agence/{uuid_agence}',
                    isProtected: true,
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_agence: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID de l\'agence'
                            }
                        },
                        query: {
                            per_page: {
                                type: 'integer',
                                required: false,
                                default: 20,
                                description: 'Nombre par page'
                            },
                            status: {
                                type: 'string',
                                required: false,
                                enum: ['en_attente', 'confirme', 'annule', 'rejete', 'traite', 'tremine','reporte'],
                                description: 'Filtrer par statut'
                            },
                            date: {
                                type: 'date',
                                required: false,
                                description: 'Filtrer par date (YYYY-MM-DD)'
                            }
                        }
                    },
                    responses: [{
                        status: 200,
                        description: 'Liste des rendez-vous de l\'agence',
                        example: {
                            success: true,
                            message: 'Liste des rendez-vous de l\'agence.',
                            code: 'AGENCE_RDVS_LISTED',
                            data: [{
                                uuid_rdvs: '...',
                                code: 'RDV-20260706-AbC12345',
                                client: {
                                    uuid_user: '...',
                                    email: 'client@example.com',
                                    details: {
                                        full_name: 'Jean Dupont'
                                    }
                                },
                                motif: {
                                    libelle: 'Décès'
                                },
                                contrat_reference: 'YAK-2021-004821',
                                date_rdv: '2026-07-06',
                                status: 'confirme',
                                status_label: 'Confirmé',
                                created_at: '2026-07-01T10:30:00.000000Z'
                            }],
                            meta: {
                                current_page: 1,
                                per_page: 20,
                                total: 5,
                                last_page: 1
                            }
                        }
                    }]
                },

                // ============================================================
                // 12. ADMIN - METTRE À JOUR LE STATUT
                // ============================================================
                {
                    id: 'rdv-admin-update-status',
                    module: 'rdvs',
                    name: '[Admin] Mettre à jour le statut d\'un rendez-vous',
                    description: 'Met à jour le statut d\'un rendez-vous. Permet de confirmer, rejeter, traiter, treminer un rendez-vous ou de reporter un rendez-vous. Réservé aux administrateurs.',
                    method: 'PUT',
                    path: '/admin/rdvs/{uuid_rdvs}/status',
                    isProtected: true,
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_rdvs: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID du rendez-vous'
                            }
                        },
                        body: {
                            status: {
                                type: 'string',
                                required: true,
                                enum: ['en_attente', 'confirme', 'rejete', 'traite', 'tremine', 'reporte'],
                                description: 'Nouveau statut'
                            },
                            observation: {
                                type: 'string',
                                required: false,
                                description: 'Observation du gestionnaire'
                            }
                        }
                    },
                    exampleRequest: {
                        status: 'confirme',
                        observation: 'Rendez-vous confirmé par le gestionnaire'
                    },
                    responses: [{
                        status: 200,
                        description: 'Statut mis à jour',
                        example: {
                            success: true,
                            message: 'Statut du rendez-vous mis à jour.',
                            code: 'RDV_STATUS_UPDATED',
                            data: {
                                uuid_rdvs: '...',
                                code: 'RDV-20260706-AbC12345',
                                status: 'confirme',
                                status_label: 'Confirmé',
                                date_traitement: '2026-07-02T08:00:00.000000Z',
                                observation: 'Rendez-vous confirmé par le gestionnaire'
                            }
                        }
                    }]
                },

                // ============================================================
                // 13. ADMIN - ASSIGNER UN GESTIONNAIRE
                // ============================================================
                {
                    id: 'rdv-admin-assign-gestionnaire',
                    module: 'rdvs',
                    name: '[Admin] Assigner un gestionnaire',
                    description: 'Assigne un gestionnaire à un rendez-vous. Réservé aux administrateurs.',
                    method: 'POST',
                    path: '/admin/rdvs/{uuid_rdvs}/assign-gestionnaire',
                    isProtected: true,
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            uuid_rdvs: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID du rendez-vous'
                            }
                        },
                        body: {
                            gestionnaire_uuid: {
                                type: 'uuid',
                                required: true,
                                description: 'UUID de l\'utilisateur gestionnaire'
                            }
                        }
                    },
                    exampleRequest: {
                        gestionnaire_uuid: '550e8400-e29b-41d4-a716-446655440020'
                    },
                    responses: [{
                        status: 200,
                        description: 'Gestionnaire assigné avec succès',
                        example: {
                            success: true,
                            message: 'Gestionnaire assigné avec succès.',
                            code: 'GESTIONNAIRE_ASSIGNED',
                            data: {
                                uuid_rdvs: '...',
                                code: 'RDV-20260706-AbC12345',
                                gestionnaire: {
                                    uuid_user: '550e8400-e29b-41d4-a716-446655440020',
                                    email: 'gestionnaire@yako.ci',
                                    details: {
                                        full_name: 'Koffi Serge'
                                    }
                                }
                            }
                        }
                    }]
                },

                // ============================================================
                // ESPACE CLIENT - ÉTAT DE COTISATION D'UN CONTRAT
                // ============================================================
                {
                    id: 'customer-contrat-etat-cotisation',
                    module: 'espaces_client',
                    name: 'État de cotisation d\'un contrat',
                    description: 'Récupère l\'état détaillé des cotisations d\'un contrat spécifique. Retourne les informations financières complètes : primes payées, impayées, encaissements, et le détail des paiements. Inclut également les informations des assurés, bénéficiaires, payeurs et garanties.',
                    method: 'GET',
                    path: '/espaces-client/contrat-etat-cotisation/{contrat_id}',
                    isProtected: true,
                    headers: {
                        'Authorization': 'Bearer {token}',
                        'Accept': 'application/json'
                    },
                    requestParams: {
                        path: {
                            contrat_id: {
                                type: 'integer',
                                required: true,
                                description: 'ID du contrat (identifiant numérique)'
                            }
                        }
                    },
                    responses: [{
                            status: 200,
                            description: 'État de cotisation récupéré avec succès',
                            example: {
                                success: true,
                                code: 'GET_ALL_CONTRAT_SUCCESS',
                                message: 'Etats de cotisation du contrat récupérés avec succès.',
                                data: {
                                    details: {
                                        IdProposition: 'PROP2024001',
                                        NumBulletin: 'BUL-2024-001',
                                        NumPolice: 'POL-2024-001',
                                        CodeProposition: 'PROP-2024-001',
                                        CapitalSouscrit: 15000000,
                                        TotalPrime: 5400000,
                                        NbreImpayes: 0,
                                        NbreEmission: 12,
                                        NbreEncaissment: 10,
                                        NbrencPartielle: 0,
                                        TotalEncaissement: 4200000,
                                        TotalEncaissementPartielle: 0,
                                        TotalImpayes: 1200000,
                                        produit: 'PERFORMA Individuel',
                                        EtatAvancementCotisation: 78.5,
                                        DureeCotisationAns: 20,
                                        Periodicite: 'Mensuel',
                                        ModePaiement: 'Prélèvement automatique',
                                        DateFinAdhesion: '31/12/2040',
                                        DateEffetAdhesion: '15/01/2021',
                                        Conseiller: 'C12345 - KOFFI Serge',
                                        Adherent: 'YAPO BRUCE BERNADIN EVRARD JUNIOR',
                                        Status: 'En cours'
                                    },
                                    Assures: [{
                                        CodePersonne: 'P001',
                                        Nom: 'YAPO',
                                        Prenoms: 'BRUCE BERNADIN EVRARD JUNIOR',
                                        NomComplet: 'YAPO BRUCE BERNADIN EVRARD JUNIOR',
                                        DateNaissance: '2000-11-20',
                                        LieuNaissance: 'Grand-Lahou',
                                        Profession: 'Informaticien',
                                        CodeFiliation: 'FIL001',
                                        Filiation: 'Fils'
                                    }],
                                    AssuresGaranties: [{
                                            NomPrenom: 'YAPO BRUCE BERNADIN EVRARD JUNIOR',
                                            CodeGarantie: 'G001',
                                            Libelle: 'Décès',
                                            Capital: 15000000,
                                            Prime: 450000,
                                            PrimePrincipale: 450000,
                                            FraisAccessoires: 0,
                                            DateEffet: '2021-01-15',
                                            DateEcheance: '2040-12-31',
                                            DureeCouvAns: 20,
                                            DureePrimeAns: 20,
                                            Periodicite: 'M'
                                        },
                                        {
                                            NomPrenom: 'YAPO BRUCE BERNADIN EVRARD JUNIOR',
                                            CodeGarantie: 'G002',
                                            Libelle: 'Invalidité',
                                            Capital: 5000000,
                                            Prime: 150000,
                                            PrimePrincipale: 150000,
                                            FraisAccessoires: 0,
                                            DateEffet: '2021-01-15',
                                            DateEcheance: '2040-12-31',
                                            DureeCouvAns: 20,
                                            DureePrimeAns: 20,
                                            Periodicite: 'M'
                                        }
                                    ],
                                    Beneficiaires: [{
                                        CodePersonne: 'P002',
                                        Nom: 'YAPO',
                                        Prenoms: 'MARIE CLAIRE',
                                        NomComplet: 'YAPO MARIE CLAIRE',
                                        DateNaissance: '1975-03-15',
                                        LieuNaissance: 'Abidjan',
                                        Profession: 'Enseignante',
                                        CodeFiliation: 'FIL002',
                                        Filiation: 'Conjoint'
                                    }],
                                    PayeurPrime: [{
                                        CodePersonne: 'P001',
                                        NomPrenom: 'YAPO BRUCE BERNADIN EVRARD JUNIOR',
                                        ModePaiement: 'PRE',
                                        Organisme: 'BICICI',
                                        NumCompte: '12345678901'
                                    }],
                                    PrimeNonRegles: [{
                                            DateEcheance: '2024-12-15',
                                            MontantNet: 450000,
                                            NumEcheance: 'ECH-2024-12',
                                            Statut: 'En attente'
                                        },
                                        {
                                            DateEcheance: '2024-11-15',
                                            MontantNet: 450000,
                                            NumEcheance: 'ECH-2024-11',
                                            Statut: 'En attente'
                                        }
                                    ],
                                    PrimeRegles: [{
                                            DateReglement: '2024-10-20',
                                            Montant: 450000,
                                            NumEcheance: 'ECH-2024-10',
                                            ModePaiement: 'PRE',
                                            Reference: 'PAY-2024-10-001'
                                        },
                                        {
                                            DateReglement: '2024-09-18',
                                            Montant: 450000,
                                            NumEcheance: 'ECH-2024-09',
                                            ModePaiement: 'PRE',
                                            Reference: 'PAY-2024-09-001'
                                        }
                                    ],
                                    PrimeReglesPartielle: []
                                }
                            }
                        },
                        {
                            status: 404,
                            description: 'Contrat non trouvé',
                            example: {
                                success: false,
                                code: 'CONTRAT_NOT_FOUND',
                                message: 'Contrat non trouvé ou non associé à cet utilisateur.'
                            }
                        },
                        {
                            status: 422,
                            description: 'Erreur de récupération',
                            example: {
                                success: false,
                                code: 'CONTRACT_DETAILS_ERROR',
                                message: 'Une erreur est survenue lors de la récupération des détails du contrat.'
                            }
                        },
                        {
                            status: 401,
                            description: 'Non authentifié',
                            example: {
                                success: false,
                                message: 'Non authentifié.'
                            }
                        }
                    ]
                },

                // ============================================================
                // WIDGET JEKO - INTÉGRATION
                // ============================================================
                {
                    id: 'jeko-widget-intro',
                    module: 'jeko_widget',
                    name: 'Widget Jeko Payment',
                    description: 'Le widget Jeko est un composant JavaScript autonome qui permet d\'intégrer facilement un paiement sécurisé via Jeko dans n\'importe quelle application web. Il gère automatiquement la vérification des contrats, la sélection des factures et l\'initialisation du paiement pour les 3 types : <strong>firstPayment</strong> (premier paiement), <strong>earlyPayment</strong> (paiement anticipé) et <strong>recoveryPrime</strong> (régularisation de primes impayées).',
                    method: 'GET',
                    path: '/paiements/jeko/jeko-payment-widget.js',
                    isProtected: false,
                    isHome: false,
                    hasWidgetInfo: true,
                    widgetInfo: {
                        features: [
                            'Vérification automatique des contrats',
                            'Sélection des factures impayées',
                            '3 types de paiement supportés',
                            'Pré-sélection des factures',
                            'Design responsive et personnalisable',
                            'Gestion d\'erreurs avancée',
                            'Accessibilité (ARIA)',
                            'Intégrable dans n\'importe quel framework'
                        ],
                        paymentTypes: [
                            { 
                                code: 'firstPayment', 
                                label: 'Premier paiement', 
                                description: 'Souscription d\'un nouveau contrat',
                                icon: 'fa-file-contract'
                            },
                            { 
                                code: 'earlyPayment', 
                                label: 'Paiement anticipé', 
                                description: 'Paiement en avance des primes',
                                icon: 'fa-calendar-check'
                            },
                            { 
                                code: 'recoveryPrime', 
                                label: 'Régularisation', 
                                description: 'Récupération de primes impayées',
                                icon: 'fa-hand-holding-usd'
                            }
                        ],
                        docsLink: '/api/v1/demo-jeko-widget',
                        widgetJs: '/api/v1/paiements/jeko/jeko-payment-widget.js'
                    },
                    responses: [{
                        status: 200,
                        description: 'Documentation du widget Jeko',
                        example: {
                            title: 'Widget Jeko Payment',
                            description: 'Paiement sécurisé intégré',
                            version: '1.0.0',
                            paymentTypes: ['firstPayment', 'earlyPayment', 'recoveryPrime'],
                            documentation: 'https://votre-domaine.com/demo-jeko-widget'
                        }
                    }]
                },

                // ============================================================
                // WIDGET JEKO - PAGE DE DÉMONSTRATION
                // ============================================================
                {
                    id: 'jeko-widget-demo',
                    module: 'jeko_widget',
                    name: 'Démonstration du Widget Jeko',
                    description: 'Page de démonstration interactive du widget Jeko avec les 3 types de paiement. Permet de tester le widget en conditions réelles avec des exemples concrets.',
                    method: 'GET',
                    path: '/demo-jeko-widget',
                    isProtected: false,
                    isHome: false,
                    isWidgetDemo: true,
                    responses: [{
                        status: 200,
                        description: 'Page de démonstration du widget',
                        example: {
                            title: 'Jeko Payment Widget - Démonstration',
                            description: 'Testez les 3 types de paiement en direct',
                            scenarios: [
                                {
                                    type: 'firstPayment',
                                    label: 'Premier paiement',
                                    description: 'Souscription d\'un nouveau contrat'
                                },
                                {
                                    type: 'earlyPayment',
                                    label: 'Paiement anticipé',
                                    description: 'Paiement en avance des primes'
                                },
                                {
                                    type: 'recoveryPrime',
                                    label: 'Régularisation',
                                    description: 'Récupération de primes impayées'
                                }
                            ]
                        }
                    }]
                },

            ],

            // ================================================================
            // 4. CODES HTTP
            // ================================================================
            httpCodes: [{
                    code: 200,
                    name: 'OK',
                    desc: 'Requête traitée avec succès.'
                },
                {
                    code: 201,
                    name: 'Created',
                    desc: 'Ressource créée avec succès.'
                },
                {
                    code: 401,
                    name: 'Unauthorized',
                    desc: 'Authentification manquante ou invalide.'
                },
                {
                    code: 403,
                    name: 'Forbidden',
                    desc: 'Accès refusé (permission manquante, compte bloqué/inactif, IP restreinte).'
                },
                {
                    code: 404,
                    name: 'Not Found',
                    desc: 'Ressource introuvable.'
                },
                {
                    code: 409,
                    name: 'Conflict',
                    desc: 'Conflit d\'état métier (compte déjà gelé/non gelable).'
                },
                {
                    code: 422,
                    name: 'Unprocessable Entity',
                    desc: 'Erreur de validation des données.'
                },
                {
                    code: 423,
                    name: 'Locked',
                    desc: 'Compte temporairement gelé (AccountFrozenException).'
                },
                {
                    code: 429,
                    name: 'Too Many Requests',
                    desc: 'Limite de tentatives dépassée.'
                },
                {
                    code: 500,
                    name: 'Internal Server Error',
                    desc: 'Erreur interne inattendue.'
                }
            ],

            // ================================================================
            // 5. ERREURS MÉTIER
            // ================================================================
            businessErrors: [{
                    code: 'AUTH_ERROR',
                    message: 'Identifiants incorrects.',
                    cause: 'Login/mot de passe invalide ou IP bloquée.',
                    endpoint: 'POST /auth/login',
                    action: 'Vérifier les identifiants.'
                },
                {
                    code: 'SERVER_ERROR',
                    message: 'Une erreur interne est survenue.',
                    cause: 'Exception non prévue.',
                    endpoint: 'POST /auth/login',
                    action: 'Réessayer plus tard.'
                },
                {
                    code: '2FA_REQUIRED',
                    message: 'Vérification 2FA requise.',
                    cause: '2FA activée, appareil non de confiance.',
                    endpoint: 'POST /auth/login',
                    action: 'Appeler /auth/2fa/verify-login.'
                },
                {
                    code: 'PASSWORD_CHANGE_REQUIRED',
                    message: 'Vous devez changer votre mot de passe.',
                    cause: 'Première connexion ou mot de passe expiré.',
                    endpoint: 'POST /auth/login',
                    action: 'Appeler /auth/first-login.'
                },
                {
                    code: 'ACCOUNT_FROZEN',
                    message: 'Compte temporairement gelé.',
                    cause: 'Trop de tentatives ou gel manuel.',
                    endpoint: 'Middleware CheckAccountStatus',
                    action: 'Attendre l\'expiration ou contacter un admin.'
                },
                {
                    code: 'ACCOUNT_BLOCKED',
                    message: 'Compte bloqué.',
                    cause: '6 tentatives échouées ou blocage manuel.',
                    endpoint: 'Middleware CheckAccountStatus',
                    action: 'Contacter un administrateur.'
                },
                {
                    code: 'IP_BLOCKED',
                    message: 'Accès refusé depuis cette adresse IP.',
                    cause: 'IP blacklistée ou non whitelistée.',
                    endpoint: 'Middleware IpRestriction',
                    action: 'Utiliser une IP autorisée.'
                },
                {
                    code: 'PASSWORD_EXPIRED',
                    message: 'Votre mot de passe a expiré.',
                    cause: 'password_expires_at dépassé.',
                    endpoint: 'Middleware CheckPasswordExpiration',
                    action: 'Appeler /auth/change-password.'
                },
                {
                    code: 'PASSWORD_CHANGE_NOT_REQUIRED',
                    message: 'Le changement n\'est pas requis.',
                    cause: 'Appel de /auth/first-login sans nécessité.',
                    endpoint: 'POST /auth/first-login',
                    action: 'Utiliser /auth/change-password.'
                },
                {
                    code: 'PERMISSION_DENIED',
                    message: 'Vous n\'avez pas la/les permission(s) nécessaire(s).',
                    cause: 'Permission manquante sur le rôle.',
                    endpoint: 'Middleware permission:*',
                    action: 'Demander l\'attribution de la permission.'
                },
                {
                    code: 'ROLE_PROTECTED',
                    message: 'Rôle système protégé.',
                    cause: 'Tentative de modification/suppression d\'un rôle système.',
                    endpoint: 'PUT/DELETE /roles',
                    action: 'Créer un rôle personnalisé.'
                },
                {
                    code: 'ROLE_SUPER_ADMIN_NOT_ASSIGNABLE',
                    message: 'Super Admin déjà tous droits.',
                    cause: 'Tentative d\'attribution de permissions explicites au Super Admin.',
                    endpoint: 'POST /roles/{uuid}/permissions',
                    action: 'Ne rien faire.'
                },
                {
                    code: 'PERMISSION_IN_USE',
                    message: 'Permission attribuée à un rôle.',
                    cause: 'Permission encore liée à un rôle.',
                    endpoint: 'DELETE /permissions',
                    action: 'Retirer la permission de tous les rôles.'
                },
                {
                    code: 'PERMISSION_GROUP_NOT_EMPTY',
                    message: 'Le groupe contient des permissions.',
                    cause: 'Groupe non vide.',
                    endpoint: 'DELETE /permission-groups',
                    action: 'Supprimer ou déplacer les permissions.'
                },
                {
                    code: 'INVALID_PASSWORD',
                    message: 'Mot de passe incorrect.',
                    cause: 'Vérification échouée.',
                    endpoint: 'POST /security/user-questions',
                    action: 'Ressaisir le mot de passe.'
                },
                {
                    code: 'TOO_MANY_ATTEMPTS',
                    message: 'Trop de tentatives.',
                    cause: 'Rate limiting dépassé.',
                    endpoint: 'Security endpoints',
                    action: 'Attendre le délai indiqué.'
                },
                {
                    code: 'OTP_INVALID',
                    message: 'Code OTP invalide ou expiré.',
                    cause: 'Le code OTP fourni est incorrect, a déjà été utilisé ou a expiré.',
                    endpoint: 'POST /auth/otp/verify-code',
                    action: 'Demander un nouveau code OTP.'
                },
                {
                    code: 'OTP_SMS_ALREADY_SENT',
                    message: 'Vous avez déjà utilisé la vérification par SMS au cours des dernières 24 heures.',
                    cause: 'L\'utilisateur a déjà reçu un OTP SMS pour une réinitialisation dans les dernières 24 heures.',
                    endpoint: 'POST /auth/forgot-password (option=sms)',
                    action: 'Utiliser un autre canal de récupération.'
                },
                {
                    code: 'EMAIL_INVALID',
                    message: 'Aucune adresse email disponible pour l\'envoi de l\'OTP.',
                    cause: 'L\'utilisateur n\'a pas d\'email associé à son compte.',
                    endpoint: 'POST /auth/forgot-password (option=email)',
                    action: 'Utiliser un autre canal.'
                },
                {
                    code: 'TELEPHONE_INVALID',
                    message: 'Numéro de téléphone invalide pour l\'envoi SMS.',
                    cause: 'Le numéro de téléphone est manquant ou ne comporte pas 10 chiffres.',
                    endpoint: 'POST /auth/forgot-password (option=sms)',
                    action: 'Utiliser un autre canal.'
                },
                {
                    code: 'WHATSAPP_NOT_CONFIGURED',
                    message: 'Le canal WhatsApp n\'est pas encore configuré.',
                    cause: 'Le service WhatsApp n\'est pas encore implémenté côté serveur.',
                    endpoint: 'POST /auth/forgot-password (option=whatsapp)',
                    action: 'Utiliser un autre canal.'
                },
                {
                    code: 'CHANNEL_INVALID',
                    message: 'Canal d\'envoi OTP invalide.',
                    cause: 'Le canal demandé n\'est pas supporté.',
                    endpoint: 'POST /auth/otp/send ou /auth/forgot-password',
                    action: 'Utiliser un canal valide.'
                },
                {
                    code: 'RECOVERY_CODE_INVALID',
                    message: 'Code de récupération invalide ou déjà utilisé.',
                    cause: 'Le code de récupération fourni est incorrect ou a déjà été utilisé.',
                    endpoint: 'POST /auth/2fa/verify-recovery',
                    action: 'Utiliser un autre code de récupération.'
                },
                {
                    code: 'RECOVERY_CODES_EXHAUSTED',
                    message: 'Plus de codes de récupération disponibles.',
                    cause: 'Tous les codes de récupération ont été utilisés.',
                    endpoint: 'POST /auth/2fa/recovery-codes',
                    action: 'Régénérer de nouveaux codes.'
                },
                {
                    code: '2FA_ALREADY_ENABLED',
                    message: 'La 2FA est déjà activée pour ce compte.',
                    cause: 'Tentative d\'activation de la 2FA alors qu\'elle est déjà active.',
                    endpoint: 'GET /auth/2fa/qrcode',
                    action: 'Désactiver la 2FA avant de la réactiver.'
                },
                {
                    code: '2FA_NOT_ENABLED',
                    message: 'La 2FA n\'est pas activée pour ce compte.',
                    cause: 'Tentative d\'utilisation de la 2FA alors qu\'elle n\'est pas activée.',
                    endpoint: 'POST /auth/2fa/verify',
                    action: 'Activer la 2FA via /auth/2fa/qrcode et /auth/2fa/confirm.'
                },
                {
                    code: 'OTP_SEND_FAILED',
                    message: 'Impossible d\'envoyer le code OTP.',
                    cause: 'Erreur lors de l\'envoi du code OTP (service indisponible).',
                    endpoint: 'POST /auth/otp/send',
                    action: 'Réessayer plus tard ou contacter le support.'
                },
                {
                    code: 'PARTNER_HAS_RESEAVX',
                    message: 'Ce partenaire a des réseaux associés et ne peut pas être supprimé.',
                    cause: 'Le partenaire est référencé dans la table reseaux.',
                    endpoint: 'DELETE /partners/{uuid_partner}',
                    action: 'Supprimer les réseaux associés avant de supprimer le partenaire.'
                },
                {
                    code: 'RESEAU_HAS_AGENCES',
                    message: 'Ce réseau a des agences associées et ne peut pas être supprimé.',
                    cause: 'Le réseau est référencé dans la table agences.',
                    endpoint: 'DELETE /reseaux/{uuid_reseau}',
                    action: 'Supprimer les agences associées avant de supprimer le réseau.'
                },
                {
                    code: 'AGENCE_HAS_USERS',
                    message: 'Cette agence est associée à des utilisateurs et ne peut pas être supprimée.',
                    cause: 'L\'agence est référencée dans la table user_agences.',
                    endpoint: 'DELETE /agences/{uuid_agence}',
                    action: 'Retirer les utilisateurs associés avant de supprimer l\'agence.'
                },
                {
                    code: 'GET_ALL_CONTRAT_SUCCESS',
                    message: 'Contrats récupérés avec succès.',
                    cause: 'La liste des contrats du client a été récupérée avec succès.',
                    endpoint: 'GET /espaces-client/contrats',
                    action: 'Aucune action requise.'
                },
                {
                    code: 'NO_CONTRAT_FOUND',
                    message: 'Aucun contrat actif trouvé.',
                    cause: 'Le client n\'a pas de contrats actifs ou tous ses contrats sont arrêtés (OnStdbyOff = 3).',
                    endpoint: 'GET /espaces-client/contrats',
                    action: 'Aucune action requise.'
                },
                {
                    code: 'GET_CONTRAT_ERROR',
                    message: 'Une erreur est survenue lors de la récupération des contrats.',
                    cause: 'Erreur technique lors de l\'appel au service externe (encaissement-bis).',
                    endpoint: 'GET /espaces-client/contrats',
                    action: 'Réessayer plus tard ou contacter le support.'
                },
                {
                    code: 'CONTRAT_DETAILS_FOUND',
                    message: 'Détails du contrat récupérés avec succès.',
                    cause: 'Les détails du contrat ont été récupérés avec succès.',
                    endpoint: 'GET /espaces-client/contrat-details/{contrat_id}',
                    action: 'Aucune action requise.'
                },
                {
                    code: 'CONTRAT_NOT_FOUND',
                    message: 'Contrat non trouvé ou non associé à cet utilisateur.',
                    cause: 'Le contrat n\'existe pas ou n\'appartient pas à l\'utilisateur authentifié.',
                    endpoint: 'GET /espaces-client/contrat-details/{contrat_id}',
                    action: 'Vérifier l\'ID du contrat ou contacter le support.'
                },
                {
                    code: 'CONTRACT_DETAILS_ERROR',
                    message: 'Une erreur est survenue lors de la récupération des détails du contrat.',
                    cause: 'Erreur technique lors de la récupération des détails du contrat (service externe indisponible).',
                    endpoint: 'GET /espaces-client/contrat-details/{contrat_id}',
                    action: 'Réessayer plus tard ou contacter le support.'
                },
                {
                    code: 'CONTRACT_NO_DETAILS',
                    message: 'Aucun détail trouvé pour ce contrat.',
                    cause: 'Le contrat existe mais n\'a pas de détails disponibles.',
                    endpoint: 'GET /espaces-client/contrat-details/{contrat_id}',
                    action: 'Contacter le support pour vérifier l\'intégrité du contrat.'
                },

                {
                    code: 'CONTRACT_ADDED',
                    message: 'Contract ajouté avec succès.',
                    cause: 'Le contrat a été ajouté avec succès au compte du client.',
                    endpoint: 'POST /espaces-client/add-new-contrats',
                    action: 'Aucune action requise.'
                },
                {
                    code: 'CONTRAT_ALREADY_EXISTS',
                    message: 'Le contrat est déjà associé à votre compte.',
                    cause: 'Le client tente d\'ajouter un contrat déjà associé à son compte.',
                    endpoint: 'POST /espaces-client/add-new-contrats',
                    action: 'Le contrat est déjà présent dans la liste des contrats du client.'
                },
                {
                    code: 'DATE_OF_BIRTH_MISMATCH',
                    message: 'La date de naissance ne correspond pas à celle enregistrée dans le contrat.',
                    cause: 'La date de naissance du client ne correspond pas à celle du contrat.',
                    endpoint: 'POST /espaces-client/add-new-contrats',
                    action: 'Vérifier la date de naissance ou contacter le support.'
                },
                {
                    code: 'CONTRACT_FROZEN',
                    message: 'Ce contrat est arrêté.',
                    cause: 'Le contrat est arrêté (OnStdbyOff = 3) et ne peut pas être ajouté.',
                    endpoint: 'POST /espaces-client/add-new-contrats',
                    action: 'Le contrat est arrêté, il ne peut pas être ajouté au compte.'
                },
                // Dans businessErrors
                {
                    code: 'FACTURE_FOUND',
                    message: 'Contrats avec facture impaymée récupérés',
                    cause: 'Les contrats avec factures impayées ont été récupérés avec succès.',
                    endpoint: 'GET /espaces-client/contrats-factures',
                    action: 'Aucune action requise.'
                },
                {
                    code: 'NO_FACTURE_FOUND',
                    message: 'Aucun contrat trouvé avec facture impayée.',
                    cause: 'Le client n\'a pas de contrats avec des factures impayées.',
                    endpoint: 'GET /espaces-client/contrats-factures',
                    action: 'Aucune action requise.'
                }
            ]
        };

        // ================================================================
        // 6. ÉTAT DE L'APPLICATION
        // ================================================================
        const AppState = {
            currentEnv: 'local',
            authToken: null,
            currentUser: null,
            isAuthenticated: false,
            selectedEndpoint: 'home',
            searchQuery: '',
            tryItOpen: {}
        };

        // ================================================================
        // 7. UTILITAIRES, CLIENT API, AUTH MANAGER, RENDERER, SEARCH, INIT
        // ================================================================

        const Utils = {
            sanitizeForDisplay(data) {
                if (!data) return data;
                try {
                    const str = JSON.stringify(data);
                    return str
                        .replace(/"token":"[^"]*"/g, '"token":"••••••••"')
                        .replace(/"password":"[^"]*"/g, '"password":"••••••••"')
                        .replace(/"secret":"[^"]*"/g, '"secret":"••••••••"')
                        .replace(/"code_plain":"[^"]*"/g, '"code_plain":"••••••••"')
                        .replace(/"access_token":"[^"]*"/g, '"access_token":"••••••••"')
                        .replace(/"reset_token":"[^"]*"/g, '"reset_token":"••••••••"');
                } catch (e) {
                    return data;
                }
            },

            generateId() {
                return Math.random().toString(36).substring(2, 10);
            },

            formatDate(date) {
                return new Date(date).toLocaleString('fr-FR');
            },

            getMethodBadgeClass(method) {
                const classes = {
                    'GET': 'get',
                    'POST': 'post',
                    'PUT': 'put',
                    'PATCH': 'patch',
                    'DELETE': 'delete'
                };
                return classes[method] || '';
            },

            getStatusBadgeClass(status) {
                if (status >= 200 && status < 300) return 'success';
                if (status >= 400 && status < 500) return 'warning';
                if (status >= 500) return 'error';
                return 'info';
            },

            escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            },

            truncate(text, maxLength = 60) {
                if (text.length <= maxLength) return text;
                return text.substring(0, maxLength) + '...';
            },

            showToast(message, type = 'info') {
                const toast = document.getElementById('liveToast');
                const title = document.getElementById('toastTitle');
                const body = document.getElementById('toastMessage');

                const icons = {
                    success: '✅',
                    danger: '❌',
                    warning: '⚠️',
                    info: 'ℹ️'
                };

                title.textContent = icons[type] || 'ℹ️';
                body.textContent = message;
                toast.className = `toast bg-${type} text-white`;

                const bsToast = new bootstrap.Toast(toast, {
                    delay: 4000
                });
                bsToast.show();
            }
        };

        // ================================================================
        // 8. CLIENT API
        // ================================================================
        const ApiClient = {
            getBaseUrl() {
                const env = API_DATA.environments[AppState.currentEnv];
                return env ? env.url : 'http://localhost:8000/api/v1';
            },

            getHeaders(extraHeaders = {}) {
                const headers = {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    ...extraHeaders
                };

                if (AppState.isAuthenticated && AppState.authToken) {
                    headers['Authorization'] = `Bearer ${AppState.authToken}`;
                }

                return headers;
            },

            async request(method, path, data = null, extraHeaders = {}) {
                const url = this.getBaseUrl() + path;
                const headers = this.getHeaders(extraHeaders);
                const options = {
                    method: method.toUpperCase(),
                    headers: headers
                };

                if (data && (method === 'POST' || method === 'PUT' || method === 'PATCH')) {
                    options.body = JSON.stringify(data);
                }

                const startTime = performance.now();

                try {
                    const response = await fetch(url, options);
                    const endTime = performance.now();
                    const responseTime = Math.round(endTime - startTime);

                    let responseData;
                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('application/json')) {
                        responseData = await response.json();
                    } else {
                        responseData = await response.text();
                    }

                    return {
                        ok: response.ok,
                        status: response.status,
                        statusText: response.statusText,
                        headers: response.headers,
                        data: responseData,
                        responseTime: responseTime
                    };
                } catch (error) {
                    const endTime = performance.now();
                    return {
                        ok: false,
                        status: 0,
                        statusText: 'Network Error',
                        data: {
                            error: error.message
                        },
                        responseTime: Math.round(endTime - startTime),
                        isNetworkError: true
                    };
                }
            },

            async login(login, password) {
                return this.request('POST', '/auth/login', {
                    login,
                    password
                });
            },

            async testEndpoint(endpoint, params) {
                let path = endpoint.path;

                if (params.path) {
                    for (const [key, value] of Object.entries(params.path)) {
                        path = path.replace(`{${key}}`, encodeURIComponent(value));
                    }
                }

                const queryParams = new URLSearchParams();
                if (params.query) {
                    for (const [key, value] of Object.entries(params.query)) {
                        if (value !== undefined && value !== null && value !== '') {
                            queryParams.append(key, value);
                        }
                    }
                }
                const queryString = queryParams.toString();
                if (queryString) {
                    path += '?' + queryString;
                }

                return this.request(
                    endpoint.method,
                    path,
                    params.body || null
                );
            }
        };

        // ================================================================
        // 9. GESTION DE L'AUTHENTIFICATION
        // ================================================================
        const AuthManager = {
            async login(login, password) {
                try {
                    const result = await ApiClient.login(login, password);

                    if (result.ok && result.data && result.data.data && result.data.data.access_token) {
                        this.setToken(result.data.data.access_token);
                        this.setUser(result.data.data.user || null);
                        return {
                            success: true,
                            data: result.data
                        };
                    }

                    if (result.data && result.data.code === '2FA_REQUIRED') {
                        return {
                            success: true,
                            data: result.data,
                            requires2fa: true
                        };
                    }
                    if (result.data && result.data.code === 'PASSWORD_CHANGE_REQUIRED') {
                        return {
                            success: true,
                            data: result.data,
                            requiresPasswordChange: true
                        };
                    }

                    return {
                        success: false,
                        message: result.data?.message || 'Erreur de connexion',
                        status: result.status
                    };
                } catch (error) {
                    return {
                        success: false,
                        message: error.message
                    };
                }
            },

            setToken(token) {
                AppState.authToken = token;
                AppState.isAuthenticated = true;
                this.updateUI();
            },

            setUser(user) {
                AppState.currentUser = user;
                this.updateUI();
            },

            logout() {
                AppState.authToken = null;
                AppState.currentUser = null;
                AppState.isAuthenticated = false;
                this.updateUI();
                Utils.showToast('Déconnexion réussie', 'info');
            },

            updateUI() {
                const statusText = document.getElementById('authStatusText');
                const statusDot = document.getElementById('statusDot');
                const authBtnText = document.getElementById('authBtnText');
                const logoutBtn = document.getElementById('authLogoutBtn');
                const submitBtn = document.getElementById('authSubmitBtn');

                if (AppState.isAuthenticated) {
                    statusText.textContent = AppState.currentUser?.email || 'Authentifié';
                    statusDot.className = 'status-dot online';
                    authBtnText.textContent = 'Connecté';
                    if (logoutBtn) logoutBtn.style.display = 'inline-block';
                    if (submitBtn) submitBtn.innerHTML = '<i class="fas fa-sync me-1"></i> Rafraîchir';
                } else {
                    statusText.textContent = 'Non authentifié';
                    statusDot.className = 'status-dot offline';
                    authBtnText.textContent = 'Se connecter';
                    if (logoutBtn) logoutBtn.style.display = 'none';
                    if (submitBtn) submitBtn.innerHTML = '<i class="fas fa-sign-in-alt me-1"></i> Se connecter';
                }
            }
        };

        // ================================================================
        // 10. RENDU DE LA DOCUMENTATION
        // ================================================================
        const Renderer = {
            renderSidebar() {
                const nav = document.getElementById('sidebarNav');
                const modules = API_DATA.modules;
                const endpoints = API_DATA.endpoints;

                const grouped = {};
                for (const ep of endpoints) {
                    if (!grouped[ep.module]) grouped[ep.module] = [];
                    grouped[ep.module].push(ep);
                }

                let html = '';

                html += `
                <a class="nav-item ${!AppState.selectedEndpoint || AppState.selectedEndpoint === 'home' ? 'active' : ''}" data-endpoint="home">
                    <i class="fas fa-house nav-icon"></i>
                    Accueil
                </a>
            `;

                for (const [moduleKey, moduleData] of Object.entries(modules)) {
                    const eps = grouped[moduleKey] || [];
                    if (eps.length === 0 || moduleKey === 'home') continue;

                    html += `<div class="nav-module">${moduleData.label}</div>`;

                    for (const ep of eps) {
                        if (ep.isHome) continue;
                        const isActive = AppState.selectedEndpoint === ep.id;
                        const methodClass = Utils.getMethodBadgeClass(ep.method);
                        const protectedIcon = ep.isProtected ? ' 🔒' : '';

                        html += `
                        <a class="nav-item ${isActive ? 'active' : ''}" data-endpoint="${ep.id}">
                            <i class="fas ${moduleData.icon} nav-icon"></i>
                            <span class="method-badge ${methodClass}">${ep.method || 'GET'}</span>
                            <span class="flex-grow-1">${ep.name}${protectedIcon}</span>
                        </a>
                    `;
                    }
                }

                nav.innerHTML = html;

                nav.querySelectorAll('.nav-item').forEach(el => {
                    el.addEventListener('click', function() {
                        const endpointId = this.dataset.endpoint;
                        AppState.selectedEndpoint = endpointId;
                        Renderer.renderContent();
                        Renderer.updateSidebarActive();
                        if (window.innerWidth < 992) {
                            document.getElementById('sidebar').classList.remove('show');
                            document.getElementById('sidebarOverlay').classList.remove('show');
                        }
                    });
                });
            },

            updateSidebarActive() {
                document.querySelectorAll('.nav-item').forEach(el => {
                    el.classList.toggle('active', el.dataset.endpoint === AppState.selectedEndpoint);
                });
            },

            renderContent() {
                const main = document.getElementById('mainContent');

                if (!AppState.selectedEndpoint || AppState.selectedEndpoint === 'home') {
                    main.innerHTML = this.renderHome();
                    return;
                }

                const endpoint = API_DATA.endpoints.find(e => e.id === AppState.selectedEndpoint);
                if (!endpoint) {
                    main.innerHTML = `<div class="alert alert-warning">Endpoint non trouvé.</div>`;
                    return;
                }

                if (endpoint.isHome) {
                    main.innerHTML = this.renderHome();
                    return;
                }

                main.innerHTML = this.renderEndpoint(endpoint);
            },

            renderHome() {
                const endpoints = API_DATA.endpoints.filter(e => !e.isHome);
                const protectedCount = endpoints.filter(e => e.isProtected).length;
                const totalCount = endpoints.length;

                return `
                <div class="fade-in">
                    <div class="home-hero">
                        <h1>YNOV API Documentation</h1>
                        <p>
                            Documentation technique officielle de l'API REST utilisée par l'application 
                            Front-Office YNOV de <strong>YAKO AFRICA Assurances Vie Côte d'Ivoire</strong>.
                        </p>
                        <div class="quick-links">
                            <button class="btn btn-light" onclick="Renderer.selectEndpoint('auth-login')">
                                <i class="fas fa-right-to-bracket me-1"></i> Authentification
                            </button>
                            <button class="btn btn-outline-light" onclick="Renderer.selectEndpoint('users-list')">
                                <i class="fas fa-users me-1"></i> Utilisateurs
                            </button>
                            <button class="btn btn-outline-light" onclick="Renderer.selectEndpoint('security-suggested')">
                                <i class="fas fa-question-circle me-1"></i> Questions de sécurité
                            </button>
                            <button class="btn btn-outline-light" onclick="document.getElementById('searchInput').focus()">
                                <i class="fas fa-search me-1"></i> Rechercher
                            </button>
                        </div>
                    </div>

                    <div class="home-stats">
                        <div class="stat-card">
                            <div class="stat-number">${totalCount}</div>
                            <div class="stat-label">Endpoints documentés</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">${protectedCount}</div>
                            <div class="stat-label">Endpoints protégés</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">${Object.keys(API_DATA.modules).length}</div>
                            <div class="stat-label">Modules</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">v1.0</div>
                            <div class="stat-label">Version API</div>
                        </div>
                    </div>

                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title"><i class="fas fa-rocket text-primary me-2"></i>Quick Start</h5>
                                    <p class="card-text">
                                        Pour commencer à utiliser l'API, consultez le guide d'authentification 
                                        et testez vos premiers endpoints.
                                    </p>
                                    <button class="btn btn-primary btn-sm" onclick="Renderer.selectEndpoint('auth-login')">
                                        <i class="fas fa-right-to-bracket me-1"></i> Tester la connexion
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title"><i class="fas fa-shield-halved text-danger me-2"></i>Sécurité</h5>
                                    <p class="card-text">
                                        L'API intègre des mécanismes de sécurité avancés : 
                                        gel de compte, 2FA, OTP, questions de sécurité et restrictions IP.
                                    </p>
                                    <button class="btn btn-outline-secondary btn-sm" onclick="Renderer.selectEndpoint('2fa-enable')">
                                        <i class="fas fa-shield-halved me-1"></i> Découvrir la 2FA
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Environnement actif :</strong> ${API_DATA.environments[AppState.currentEnv]?.label || 'Local'} 
                            (${ApiClient.getBaseUrl()})
                            ${AppState.isAuthenticated ? ' — <span class="text-success"><i class="fas fa-check-circle"></i> Authentifié</span>' : ' — <span class="text-warning"><i class="fas fa-circle"></i> Non authentifié</span>'}
                        </div>
                    </div>
                </div>
            `;
            },

            selectEndpoint(id) {
                AppState.selectedEndpoint = id;
                this.renderContent();
                this.updateSidebarActive();
                document.getElementById('mainContent').scrollIntoView({
                    behavior: 'smooth'
                });
            },

            renderEndpoint(endpoint) {
                const methodClass = Utils.getMethodBadgeClass(endpoint.method);
                const baseUrl = ApiClient.getBaseUrl();

                let pathParamsHtml = '';
                if (endpoint.requestParams?.path && Object.keys(endpoint.requestParams.path).length > 0) {
                    pathParamsHtml = this.renderParamTable(endpoint.requestParams.path, 'path');
                }

                let queryParamsHtml = '';
                if (endpoint.requestParams?.query && Object.keys(endpoint.requestParams.query).length > 0) {
                    queryParamsHtml = this.renderParamTable(endpoint.requestParams.query, 'query');
                }

                let bodyParamsHtml = '';
                let exampleRequestHtml = '';
                let invalidExampleHtml = '';
                if (endpoint.requestParams?.body && Object.keys(endpoint.requestParams.body).length > 0) {
                    bodyParamsHtml = this.renderParamTable(endpoint.requestParams.body, 'body');

                    if (endpoint.exampleRequest) {
                        exampleRequestHtml = `
                        <div class="section-title"><i class="fas fa-check-circle text-success"></i> Exemple de requête valide</div>
                        <div class="code-block">
                            <pre><code class="language-json">${JSON.stringify(endpoint.exampleRequest, null, 2)}</code></pre>
                            <button class="copy-btn" onclick="Renderer.copyCode(this)">Copier</button>
                        </div>
                    `;
                    }

                    if (endpoint.invalidExample) {
                        invalidExampleHtml = `
                        <div class="section-title"><i class="fas fa-times-circle text-danger"></i> Exemple invalide</div>
                        <div class="warning-box">
                            <i class="fas fa-info-circle me-1"></i>
                            <strong>Pourquoi cet exemple est invalide :</strong> ${endpoint.invalidReason || 'Non spécifié'}
                        </div>
                        <div class="code-block" style="border-color: #f1c3bc;">
                            <pre><code class="language-json">${JSON.stringify(endpoint.invalidExample, null, 2)}</code></pre>
                        </div>
                    `;
                    }
                }

                let responsesHtml = '';
                if (endpoint.responses && endpoint.responses.length > 0) {
                    responsesHtml = `
                    <div class="section-title"><i class="fas fa-reply"></i> Réponses</div>
                    <div class="table-responsive">
                        <table class="table doc-table">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Description</th>
                                    <th>Exemple</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${endpoint.responses.map(r => `
                                    <tr>
                                        <td><span class="badge bg-${Utils.getStatusBadgeClass(r.status)}">${r.status}</span></td>
                                        <td>${r.description}</td>
                                        <td>
                                            <div class="code-block" style="max-height:200px; overflow-y:auto;">
                                                <pre><code class="language-json">${JSON.stringify(r.example, null, 2)}</code></pre>
                                            </div>
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                `;
                }

                // ============================================================
    // WIDGET JEKO - RENDU SPÉCIFIQUE
    // ============================================================
    let widgetDemoHtml = '';
    if (endpoint.isWidgetDemo || endpoint.id === 'jeko-widget-demo') {
        // Rediriger vers la page de démonstration
        window.location.href = '/api/v1/demo-jeko-widget';
        return;
    }

    if (endpoint.hasWidgetInfo && endpoint.widgetInfo) {
        const features = endpoint.widgetInfo.features || [];
        const paymentTypes = endpoint.widgetInfo.paymentTypes || [];
        const docsLink = endpoint.widgetInfo.docsLink || '/api/v1/demo-jeko-widget';
        const widgetJs = endpoint.widgetInfo.widgetJs || '/api/v1/paiements/jeko/jeko-payment-widget.js';

        widgetDemoHtml = `
            <div class="section-title"><i class="fas fa-credit-card text-success"></i> Widget Jeko</div>
            
            <div style="background: linear-gradient(135deg, #f0f7f3, #e8f3ec); border-radius: 12px; padding: 20px 24px; margin-bottom: 16px; border: 1px solid #cde6db;">
                <div style="display: flex; flex-wrap: wrap; gap: 16px; align-items: flex-start;">
                    <div style="flex: 1; min-width: 200px;">
                        <h4 style="margin: 0 0 8px; color: #1D603D;">🚀 Intégration rapide</h4>
                        <p style="margin: 0 0 12px; font-size: 14px; color: #374151;">
                            Le widget Jeko s'intègre en quelques minutes dans votre application.
                            Il est autonome et ne nécessite aucune dépendance externe.
                        </p>
                        <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                            <a href="${docsLink}" target="_blank" style="display: inline-block; background: #1D603D; color: #fff; padding: 8px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px;">
                                <i class="fas fa-external-link-alt"></i> Voir la démo
                            </a>
                            <a href="${widgetJs}" target="_blank" style="display: inline-block; background: #fff; color: #1D603D; padding: 8px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; border: 1px solid #1D603D;">
                                <i class="fas fa-file-code"></i> Widget JS
                            </a>
                        </div>
                    </div>
                    <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                        ${paymentTypes.map(p => `
                            <div style="background: #fff; border-radius: 8px; padding: 12px 16px; min-width: 120px; border: 1px solid #dce8e0; text-align: center;">
                                <div style="font-size: 24px; margin-bottom: 4px;">${p.icon === 'fa-file-contract' ? '📄' : p.icon === 'fa-calendar-check' ? '📅' : '💰'}</div>
                                <div style="font-weight: 700; font-size: 12px; color: #1D603D;">${p.label}</div>
                                <code style="font-size: 10px; background: #f3f4f6; padding: 2px 6px; border-radius: 4px; display: block; margin-top: 4px;">${p.code}</code>
                            </div>
                        `).join('')}
                    </div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; margin: 12px 0 16px;">
                ${features.map(f => `
                    <div style="background: #f8faf9; border-radius: 8px; padding: 10px 14px; display: flex; align-items: center; gap: 8px; border: 1px solid #eef2f0;">
                        <span style="color: #1D603D; font-size: 16px;">✅</span>
                        <span style="font-size: 13px; color: #374151;">${f}</span>
                    </div>
                `).join('')}
            </div>

            <div style="background: #fef9e7; border-radius: 8px; padding: 14px 18px; border: 1px solid #fcd34d; margin-bottom: 16px;">
                <strong style="color: #92400e;">💡 Intégration dans votre projet :</strong>
                <div style="margin-top: 8px; font-size: 13px; color: #78350f;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                        <code style="background: #fff; padding: 2px 8px; border-radius: 4px; font-size: 12px;">&lt;script src="${widgetJs}"&gt;&lt;/script&gt;</code>
                        <span style="color: #6b7280; font-size: 12px;">— Ajoutez ceci dans votre HTML</span>
                    </div>
                    <div style="font-size: 12px; color: #6b7280;">
                        <a href="${docsLink}" target="_blank" style="color: #1D603D; font-weight: 600;">📖 Voir la documentation complète →</a>
                    </div>
                </div>
            </div>
        `;
    }

                const authBadges = [];
                if (endpoint.isProtected) {
                    authBadges.push(`<span class="badge bg-warning text-dark"><i class="fas fa-lock me-1"></i>Protégé</span>`);
                } else {
                    authBadges.push(`<span class="badge bg-success"><i class="fas fa-unlock me-1"></i>Public</span>`);
                }
                if (endpoint.permissionsRequired && endpoint.permissionsRequired.length > 0) {
                    authBadges.push(`<span class="badge bg-danger">Permission: ${endpoint.permissionsRequired.join(', ')}</span>`);
                }
                if (endpoint.abilityRequired) {
                    authBadges.push(`<span class="badge bg-info">Ability: ${endpoint.abilityRequired}</span>`);
                }
                if (endpoint.rateLimit) {
                    authBadges.push(`<span class="badge bg-secondary"><i class="fas fa-gauge-high me-1"></i>${endpoint.rateLimit}</span>`);
                }
                if (endpoint.isDestructive) {
                    authBadges.push(`<span class="badge bg-danger"><i class="fas fa-triangle-exclamation me-1"></i>Destructive</span>`);
                }

                const html = `
                <div class="fade-in">
                    <div class="page-header">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="#" onclick="Renderer.selectEndpoint('home'); return false;">Accueil</a></li>
                                <li class="breadcrumb-item active">${API_DATA.modules[endpoint.module]?.label || endpoint.module}</li>
                                <li class="breadcrumb-item active">${endpoint.name}</li>
                            </ol>
                        </nav>
                        <h1>${endpoint.name}</h1>
                    </div>

                    <div class="endpoint-card">
                        <div class="endpoint-header">
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <span class="endpoint-method ${methodClass}">${endpoint.method || 'GET'}</span>
                                <span class="endpoint-path">
                                    <span class="base-url">${baseUrl}</span>${endpoint.path}
                                </span>
                            </div>
                            <div class="endpoint-badges">
                                ${authBadges.join(' ')}
                            </div>
                        </div>
                        <div class="endpoint-body">
                            <div class="description">
                                <p>${endpoint.description}</p>
                            </div>

                            ${pathParamsHtml ? `
                                <div class="section-title"><i class="fas fa-link"></i> Paramètres du chemin</div>
                                ${pathParamsHtml}
                            ` : ''}

                            ${queryParamsHtml ? `
                                <div class="section-title"><i class="fas fa-search"></i> Paramètres de requête</div>
                                ${queryParamsHtml}
                            ` : ''}

                            ${bodyParamsHtml ? `
                                <div class="section-title"><i class="fas fa-file"></i> Corps de la requête</div>
                                ${bodyParamsHtml}
                            ` : ''}

                            ${exampleRequestHtml}
                            ${invalidExampleHtml}

                            ${responsesHtml}

                            <div class="try-it-section">
                                <button class="try-it-toggle" onclick="Renderer.toggleTryIt('${endpoint.id}')">
                                    <i class="fas fa-play"></i> Tester l'endpoint
                                </button>

                                <div class="try-it-panel ${AppState.tryItOpen[endpoint.id] ? 'show' : ''}" id="tryItPanel_${endpoint.id}">
                                    ${this.renderTryItForm(endpoint)}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

                setTimeout(() => {
                    if (typeof Prism !== 'undefined') {
                        Prism.highlightAll();
                    }
                }, 100);

                return html;
            },

            renderParamTable(params, type) {
                let html = `
                <div class="table-responsive">
                    <table class="table doc-table">
                        <thead>
                            <tr>
                                <th>Paramètre</th>
                                <th>Type</th>
                                <th>Requis</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

                for (const [key, value] of Object.entries(params)) {
                    const required = value.required ? '<span class="required-badge">Obligatoire</span>' : '<span class="optional-badge">Optionnel</span>';
                    const typeInfo = value.type || 'string';
                    let desc = value.description || '';
                    if (value.enum) {
                        desc += ` (valeurs: ${value.enum.join(', ')})`;
                    }
                    if (value.default !== undefined) {
                        desc += ` (défaut: ${value.default})`;
                    }
                    if (value.min !== undefined) {
                        desc += ` (min: ${value.min})`;
                    }
                    if (value.max !== undefined) {
                        desc += ` (max: ${value.max})`;
                    }
                    if (value.size !== undefined) {
                        desc += ` (taille: ${value.size})`;
                    }

                    html += `
                    <tr>
                        <td><code>${key}</code></td>
                        <td><code>${typeInfo}</code></td>
                        <td>${required}</td>
                        <td>${desc}</td>
                    </tr>
                `;
                }

                html += `
                        </tbody>
                    </table>
                </div>
            `;

                return html;
            },

            renderTryItForm(endpoint) {
                const endpointId = endpoint.id;

                let pathFields = '';
                if (endpoint.requestParams?.path) {
                    for (const [key, value] of Object.entries(endpoint.requestParams.path)) {
                        pathFields += `
                        <div class="row mb-2">
                            <div class="col-md-3"><label class="form-label"><code>{${key}}</code></label></div>
                            <div class="col-md-9">
                                <input type="text" class="form-control" id="try_${endpointId}_path_${key}" 
                                    placeholder="${value.description || key}" 
                                    ${value.required ? 'required' : ''}>
                            </div>
                        </div>
                    `;
                    }
                }

                let queryFields = '';
                if (endpoint.requestParams?.query) {
                    for (const [key, value] of Object.entries(endpoint.requestParams.query)) {
                        queryFields += `
                        <div class="row mb-2">
                            <div class="col-md-3"><label class="form-label">${key}</label></div>
                            <div class="col-md-9">
                                <input type="text" class="form-control" id="try_${endpointId}_query_${key}" 
                                    placeholder="${value.description || key}"
                                    ${value.required ? 'required' : ''}>
                            </div>
                        </div>
                    `;
                    }
                }

                let bodyField = '';
                if (endpoint.requestParams?.body && Object.keys(endpoint.requestParams.body).length > 0) {
                    let defaultBody = '{}';
                    if (endpoint.exampleRequest) {
                        defaultBody = JSON.stringify(endpoint.exampleRequest, null, 2);
                    } else {
                        const sampleBody = {};
                        for (const [key, value] of Object.entries(endpoint.requestParams.body)) {
                            sampleBody[key] = value.type === 'string' ? '' : null;
                        }
                        defaultBody = JSON.stringify(sampleBody, null, 2);
                    }
                    bodyField = `
                    <div class="form-group">
                        <label class="form-label">JSON Body</label>
                        <textarea class="form-control json-editor" id="try_${endpointId}_body" 
                                rows="6" spellcheck="false">${defaultBody}</textarea>
                        <small class="text-muted">Format JSON valide requis.</small>
                    </div>
                `;
                }

                let headersHtml = '';
                if (endpoint.headers) {
                    let headersObj = {
                        ...endpoint.headers
                    };
                    if (AppState.isAuthenticated && AppState.authToken) {
                        headersObj['Authorization'] = 'Bearer {token}';
                    }
                    headersHtml = `
                    <div class="form-group">
                        <label class="form-label">Headers</label>
                        <pre style="background:#f8f9fa; padding:8px 12px; border-radius:6px; font-size:0.85rem; margin:0;">${JSON.stringify(headersObj, null, 2)}</pre>
                    </div>
                `;
                }

                // Avertissement pour les actions destructives
                let destructiveWarning = '';
                if (endpoint.isDestructive) {
                    destructiveWarning = `
                    <div class="danger-box">
                        <i class="fas fa-triangle-exclamation me-1"></i>
                        <strong>⚠️ Action destructive :</strong> Cette opération peut modifier ou supprimer des données de manière irréversible.
                        Vérifiez l'environnement sélectionné avant de continuer.
                    </div>
                `;
                }

                return `
                <div class="try-it-form">
                    ${destructiveWarning}
                    ${headersHtml}

                    ${pathFields ? `
                        <div class="form-group">
                            <label class="form-label">Paramètres du chemin</label>
                            ${pathFields}
                        </div>
                    ` : ''}

                    ${queryFields ? `
                        <div class="form-group">
                            <label class="form-label">Paramètres de requête</label>
                            ${queryFields}
                        </div>
                    ` : ''}

                    ${bodyField}

                    <div class="d-flex gap-2 flex-wrap mt-3">
                        <button class="btn btn-primary send-btn" onclick="Renderer.sendTryIt('${endpointId}')">
                            <i class="fas fa-paper-plane me-1"></i> Envoyer
                        </button>
                        <button class="btn btn-outline-secondary" onclick="Renderer.clearTryIt('${endpointId}')">
                            <i class="fas fa-undo me-1"></i> Réinitialiser
                        </button>
                        ${endpoint.exampleRequest ? `
                            <button class="btn btn-outline-info" onclick="Renderer.loadExample('${endpointId}')">
                                <i class="fas fa-file me-1"></i> Charger l'exemple
                            </button>
                        ` : ''}
                    </div>

                    <div class="response-viewer" id="responseViewer_${endpointId}">
                        <div class="response-meta" id="responseMeta_${endpointId}"></div>
                        <div class="response-body" id="responseBody_${endpointId}">
                            <pre><code class="language-json">${JSON.stringify({ message: 'En attente de requête...' }, null, 2)}</code></pre>
                        </div>
                    </div>

                    <div class="spinner-overlay" id="spinner_${endpointId}">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                    </div>
                </div>
            `;
            },

            toggleTryIt(endpointId) {
                AppState.tryItOpen[endpointId] = !AppState.tryItOpen[endpointId];
                const panel = document.getElementById(`tryItPanel_${endpointId}`);
                if (panel) {
                    panel.classList.toggle('show');
                }
                this.renderContent();
            },

            async sendTryIt(endpointId) {
                const endpoint = API_DATA.endpoints.find(e => e.id === endpointId);
                if (!endpoint) return;

                const spinner = document.getElementById(`spinner_${endpointId}`);
                const viewer = document.getElementById(`responseViewer_${endpointId}`);
                const meta = document.getElementById(`responseMeta_${endpointId}`);
                const body = document.getElementById(`responseBody_${endpointId}`);

                if (spinner) spinner.classList.add('show');
                if (viewer) viewer.classList.remove('show');

                const params = {
                    path: {},
                    query: {},
                    body: null
                };

                if (endpoint.requestParams?.path) {
                    for (const [key] of Object.entries(endpoint.requestParams.path)) {
                        const input = document.getElementById(`try_${endpointId}_path_${key}`);
                        if (input && input.value) {
                            params.path[key] = input.value;
                        }
                    }
                }

                if (endpoint.requestParams?.query) {
                    for (const [key] of Object.entries(endpoint.requestParams.query)) {
                        const input = document.getElementById(`try_${endpointId}_query_${key}`);
                        if (input && input.value) {
                            params.query[key] = input.value;
                        }
                    }
                }

                if (endpoint.requestParams?.body) {
                    const bodyInput = document.getElementById(`try_${endpointId}_body`);
                    if (bodyInput && bodyInput.value) {
                        try {
                            params.body = JSON.parse(bodyInput.value);
                        } catch (e) {
                            if (spinner) spinner.classList.remove('show');
                            if (viewer) viewer.classList.add('show');
                            if (meta) {
                                meta.innerHTML = `
                                <span class="meta-item">
                                    <span class="label">Erreur:</span>
                                    <span class="text-danger">JSON invalide</span>
                                </span>
                            `;
                            }
                            if (body) {
                                body.innerHTML = `
                                <pre><code>${JSON.stringify({ error: 'Format JSON invalide', details: e.message }, null, 2)}</code></pre>
                            `;
                            }
                            return;
                        }
                    }
                }

                try {
                    const result = await ApiClient.testEndpoint(endpoint, params);

                    if (spinner) spinner.classList.remove('show');
                    if (viewer) viewer.classList.add('show');

                    const statusClass = result.ok ? 'success' : (result.isNetworkError ? 'warning' : 'error');
                    const statusText = result.ok ? `${result.status} ${result.statusText}` : (result.isNetworkError ? '⚠️ Erreur réseau' : `${result.status} ${result.statusText}`);

                    if (meta) {
                        meta.innerHTML = `
                        <span class="meta-item">
                            <span class="label">Statut:</span>
                            <span class="status-badge ${statusClass}">${statusText}</span>
                        </span>
                        <span class="meta-item">
                            <span class="label">Temps:</span>
                            <span>${result.responseTime} ms</span>
                        </span>
                        <span class="meta-item">
                            <span class="label">URL:</span>
                            <code style="font-size:0.8rem; word-break:break-all;">${ApiClient.getBaseUrl() + endpoint.path}</code>
                        </span>
                        <span class="meta-item">
                            <span class="label">Méthode:</span>
                            <strong>${endpoint.method}</strong>
                        </span>
                    `;
                    }

                    if (body) {
                        const sanitized = Utils.sanitizeForDisplay(result.data);
                        let displayData = sanitized;
                        try {
                            const parsed = JSON.parse(sanitized);
                            displayData = JSON.stringify(parsed, null, 2);
                        } catch (e) {
                            displayData = sanitized;
                        }

                        body.innerHTML = `<pre><code class="language-json">${displayData}</code></pre>`;

                        setTimeout(() => {
                            if (typeof Prism !== 'undefined') {
                                Prism.highlightElement(body.querySelector('code'));
                            }
                        }, 50);
                    }

                    if (!result.ok) {
                        const msg = result.data?.message || result.statusText || 'Erreur lors de la requête';
                        Utils.showToast(`❌ ${msg}`, 'danger');
                    } else {
                        Utils.showToast('✅ Requête réussie', 'success');
                    }

                } catch (error) {
                    if (spinner) spinner.classList.remove('show');
                    if (viewer) viewer.classList.add('show');
                    if (body) {
                        body.innerHTML = `
                        <pre><code>${JSON.stringify({ error: 'Erreur lors de la requête', details: error.message }, null, 2)}</code></pre>
                    `;
                    }
                    Utils.showToast('❌ Erreur: ' + error.message, 'danger');
                }
            },

            clearTryIt(endpointId) {
                const endpoint = API_DATA.endpoints.find(e => e.id === endpointId);
                if (!endpoint) return;

                if (endpoint.requestParams?.path) {
                    for (const [key] of Object.entries(endpoint.requestParams.path)) {
                        const input = document.getElementById(`try_${endpointId}_path_${key}`);
                        if (input) input.value = '';
                    }
                }
                if (endpoint.requestParams?.query) {
                    for (const [key] of Object.entries(endpoint.requestParams.query)) {
                        const input = document.getElementById(`try_${endpointId}_query_${key}`);
                        if (input) input.value = '';
                    }
                }
                if (endpoint.requestParams?.body) {
                    const input = document.getElementById(`try_${endpointId}_body`);
                    if (input) {
                        let defaultBody = '{}';
                        if (endpoint.exampleRequest) {
                            defaultBody = JSON.stringify(endpoint.exampleRequest, null, 2);
                        }
                        input.value = defaultBody;
                    }
                }

                const viewer = document.getElementById(`responseViewer_${endpointId}`);
                if (viewer) viewer.classList.remove('show');

                Utils.showToast('Formulaire réinitialisé', 'info');
            },

            loadExample(endpointId) {
                const endpoint = API_DATA.endpoints.find(e => e.id === endpointId);
                if (!endpoint || !endpoint.exampleRequest) return;

                if (endpoint.requestParams?.body) {
                    const input = document.getElementById(`try_${endpointId}_body`);
                    if (input) {
                        input.value = JSON.stringify(endpoint.exampleRequest, null, 2);
                    }
                }

                Utils.showToast('Exemple chargé', 'success');
            },

            copyCode(btn) {
                const pre = btn.parentElement.querySelector('pre');
                const code = pre ? pre.textContent : '';
                navigator.clipboard.writeText(code).then(() => {
                    btn.textContent = 'Copié !';
                    setTimeout(() => {
                        btn.textContent = 'Copier';
                    }, 2000);
                });
            }
        };

        // ================================================================
        // 11. RECHERCHE
        // ================================================================
        const SearchManager = {
            filter(query) {
                AppState.searchQuery = query.toLowerCase().trim();

                const navItems = document.querySelectorAll('.nav-item[data-endpoint]');
                const endpoints = API_DATA.endpoints;

                navItems.forEach(el => {
                    const epId = el.dataset.endpoint;
                    const ep = endpoints.find(e => e.id === epId);
                    if (!ep || ep.isHome) {
                        el.style.display = '';
                        return;
                    }

                    const searchable = [
                        ep.name,
                        ep.path,
                        ep.method,
                        ep.description,
                        ep.module,
                        ...(ep.permissionsRequired || [])
                    ].join(' ').toLowerCase();

                    const match = !AppState.searchQuery || searchable.includes(AppState.searchQuery);
                    el.style.display = match ? '' : 'none';
                });
            }
        };

        // ================================================================
        // 12. INITIALISATION
        // ================================================================
        document.addEventListener('DOMContentLoaded', function() {
            AppState.selectedEndpoint = 'home';

            Renderer.renderSidebar();
            Renderer.renderContent();

            document.getElementById('envSelector').addEventListener('change', function() {
                AppState.currentEnv = this.value;
                Renderer.renderContent();
                Utils.showToast(`Environnement : ${API_DATA.environments[this.value]?.label || this.value}`, 'info');
            });

            document.getElementById('searchInput').addEventListener('input', function() {
                SearchManager.filter(this.value);
            });

            document.getElementById('sidebarToggle').addEventListener('click', function() {
                document.getElementById('sidebar').classList.toggle('show');
                document.getElementById('sidebarOverlay').classList.toggle('show');
            });

            document.getElementById('sidebarOverlay').addEventListener('click', function() {
                document.getElementById('sidebar').classList.remove('show');
                this.classList.remove('show');
            });

            document.getElementById('authForm').addEventListener('submit', async function(e) {
                e.preventDefault();
                const login = document.getElementById('authLogin').value;
                const password = document.getElementById('authPassword').value;
                const statusDiv = document.getElementById('authFormStatus');

                if (!login || !password) {
                    statusDiv.className = 'alert alert-danger';
                    statusDiv.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>Veuillez remplir tous les champs.';
                    return;
                }

                statusDiv.className = 'alert alert-info';
                statusDiv.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Connexion en cours...';

                const result = await AuthManager.login(login, password);

                if (result.success) {
                    if (result.requires2fa) {
                        statusDiv.className = 'alert alert-warning';
                        statusDiv.innerHTML = `
                        <i class="fas fa-shield-halved me-2"></i>
                        Une vérification 2FA est requise. Utilisez l'endpoint /auth/2fa/verify-login.
                    `;
                        if (result.data?.data?.two_factor_token) {
                            AuthManager.setToken(result.data.data.two_factor_token);
                        }
                    } else if (result.requiresPasswordChange) {
                        statusDiv.className = 'alert alert-warning';
                        statusDiv.innerHTML = `
                        <i class="fas fa-key me-2"></i>
                        Un changement de mot de passe est requis. Utilisez l'endpoint /auth/first-login.
                    `;
                        if (result.data?.data?.change_password_token) {
                            AuthManager.setToken(result.data.data.change_password_token);
                        }
                    } else {
                        statusDiv.className = 'alert alert-success';
                        statusDiv.innerHTML = '<i class="fas fa-check-circle me-2"></i>Authentification réussie !';
                        AuthManager.updateUI();
                        setTimeout(() => {
                            const modal = bootstrap.Modal.getInstance(document.getElementById('authModal'));
                            if (modal) modal.hide();
                            Renderer.renderContent();
                        }, 1500);
                    }
                } else {
                    statusDiv.className = 'alert alert-danger';
                    statusDiv.innerHTML = `<i class="fas fa-exclamation-circle me-2"></i>${result.message || 'Erreur de connexion'}`;
                }
            });

            document.getElementById('authLogoutBtn').addEventListener('click', function() {
                AuthManager.logout();
                Renderer.renderContent();
                const modal = bootstrap.Modal.getInstance(document.getElementById('authModal'));
                if (modal) modal.hide();
            });

            document.getElementById('authBtn').addEventListener('click', function(e) {
                if (AppState.isAuthenticated) {
                    e.preventDefault();
                    document.getElementById('authFormStatus').className = 'alert alert-success';
                    document.getElementById('authFormStatus').innerHTML = `
                    <i class="fas fa-check-circle me-2"></i>
                    Connecté en tant que ${AppState.currentUser?.email || 'utilisateur'}
                `;
                    document.getElementById('authLogin').style.display = 'none';
                    document.getElementById('authPassword').style.display = 'none';
                    document.querySelector('#authForm label[for="authLogin"]').style.display = 'none';
                    document.querySelector('#authForm label[for="authPassword"]').style.display = 'none';
                    document.getElementById('authSubmitBtn').style.display = 'none';
                    document.getElementById('authLogoutBtn').style.display = 'inline-block';
                } else {
                    document.getElementById('authLogin').style.display = '';
                    document.getElementById('authPassword').style.display = '';
                    document.querySelector('#authForm label[for="authLogin"]').style.display = '';
                    document.querySelector('#authForm label[for="authPassword"]').style.display = '';
                    document.getElementById('authSubmitBtn').style.display = '';
                    document.getElementById('authLogoutBtn').style.display = 'none';
                    document.getElementById('authFormStatus').className = 'alert alert-info';
                    document.getElementById('authFormStatus').innerHTML = `
                    <i class="fas fa-info-circle me-2"></i>
                    Connectez-vous pour tester les endpoints protégés.
                `;
                    document.getElementById('authLogin').value = '';
                    document.getElementById('authPassword').value = '';
                }
            });

            AuthManager.updateUI();

            setTimeout(() => {
                Utils.showToast('Bienvenue sur la documentation interactive YNOV API 🚀', 'info');
            }, 500);
        });

        window.Renderer = Renderer;
        window.Utils = Utils;
        window.AuthManager = AuthManager;
        window.AppState = AppState;

})();