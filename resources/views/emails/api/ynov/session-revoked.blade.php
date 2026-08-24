{{-- resources/views/emails/api/ynov/session-revoked.blade.php --}}
@extends('emails.api.ynov.layouts.base')

@php
    $bannerColor = '#096835';
    $bannerText = $allSessions ? '🔒 Déconnexion générale' : '🔒 Session révoquée';
@endphp

@section('content')
    {{-- En-tête --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td style="padding-bottom:16px;">
                <span style="font-size:24px; font-weight:700; color:#096835;">
                    {{ $allSessions ? '🔒 Déconnexion générale' : '🔒 Session révoquée' }}
                </span>
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
        @if($allSessions)
            <tr>
                <td style="padding-bottom:16px;">
                    <span style="font-size:14px; color:#475569; line-height:1.7;">
                        Toutes les sessions actives de votre compte YNOV ont été 
                        <strong style="color:#c9372c;">déconnectées</strong>. 
                        Vous devrez vous reconnecter sur chacun de vos appareils.
                    </span>
                </td>
            </tr>
        @else
            <tr>
                <td style="padding-bottom:16px;">
                    <span style="font-size:14px; color:#475569; line-height:1.7;">
                        Une session a été <strong style="color:#c9372c;">révoquée</strong> sur votre compte YNOV.
                        Vous avez été déconnecté de cet appareil.
                    </span>
                </td>
            </tr>
        @endif
    </table>

    {{-- Avertissement sécurité (si toutes les sessions) --}}
    @if($allSessions)
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
                                    <strong>Action :</strong> Toutes vos sessions ont été révoquées. 
                                    Si vous n'êtes pas à l'origine de cette action, contactez immédiatement le support.
                                </span>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    @endif

    {{-- Informations de la session révoquée (si une seule session) --}}
    @if(!$allSessions)
        @include('emails.api.ynov.partials.info-table', [
            'rows' => [
                '📱 Session révoquée' => $sessionName ?? 'Session inconnue',
                '📅 Date de révocation' => $revokedAt ?? now()->format('d/m/Y à H:i:s'),
                '🔒 Statut' => '⛔ Révoquée',
            ]
        ])
    @else
        @include('emails.api.ynov.partials.info-table', [
            'rows' => [
                '📅 Date de déconnexion' => $revokedAt ?? now()->format('d/m/Y à H:i:s'),
                '📱 Nombre de sessions' => 'Toutes les sessions',
                '🔒 Statut' => '⛔ Déconnecté',
            ]
        ])
    @endif

    {{-- Message d'action --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td style="padding:8px 0 4px 0;">
                <span style="font-size:14px; color:#475569; line-height:1.7;">
                    @if($allSessions)
                        <strong style="color:#096835;">Que faire ?</strong>
                    @else
                        <strong style="color:#096835;">Que faire si ce n'était pas vous ?</strong>
                    @endif
                </span>
            </td>
        </tr>
        <tr>
            <td style="padding-left:16px; padding-bottom:12px;">
                <table role="presentation" cellpadding="0" cellspacing="0">
                    @if(!$allSessions)
                        <tr>
                            <td style="padding-bottom:6px; font-size:13px; color:#475569; line-height:1.5;">
                                🔑 Si vous reconnaissez cette action, aucune démarche n'est requise.
                            </td>
                        </tr>
                    @endif
                    <tr>
                        <td style="padding-bottom:6px; font-size:13px; color:#475569; line-height:1.5;">
                            🔒 Activez la double authentification (2FA) pour une protection renforcée
                        </td>
                    </tr>
                    <tr>
                        <td style="padding-bottom:6px; font-size:13px; color:#475569; line-height:1.5;">
                            📱 Vérifiez régulièrement vos sessions actives
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
                    'label' => '🔒 Voir mes sessions',
                    'color' => '#096835',
                    'hoverColor' => '#06471f'
                ])
            </td>
            <td>
                @include('emails.api.ynov.partials.button', [
                    'url' => config('app.frontend_url'),
                    'label' => '🔐 Sécuriser mon compte',
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
                    <strong style="color:#096835;">💡 Pour renforcer la sécurité de votre compte :</strong>
                </span>
            </td>
        </tr>
        <tr>
            <td style="padding-left:16px; padding-top:4px;">
                <table role="presentation" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="padding-bottom:4px; font-size:13px; color:#06471f; line-height:1.5;">
                            🔐 Activez la double authentification (2FA) pour une protection renforcée
                        </td>
                    </tr>
                    <tr>
                        <td style="padding-bottom:4px; font-size:13px; color:#06471f; line-height:1.5;">
                            📱 Vérifiez régulièrement vos sessions et appareils connectés
                        </td>
                    </tr>
                    <tr>
                        <td style="padding-bottom:4px; font-size:13px; color:#06471f; line-height:1.5;">
                            🔑 Utilisez un mot de passe unique et complexe
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size:13px; color:#06471f; line-height:1.5;">
                            🚪 Déconnectez-vous des appareils que vous n'utilisez plus
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
                <span style="font-size:13px; color:#64748b; line-height:1.6;">
                    <i class="bi bi-shield-check" style="margin-right:6px;"></i>
                    Si vous avez des questions, contactez le support : 
                    <a href="mailto:support@ynov.ci" style="color:#096835; font-weight:600; text-decoration:none;">
                        support@ynov.ci
                    </a>
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