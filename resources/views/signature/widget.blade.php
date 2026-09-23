
{{--
    resources/views/signature/widget.blade.php

    Page publique de signature.

    CHANGEMENTS CLÉS :
    - `backendWebhookUrl` est enfin transmis (c'était LA cause du token jamais marqué).
    - `token` est passé explicitement : plus aucun parsing d'URL côté JS.
    - `webhookUrl` et `apiKey` ne sont PLUS rendus dans la page : le secret reste serveur.

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="no-referrer">
    <title>Signature Électronique</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #075429 0%, #0a6b35 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            padding-top: calc(20px + env(safe-area-inset-top, 0px));
            padding-bottom: calc(20px + env(safe-area-inset-bottom, 0px));
        }

        .widget-container {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, .2);
            max-width: 800px;
            width: 100%;
            overflow: hidden;
        }

        .widget-header {
            background: #075429;
            color: #fff;
            padding: 24px;
            text-align: center;
        }

        .widget-header h1 { font-size: 24px; margin-bottom: 8px; font-weight: 600; }
        .widget-header p  { font-size: 14px; opacity: .9; }

        .widget-content { padding: 24px; }
        #signature-widget-root { min-height: 400px; }

        @media (max-width: 768px) {
            body { padding: 0; background: #fff; }
            .widget-container { border-radius: 0; box-shadow: none; }
            .widget-header { border-radius: 0; }
        }
    </style>
</head>
<body>
    <div class="widget-container">
        <div class="widget-header">
            <h1>Signature de Document</h1>
            <p>{{ $document_description }}</p>
        </div>

        <div class="widget-content">
            <div id="signature-widget-root"></div>
        </div>
    </div>

    <script src="{{ asset('assets/js/signature-widget.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var isMobile = window.innerWidth < 768;

            // Le proxy n'est appliqué qu'aux documents hébergés sur un autre domaine.
            var documentUrl = @json($document_url);
            if (documentUrl && documentUrl.indexOf(window.location.origin) !== 0) {
                documentUrl = window.location.origin
                    + '/signature/proxy-document?url=' + encodeURIComponent(documentUrl);
            }

            new SignatureWidget({
                container:   '#signature-widget-root',
                token:       @json($token),
                documentUrl: documentUrl,
                documentDescription: @json($document_description),

                // >>> LE CHAÎNON QUI MANQUAIT <<<
                // Le widget poste sur Laravel (même origine : ni CORS, ni preflight, ni 404).
                // Laravel relaie ensuite vers l'app hôte ET marque le token comme utilisé.
                backendWebhookUrl: @json(url('/api/v1/signature/webhook')),

                apiUrl:      @json(url('/api/v1/signature')),
                signingLink: window.location.origin + window.location.pathname,

                forceMode:          isMobile ? 'mobile' : null,
                successRedirectUrl: @json($success_redirect_url),
                cancelRedirectUrl:  @json($cancel_redirect_url),
                enableAutoPolling:  @json((bool) $enable_auto_polling),

                onSigned: function (data) {
                    // Rien à faire : Laravel a déjà relayé la signature et consommé le token.
                    if (window.console) console.log('[Signature] terminée', data);
                },
                onError: function (error) {
                    if (window.console) console.error('[Signature] erreur', error);
                }
            });
        });
    </script>
</body>
</html>--}}

{{--
    resources/views/signature/widget.blade.php

    Page publique de signature.
    - Mode OTP par défaut (SMS / Email / WhatsApp)
    - Signature graphique accessible via lien discret ou repli si OTP expiré
--}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="no-referrer">
    <title>Signature Électronique</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #075429 0%, #0a6b35 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            padding-top: calc(20px + env(safe-area-inset-top, 0px));
            padding-bottom: calc(20px + env(safe-area-inset-bottom, 0px));
        }

        .widget-container {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, .2);
            max-width: 800px;
            width: 100%;
            overflow: hidden;
        }

        .widget-header {
            background: #075429;
            color: #fff;
            padding: 24px;
            text-align: center;
        }

        .widget-header h1 { font-size: 24px; margin-bottom: 8px; font-weight: 600; }
        .widget-header p  { font-size: 14px; opacity: .9; }

        .widget-content { padding: 24px; }
        #signature-widget-root { min-height: 400px; }

        @media (max-width: 768px) {
            body { padding: 0; background: #fff; }
            .widget-container { border-radius: 0; box-shadow: none; }
            .widget-header { border-radius: 0; }
        }
    </style>
</head>
<body>
    <div class="widget-container">
        <div class="widget-header">
            <h1>Signature de Document</h1>
            <p>{{ $document_description }}</p>
        </div>

        <div class="widget-content">
            <div id="signature-widget-root"></div>
        </div>
    </div>

    <script src="{{ asset('assets/js/signature-widget.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var isMobile = window.innerWidth < 768;

            var documentUrl = @json($document_url);
            if (documentUrl && documentUrl.indexOf(window.location.origin) !== 0) {
                documentUrl = window.location.origin
                    + '/signature/proxy-document?url=' + encodeURIComponent(documentUrl);
            }

            new SignatureWidget({
                container:   '#signature-widget-root',
                token:       @json($token),
                documentUrl: documentUrl,
                documentDescription: @json($document_description),

                backendWebhookUrl: @json(url('/api/v1/signature/webhook')),
                apiUrl:            @json(url('/api/v1/signature')),
                signingLink:       window.location.origin + window.location.pathname,

                // --- OTP ---
                otpSendUrl:   @json(url('/api/v1/auth/otp/send')),
                otpVerifyUrl: @json(url('/api/v1/signature/otp/verify')),
                signerLogin:    @json($signer_login),
                signerUserUuid: @json($signer_user_uuid),
                signerEmail:    @json($signer_email),
                signerPhone:    @json($signer_phone),
                otpPurpose:     @json($otp_purpose),
                otpQrUrlTemplate: @json($otp_qr_url_template),

                forceMode:          isMobile ? 'mobile' : null,
                successRedirectUrl: @json($success_redirect_url),
                cancelRedirectUrl:  @json($cancel_redirect_url),
                enableAutoPolling:  @json((bool) $enable_auto_polling),

                onSigned: function (data) {
                    if (window.console) console.log('[Signature] terminée', data);
                },
                onError: function (error) {
                    if (window.console) console.error('[Signature] erreur', error);
                }
            });
        });
    </script>
</body>
</html>