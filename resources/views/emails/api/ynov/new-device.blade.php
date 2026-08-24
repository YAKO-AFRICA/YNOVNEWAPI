{{-- resources/views/emails/api/ynov/new-device.blade.php --}}
@extends('emails.api.ynov.layouts.base')

@php
    $bannerColor = '#096835';
    $bannerText = '🖥️ Nouvelle connexion détectée';
@endphp

@section('content')
    {{-- En-tête --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td style="padding-bottom:16px;">
                <span style="font-size:24px; font-weight:700; color:#096835;">🖥️ Nouvelle connexion</span>
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
                    Une connexion à votre compte YNOV vient d'être effectuée depuis un 
                    <strong style="color:#096835;">appareil non reconnu</strong>.
                </span>
            </td>
        </tr>
    </table>

    {{-- Information de sécurité --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:8px 0 16px 0;">
        <tr>
            <td style="background-color:#fff6e5; border-left:4px solid #F7A400; padding:12px 16px; border-radius:4px;">
                <table role="presentation" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="vertical-align:middle; padding-right:10px;">
                            <span style="font-size:18px; color:#F7A400;">🔔</span>
                        </td>
                        <td>
                            <span style="font-size:13px; color:#7c5a00; line-height:1.6;">
                                <strong>Vérification nécessaire :</strong> Si vous ne reconnaissez pas cette connexion, 
                                <strong style="color:#c9372c;">changez immédiatement votre mot de passe</strong> 
                                et contactez le support YNOV.
                            </span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Informations de l'appareil --}}
    @include('emails.api.ynov.partials.info-table', [
        'rows' => [
            '📱 Appareil' => $deviceName ?? 'Appareil inconnu',
            '🌐 Navigateur / OS' => $browserOs ?? '—',
            '🌍 Adresse IP' => $ipAddress ?? '—',
            '📍 Localisation' => $location ?? 'Non déterminée',
            '📅 Date de connexion' => $loginAt ?? now()->format('d/m/Y à H:i:s'),
            '🔒 Statut' => '🆕 Nouvel appareil',
        ]
    ])

    {{-- Message d'action --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td style="padding:8px 0 4px 0;">
                <span style="font-size:14px; color:#475569; line-height:1.7;">
                    <strong style="color:#096835;">Que faire ?</strong>
                </span>
            </td>
        </tr>
        <tr>
            <td style="padding-left:16px; padding-bottom:12px;">
                <table role="presentation" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="padding-bottom:6px; font-size:13px; color:#475569; line-height:1.5;">
                            ✅ Si c'est <strong>vous</strong>, aucune action n'est nécessaire.
                            Cet appareil a été ajouté à votre liste.
                        </td>
                    </tr>
                    <tr>
                        <td style="padding-bottom:6px; font-size:13px; color:#475569; line-height:1.5;">
                            ❌ Si ce n'est <strong>pas vous</strong>, changez immédiatement votre mot de passe.
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
                    'url' => $securityUrl ?? config('app.frontend_url') . '/profile/devices',
                    'label' => '🔒 Voir mes appareils',
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
                    <strong style="color:#096835;">💡 Pour renforcer la sécurité de votre compte :</strong>
                </span>
            </td>
        </tr>
        <tr>
            <td style="padding-left:16px; padding-top:4px;">
                <table role="presentation" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="padding-bottom:4px; font-size:13px; color:#06471f; line-height:1.5;">
                            🔐 Activez la double authentification (2FA)
                        </td>
                    </tr>
                    <tr>
                        <td style="padding-bottom:4px; font-size:13px; color:#06471f; line-height:1.5;">
                            📱 Vérifiez régulièrement vos appareils connectés
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
                <span style="font-size:13px; color:#94a3b8; line-height:1.6;">
                    <i class="bi bi-shield-check" style="margin-right:6px;"></i>
                    Vous recevez cet email car un nouvel appareil s'est connecté à votre compte YNOV.
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