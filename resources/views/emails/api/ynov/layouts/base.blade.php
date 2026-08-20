{{-- resources/views/emails/api/ynov/layouts/base.blade.php --}}
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $subject ?? 'YNOV' }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f5f7; font-family: 'Segoe UI', Arial, sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f5f7; padding:32px 0;">
    <tr>
        <td align="center">
            <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.08);">
                {{-- En-tête --}}
                <tr>
                    <td style="background-color:#0f172a; padding:24px 32px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="text-align:left;">
                                    <span style="font-size:20px; font-weight:700; color:#ffffff; letter-spacing:0.5px;">YNOV</span>
                                    <span style="font-size:12px; color:#94a3b8; margin-left:8px;">YAKO AFRICA Assurances Vie</span>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding-top:4px;">
                                    <span style="font-size:11px; color:#64748b;">Plateforme de gestion des assurances</span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                {{-- Bandeau statut --}}
                @isset($bannerColor)
                <tr>
                    <td style="background-color:{{ $bannerColor }}; padding:10px 32px;">
                        <span style="font-size:13px; font-weight:600; color:#ffffff;">{{ $bannerText ?? '' }}</span>
                    </td>
                </tr>
                @endisset

                {{-- Corps --}}
                <tr>
                    <td style="padding:32px;">
                        @yield('content')
                    </td>
                </tr>

                {{-- Pied de page --}}
                <tr>
                    <td style="padding:24px 32px; background-color:#f8fafc; border-top:1px solid #e2e8f0;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="text-align:left;">
                                    <p style="margin:0 0 8px 0; font-size:12px; color:#64748b; line-height:1.6;">
                                        <strong>YNOV — YAKO AFRICA Assurances Vie</strong>
                                    </p>
                                    <p style="margin:0 0 8px 0; font-size:12px; color:#64748b; line-height:1.6;">
                                        Cet email a été envoyé automatiquement par la plateforme YNOV.
                                        Merci de ne pas y répondre directement.
                                    </p>
                                    <p style="margin:0; font-size:12px; color:#94a3b8;">
                                        &copy; {{ date('Y') }} YAKO AFRICA Assurances Vie — Abidjan, Côte d'Ivoire
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding-top:12px;">
                                    <table role="presentation" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td style="padding-right:16px;">
                                                <a href="{{ config('app.frontend_url') ?? '#' }}" 
                                                   style="font-size:11px; color:#64748b; text-decoration:none;">
                                                    Plateforme
                                                </a>
                                            </td>
                                            <td style="padding-right:16px;">
                                                <a href="#" 
                                                   style="font-size:11px; color:#64748b; text-decoration:none;">
                                                    Confidentialité
                                                </a>
                                            </td>
                                            <td>
                                                <a href="#" 
                                                   style="font-size:11px; color:#64748b; text-decoration:none;">
                                                    Contact
                                                </a>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            {{-- Footer externe --}}
            <table role="presentation" width="600" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="padding:16px 32px; text-align:center;">
                        <p style="margin:0; font-size:11px; color:#94a3b8; line-height:1.5;">
                            <i class="bi bi-shield-check" style="margin-right:4px;"></i>
                            Si vous n'êtes pas à l'origine de cette action, 
                            contactez immédiatement votre administrateur.
                        </p>
                        <p style="margin:4px 0 0 0; font-size:10px; color:#cbd5e1;">
                            ID du message : {{ uniqid() }}
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>