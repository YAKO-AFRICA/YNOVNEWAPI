# Widget de Signature Électronique — Guide d'intégration

YAKOA AFRICASSUR · Backend Laravel + widget JavaScript embarquable

---

## 1. Ce qui a changé et pourquoi

| Avant | Après |
|---|---|
| Le widget postait la signature **directement** à l'app hôte ; Laravel n'était jamais notifié (`backendWebhookUrl` n'était pas transmis par le Blade) | Le widget poste sur **Laravel**, qui relaie à l'app hôte **et** consomme le token dans la même opération |
| Token = `plainTextToken` Sanctum, contenant un `\|` réencodé en `%7C` selon le chemin parcouru | Token opaque de 64 caractères `[A-Za-z0-9]`, insensible à l'encodage d'URL |
| `api_key` et `webhook_url` rendus dans le HTML public, et surchargeables par query string | Ne quittent jamais le serveur |
| `mark-token-used` en GET, sans authentification | Endpoint supprimé |
| La signature était écrite en base par `markAsUsed($signatureBase64)` | `markAsUsed()` n'accepte plus d'argument — aucune persistance |
| L'app hôte devait notifier Laravel (étape fragile) | L'app hôte n'a plus rien à notifier |

**Conséquence sur le scénario 2 (desktop + QR) :** la chaîne ne dépend plus d'une action
de l'app hôte. Le mobile signe → Laravel consomme le token → le polling desktop le voit.

---

## 2. Flux complet

```
App hôte ──POST /api/v1/signature/generate-link──> Laravel
                                                     │  crée le token + métadonnées
        <──── token, widget_url, status_url ─────────┘

Desktop agence : ouvre widget_url  →  affiche un QR code  →  poll status_url
Client mobile  : scanne le QR      →  ouvre widget_url    →  signe

Mobile ──POST /api/v1/signature/webhook {token, signature}──> Laravel
                                                                │ 1. verrouille + consomme le token
                                                                │ 2. relaie au webhook de l'app hôte
                                                                │    (header X-Api-Key lu en base)
                                                                └─> App hôte appose la signature

Desktop : status_url renvoie is_used = true  →  écran « Document signé »
```

---

## 3. Qui fait quoi ? (repère rapide pour le dev front)

Point de confusion fréquent : **ce n'est jamais votre page web qui reçoit la signature.**
Le navigateur du client (mobile ou desktop) ne voit jamais la signature finale — il ne fait
que l'envoyer. C'est **Laravel qui rappelle votre serveur** en coulisses, de serveur à serveur,
bien après que le client a fermé l'onglet.

| Qui | Fait quoi | Où ça vit |
|---|---|---|
| **Votre frontend** (la page qui déclenche la demande de signature) | Appelle `generate-link`, affiche `widget_url` (iframe, onglet, ou widget embarqué) | Navigateur du conseiller / du client |
| **Le widget** (fourni, vous ne le codez pas) | Capture la signature, la poste à Laravel | Navigateur du signataire |
| **Laravel** (fourni, vous ne le codez pas) | Reçoit la signature, la relaie à votre backend, consomme le token | Serveur YAKOA |
| **Votre backend** (à coder — voir 4.2) | Reçoit l'appel de Laravel, vérifie le secret, appose la signature sur le document, l'enregistre chez vous | Serveur de votre app hôte |

**Concrètement, pour le dev front :** votre responsabilité s'arrête à l'affichage du widget
et, éventuellement, à l'écoute de `successRedirectUrl` / `onSigned` pour rafraîchir l'écran une
fois que c'est fait. La réception réelle de la signature (section 4.2) est un endpoint HTTP
côté **backend** de votre application — assurez-vous simplement qu'il existe et que son URL
est bien celle donnée à `generate-link` (`webhook_url`).

---

## 4. Endpoints

### 4.1 Générer un lien — `POST /api/v1/signature/generate-link`

Authentification : `auth:sanctum` (token de votre app hôte).

```json
{
  "document_url": "https://docs.yakoafrica.ci/contrats/ABC123.pdf",
  "document_description": "Contrat de souscription n°ABC123",
  "webhook_url": "https://app-hote.ci/api/signature-webhook",
  "api_key": "un-secret-d-au-moins-16-caracteres",
  "success_redirect_url": "https://app-hote.ci/signature/ok",
  "cancel_redirect_url": "https://app-hote.ci/signature/annule",
  "enable_auto_polling": true,
  "expires_in": 3600
}
```

Réponse `201` :

```json
{
  "success": true,
  "code": "SIGNATURE_LINK_GENERATED",
  "data": {
    "token": "aB3xY…",
    "widget_url": "https://api.yakoafrica.ci/signature/widget/aB3xY…",
    "status_url": "https://api.yakoafrica.ci/api/v1/signature/token/aB3xY…/status",
    "expires_at": "2026-09-21T15:30:00+00:00",
    "expires_in": 3600
  }
}
```

`document_url` est facultatif (signature sans document).

### 4.2 Comment l'app hôte reçoit la signature

C'est la partie la plus mal comprise du système, donc en détail.

#### Le principe : un appel serveur → serveur, pas une réponse au navigateur

Quand le client dessine sa signature et appuie sur « Valider », voici ce qui se passe
**réellement** — et à aucun moment le navigateur ne parle directement à votre backend :

```
┌──────────────┐        1. POST signature        ┌──────────┐
│   Widget     │ ───────────────────────────────> │  Laravel │
│ (navigateur  │                                   │  (YAKOA) │
│  du client)  │ <─────────────────────────────── │          │
└──────────────┘   2. "signature reçue" (200)      └────┬─────┘
                                                          │
                                                          │ 3. POST webhook_url
                                                          │    (X-Api-Key + signature)
                                                          ▼
                                                   ┌──────────────┐
                                                   │  VOTRE       │
                                                   │  BACKEND     │
                                                   └──────┬───────┘
                                                          │ 4. appose la signature,
                                                          │    enregistre le document
                                                          │ 5. répond 200 OK
                                                          ▼
                                                   (dossier finalisé
                                                    chez vous)
```

- **Étape 1-2** se produisent dans le navigateur du client, en quelques centaines de
  millisecondes. Le widget affiche « Document signé avec succès » dès l'étape 2 — **avant même**
  que votre backend ait reçu quoi que ce soit.
- **Étape 3** est l'appel qui vous intéresse : Laravel, depuis son propre serveur, appelle
  votre `webhook_url` en `POST`. Ce n'est pas une réponse à une requête que vous avez faite —
  c'est Laravel qui **initie** cet appel, à un moment qu'il choisit (juste après avoir consommé
  le token).
- **Étapes 4-5** sont entièrement de votre ressort : c'est le seul endroit où la signature
  existe en clair. Si vous ne la traitez pas ici, elle est perdue pour toujours.

#### Ce que contient l'appel que vous recevez

```
POST https://votre-domaine.com/api/signature-webhook   (l'URL que VOUS avez donnée)
Content-Type: application/json
X-Api-Key: <le secret que vous avez fourni à generate-link>

{
  "success": true,
  "status": 200,
  "signature": "data:image/png;base64,iVBORw0KGgo…",
  "token": "aB3xY…",
  "document_url": "https://docs.yakoafrica.ci/contrats/ABC123.pdf",
  "signature_request_uuid": "0f9c…",
  "signed_at": "2026-09-21T14:52:11+00:00"
}
```

| Champ | À quoi il sert |
|---|---|
| `signature` | L'image PNG de la signature manuscrite, encodée en base64. C'est la donnée finale. |
| `X-Api-Key` (en-tête) | Le secret que **vous** avez choisi et transmis à `generate-link`. Sert à vérifier que l'appel vient bien de Laravel et pas d'un tiers. |
| `signature_request_uuid` | Identifiant unique de cette demande — le plus fiable pour retrouver votre dossier. |
| `token` | Le même token que celui du lien envoyé au client. Utile si vous l'aviez déjà associé à votre dossier au moment de `generate-link`. |
| `document_url` | Rappel de l'URL du document, telle que vous l'aviez fournie. |
| `signed_at` | Horodatage de la signature. |

#### Ce que votre backend doit faire, dans l'ordre

1. **Vérifier `X-Api-Key`** avant toute chose, en comparaison à temps constant
   (`hash_equals()` en PHP, équivalent dans votre langage) — jamais avec `===` ou `==`,
   qui fuient un timing exploitable.
2. **Retrouver votre dossier métier** (le contrat, la souscription…) grâce à
   `signature_request_uuid` ou `token` — vous devez les avoir mémorisés au moment où
   vous avez appelé `generate-link` pour ce dossier précis.
3. **Décoder et apposer** la signature sur votre document, puis **l'enregistrer chez vous**.
   C'est le seul endroit du système entier où la signature est persistée.
4. **Répondre `2xx`**. N'importe quel autre code (ou une absence de réponse) est interprété
   par Laravel comme un échec de livraison.

```php
// Exemple minimal côté app hôte (Laravel, adaptez à votre stack)

Route::post('/api/signature-webhook', function (Illuminate\Http\Request $request) {

    // 1) Authentifier l'appel — le secret ne transite jamais ailleurs que par ici
    $expected = config('services.yakoa_signature.api_key');
    if (!hash_equals($expected, (string) $request->header('X-Api-Key'))) {
        return response()->json(['success' => false], 401);
    }

    $data = $request->validate([
        'signature'              => ['required', 'string'],
        'signature_request_uuid' => ['required', 'string'],
        'token'                  => ['required', 'string'],
        'signed_at'              => ['required', 'string'],
    ]);

    // 2) Retrouver le dossier — à adapter à votre modèle de données
    $dossier = Dossier::where('signature_request_uuid', $data['signature_request_uuid'])
        ->firstOrFail();

    // 3) Apposer la signature et l'enregistrer CHEZ VOUS
    $png = base64_decode(preg_replace('#^data:image/\w+;base64,#', '', $data['signature']));
    $dossier->appliquerSignature($png, $data['signed_at']);

    // 4) Répondre 2xx — sinon Laravel marque la livraison en échec
    return response()->json(['success' => true]);
});
```

#### Pourquoi c'est important de ne pas traîner

Laravel ne conserve **jamais** la signature — ni avant, ni après cet appel. Si votre endpoint
plante, met trop de temps, ou répond autre chose qu'un `2xx`, la signature est perdue
définitivement (`delivery_status: failed` — voir 4.3) et il faut faire signer le client une
seconde fois. Pour éviter ça : votre endpoint doit être **rapide et simple** — stockez le
payload brut immédiatement (avant tout traitement lourd comme la fusion PDF), puis traitez le
reste en file d'attente si besoin.

#### Comment savoir, côté front, que c'est fait

Une fois votre backend informé, **votre frontend** n'a rien à interroger côté Laravel :
c'est chez vous que ça se passe. Deux façons de le savoir dans votre propre application :

- **Poll votre propre backend** (pas celui de Laravel) sur l'état du dossier, une fois que
  vous savez qu'une signature était attendue.
- **Utilisez `successRedirectUrl`** : le widget y redirige automatiquement le client 2 secondes
  après la signature — pratique pour ramener l'utilisateur sur un écran « Signature reçue,
  traitement en cours » pendant que votre webhook fait son travail en tâche de fond.

> ⚠️ Ne confondez pas `onSigned` (callback JS du widget, déclenché dès l'étape 2 du schéma —
> **avant** que votre backend ait reçu quoi que ce soit) avec la confirmation que votre webhook
> a bien traité la signature. `onSigned` dit « le client a fini de signer », pas « le document
> est enregistré chez vous ».

### 4.3 Statut du token — `GET /api/v1/signature/token/{token}/status`

Public, limité à 240 req/min. Utilisé par le polling desktop, et disponible
pour votre app hôte.

```json
{
  "success": true,
  "code": "TOKEN_STATUS",
  "data": {
    "valid": true,
    "expired": false,
    "is_used": true,
    "is_valid": false,
    "signed_at": "2026-09-21T14:52:11+00:00",
    "delivery_status": "delivered"
  }
}
```

`delivery_status` : `pending` · `delivered` · `failed`.

### 4.4 Flux de distribution du lien

Le flux de production actuel privilégie le QR code et le mobile : le desktop affiche un QR
qui ouvre le widget sur le téléphone, sans passer par un envoi par email, SMS ou WhatsApp.

```text
Desktop : widget_url → affiche QR code → client scanne → ouvre le widget sur mobile
Mobile  : signe → Laravel verrouille le token → webhook_url de votre backend est appelé
```

Le backend app hôte n'a pas à exposer ni à gérer un canal d'envoi supplémentaire pour que la
signature fonctionne. Il suffit d'appeler `generate-link`, d'afficher le `widget_url` ou le QR,
puis de traiter le `webhook_url` quand la signature est validée.

---

## 5. Intégration du widget dans votre page

La façon la plus simple : ouvrez `widget_url` dans un onglet, une iframe ou
une webview. Tout est déjà configuré côté serveur.

Pour un rendu embarqué dans votre propre page :

```html
<script src="https://api.yakoafrica.ci/api/v1/signature/signature-widget.js"></script>
<div id="signature-widget"></div>

<script>
  new SignatureWidget({
    container: '#signature-widget',
    token: 'aB3xY…',
    documentUrl: 'https://docs.yakoafrica.ci/contrats/ABC123.pdf',
    documentDescription: 'Contrat de souscription n°ABC123',
    backendWebhookUrl: 'https://api.yakoafrica.ci/api/v1/signature/webhook',
    apiUrl: 'https://api.yakoafrica.ci/api/v1/signature',
    signingLink: 'https://api.yakoafrica.ci/signature/widget/aB3xY…',
    enableAutoPolling: true,
    onSigned: function (data) { /* … */ },
    onError: function (err) { /* … */ }
  });
</script>
```

Notez qu'il n'y a plus ni `webhookUrl` ni `apiKey` : ces valeurs sont
rattachées au token côté serveur.

> **Important en embarqué :** l'appel à `backendWebhookUrl` devient
> cross-origin. Ajoutez le domaine de votre app hôte dans `config/cors.php`
> pour le chemin `api/v1/signature/*`, sinon le navigateur bloquera le
> preflight — exactement le symptôme « 404 sur la prod » rencontré
> précédemment. En ouvrant simplement `widget_url`, le problème ne se pose pas.

---

## 6. Déploiement

1. Copier les fichiers :
   - `app/Services/Api/Ynov/SignatureService.php`
   - `app/Http/Controllers/Api/Ynov/SignatureController.php`
   - `app/Models/Api/Ynov/SignatureRequest.php`
   - `resources/views/signature/widget.blade.php`
   - `resources/views/signature/invalid.blade.php`
   - `public/assets/js/signature-widget.js`
2. Reporter les routes depuis `routes/signature-routes.php` dans `web.php` et `api.php`.
3. `php artisan migrate` (la migration purge les demandes à l'ancien format de token).
4. Supprimer l'utilisateur système devenu inutile :
   `User::where('email', 'signature@system.local')->delete();`
5. Configurer `config/services.php` :

```php
'infobip' => [
    'api_key'       => env('INFOBIP_API_KEY'),
    'base_url'      => env('INFOBIP_BASE_URL'),
    'sms_from'      => env('INFOBIP_SMS_FROM', 'YAKOA'),
    'whatsapp_from' => env('INFOBIP_WHATSAPP_FROM'),
],
'signature' => [
    'allowed_document_hosts' => array_filter(
        explode(',', (string) env('SIGNATURE_ALLOWED_DOC_HOSTS', ''))
    ),
],
```

6. `php artisan route:clear && php artisan config:clear && php artisan view:clear`
   (un cache de routes obsolète est une cause classique de 404 en production).

---

## 7. Recette de test

| # | Test | Attendu |
|---|---|---|
| 1 | Générer un lien, ouvrir `widget_url` sur mobile, signer | Écran de succès côté client (immédiat) ; votre endpoint webhook reçoit l'appel décrit en **4.2** quelques centaines de ms plus tard ; `is_used: true` |
| 2 | Ouvrir `widget_url` sur desktop, scanner le QR, signer sur mobile | Le desktop bascule sur « Document signé » en moins de 5 s |
| 3 | Rouvrir le même lien après signature | Page « Ce lien n'est plus valable » (404) |
| 4 | Générer avec `expires_in: 60`, attendre 2 min, ouvrir | Page « Ce lien n'est plus valable » |
| 5 | Poster deux signatures en parallèle sur le même token | Une seule réussit, l'autre reçoit `TOKEN_ALREADY_USED` (409) |
| 6 | Couper le webhook de l'app hôte, signer | `WEBHOOK_DELIVERY_FAILED` (502), `delivery_status: failed`, token consommé |
| 7 | `SELECT * FROM signature_requests` après signature | Aucune colonne ne contient la signature |
| 8 | `GET /signature/proxy-document?url=http://127.0.0.1/` | `403` |
