{{-- resources/views/emails/api/ynov/two-factor-enabled.blade.php --}}
@extends('emails.api.ynov.layouts.base')

@php
    $bannerColor = '#096835';
    $bannerText = '🔐 Authentification à deux facteurs activée';
@endphp

@section('content')
    {{-- En-tête --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td style="padding-bottom:16px;">
                <span style="font-size:24px; font-weight:700; color:#096835;">🔐 2FA activée</span>
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
                    L'authentification à deux facteurs (2FA) vient d'être 
                    <strong style="color:#096835;">activée</strong> sur votre compte YNOV. 
                    Votre compte est désormais protégé par une double vérification.
                </span>
            </td>
        </tr>
    </table>

    {{-- Information de sécurité --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:8px 0 16px 0;">
        <tr>
            <td style="background-color:#e8f5ee; border-left:4px solid #096835; padding:12px 16px; border-radius:4px;">
                <table role="presentation" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="vertical-align:middle; padding-right:10px;">
                            <span style="font-size:18px; color:#096835;">🔐</span>
                        </td>
                        <td>
                            <span style="font-size:13px; color:#06471f; line-height:1.6;">
                                <strong>Protection renforcée :</strong> Désormais, chaque connexion nécessitera 
                                <strong style="color:#096835;">votre mot de passe</strong> et 
                                <strong style="color:#F7A400;">un code de vérification</strong> 
                                depuis votre application d'authentification.
                            </span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Codes de récupération --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:16px 0;">
        <tr>
            <td style="padding-bottom:8px;">
                <span style="font-size:14px; font-weight:600; color:#096835;">📋 Vos codes de récupération</span>
            </td>
        </tr>
        <tr>
            <td>
                <span style="font-size:13px; color:#475569; line-height:1.6;">
                    Conservez ces codes dans un endroit <strong style="color:#c9372c;">sûr et sécurisé</strong>. 
                    Chaque code ne peut être utilisé qu'une seule fois. 
                    Ils vous permettront de récupérer l'accès à votre compte si vous perdez votre appareil.
                </span>
            </td>
        </tr>
    </table>

    {{-- Affichage des codes de récupération --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f8fafc; border-radius:8px; margin:12px 0; border:1px solid #e2e8f0;">
        <tr>
            <td style="padding:16px 20px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                    @php
                        $chunks = array_chunk($recoveryCodes, 2);
                    @endphp
                    @foreach($chunks as $pair)
                    <tr>
                        <td style="padding:6px 8px; font-family:'Courier New', monospace; font-size:14px; font-weight:600; color:#0f172a; width:50%; border-bottom:1px solid #f1f5f9;">
                            <span style="background-color:#e8f5ee; padding:2px 10px; border-radius:4px; display:inline-block;">
                                {{ $pair[0] ?? '' }}
                            </span>
                        </td>
                        <td style="padding:6px 8px; font-family:'Courier New', monospace; font-size:14px; font-weight:600; color:#0f172a; width:50%; border-bottom:1px solid #f1f5f9;">
                            @if(isset($pair[1]))
                                <span style="background-color:#e8f5ee; padding:2px 10px; border-radius:4px; display:inline-block;">
                                    {{ $pair[1] }}
                                </span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </table>
            </td>
        </tr>
    </table>

    {{-- Avertissement important --}}
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
                                <strong>Important :</strong> 
                                <span style="color:#c9372c;">Sans ces codes, vous ne pourrez pas récupérer votre compte</span> 
                                si vous perdez l'accès à votre application d'authentification.
                                <br>
                                <span style="color:#096835; font-weight:600;">Ne les partagez jamais avec personne.</span>
                            </span>
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
                    'url' => config('app.frontend_url') . '/profile/security',
                    'label' => '🔐 Gérer ma sécurité',
                    'color' => '#096835',
                    'hoverColor' => '#06471f'
                ])
            </td>
            <td>
                @include('emails.api.ynov.partials.button', [
                    'url' => config('app.frontend_url') . '/profile/security',
                    'label' => '📋 Voir mes codes',
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
                    <strong style="color:#096835;">💡 Recommandations de sécurité :</strong>
                </span>
            </td>
        </tr>
        <tr>
            <td style="padding-left:16px; padding-top:4px;">
                <table role="presentation" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="padding-bottom:4px; font-size:13px; color:#06471f; line-height:1.5;">
                            📱 Sauvegardez vos codes de récupération dans un gestionnaire de mots de passe sécurisé
                        </td>
                    </tr>
                    <tr>
                        <td style="padding-bottom:4px; font-size:13px; color:#06471f; line-height:1.5;">
                            🔐 Régénérez vos codes si vous pensez qu'ils ont été compromis
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size:13px; color:#06471f; line-height:1.5;">
                            📧 Ne partagez jamais vos codes de récupération par email ou SMS
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
                    Si vous n'êtes pas à l'origine de cette activation, 
                    <strong style="color:#c9372c;">contactez immédiatement le support YNOV</strong>.
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