{{-- resources/views/emails/api/ynov/welcome.blade.php --}}
@extends('emails.api.ynov.layouts.base')

@php
    $bannerColor = '#096835';
    $bannerText = '👋 Bienvenue sur YNOV';
@endphp

@section('content')
    {{-- En-tête --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td style="padding-bottom:16px;">
                <span style="font-size:24px; font-weight:700; color:#096835;">👋 Bienvenue sur YNOV</span>
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
                    Votre compte YNOV vient d'être <strong style="color:#096835;">créé</strong> avec succès. 
                    Vous pouvez dès à présent accéder à la plateforme avec les identifiants ci-dessous.
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
                            <span style="font-size:18px; color:#F7A400;">🔑</span>
                        </td>
                        <td>
                            <span style="font-size:13px; color:#7c5a00; line-height:1.6;">
                                <strong>Première connexion :</strong> Pour des raisons de sécurité, 
                                vous devrez <strong style="color:#096835;">définir votre propre mot de passe</strong> 
                                dès votre première connexion.
                            </span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Identifiants de connexion --}}
    @include('emails.api.ynov.partials.info-table', [
        'rows' => [
            '👤 Login' => $login ?? 'Non défini',
            '🔑 Mot de passe temporaire' => $password ?? '—',
            '🔒 Statut' => '🔄 Première connexion requise',
        ]
    ])

    {{-- Instructions --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td style="padding:8px 0 4px 0;">
                <span style="font-size:14px; color:#475569; line-height:1.7;">
                    <strong style="color:#096835;">Prochaines étapes :</strong>
                </span>
            </td>
        </tr>
        <tr>
            <td style="padding-left:16px; padding-bottom:12px;">
                <table role="presentation" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="padding-bottom:6px; font-size:13px; color:#475569; line-height:1.5;">
                            1. 🔑 Connectez-vous avec vos identifiants temporaires
                        </td>
                    </tr>
                    <tr>
                        <td style="padding-bottom:6px; font-size:13px; color:#475569; line-height:1.5;">
                            2. 🔐 Définissez un nouveau mot de passe sécurisé
                        </td>
                    </tr>
                    <tr>
                        <td style="padding-bottom:6px; font-size:13px; color:#475569; line-height:1.5;">
                            3. 🔒 Activez la double authentification (2FA) pour renforcer la sécurité
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size:13px; color:#475569; line-height:1.5;">
                            4. 📱 Vérifiez vos informations personnelles
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Bouton d'action --}}
    @include('emails.api.ynov.partials.button', [
        'url' => $loginUrl ?? config('app.frontend_url') . '/',
        'label' => '🚀 Accéder à YNOV',
        'color' => '#096835',
        'hoverColor' => '#06471f'
    ])

    {{-- Lien alternatif --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:12px 0 16px 0;">
        <tr>
            <td style="font-size:12px; color:#94a3b8; line-height:1.6;">
                <span style="color:#096835;">🔗</span> 
                Lien d'accès : 
                <a href="{{ $loginUrl ?? config('app.frontend_url') . '/' }}" style="color:#096835; text-decoration:underline;">
                    {{ $loginUrl ?? config('app.frontend_url') . '/' }}
                </a>
            </td>
        </tr>
    </table>

    {{-- Conseils de sécurité --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:16px 0; background-color:#e8f5ee; border-radius:6px; padding:12px 16px;">
        <tr>
            <td>
                <span style="font-size:13px; color:#06471f; line-height:1.6;">
                    <strong style="color:#096835;">💡 Recommandations de sécurité :</strong>
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
                            🔐 Activez la double authentification (2FA) pour une protection renforcée
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

    {{-- Message de support --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td style="padding:8px 0 0 0;">
                <span style="font-size:13px; color:#64748b; line-height:1.6;">
                    <i class="bi bi-shield-check" style="margin-right:6px;"></i>
                    Ce lien est valable pour votre première connexion. 
                    Si vous rencontrez un problème, contactez votre administrateur ou le support YNOV.
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
                YNOV — YAKO AFRICA Assurances Vie | Bienvenue
            </td>
        </tr>
    </table>
@endsection