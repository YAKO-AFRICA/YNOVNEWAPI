{{-- resources/views/emails/api/ynov/account-unfrozen.blade.php --}}
@extends('emails.api.ynov.layouts.base')

@php
    $bannerColor = '#0e8a47';
    $bannerText = '✅ Compte dégelé';
@endphp

@section('content')
    {{-- En-tête --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td style="padding-bottom:16px;">
                <span style="font-size:24px; font-weight:700; color:#096835;">✅ Compte dégelé</span>
            </td>
        </tr>
        <tr>
            <td style="padding-bottom:4px;">
                <span style="font-size:16px; font-weight:600; color:#0f172a;">Bonjour <strong style="color:#096835;">{{ $name ?? 'Utilisateur' }}</strong>,</span>
            </td>
        </tr>
    </table>

    {{-- Message principal --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td style="padding-bottom:16px;">
                <span style="font-size:14px; color:#475569; line-height:1.7;">
                    Nous vous informons que votre compte YNOV a été <strong style="color:#096835;">dégelé</strong> avec succès.
                    Vous pouvez désormais vous reconnecter à la plateforme en toute sécurité.
                </span>
            </td>
        </tr>
    </table>

    {{-- Carte d'information sur le dégel --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:8px 0 16px 0; background-color:#e8f5ee; border-left:4px solid #096835; border-radius:4px; padding:12px 16px;">
        <tr>
            <td style="padding-bottom:6px;">
                <span style="font-size:14px; font-weight:600; color:#06471f;">📝 Motif du dégel :</span>
            </td>
        </tr>
        <tr>
            <td style="padding-bottom:12px;">
                <span style="font-size:14px; color:#06471f; line-height:1.6;">
                    {{ $reason ?? 'Dégel manuel par administrateur' }}
                </span>
            </td>
        </tr>
        @isset($adminName)
        <tr>
            <td style="padding-top:8px; border-top:1px solid rgba(9,104,53,0.15);">
                <span style="font-size:14px; font-weight:600; color:#06471f;">👤 Effectué par :</span>
                <span style="font-size:14px; color:#06471f; margin-left:4px;">
                    {{ $adminName }}
                </span>
            </td>
        </tr>
        @endisset
    </table>

    {{-- Informations complémentaires --}}
    @include('emails.api.ynov.partials.info-table', [
        'rows' => [
            '📅 Date du dégel' => now()->format('d/m/Y à H:i:s'),
            '🔓 Statut du compte' => '✅ Actif',
            '🔑 Accès' => '📱 Tous les services disponibles',
        ]
    ])

    {{-- Message d'action --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td style="padding:8px 0 4px 0;">
                <span style="font-size:14px; color:#475569; line-height:1.7;">
                    Vous pouvez dès maintenant vous reconnecter à votre compte.
                    Nous vous recommandons de vérifier votre sécurité et de changer votre mot de passe
                    si vous pensez que votre compte a été compromis.
                </span>
            </td>
        </tr>
    </table>

    {{-- Boutons d'action --}}
    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:16px 0 8px 0;">
        <tr>
            <td style="padding-right:12px;">
                @include('emails.api.ynov.partials.button', [
                    'url' => config('app.frontend_url') . '/',
                    'label' => '🔑 Se connecter',
                    'color' => '#096835',
                    'hoverColor' => '#06471f'
                ])
            </td>
            <td>
                @include('emails.api.ynov.partials.button', [
                    'url' => config('app.frontend_url') . '/profile/security',
                    'label' => '🔒 Sécuriser mon compte',
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
                            🔑 Utilisez un mot de passe unique et complexe
                        </td>
                    </tr>
                    <tr>
                        <td style="padding-bottom:4px; font-size:13px; color:#06471f; line-height:1.5;">
                            🔐 Activez la double authentification (2FA)
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
            <td style="padding:8px 0 4px 0;">
                <span style="font-size:13px; color:#64748b; line-height:1.6;">
                    <i class="bi bi-shield-check" style="margin-right:6px;"></i>
                    Si vous n'êtes pas à l'origine de cette demande ou si vous avez des questions,
                    veuillez contacter immédiatement votre administrateur ou le support YNOV.
                </span>
            </td>
        </tr>
        <tr>
            <td style="padding-top:4px;">
                <span style="font-size:13px; color:#64748b; line-height:1.6;">
                    📧 <a href="mailto:support@ynov.ci" style="color:#096835; font-weight:600; text-decoration:none;">
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