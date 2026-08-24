{{-- resources/views/emails/api/ynov/otp.blade.php --}}
@extends('emails.api.ynov.layouts.base')

@section('content')
    @php
        $bannerColor = match ($purpose) {
            'login' => '#096835',
            '2fa' => '#F7A400',
            'reset' => '#c9372c',
            default => '#096835',
        };
        $bannerText = match ($purpose) {
            'login' => '🔐 Code de connexion OTP',
            '2fa' => '🔐 Code de double authentification',
            'reset' => '🔐 Code de réinitialisation',
            default => '🔐 Code de vérification',
        };
        
        // Couleurs des badges selon l'usage
        $badgeColor = match ($purpose) {
            'login' => '#096835',
            '2fa' => '#F7A400',
            'reset' => '#c9372c',
            default => '#096835',
        };
        
        $purposeEmoji = match ($purpose) {
            'login' => '🔑',
            '2fa' => '🛡️',
            'reset' => '🔄',
            default => '🔐',
        };
    @endphp

    {{-- En-tête --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td style="padding-bottom:16px;">
                <span style="font-size:24px; font-weight:700; color:#096835;">
                    {{ $purposeEmoji }} Code de vérification
                </span>
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
                    Vous avez demandé un code de vérification pour 
                    <strong style="color:#096835;">{{ $purposeLabel }}</strong>.
                    Voici votre code à usage unique :
                </span>
            </td>
        </tr>
    </table>

    {{-- Code OTP --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:16px 0;">
        <tr>
            <td style="background-color:#f8fafc; border:2px dashed #096835; border-radius:12px; padding:24px; text-align:center;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="padding-bottom:4px;">
                            <span style="font-size:13px; color:#94a3b8; letter-spacing:2px; text-transform:uppercase;">Votre code</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:8px 0;">
                            <span style="font-size:38px; font-weight:800; color:#096835; letter-spacing:10px; font-family: 'Courier New', monospace; background-color:#e8f5ee; padding:8px 24px; border-radius:8px; display:inline-block;">
                                {{ $code }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding-top:4px;">
                            <span style="font-size:12px; color:#94a3b8;">
                                ⏱️ Valable {{ $expiresInMinutes }} minute(s)
                            </span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Informations complémentaires --}}
    @include('emails.api.ynov.partials.info-table', [
        'rows' => [
            '📌 Usage' => $purposeEmoji . ' ' . ucfirst($purpose),
            '⏱️ Expiration' => $expiresInMinutes . ' minute(s)',
            '📅 Date' => now()->format('d/m/Y à H:i:s'),
            '🔒 Sécurité' => '🔐 À usage unique',
        ]
    ])

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
                                <strong>Ne partagez jamais ce code avec personne.</strong>
                                Ce code expire dans <strong style="color:#F7A400;">{{ $expiresInMinutes }} minutes</strong>.
                                Si vous n'êtes pas à l'origine de cette demande, ignorez cet email.
                            </span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Message d'action --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td style="padding:8px 0 4px 0;">
                <span style="font-size:14px; color:#475569; line-height:1.7;">
                    Si vous n'avez pas demandé ce code, veuillez 
                    <strong style="color:#c9372c;">contacter immédiatement</strong> 
                    l'administrateur ou le support YNOV.
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

    {{-- Message de sécurité --}}
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