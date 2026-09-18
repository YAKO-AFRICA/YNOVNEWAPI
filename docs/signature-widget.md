# Widget de Signature Électronique - Documentation

## Overview

Le widget de signature électronique est une solution JavaScript embarquable qui permet de prévisualiser un document, d'y apposer une signature manuscrite, et de transmettre cette signature à une application hôte. Le widget fonctionne sans aucune persistance du document ni de la signature.

## Fonctionnalités

- **Widget JS pur (Vanilla JS)** : Aucune dépendance externe requise
- **Documents supportés** : PDF + images (JPG/PNG)
- **Comportement responsive** :
  - Grand écran → Affichage d'un QR code pointant vers le lien de signature
  - Petit écran → Ouverture directe du widget (prévisualisation + zone de signature)
- **Zone de signature** : Canvas manuscrit avec tactile support
- **Sécurité** : Tokens Sanctum à usage unique avec expiration
- **Multi-canal** : Envoi des liens par Email, SMS et WhatsApp via Infobip

## Architecture

### Backend Laravel

- **SignatureController** : Gère les endpoints API
- **SignatureService** : Logique métier (tokens, envoi de liens)
- **SignatureRequest** : Modèle pour stocker les métadonnées de signature
- **Routes** : API publiques et routes protégées

### Frontend Widget

- **signature-widget.js** : Version module pour intégration
- **signature-widget-embed.js** : Version embarquable via script tag
- **widget.blade.php** : Vue Laravel pour affichage direct

## Installation

### 1. Configuration Backend

Ajoutez les variables d'environnement dans votre fichier `.env` :

```env
# Configuration Infobip (pour SMS/WhatsApp)
INFOBIP_API_KEY=votre_cle_api_infobip
INFOBIP_BASE_URL=https://api.infobip.com
INFOBIP_SMS_FROM=InfoSMS
INFOBIP_WHATSAPP_FROM=votre_numero_whatsapp
```

### 2. Migration de la base de données

Exécutez la migration pour créer la table `signature_requests` :

```bash
php artisan migrate
```

### 3. Seeder des permissions

Exécutez le seeder pour créer les permissions de signature :

```bash
php artisan db:seed --class=PermissionSeeder
```

Cela créera les permissions suivantes :
- `signature.envoyer` : Envoyer les liens de signature
- `signature.afficher` : Afficher les demandes de signature

## Utilisation

### 1. Générer un lien de signature

**Endpoint** : `POST /api/v1/signature/generate-link`

**Headers** : 
- `Content-Type: application/json`
- `Authorization: Bearer {token}` (si protégé)

**Body** :
```json
{
  "document_url": "https://example.com/document.pdf",
  "document_description": "Contrat d'assurance à signer",
  "webhook_url": "https://votre-app.com/api/signature-webhook",
  "api_key": "votre-secret-api-key",
  "expires_in": 3600
}
```

**Réponse** :
```json
{
  "success": true,
  "message": "Lien de signature généré avec succès.",
  "code": "SIGNATURE_LINK_GENERATED",
  "data": {
    "token": "abc123...",
    "widget_url": "https://votre-api.com/signature/widget/abc123...",
    "expires_at": "2024-09-18T12:00:00Z",
    "expires_in": 3600,
    "document_url": "https://example.com/document.pdf",
    "document_description": "Contrat d'assurance à signer",
    "webhook_url": "https://votre-app.com/api/signature-webhook",
    "signature_request_uuid": "uuid-123..."
  }
}
```

### 2. Intégrer le widget JavaScript

#### Option A : Via script tag (version embed)

```html
<div id="signature-widget-container"></div>

<script src="https://votre-api.com/api/v1/signature/signature-widget.js"></script>
<script>
  SignatureWidget.init({
    documentUrl: 'https://example.com/document.pdf',
    documentDescription: 'Contrat à signer',
    webhookUrl: 'https://votre-app.com/api/signature-webhook',
    apiKey: 'votre-secret-api-key',
    containerId: 'signature-widget-container'
  });
</script>
```

#### Option B : Via module ES6

```javascript
import SignatureWidget from './signature-widget.js';

const widget = new SignatureWidget({
  documentUrl: 'https://example.com/document.pdf',
  documentDescription: 'Contrat à signer',
  webhookUrl: 'https://votre-app.com/api/signature-webhook',
  apiKey: 'votre-secret-api-key',
  container: document.getElementById('widget-container')
});
```

### 3. Envoyer le lien par Email

**Endpoint** : `POST /api/v1/signature/send-email` (protégé)

**Body** :
```json
{
  "token": "abc123...",
  "email": "client@example.com",
  "subject": "Document à signer",
  "message": "Veuillez signer le document en cliquant sur le lien suivant"
}
```

### 4. Envoyer le lien par SMS

**Endpoint** : `POST /api/v1/signature/send-sms` (protégé)

**Body** :
```json
{
  "token": "abc123...",
  "phone": "+2250707070707",
  "message": "Signez votre document ici : https://..."
}
```

### 5. Envoyer le lien par WhatsApp

**Endpoint** : `POST /api/v1/signature/send-whatsapp` (protégé)

**Body** :
```json
{
  "token": "abc123...",
  "phone": "+2250707070707",
  "message": "Veuillez signer votre document en cliquant sur ce lien"
}
```

### 6. Recevoir la signature via Webhook

Votre application hôte doit implémenter un endpoint webhook pour recevoir la signature :

**Endpoint** : `POST {votre-webhook-url}`

**Headers** :
- `X-Api-Key: {votre-secret-api-key}`
- `Content-Type: application/json`

**Body** :
```json
{
  "signature": "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAA...",
  "token": "abc123...",
  "timestamp": "2024-09-18T12:00:00Z",
  "signature_request_uuid": "uuid-123..."
}
```

**Réponse attendue** :
```json
{
  "success": true,
  "message": "Signature reçue avec succès"
}
```

## Sécurité

### Tokens Sanctum

- Les tokens sont générés via Laravel Sanctum
- Ils ont une ability spécifique : `signature:sign`
- Ils expirent automatiquement après la durée spécifiée
- Ils sont invalidés après usage (non réutilisables)

### Webhook Authentication

- L'authentification se fait via le header `X-Api-Key`
- La clé API est stockée dans la table `signature_requests`
- Elle est comparée lors de la réception de la signature

### Règle de persistance

- **Aucune persistance** du document ni de la signature côté widget
- Le backend Laravel stocke uniquement les métadonnées dans `signature_requests`
- L'application hôte est seule responsable d'apposer et d'enregistrer la signature

## API Endpoints

### Routes Publiques

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/api/v1/signature/signature-widget.js` | Widget JS embarquable |
| POST | `/api/v1/signature/generate-link` | Générer un lien de signature |
| GET | `/signature/widget/{token}` | Servir le widget avec token |
| POST | `/api/v1/signature/webhook` | Webhook pour recevoir la signature |
| GET | `/api/v1/signature/token/{token}/status` | Vérifier le statut d'un token |

### Routes Protégées (auth:sanctum + permissions)

| Méthode | Endpoint | Permission | Description |
|---------|----------|------------|-------------|
| POST | `/api/v1/signature/send-email` | `signature.envoyer` | Envoyer lien par Email |
| POST | `/api/v1/signature/send-sms` | `signature.envoyer` | Envoyer lien par SMS |
| POST | `/api/v1/signature/send-whatsapp` | `signature.envoyer` | Envoyer lien par WhatsApp |

## Exemple d'intégration complet

### Backend (Application Hôte)

```php
// Dans votre contrôleur
public function requestSignature(Request $request)
{
    $response = Http::post('https://votre-api.com/api/v1/signature/generate-link', [
        'document_url' => $request->document_url,
        'document_description' => $request->description,
        'webhook_url' => route('signature.webhook'),
        'api_key' => config('services.signature.api_key'),
        'expires_in' => 3600,
    ]);

    $data = $response->json();
    
    return response()->json([
        'widget_url' => $data['data']['widget_url'],
        'token' => $data['data']['token'],
    ]);
}

// Webhook pour recevoir la signature
public function signatureWebhook(Request $request)
{
    // Vérifier l'API Key
    if ($request->header('X-Api-Key') !== config('services.signature.api_key')) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $signature = $request->input('signature');
    $token = $request->input('token');
    
    // Traiter la signature (appliquer sur le document, sauvegarder, etc.)
    $this->processSignature($signature, $token);
    
    return response()->json([
        'success' => true,
        'message' => 'Signature reçue avec succès'
    ]);
}
```

### Frontend (Application Hôte)

```html
<!-- Intégration du widget -->
<div id="signature-container"></div>

<script>
async function initSignatureWidget(documentUrl) {
    // Demander un lien de signature au backend
    const response = await fetch('/api/signature/request', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ document_url: documentUrl })
    });
    
    const data = await response.json();
    
    // Charger le widget
    const script = document.createElement('script');
    script.src = 'https://votre-api.com/api/v1/signature/signature-widget.js';
    document.head.appendChild(script);
    
    script.onload = () => {
        SignatureWidget.init({
            documentUrl: documentUrl,
            documentDescription: 'Document à signer',
            webhookUrl: 'https://votre-app.com/api/signature-webhook',
            apiKey: 'votre-secret-api-key',
            containerId: 'signature-container'
        });
    };
}

// Initialiser avec un document
initSignatureWidget('https://example.com/document.pdf');
</script>
```

## Personnalisation

### Styles CSS

Le widget utilise des styles inline pour une intégration facile. Vous pouvez personnaliser l'apparence en modifiant les classes CSS dans le fichier JS ou en utilisant des règles CSS personnalisées.

### Messages personnalisés

Vous pouvez personnaliser les messages d'erreur et de succès en modifiant les méthodes `showStatus()` dans le widget JavaScript.

## Dépannage

### Token invalide ou expiré

Vérifiez que :
- Le token n'a pas expiré (vérifiez le champ `expires_at`)
- Le token n'a pas déjà été utilisé (vérifiez le champ `is_used`)
- Le token a l'ability `signature:sign`

### Erreur lors de l'envoi SMS/WhatsApp

Vérifiez que :
- La configuration Infobip est correcte dans `.env`
- L'API Key Infobip est valide
- Le numéro de téléphone est au format international

### Document ne se charge pas

Vérifiez que :
- L'URL du document est accessible publiquement
- Le format du document est supporté (PDF, JPG, PNG)
- Il n'y a pas de restrictions CORS

## Support

Pour toute question ou problème, contactez l'équipe technique ou consultez la documentation Laravel Sanctum et Infobip.