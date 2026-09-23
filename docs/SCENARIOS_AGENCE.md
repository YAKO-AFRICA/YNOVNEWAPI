# Scénarios d'Utilisation - Widget Signature Électronique

## 🏢 Contexte Agence YAKOA AFRICASSUR

Ce document décrit les 3 scénarios principaux d'utilisation du widget de signature électronique dans le contexte d'une agence d'assurance.

---

## 🎨 Couleurs de l'interface

- **Principal** : `#075429` (Vert foncé - couleur de la marque)
- **Secondaire** : `#F7A400` (Orange - pour les actions principales)

---

## 📱 Scénario 1 : Client présent en agence - Petit écran

### Description
Un client vient en agence pour une prestation (ex: rachat partiel). Il remplit le formulaire sur un terminal/tablette. Une fois le formulaire terminé, une fiche de prestation temporaire est générée et passe au widget pour signature.

### Comportement
- **Détection automatique** : Le widget détecte que l'écran est petit (< 768px)
- **Affichage direct** : Le widget affiche directement le document et la zone de signature
- **Signature tactile** : Le client signe directement sur l'écran tactile
- **Transmission** : Une fois signé, le widget envoie la signature au webhook de l'app cliente

### Endpoint utilisé
```http
POST /api/v1/signature/generate-link
{
  "document_url": "https://...",
  "document_description": "Fiche de prestation - Rachat partiel",
  "webhook_url": "https://app-agence.com/api/signature-webhook",
  "api_key": "secret-partagé",
  "enable_auto_polling": false
}
```

### Intégration Frontend
```javascript
new SignatureWidget({
  container: '#signature-container',
  documentUrl: 'https://...',
  documentDescription: 'Fiche de prestation - Rachat partiel',
  webhookUrl: 'https://app-agence.com/api/signature-webhook',
  apiKey: 'secret-partagé',
  // Mode automatique : desktop = QR, mobile = signature directe
  // Pour scénario agence tablette, on peut forcer le mode mobile
  forceMode: 'mobile', 
  onSigned: function(data) {
    console.log('Signature reçue', data);
    // L'app hôte doit appeler l'endpoint Laravel pour marquer le token comme utilisé
    // Voir section "Important pour l'app hôte" ci-dessous
  },
  onError: function(error) {
    console.error('Erreur', error);
  }
});
```

### ⚠️ Important pour l'app hôte

Lorsque l'app hôte reçoit la signature via le webhook, elle doit **appeler l'endpoint Laravel** pour marquer le token comme utilisé. Cela permet au desktop (qui affiche le QR code) de détecter que la signature est terminée via le polling.

**Endpoint à appeler :**
```http
POST /api/v1/signature/mark-token-used
Content-Type: application/json
X-Api-Key: {votre-api-key}

{
  "token": "{token-de-signature}"
}
```

**Exemple d'intégration côté app hôte (Node.js) :**
```javascript
app.post('/api/signature-webhook', async (req, res) => {
  const { signature, token, timestamp } = req.body;
  
  // 1. Traiter la signature (appliquer sur document, enregistrer, etc.)
  await processSignature(signature, token);
  
  // 2. Marquer le token comme utilisé chez Laravel
  await fetch('https://apidev.yakoafricassur.com/api/v1/signature/mark-token-used', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-Api-Key': 'votre-api-key'
    },
    body: JSON.stringify({ token: token })
  });
  
  res.json({ success: true });
});
```

**Exemple d'intégration côté app hôte (PHP/Laravel) :**
```php
public function receiveSignature(Request $request)
{
    $signature = $request->input('signature');
    $token = $request->input('token');
    
    // 1. Traiter la signature
    $this->processSignature($signature, $token);
    
    // 2. Marquer le token comme utilisé chez Laravel
    Http::withHeaders([
        'X-Api-Key' => config('signature.api_key'),
        'Content-Type' => 'application/json',
    ])->post('https://apidev.yakoafricassur.com/api/v1/signature/mark-token-used', [
        'token' => $token
    ]);
    
    return response()->json(['success' => true]);
}
```
    // Signature reçue - continuer le processus
    console.log('Signature terminée', data);
  }
});
```

### Avantages
- ✅ Rapide et direct
- ✅ Pas besoin de device supplémentaire
- ✅ Signature immédiate

---

## 🖥️ Scénario 2 : Client présent en agence - Grand écran

### Description
Même scénario que le précédent, mais le client est sur un ordinateur de bureau avec grand écran.

### Comportement
- **Détection automatique** : Le widget détecte que l'écran est grand (≥ 768px)
- **Affichage QR code** : Le widget affiche un QR code pointant vers le lien de signature
- **Scan par mobile** : Le client scanne le QR code avec son téléphone
- **Ouverture automatique** : Au scan, le widget s'ouvre en mode mobile sur le téléphone
- **Polling automatique** : L'ordinateur vérifie périodiquement le statut de signature
- **Transmission** : Une fois signé sur mobile, la signature est envoyée au webhook

### Endpoint utilisé
```http
POST /api/v1/signature/generate-link
{
  "document_url": "https://...",
  "document_description": "Fiche de prestation - Rachat partiel",
  "webhook_url": "https://app-agence.com/api/signature-webhook",
  "api_key": "secret-partagé",
  "enable_auto_polling": true,  // Active le polling automatique
  "success_redirect_url": "https://app-agence.com/confirmation"
}
```

### Intégration Frontend
```javascript
new SignatureWidget({
  container: '#signature-container',
  documentUrl: 'https://...',
  documentDescription: 'Fiche de prestation - Rachat partiel',
  webhookUrl: 'https://app-agence.com/api/signature-webhook',
  apiKey: 'secret-partagé',
  signingLink: 'https://backend.com/sign/xyz', // Lien avec token
  enableAutoPolling: true,  // Active le polling automatique
  pollingInterval: 5000,     // Vérifie toutes les 5 secondes
  maxPollingAttempts: 60,    // Max 5 minutes
  apiUrl: '/api/v1/signature', // URL de l'API pour polling
  onSigned: function(data) {
    // Signature terminée détectée par polling
    console.log('Signature terminée', data);
    // Rediriger ou continuer le processus
  }
});
```

### Workflow complet
1. L'ordinateur affiche le QR code
2. Le polling démarre automatiquement (vérifie toutes les 5 secondes)
3. Le client scanne le QR code avec son téléphone
4. Le widget s'ouvre en mode mobile sur le téléphone
5. Le client signe sur son téléphone
6. Le téléphone envoie la signature au webhook
7. L'ordinateur détecte la signature via polling
8. L'application continue le processus

### Avantages
- ✅ Utilisation du téléphone du client (plus naturel)
- ✅ L'ordinateur surveille automatiquement
- ✅ Expérience utilisateur fluide

---

## 👨‍💼 Scénario 3 : Gestionnaire en agence - Envoi au client

### Description
Un gestionnaire traite une demande pour un client absent. Il génère un lien de signature et l'envoie au client par email, SMS ou WhatsApp.

### Comportement
- **Génération de lien** : Le gestionnaire génère un lien avec token Sanctum
- **Envoi multicanaux** : Email, SMS ou WhatsApp via Infobip
- **Expiration étendue** : 2 heures par défaut (plus long que scénario agence)
- **Signature à distance** : Le client signe depuis son propre device
- **Webhook callback** : L'app agence reçoit la signature quand terminée

### Endpoint utilisé
```http
POST /api/v1/signature/generate-manager-link
{
  "document_url": "https://...",
  "document_description": "Fiche de prestation - Rachat partiel",
  "client_email": "client@example.com",
  "client_phone": "+33612345678",
  "client_name": "Jean Dupont",
  "send_method": "email",  // ou "sms" ou "whatsapp"
  "webhook_url": "https://app-agence.com/api/signature-webhook",
  "api_key": "secret-partagé",
  "expires_in": 7200  // 2 heures
}
```

### Réponse
```json
{
  "success": true,
  "message": "Lien de signature généré et envoyé par email.",
  "code": "MANAGER_LINK_SENT",
  "data": {
    "token": "abc123...",
    "widget_url": "https://backend.com/sign/abc123...",
    "expires_at": "2024-09-18T14:00:00Z",
    "sent_via": "email",
    "sent_to": "client@example.com",
    "client_name": "Jean Dupont"
  }
}
```

### Messages personnalisés

**Email** :
```
Bonjour Jean Dupont,

Veuillez trouver ci-joint le document à signer en cliquant sur le lien suivant :

https://backend.com/sign/abc123...

Ce lien est valide pour 2 heures.

Cordialement,
L'équipe YAKOA AFRICASSUR
```

**SMS** :
```
Signez votre document ici : https://backend.com/sign/abc123...
```

**WhatsApp** :
```
Bonjour Jean Dupont,

Veuillez signer votre document en cliquant sur ce lien :

https://backend.com/sign/abc123...

L'équipe YAKOA AFRICASSUR
```

### Avantages
- ✅ Signature à distance
- ✅ Plusieurs canaux d'envoi
- ✅ Traçabilité complète

---

## 🔧 Configuration requise

### Variables d'environnement
```env
# Configuration Infobip (pour SMS/WhatsApp)
INFOBIP_API_KEY=votre_cle_infobip
INFOBIP_BASE_URL=https://api.infobip.com
INFOBIP_SMS_FROM=YAKOA
INFOBIP_WHATSAPP_FROM=+33600000000

# Configuration Laravel
APP_URL=https://votre-domaine.com
```

### Configuration Services
```php
// config/services.php
'infobip' => [
    'api_key' => env('INFOBIP_API_KEY'),
    'base_url' => env('INFOBIP_BASE_URL'),
    'sms_from' => env('INFOBIP_SMS_FROM', 'InfoSMS'),
    'whatsapp_from' => env('INFOBIP_WHATSAPP_FROM'),
],
```

---

## 📊 Tableau comparatif des scénarios

| Caractéristique | Scénario 1 (Petit écran) | Scénario 2 (Grand écran) | Scénario 3 (Gestionnaire) |
|----------------|------------------------|------------------------|------------------------|
| **Device client** | Tablette/ordinateur agence | Téléphone personnel | Téléphone personnel |
| **Mode affichage** | Signature directe | QR code → Signature directe | Signature directe |
| **Polling** | Non | Oui (automatique) | Non (webhook) |
| **Expiration** | 1 heure | 1 heure | 2 heures |
| **Envoi lien** | Non | Non | Oui (Email/SMS/WhatsApp) |
| **Endpoint** | `generate-link` | `generate-link` | `generate-manager-link` |
| **Usage** | Client présent en agence | Client présent en agence | Client absent |

---

## 🧪 Tests

### Scénario 1 - Test
```bash
# 1. Générer un lien sans polling
curl -X POST http://localhost:8000/api/v1/signature/generate-link \
  -H "Content-Type: application/json" \
  -d '{
    "document_url": "https://...",
    "document_description": "Test scénario 1",
    "webhook_url": "https://httpbin.org/post",
    "api_key": "test-key",
    "enable_auto_polling": false
  }'

# 2. Ouvrir le widget URL en mode mobile (forcer via ?force=mobile)
# 3. Signer et vérifier le webhook
```

### Scénario 2 - Test
```bash
# 1. Générer un lien avec polling
curl -X POST http://localhost:8000/api/v1/signature/generate-link \
  -H "Content-Type: application/json" \
  -d '{
    "document_url": "https://...",
    "document_description": "Test scénario 2",
    "webhook_url": "https://httpbin.org/post",
    "api_key": "test-key",
    "enable_auto_polling": true
  }'

# 2. Ouvrir le widget URL en mode desktop
# 3. Scanner le QR code avec un téléphone
# 4. Signer sur le téléphone
# 5. Observer le polling sur l'ordinateur
```

### Scénario 3 - Test
```bash
# 1. Générer un lien gestionnaire
curl -X POST http://localhost:8000/api/v1/signature/generate-manager-link \
  -H "Content-Type: application/json" \
  -d '{
    "document_url": "https://...",
    "document_description": "Test scénario 3",
    "client_email": "test@example.com",
    "client_name": "Test Client",
    "send_method": "email",
    "webhook_url": "https://httpbin.org/post",
    "api_key": "test-key"
  }'

# 2. Vérifier que l'email est envoyé
# 3. Cliquer sur le lien dans l'email
# 4. Signer
# 5. Vérifier le webhook
```

---

## 🔐 Sécurité

### Tokens Sanctum
- **Usage unique** : Chaque token ne peut être utilisé qu'une fois
- **Expiration** : Configurable selon le scénario
- **Invalidation** : Token supprimé après signature réussie

### Webhook Authentication
- **Secret partagé** : API key entre backend et app agence
- **Header X-Api-Key** : Authentification de chaque requête webhook
- **Validation** : Vérification de la clé avant traitement

### Infobip
- **API Key** : Clé Infobip pour SMS/WhatsApp
- **Configuration** : Sécurisée via variables d'environnement

---

## 📝 Intégration App Agence

### Exemple d'intégration Laravel

```php
// Dans votre contrôleur agence
public function traiterPrestation(Request $request)
{
    // 1. Générer la fiche de prestation
    $fichePrestation = $this->genererFichePrestation($request->all());
    
    // 2. Générer le lien de signature
    $response = Http::post(config('services.signature.api_url') . '/generate-link', [
        'document_url' => $fichePrestation['url'],
        'document_description' => 'Fiche de prestation - ' . $request->type_prestation,
        'webhook_url' => route('signature.webhook'),
        'api_key' => config('services.signature.api_key'),
        'enable_auto_polling' => $this->detectScreenSize() > 768, // Grand écran
        'success_redirect_url' => route('prestations.confirmation', ['id' => $fichePrestation['id']]),
    ]);
    
    if ($response->successful()) {
        $data = $response->json();
        
        // 3. Afficher le widget selon le scénario
        if ($this->detectScreenSize() > 768) {
            // Scénario 2 : Grand écran avec QR
            return view('agence.signature-desktop', [
                'widget_url' => $data['widget_url'],
                'token' => $data['token'],
                'enable_polling' => true
            ]);
        } else {
            // Scénario 1 : Petit écran direct
            return view('agence.signature-mobile', [
                'document_url' => $fichePrestation['url'],
                'webhook_url' => route('signature.webhook'),
                'api_key' => config('services.signature.api_key')
            ]);
        }
    }
}

// Webhook pour recevoir la signature
public function signatureWebhook(Request $request)
{
    $signature = $request->input('signature');
    $token = $request->token;
    
    // 1. Valider l'API key
    if ($request->header('X-Api-Key') !== config('services.signature.api_key')) {
        return response()->json(['success' => false], 401);
    }
    
    // 2. Apposer la signature sur la fiche
    $fichePrestation = $this->apposerSignature($token, $signature);
    
    // 3. Finaliser la prestation
    $this->finaliserPrestation($fichePrestation);
    
    return response()->json(['success' => true]);
}
```

---

## 🎯 Bonnes pratiques

### Pour les scénarios 1 et 2 (Client présent)
- **Délai d'expiration** : 1 heure maximum (client est présent)
- **Polling intervalle** : 5 secondes (bon compromis réactivité/ressources)
- **Redirect après succès** : Important pour UX (continuer le processus)

### Pour le scénario 3 (Gestionnaire)
- **Délai d'expiration** : 2 heures (client peut être occupé)
- **Personnalisation** : Inclure le nom du client dans les messages
- **Traçabilité** : Logger qui a envoyé le lien et quand

### Général
- **Fallback** : Prévoir un lien manuel si QR code échoue
- **Erreur handling** : Messages clairs en cas d'échec
- **Monitoring** : Surveiller les taux de succès des signatures

---

## 🚀 Prochaines étapes

1. **Tester les 3 scénarios** avec de vrais documents
2. **Configurer Infobip** pour SMS/WhatsApp
3. **Implémenter l'intégration email** avec Laravel Mail
4. **Créer les vues agence** pour les différents scénarios
5. **Monitorer** les performances et les taux de succès