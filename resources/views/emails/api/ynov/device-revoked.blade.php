{{-- resources/views/emails/api/ynov/device-revoked.blade.php --}}
@extends('emails.api.ynov.layouts.base')

@php
    $bannerColor = '#096835';
    $bannerText = '🔒 Appareil retiré';
@endphp

@section('content')
    {{-- En-tête --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td style="padding-bottom:16px;">
                <span style="font-size:24px; font-weight:700; color:#096835;">🔒 Appareil retiré</span>
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
                    L'appareil suivant a été <strong style="color:#c9372c;">retiré</strong> de la liste des appareils de confiance 
                    de votre compte YNOV. Il devra repasser par la vérification (2FA/OTP) lors de sa prochaine connexion.
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
                                <strong>Action requise :</strong> Si vous n'êtes pas à l'origine de cette action, 
                                contactez immédiatement le support YNOV.
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
            '📅 Retiré le' => $revokedAt ?? now()->format('d/m/Y à H:i'),
            '🔒 Statut' => '⛔ Révoqué',
        ]
    ])

    {{-- Message d'action --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td style="padding:8px 0 4px 0;">
                <span style="font-size:14px; color:#475569; line-height:1.7;">
                    Pour des raisons de sécurité, cet appareil devra être réapprouvé lors de sa prochaine connexion.
                    Si vous reconnaissez cette action, aucune démarche supplémentaire n'est requise.
                </span>
            </td>
        </tr>
    </table>

    {{-- Bouton d'action --}}
    @include('emails.api.ynov.partials.button', [
        'url' => $securityUrl ?? config('app.frontend_url'),
        'label' => '🔒 Voir mes appareils',
        'color' => '#096835',
        'hoverColor' => '#06471f'
    ])

    {{-- Message de sécurité --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:16px; background-color:#e8f5ee; border-radius:6px; padding:12px 16px;">
        <tr>
            <td>
                <span style="font-size:13px; color:#06471f; line-height:1.6;">
                    <strong style="color:#096835;">🔐 Pour votre sécurité :</strong> 
                    Surveillez régulièrement la liste de vos appareils et sessions actives.
                </span>
            </td>
        </tr>
    </table>

    {{-- Contact support --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td style="padding:12px 0 0 0;">
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