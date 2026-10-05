# Réassignation Multiple de RDV

## Overview

Cette fonctionnalité permet de réassigner plusieurs rendez-vous (RDV) à un gestionnaire en une seule opération, avec une notification groupée au lieu de notifications individuelles.

## Endpoint

```
POST /api/v1/rdvs/traitement/reassigner-multiple
```

## Permissions

- `rdvs.retransmettre` - Permission requise pour réassigner des RDV

## Request Body

```json
{
  "rdv_uuids": [
    "uuid-rdv-1",
    "uuid-rdv-2",
    "uuid-rdv-3"
  ],
  "gestionnaire_uuid": "uuid-du-gestionnaire",
  "motif_reassignations": [
    "uuid-motif-1",
    "uuid-motif-2"
  ],
  "agence_effective_uuid": "uuid-agence-optionnelle",
  "date_rdv_effective": "2026-09-15",
  "observation": "Réassignation groupée pour optimisation"
}
```

### Paramètres

| Paramètre | Type | Requis | Description |
|-----------|------|---------|-------------|
| `rdv_uuids` | array | Oui | Liste des UUIDs des RDV à réassigner |
| `rdv_uuids.*` | string | Oui | UUID d'un RDV (doit exister) |
| `gestionnaire_uuid` | string | Oui | UUID du nouveau gestionnaire |
| `motif_reassignations` | array | Oui | Liste des UUIDs des motifs de réassignation |
| `motif_reassignations.*` | string | Oui | UUID d'un motif de traitement |
| `agence_effective_uuid` | string | Non | UUID de la nouvelle agence effective (optionnel) |
| `date_rdv_effective` | date | Non | Nouvelle date du RDV effective (optionnel) |
| `observation` | string | Non | Observation sur la réassignation (max 1000 caractères) |

## Response

### Succès (200)

```json
{
  "success": true,
  "code": "RDVS_REASSIGNES",
  "message": "3 RDV réassigné(s) avec succès.",
  "data": {
    "total": 3,
    "reassignees": 3,
    "echecs": 0,
    "details": [
      {
        "rdv_code": "RDV-20260706-AbC12345",
        "status": "reassignee",
        "ancien_gestionnaire": "550e8400-e29b-41d4-a716-446655440020"
      },
      {
        "rdv_code": "RDV-20260706-AbC12346",
        "status": "reassignee",
        "ancien_gestionnaire": "550e8400-e29b-41d4-a716-446655440020"
      },
      {
        "rdv_code": "RDV-20260706-AbC12347",
        "status": "reassignee",
        "ancien_gestionnaire": "550e8400-e29b-41d4-a716-446655440020"
      }
    ],
    "gestionnaire_uuid": "550e8400-e29b-41d4-a716-446655440021",
    "gestionnaire_label": "Konan Blaise"
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

#### Gestionnaire n'appartient pas à l'agence (422)

```json
{
  "success": false,
  "message": "Le gestionnaire n'appartient pas à cette agence.",
  "code": "GESTIONNAIRE_NOT_IN_AGENCE"
}
```

#### RDV non trouvés (404)

```json
{
  "success": false,
  "message": "Aucun RDV trouvé.",
  "code": "RDVS_NOT_FOUND"
}
```

#### Erreur de validation (422)

```json
{
  "success": false,
  "message": "Erreur de validation.",
  "errors": {
    "rdv_uuids": ["La liste des RDV est requise."],
    "gestionnaire_uuid": ["Le nouveau gestionnaire est requis."],
    "motif_reassignations": ["Au moins un motif de réassignation est requis."]
  },
  "code": "VALIDATION_ERROR"
}
```

## Comportement

### Logique de traitement

1. **Validation** : Vérifie que le gestionnaire existe et appartient à l'agence des RDV
2. **Récupération** : Charge tous les RDV spécifiés
3. **Traitement individuel** : Pour chaque RDV :
   - Vérifie qu'il n'est pas déjà assigné au même gestionnaire
   - Vérifie que le gestionnaire appartient à l'agence du RDV
   - Met à jour le gestionnaire, les motifs et les données optionnelles
   - Change le statut en 'transmis' si le RDV était en 'en_attente'
   - Crée un log d'activité individuel
4. **Notification groupée** : Envoie une seule notification au nouveau gestionnaire avec la liste des RDV
5. **Retour** : Renvoie un résumé détaillé de l'opération

### Gestion des erreurs individuelles

- Si un RDV est déjà assigné au même gestionnaire, il est ignoré mais comptabilisé comme échec
- Si le gestionnaire n'appartient pas à l'agence du RDV, le RDV est ignoré
- L'opération continue pour les autres RDV même si certains échouent
- Les détails de chaque RDV sont retournés dans la réponse

### Notification

Une seule notification est envoyée au nouveau gestionnaire avec :
- **Titre** : "📋 Réassignation groupée de RDV"
- **Corps** : "X rendez-vous vous ont été réassignés : RDV-001, RDV-002, RDV-003"
- **Métadonnées** : Liste complète des UUIDs et codes des RDV

## Comparaison avec la réassignation simple

| Aspect | Réassignation Simple | Réassignation Multiple |
|--------|---------------------|------------------------|
| Endpoint | `/rdvs/traitement/{uuid}/reassigner` | `/rdvs/traitement/reassigner-multiple` |
| Notifications | 1 notification par RDV (gestionnaire + client) | 1 notification groupée (gestionnaire uniquement) |
| Performance | Plusieurs requêtes API | 1 seule requête API |
| Logs | 1 log par RDV | 1 log par RDV |
| Transaction | 1 transaction par RDV | 1 transaction globale |
| Motifs | Requis pour chaque RDV | Appliqués à tous les RDV |

## Exemple d'utilisation

### JavaScript/Fetch

```javascript
const response = await fetch('/api/v1/rdvs/traitement/reassigner-multiple', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${token}`
  },
  body: JSON.stringify({
    rdv_uuids: [
      'uuid-rdv-1',
      'uuid-rdv-2',
      'uuid-rdv-3'
    ],
    gestionnaire_uuid: 'gestionnaire-uuid',
    motif_reassignations: ['motif-uuid-1', 'motif-uuid-2'],
    agence_effective_uuid: 'agence-uuid-optionnelle',
    date_rdv_effective: '2026-09-15',
    observation: 'Réassignation groupée pour optimisation'
  })
});

const result = await response.json();
console.log(result);
```

### PHP/Laravel

```php
$response = Http::withToken($token)
    ->post('/api/v1/rdvs/traitement/reassigner-multiple', [
        'rdv_uuids' => [
            'uuid-rdv-1',
            'uuid-rdv-2',
            'uuid-rdv-3'
        ],
        'gestionnaire_uuid' => 'gestionnaire-uuid',
        'motif_reassignations' => ['motif-uuid-1', 'motif-uuid-2'],
        'agence_effective_uuid' => 'agence-uuid-optionnelle',
        'date_rdv_effective' => '2026-09-15',
        'observation' => 'Réassignation groupée pour optimisation'
    ]);

return $response->json();
```

## Avantages

1. **Performance** : Une seule requête au lieu de plusieurs
2. **Experience utilisateur** : Une seule notification au lieu de plusieurs
3. **Cohérence** : Transaction globale garantissant l'intégrité
4. **Traçabilité** : Logs détaillés pour chaque RDV
5. **Flexibilité** : Possibilité de mixer réussites et échecs dans une même opération
6. **Efficiency** : Réduction de la charge de notification pour les gestionnaires

## Différences avec les prestations

La réassignation multiple de RDV diffère légèrement de celle des prestations :

- **Motifs obligatoires** : Les RDV nécessitent des motifs de réassignation (contrairement aux prestations)
- **Validation d'agence** : Vérifie que le gestionnaire appartient à l'agence du RDV
- **Statut automatique** : Les RDV en 'en_attente' passent automatiquement en 'transmis'
- **Client notification** : Les clients ne sont pas notifiés dans la version groupée (contrairement à la version simple)
