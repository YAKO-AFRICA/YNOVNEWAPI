{{-- resources/views/emails/api/ynov/account-unblocked.blade.php --}}
@extends('emails.api.ynov.layouts.base')

@php
    $bannerColor = '#0e8a47';
    $bannerText = '✅ Compte débloqué';
@endphp

@section('content')
    {{-- En-tête avec salutation --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td style="padding-bottom:16px;">
                <span style="font-size:24px; font-weight:700; color:#096835;">✅ Compte débloqué</span>
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
                    Bonne nouvelle : votre compte YNOV vient d'être <strong style="color:#096835;">débloqué</strong>.
                    Vous pouvez désormais vous reconnecter normalement à la plateforme.
                </span>
            </td>
        </tr>
    </table>

    {{-- Informations du déblocage --}}
    @include('emails.api.ynov.partials.info-table', [
        'rows' => [
            'Date de déblocage' => $unblockedAt ?? now()->format('d/m/Y à H:i'),
            'Débloqué par' => $unblockedBy ?? 'Administrateur',
            'Statut' => '✅ Actif',
        ]
    ])

    {{-- Bouton de connexion --}}
    @include('emails.api.ynov.partials.button', [
        'url' => $loginUrl ?? config('app.frontend_url'),
        'label' => '🔑 Se connecter',
        'color' => '#F7A400',
        'hoverColor' => '#d68b00'
    ])

    {{-- Message de sécurité --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:24px; border-top:1px solid #e2e8f0; padding-top:20px;">
        <tr>
            <td style="font-size:12px; color:#94a3b8; line-height:1.6; text-align:center;">
                <span style="color:#096835;">●</span> 
                Si vous rencontrez des difficultés pour vous connecter, contactez le support YNOV.
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