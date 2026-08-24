{{-- resources/views/emails/api/ynov/password-reset.blade.php --}}
@extends('emails.api.ynov.layouts.base')

@php
    $bannerColor = '#096835';
    $bannerText = '🔑 Réinitialisation de mot de passe';
@endphp

@section('content')
    {{-- En-tête --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td style="padding-bottom:16px;">
                <span style="font-size:24px; font-weight:700; color:#096835;">🔑 Réinitialisation de mot de passe</span>
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
                    Vous avez demandé la <strong style="color:#096835;">réinitialisation</strong> de votre mot de passe YNOV.
                    Cliquez sur le bouton ci-dessous pour définir un nouveau mot de passe.
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
                                <strong>Lien de réinitialisation :</strong> Ce lien expire dans 
                                <strong style="color:#F7A400;">{{ $expiresInMinutes }} minutes</strong>.
                                Pour des raisons de sécurité, ce lien est à usage unique.
                            </span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Bouton d'action --}}
    @include('emails.api.ynov.partials.button', [
        'url' => $resetUrl,
        'label' => '🔑 Réinitialiser mon mot de passe',
        'color' => '#096835',
        'hoverColor' => '#06471f'
    ])

    {{-- Lien alternatif --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:12px 0 16px 0;">
        <tr>
            <td style="font-size:12px; color:#94a3b8; line-height:1.6;">
                <span style="color:#096835;">🔗</span> 
                Si le bouton ne fonctionne pas, copiez ce lien dans votre navigateur :
            </td>
        </tr>
        <tr>
            <td style="background-color:#f8fafc; padding:10px 14px; border-radius:4px; border:1px solid #e2e8f0; word-break:break-all; margin-top:4px;">
                <span style="font-size:12px; color:#64748b; font-family:monospace;">
                    {{ $resetUrl }}
                </span>
            </td>
        </tr>
    </table>

    {{-- Instructions --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td style="padding:8px 0 4px 0;">
                <span style="font-size:14px; color:#475569; line-height:1.7;">
                    <strong style="color:#096835;">Pour votre nouveau mot de passe, assurez-vous qu'il respecte les critères suivants :</strong>
                </span>
            </td>
        </tr>
        <tr>
            <td style="padding-left:16px; padding-bottom:12px;">
                <table role="presentation" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="padding-bottom:4px; font-size:13px; color:#475569; line-height:1.5;">
                            ✅ Minimum 12 caractères
                        </td>
                    </tr>
                    <tr>
                        <td style="padding-bottom:4px; font-size:13px; color:#475569; line-height:1.5;">
                            ✅ Au moins une lettre majuscule et une minuscule
                        </td>
                    </tr>
                    <tr>
                        <td style="padding-bottom:4px; font-size:13px; color:#475569; line-height:1.5;">
                            ✅ Au moins un chiffre
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size:13px; color:#475569; line-height:1.5;">
                            ✅ Au moins un symbole (+, *, #, etc.)
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Message de sécurité --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:8px; border-top:1px solid #e2e8f0; padding-top:16px;">
        <tr>
            <td style="padding:8px 0 4px 0;">
                <span style="font-size:13px; color:#94a3b8; line-height:1.6;">
                    <i class="bi bi-shield-check" style="margin-right:6px;"></i>
                    Si vous n'êtes pas à l'origine de cette demande, ignorez cet email : 
                    votre mot de passe restera inchangé. Par sécurité, ne partagez jamais ce lien.
                </span>
            </td>
        </tr>
        <tr>
            <td style="padding-top:4px;">
                <span style="font-size:13px; color:#94a3b8; line-height:1.6;">
                    📧 Besoin d'aide ? Contactez le support : 
                    <a href="mailto:support@ynov.ci" style="color:#096835; font-weight:600; text-decoration:none;">
                        support@ynov.ci
                    </a>
                </span>
            </td>
        </tr>
    </table>

    {{-- Note de sécurité --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:16px; border-top:1px solid #e2e8f0; padding-top:20px;">
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