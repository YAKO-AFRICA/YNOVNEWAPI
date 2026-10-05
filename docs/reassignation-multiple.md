# Réassignation Multiple de Prestations

## Overview

Cette fonctionnalité permet de réassigner plusieurs prestations à un gestionnaire en une seule opération, avec une notification groupée au lieu de notifications individuelles.

## Endpoint

```
POST /api/prestations/routing/reassign-multiple
```

## Permissions

- `prestations.retransmettre` - Permission requise pour réassigner des prestations

## Request Body

```json
{
  "prestation_uuids": [
    "uuid-prestation-1",
    "uuid-prestation-2",
    "uuid-prestation-3"
  ],
  "gestionnaire_uuid": "uuid-du-gestionnaire",
  "observation": "Réassignation pour optimisation de la charge de travail",
  "status": "transmis"
}
```

### Paramètres

| Paramètre | Type | Requis | Description |
|-----------|------|---------|-------------|
| `prestation_uuids` | array | Oui | Liste des UUIDs des prestations à réassigner |
| `prestation_uuids.*` | string | Oui | UUID d'une prestation (doit exister) |
| `gestionnaire_uuid` | string | Oui | UUID du nouveau gestionnaire |
| `observation` | string | Non | Observation pour la réassignation |
| `status` | string | Non | Nouveau statut (en_attente, transmis, accepte, rejete, annule) |

## Response

### Succès (200)

```json
{
  "success": true,
  "code": "PRESTATIONS_REASSIGNEES",
  "message": "3 prestation(s) réassignée(s) avec succès.",
  "data": {
    "total": 3,
    "reassignees": 3,
    "echecs": 0,
    "details": [
      {
        "prestation_code": "PREST-001",
        "status": "reassignee",
        "ancien_gestionnaire": "ancien-uuid-1"
      },
      {
        "prestation_code": "PREST-002",
        "status": "reassignee",
        "ancien_gestionnaire": "ancien-uuid-2"
      },
      {
        "prestation_code": "PREST-003",
        "status": "reassignee",
        "ancien_gestionnaire": "ancien-uuid-3"
      }
    ],
    "gestionnaire_uuid": "nouveau-gestionnaire-uuid",
    "gestionnaire_label": "Jean Dupont"
  }
}
```

### Erreurs

#### Gestionnaire non trouvé (422)

```json
{
  "success": false,
  "message": "Le gestionnaire n'existe pas.",
  "code": "GESTIONNAIRE_NOT_FOUND"
}
```

#### Pas un gestionnaire de prestation (422)

```json
{
  "success": false,
  "message": "Cet utilisateur n'est pas un gestionnaire de prestation.",
  "code": "NOT_GESTIONNAIRE_PRESTATION"
}
```

#### Prestations non trouvées (404)

```json
{
  "success": false,
  "message": "Aucune prestation trouvée.",
  "code": "PRESTATIONS_NOT_FOUND"
}
```

#### Erreur de validation (422)

```json
{
  "success": false,
  "message": "Erreur de validation.",
  "errors": {
    "prestation_uuids": ["La liste des prestations est requise."],
    "gestionnaire_uuid": ["Le gestionnaire est requis."]
  },
  "code": "VALIDATION_ERROR"
}
```

## Comportement

### Logique de traitement

1. **Validation** : Vérifie que le gestionnaire existe et a le rôle requis
2. **Récupération** : Charge toutes les prestations spécifiées
3. **Traitement individuel** : Pour chaque prestation :
   - Vérifie qu'elle n'est pas déjà assignée au même gestionnaire
   - Met à jour le gestionnaire et le statut si spécifié
   - Crée un log d'activité individuel
4. **Notification groupée** : Envoie une seule notification au nouveau gestionnaire avec la liste des prestations
5. **Retour** : Renvoie un résumé détaillé de l'opération

### Gestion des erreurs individuelles

- Si une prestation est déjà assignée au même gestionnaire, elle est ignorée mais comptabilisée comme échec
- L'opération continue pour les autres prestations même si certaines échouent
- Les détails de chaque prestation sont retournés dans la réponse

### Notification

Une seule notification est envoyée au nouveau gestionnaire avec :
- **Titre** : "📋 Réassignation groupée de prestations"
- **Corps** : "X prestation(s) vous ont été réassignées : PREST-001, PREST-002, PREST-003"
- **Métadonnées** : Liste complète des UUIDs et codes des prestations

## Comparaison avec la réassignation simple

| Aspect | Réassignation Simple | Réassignation Multiple |
|--------|---------------------|------------------------|
| Endpoint | `/prestations/{uuid}/reassign` | `/prestations/routing/reassign-multiple` |
| Notifications | 1 notification par prestation | 1 notification groupée |
| Performance | Plusieurs requêtes API | 1 seule requête API |
| Logs | 1 log par prestation | 1 log par prestation |
| Transaction | 1 transaction par prestation | 1 transaction globale |

## Exemple d'utilisation

### JavaScript/Fetch

```javascript
const response = await fetch('/api/prestations/routing/reassign-multiple', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${token}`
  },
  body: JSON.stringify({
    prestation_uuids: [
      'uuid-1',
      'uuid-2',
      'uuid-3'
    ],
    gestionnaire_uuid: 'gestionnaire-uuid',
    observation: 'Réassignation pour optimisation',
    status: 'transmis'
  })
});

const result = await response.json();
console.log(result);
```

### PHP/Laravel

```php
$response = Http::withToken($token)
    ->post('/api/prestations/routing/reassign-multiple', [
        'prestation_uuids' => [
            'uuid-1',
            'uuid-2',
            'uuid-3'
        ],
        'gestionnaire_uuid' => 'gestionnaire-uuid',
        'observation' => 'Réassignation pour optimisation',
        'status' => 'transmis'
    ]);

return $response->json();
```

## Avantages

1. **Performance** : Une seule requête au lieu de plusieurs
2. **Experience utilisateur** : Une seule notification au lieu de plusieurs
3. **Cohérence** : Transaction globale garantissant l'intégrité
4. **Traçabilité** : Logs détaillés pour chaque prestation
5. **Flexibilité** : Possibilité de mixer réussites et échecs dans une même opération
