{{-- resources/views/emails/api/ynov/account-freeze-warning.blade.php --}}
@extends('emails.api.ynov.layouts.base')

@php
    $bannerColor = '#F7A400';
    $bannerText = '⚠️ Activité suspecte détectée';
@endphp

@section('content')
    {{-- En-tête avec salutation --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td style="padding-bottom:16px;">
                <span style="font-size:24px; font-weight:700; color:#096835;">⚠️ Alerte de sécurité</span>
            </td>
        </tr>
        <tr>
            <td style="padding-bottom:4px;">
                <span style="font-size:16px; font-weight:600; color:#0f172a;">Bonjour <strong style="color:#096835;">{{ $name }}</strong>,</span>
            </td>
        </tr>
    </table>

    {{-- Message principal --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td style="padding-bottom:16px;">
                <span style="font-size:14px; color:#475569; line-height:1.7;">
                    Nous avons détecté <strong style="color:#c9372c;">{{ $attemptCount }}</strong> tentative(s) de connexion échouée(s) 
                    sur votre compte YNOV. Il vous reste <strong style="color:#F7A400;">{{ $remainingAttempts }}</strong> tentative(s) 
                    avant un gel temporaire plus long de votre compte.
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
                                <strong>Attention :</strong> Après {{ $remainingAttempts }} tentative(s) supplémentaire(s), 
                                votre compte sera temporairement gelé.
                            </span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Informations de sécurité --}}
    @include('emails.api.ynov.partials.info-table', [
        'rows' => [
            'Niveau d\'alerte' => $level ?? 'Modéré',
            'Tentatives échouées' => $attemptCount,
            'Tentatives restantes' => $remainingAttempts,
            'Statut' => '⚠️ Surveillance active',
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
                            ✅ Si c'est <strong>vous</strong> qui tentez de vous connecter, vérifiez votre mot de passe.
                        </td>
                    </tr>
                    <tr>
                        <td style="padding-bottom:6px; font-size:13px; color:#475569; line-height:1.5;">
                            🔒 Si ce n'est <strong>pas vous</strong>, sécurisez votre compte immédiatement.
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size:13px; color:#475569; line-height:1.5;">
                            📧 Contactez le support YNOV pour toute assistance : 
                            <a href="mailto:support@ynov.ci" style="color:#096835; font-weight:600; text-decoration:none;">
                                support@ynov.ci
                            </a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Bouton d'action --}}
    @include('emails.api.ynov.partials.button', [
        'url' => $securityUrl ?? config('app.frontend_url'),
        'label' => '🔒 Vérifier la sécurité de mon compte',
        'color' => '#F7A400',
        'hoverColor' => '#d68b00'
    ])

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