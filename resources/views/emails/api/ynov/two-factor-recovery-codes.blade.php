{{-- resources/views/emails/api/ynov/two-factor-recovery-codes.blade.php --}}
@extends('emails.api.ynov.layouts.base')

@section('content')
    {{-- En-tête --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td style="padding-bottom:16px;">
                <span style="font-size:24px; font-weight:700; color:#096835;">🔐 Codes de récupération 2FA</span>
            </td>
        </tr>
        <tr>
            <td style="padding-bottom:4px;">
                <span style="font-size:14px; color:#1e293b;">Bonjour <strong>{{ $name }}</strong>,</span>
            </td>
        </tr>
        <tr>
            <td style="padding-bottom:16px;">
                <span style="font-size:14px; color:#475569; line-height:1.7;">
                    Voici vos <strong style="color:#096835;">codes de récupération</strong> pour la 
                    double authentification (2FA) de votre compte YNOV.
                </span>
            </td>
        </tr>
        <tr>
            <td style="padding-bottom:8px;">
                <div style="background-color:#f0f7f2; border-left:4px solid #F7A400; padding:12px 16px; border-radius:4px;">
                    <span style="font-size:13px; color:#475569; line-height:1.6;">
                        <strong>⚠️ Important :</strong> Ces codes sont à usage unique. 
                        Conservez-les dans un endroit sécurisé. 
                        Chaque code ne peut être utilisé qu'une seule fois.
                    </span>
                </div>
            </td>
        </tr>
    </table>

    {{-- Liste des codes de récupération --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:20px 0; background-color:#ffffff; border-radius:8px; border:1px solid #e2e8f0;">
        <thead>
            <tr>
                <td style="padding:12px 20px; background-color:#f8fafc; border-bottom:2px solid #e2e8f0; font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px;">
                    # Code de récupération
                </td>
            </tr>
        </thead>
        <tbody>
            @foreach($recoveryCodes as $index => $code)
            <tr>
                <td style="padding:10px 20px; border-bottom:1px solid #f1f5f9; font-family: 'Courier New', monospace; font-size:14px; font-weight:600; color:#0f172a; letter-spacing:1px;">
                    <span style="display:inline-block; background-color:#f8fafc; padding:4px 12px; border-radius:4px; border:1px solid #e2e8f0;">
                        {{ $index + 1 }}. {{ $code }}
                    </span>
                </td>
            </tr>
            @endforeach
            <tr>
                <td style="padding:10px 20px; background-color:#f8fafc; font-size:12px; color:#94a3b8; text-align:center;">
                    {{ $count }} code(s) de récupération disponibles
                </td>
            </tr>
        </tbody>
    </table>

    {{-- Instructions --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:16px 0;">
        <tr>
            <td style="padding-bottom:8px;">
                <span style="font-size:14px; font-weight:600; color:#096835;">📋 Comment utiliser vos codes de récupération</span>
            </td>
        </tr>
        <tr>
            <td style="padding-left:16px; padding-bottom:16px;">
                <table role="presentation" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="padding-bottom:6px; font-size:13px; color:#475569; line-height:1.5;">
                            1. <strong>Conservez</strong> ces codes dans un endroit sûr (hors ligne de préférence).
                        </td>
                    </tr>
                    <tr>
                        <td style="padding-bottom:6px; font-size:13px; color:#475569; line-height:1.5;">
                            2. <strong>Utilisez</strong> un code si vous perdez l'accès à votre application d'authentification.
                        </td>
                    </tr>
                    <tr>
                        <td style="padding-bottom:6px; font-size:13px; color:#475569; line-height:1.5;">
                            3. <strong>Régénérez</strong> vos codes depuis votre espace sécurisé si nécessaire.
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size:13px; color:#475569; line-height:1.5;">
                            4. <strong>Ne partagez</strong> jamais vos codes de récupération.
                        </td>
                    </tr>
                </table>
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
                                <strong>Action requise :</strong> Si vous n'avez pas demandé ces codes, 
                                contactez immédiatement l'équipe YNOV à 
                                <a href="mailto:support@ynov.ci" style="color:#096835; font-weight:600;">support@ynov.ci</a>.
                            </span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Accès rapide --}}
    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:16px 0;">
        <tr>
            <td style="border-radius:6px; background-color:#096835;">
                <a href="{{ config('app.frontend_url') ?? '#' }}/profile/security" 
                   target="_blank"
                   style="display:inline-block; padding:12px 28px; font-size:14px; font-weight:600; color:#ffffff; text-decoration:none; border-radius:6px;">
                   🔒 Accéder à la gestion 2FA
                </a>
            </td>
        </tr>
    </table>

    {{-- Message de sécurité --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:20px; border-top:1px solid #e2e8f0; padding-top:20px;">
        <tr>
            <td style="font-size:12px; color:#94a3b8; line-height:1.6; text-align:center;">
                <span style="color:#096835;">●</span> 
                Ces codes ont été générés le {{ now()->format('d/m/Y à H:i') }}.
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