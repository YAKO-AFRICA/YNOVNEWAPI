<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signature Électronique</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #075429 0%, #0a6b35 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .widget-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 800px;
            width: 100%;
            overflow: hidden;
        }
        
        .widget-header {
            background: #075429;
            color: white;
            padding: 24px;
            text-align: center;
        }
        
        .widget-header h1 {
            font-size: 24px;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .widget-header p {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .widget-content {
            padding: 24px;
        }
        
        #signature-widget-root {
            min-height: 400px;
        }
        
        @media (max-width: 768px) {
            .widget-container {
                border-radius: 0;
                box-shadow: none;
            }
            
            body {
                padding: 0;
                background: white;
            }
            
            .widget-header {
                border-radius: 0;
            }
        }
    </style>
</head>
<body>
    <div class="widget-container">
        <div class="widget-header">
            <h1>Signature de Document</h1>
            <p>{{ $document_description ?? 'Document à signer' }}</p>
        </div>
        
        <div class="widget-content">
            <!-- Conteneur pour le widget JS consolidé -->
            <div id="signature-widget-root"></div>
        </div>
    </div>

    <!-- Chargement du widget JS consolidé -->
    <script src="{{ asset('assets/js/signature-widget.js') }}"></script>
    
    <script>
        // Configuration et initialisation du widget consolidé
        document.addEventListener('DOMContentLoaded', function() {
            // Détection du mode (mobile/desktop) pour forcer l'affichage approprié
            const isMobile = window.innerWidth < 768;
            const currentUrl = window.location.href;
            
            // Récupérer les URLs de redirection depuis les métadonnées du token si disponibles
            // Ces URLs sont stockées dans metadata lors de la génération du lien
            const successRedirectUrl = '{{ $success_redirect_url ?? '' }}';
            const cancelRedirectUrl = '{{ $cancel_redirect_url ?? '' }}';
            const enableAutoPolling = {{ $enable_auto_polling ? 'true' : 'false' }}; // Pour scénario agence
            
            // Appliquer le proxy pour l'URL du document si c'est une URL externe
            let documentUrl = '{{ $document_url ?? '' }}';
            if (documentUrl && !documentUrl.startsWith(window.location.origin)) {
                documentUrl = window.location.origin + '/signature/proxy-document?url=' + encodeURIComponent(documentUrl);
                // console.log('Proxy appliqué pour:', documentUrl);
            }
            
            // Initialisation du widget avec les données du token
            new SignatureWidget({
                container: '#signature-widget-root',
                documentUrl: documentUrl,
                documentDescription: '{{ $document_description ?? 'Document à signer' }}',
                webhookUrl: '{{ $webhook_url ?? '' }}',
                apiKey: '{{ $api_key ?? '' }}',
                signingLink: currentUrl,  // Lien actuel avec token pour QR code desktop
                forceMode: isMobile ? 'mobile' : null,  // Forcer mode mobile sur petits écrans
                successRedirectUrl: successRedirectUrl || null,  // Redirection après succès
                cancelRedirectUrl: cancelRedirectUrl || null,  // Redirection après annulation
                enableAutoPolling: enableAutoPolling,  // Polling auto pour scénario agence
                apiUrl: window.location.origin + '/api/v1/signature',  // URL de l'API pour polling (avec origine complète)
                useProxy: true,  // Utiliser le proxy dans le widget
                onSigned: function(data) {
                    console.log('=== onSigned appelé ===');
                    console.log('Signature envoyée avec succès', data);
                    
                    // Marquer le token comme utilisé via une image invisible (pixel de tracking)
                    // C'est la méthode la plus fiable sur mobile
                    const urlParts = currentUrl.split('/signature/widget/');
                    const token = urlParts.length > 1 ? urlParts[1] : currentUrl.split('/').pop();
                    console.log('Token extrait:', token);
                    
                    const markUrl = window.location.origin + '/api/v1/signature/mark-token-used/' + encodeURIComponent(token);
                    console.log('Chargement pixel:', markUrl);
                    
                    // Créer une image invisible
                    const img = new Image();
                    img.onload = function() {
                        console.log('Token marqué avec succès (pixel)');
                    };
                    img.onerror = function() {
                        console.error('Erreur marquage token (pixel)');
                    };
                    img.src = markUrl;
                    
                    // La redirection est gérée automatiquement si successRedirectUrl est fourni
                },
                onError: function(error) {
                    console.error('Erreur de signature', error);
                    // Redirection vers cancelRedirectUrl si fourni
                    if (cancelRedirectUrl) {
                        window.location.href = cancelRedirectUrl;
                    }
                }
            });
        });
    </script>
</body>
</html>