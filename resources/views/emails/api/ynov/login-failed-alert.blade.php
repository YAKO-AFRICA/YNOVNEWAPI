{{-- resources/views/emails/api/ynov/login-failed-alert.blade.php --}}
@extends('emails.api.ynov.layouts.base')

@php
    $bannerColor = '#F7A400';
    $bannerText = '⚠️ Tentatives de connexion échouées';
@endphp

@section('content')
    {{-- En-tête --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td style="padding-bottom:16px;">
                <span style="font-size:24px; font-weight:700; color:#096835;">⚠️ Alerte de sécurité</span>
            </td>
        </tr>
        <tr>
            <td style="padding-bottom:4px;">
                <span style="font-size:16px; font-weight:600; color:#0f172a;">Bonjour <strong style="color:#096835;">{{ $fullName }}</strong>,</span>
            </td>
        </tr>
    </table>

    {{-- Message principal --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td style="padding-bottom:16px;">
                <span style="font-size:14px; color:#475569; line-height:1.7;">
                    <strong style="color:#c9372c;">{{ $attemptsCount }}</strong> tentative(s) de connexion consécutives 
                    ont échoué sur votre compte YNOV.
                </span>
            </td>
        </tr>
    </table>

    {{-- Avertissement sécurité --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:8px 0 16px 0;">
        <tr>
            <td style="background-color:#fff6e5; border-left:4px solid #F7A400; padding:12px 16px; border-radius:4px;">
                <table role="presentation" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="vertical-align:middle; padding-right:10px;">
                            <span style="font-size:18px; color:#F7A400;">⚠️</span>
                        </td>
                        <td>
                            <span style="font-size:13px; color:#7c5a00; line-height:1.6;">
                                <strong>Action recommandée :</strong> Si vous n'êtes pas à l'origine de ces tentatives, 
                                nous vous recommandons de <strong style="color:#c9372c;">changer votre mot de passe</strong> 
                                dès que possible.
                            </span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Informations de connexion --}}
    @include('emails.api.ynov.partials.info-table', [
        'rows' => [
            '🔑 Nombre de tentatives' => $attemptsCount . ' tentative(s)',
            '🌐 Adresse IP' => $ipAddress ?? '—',
            '📍 Localisation' => $location ?? 'Non déterminée',
            '📅 Date' => $attemptedAt ?? now()->format('d/m/Y à H:i:s'),
            '🔒 Statut' => '⚠️ Échec de connexion',
        ]
    ])

    {{-- Message d'action --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td style="padding:8px 0 4px 0;">
                <span style="font-size:14px; color:#475569; line-height:1.7;">
                    <strong style="color:#096835;">Si ce n'était pas vous :</strong>
                </span>
            </td>
        </tr>
        <tr>
            <td style="padding-left:16px; padding-bottom:12px;">
                <table role="presentation" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="padding-bottom:6px; font-size:13px; color:#475569; line-height:1.5;">
                            🔐 Changez immédiatement votre mot de passe
                        </td>
                    </tr>
                    <tr>
                        <td style="padding-bottom:6px; font-size:13px; color:#475569; line-height:1.5;">
                            🔒 Activez la double authentification (2FA)
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size:13px; color:#475569; line-height:1.5;">
                            📧 Contactez le support YNOV : 
                            <a href="mailto:support@ynov.ci" style="color:#096835; font-weight:600; text-decoration:none;">
                                support@ynov.ci
                            </a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Boutons d'action --}}
    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:16px 0 8px 0;">
        <tr>
            <td style="padding-right:12px;">
                @include('emails.api.ynov.partials.button', [
                    'url' => $securityUrl ?? config('app.frontend_url'),
                    'label' => '🔒 Sécuriser mon compte',
                    'color' => '#096835',
                    'hoverColor' => '#06471f'
                ])
            </td>
            <td>
                @include('emails.api.ynov.partials.button', [
                    'url' => config('app.frontend_url'),
                    'label' => '🔑 Changer mon mot de passe',
                    'color' => '#F7A400',
                    'hoverColor' => '#d68b00'
                ])
            </td>
        </tr>
    </table>

    {{-- Conseils de sécurité --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:16px 0; background-color:#e8f5ee; border-radius:6px; padding:12px 16px;">
        <tr>
            <td>
                <span style="font-size:13px; color:#06471f; line-height:1.6;">
                    <strong style="color:#096835;">💡 Conseils pour protéger votre compte :</strong>
                </span>
            </td>
        </tr>
        <tr>
            <td style="padding-left:16px; padding-top:4px;">
                <table role="presentation" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="padding-bottom:4px; font-size:13px; color:#06471f; line-height:1.5;">
                            🔑 Utilisez un mot de passe unique d'au moins 12 caractères
                        </td>
                    </tr>
                    <tr>
                        <td style="padding-bottom:4px; font-size:13px; color:#06471f; line-height:1.5;">
                            🔐 Activez la double authentification (2FA) pour une sécurité renforcée
                        </td>
                    </tr>
                    <tr>
                        <td style="padding-bottom:4px; font-size:13px; color:#06471f; line-height:1.5;">
                            📱 Vérifiez régulièrement vos sessions et appareils connectés
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size:13px; color:#06471f; line-height:1.5;">
                            📧 Ne partagez jamais vos identifiants de connexion
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Message de sécurité --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td style="padding:8px 0 0 0;">
                <span style="font-size:13px; color:#94a3b8; line-height:1.6;">
                    <i class="bi bi-shield-check" style="margin-right:6px;"></i>
                    Si vous reconnaissez ces tentatives (mot de passe oublié par exemple), 
                    aucune action supplémentaire n'est nécessaire.
                </span>
            </td>
        </tr>
    </table>

    {{-- Note de sécurité --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:24px; border-top:1px solid #e2e8f0; padding-top:20px;">
        <tr>
            <td style="font-size:12px; color:#94a3b8; line-height:1.6; text-align:center;">
                <span style="color:#096835;">●</span> 
                Cet email a été envoyé automatiquement par la plateforme YNOV.
                <span style="color:#096835;">●</span>
            </td>
        </tr>
        <tr>
            <td style="font-size:11px; color:#cbd5e1; text-align:center; padding-top:4px;">
                YNOV — YAKO AFRICA Assurances Vie | Sécurité des comptes
            </td>
        </tr>
    </table>
@endsection