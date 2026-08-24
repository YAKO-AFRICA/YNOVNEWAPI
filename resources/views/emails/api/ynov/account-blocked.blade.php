{{-- resources/views/emails/api/ynov/account-blocked.blade.php --}}
@extends('emails.api.ynov.layouts.base')

@php
    $bannerColor = '#c9372c';
    $bannerText = '⚠️ Compte bloqué';
@endphp

@section('content')
    {{-- En-tête avec salutation --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td style="padding-bottom:16px;">
                <span style="font-size:24px; font-weight:700; color:#096835;">🔒 Sécurité YNOV</span>
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
                    Votre compte YNOV a été <strong style="color:#c9372c;">bloqué</strong> par un administrateur. 
                    Vous ne pouvez plus vous connecter à la plateforme jusqu'à nouvel ordre.
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
                                <strong>Action requise :</strong> Contactez votre administrateur pour plus d'informations.
                            </span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Informations du blocage --}}
    @include('emails.api.ynov.partials.info-table', [
        'rows' => [
            'Motif du blocage' => $reason ?? 'Non spécifié',
            'Date de blocage' => $blockedAt ?? now()->format('d/m/Y à H:i'),
            'Bloqué par' => $blockedBy ?? 'Administrateur',
            'Statut' => '🔒 Bloqué',
        ]
    ])

    {{-- Message de support --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td style="padding:16px 0 8px 0;">
                <span style="font-size:14px; color:#475569; line-height:1.7;">
                    Si vous pensez qu'il s'agit d'une erreur, veuillez contacter votre administrateur 
                    ou le support YNOV à l'adresse suivante :
                </span>
            </td>
        </tr>
        <tr>
            <td style="padding-bottom:16px;">
                <a href="mailto:support@ynov.ci" style="color:#096835; font-weight:600; text-decoration:none; font-size:14px;">
                    📧 support@ynov.ci
                </a>
            </td>
        </tr>
    </table>

    {{-- Bouton d'action --}}
    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:16px 0 24px 0;">
        <tr>
            <td style="border-radius:6px; background-color:#096835;">
                <a href="{{ config('app.frontend_url') ?? '#' }}" 
                   target="_blank"
                   style="display:inline-block; padding:12px 28px; font-size:14px; font-weight:600; color:#ffffff; text-decoration:none; border-radius:6px; background-color:#096835;">
                   🏠 Accéder à la plateforme
                </a>
            </td>
        </tr>
    </table>

    {{-- Note de sécurité --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid #e2e8f0; padding-top:20px; margin-top:8px;">
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