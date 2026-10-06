# Documentation - Insertion d'une proposition eSouscription

## Objet

Cette documentation décrit le flux utilisé par la méthode `storeSouscription()` du contrôleur `PropositionController` pour créer une proposition de souscription complète.

L’endpoint permet de créer en une seule transaction :

- l’adhérent
- les assurés et leur déclaration de santé
- les bénéficiaires
- les documents
- le contrat

La création est faite dans une transaction database (`DB::transaction`), donc si une étape échoue, toutes les modifications sont annulées.

---

## Endpoint

```http
POST /api/v1/esouscription/store-propositition
```

Le point d’entrée est déclaré dans `routes/api.php` avec :

```php
Route::prefix('esouscription')->group(function () {
    Route::post('store-propositition', [PropositionController::class, 'storeSouscription']);
});
```

---

## Corps de requête

Le payload est envoyé au format JSON. Il contient 5 blocs principaux :

- `adherentData`
- `assurerDatas`
- `BeneficiaireDatas`
- `documentDatas`
- `contratData`

### 1) `adherentData`

Informations du souscripteur principal (adhérent).

Exemple :

```json
{
  "adherentData": {
    "created_by": "user-123",
    "civilite": "M",
    "genre": 1,
    "nom": "DIOP",
    "prenoms": "Ali",
    "date_naissance": "1990-05-15",
    "lieunaissance_code": "SN",
    "email": "ali@example.com",
    "mobile": "770000000",
    "telephone": "221000000",
    "numero_piece": "A123456",
    "nni": "1234567890123",
    "nature_piece": "CNI",
    "situation_matrimoniale": "CELIBATAIRE",
    "profession_code": "PROF-01",
    "employeur": "YAKO AFRICA",
    "lieuresidence_code": "DK",
    "pays_code": "SN"
  }
}
```

Notes :

- `genre` est casté en entier : `(int) $adherentData['genre']`
- la date de naissance est utilisée pour générer un `idClient`
- `created_by` est optionnel s’il n’est pas fourni, la méthode essaie de le récupérer depuis les autres blocs

---

### 2) `assurerDatas`

Tableau des assurés. Chaque élément contient :

- `info` : informations personnelles de l’assuré
- `sante` : déclaration médicale
- `is_adherent` : valeur `oui` pour indiquer que l’assuré est l’adhérent lui-même

Exemple :

```json
{
  "assurerDatas": [
    {
      "is_adherent": "oui",
      "info": {
        "created_by": "user-123",
        "civilite": "M",
        "genre": 1,
        "nom": "DIOP",
        "prenoms": "Ali",
        "date_naissance": "1990-05-15",
        "lieunaissance_code": "SN",
        "email": "ali@example.com",
        "mobile": "770000000",
        "telephone": "221000000",
        "numero_piece": "A123456",
        "nni": "1234567890123",
        "nature_piece": "CNI",
        "situation_matrimoniale": "CELIBATAIRE",
        "profession_code": "PROF-01",
        "employeur": "YAKO AFRICA",
        "lieuresidence_code": "DK",
        "pays_code": "SN"
      },
      "sante": {
        "taille": 175,
        "poids": 70,
        "tension_min": 10,
        "tension_max": 13,
        "tabagisme": "non",
        "alcool": "non",
        "sport": "oui",
        "accident": "non",
        "traitement": "non",
        "transfusion_sanguine": "non",
        "intervention_chirurgicale": "non",
        "prochaine_intervention_chirurgicale": "non",
        "diabete": "non",
        "hypertension": "non",
        "drepanocytose": "non",
        "cirrhose_foie": "non",
        "maladie_pulmonaire": "non",
        "cancer": "non",
        "anemie": "non",
        "insuffisance_renale": "non",
        "avc": "non"
      }
    },
    {
      "is_adherent": "non",
      "info": {
        "civilite": "F",
        "genre": 2,
        "nom": "DIOP",
        "prenoms": "Awa",
        "date_naissance": "1995-08-20",
        "lieunaissance_code": "SN",
        "email": "awa@example.com",
        "mobile": "771111111",
        "telephone": "221111111",
        "numero_piece": "B654321",
        "nni": "9876543210123",
        "nature_piece": "CNI",
        "situation_matrimoniale": "CELIBATAIRE",
        "profession_code": "PROF-02",
        "employeur": "Entreprise X",
        "lieuresidence_code": "DK",
        "pays_code": "SN"
      },
      "sante": {
        "taille": 165,
        "poids": 62,
        "tension_min": 9,
        "tension_max": 12,
        "tabagisme": "non",
        "alcool": "non",
        "sport": "oui",
        "accident": "non",
        "traitement": "non",
        "transfusion_sanguine": "non",
        "intervention_chirurgicale": "non",
        "prochaine_intervention_chirurgicale": "non",
        "diabete": "non",
        "hypertension": "non",
        "drepanocytose": "non",
        "cirrhose_foie": "non",
        "maladie_pulmonaire": "non",
        "cancer": "non",
        "anemie": "non",
        "insuffisance_renale": "non",
        "avc": "non"
      }
    }
  ]
}
```

Règles de traitement :

- si `is_adherent = "oui"` : l’assuré est l’adhérent ; pas de création d’acteur supplémentaire
- sinon : le code crée un acteur séparé pour cet assuré puis l’associe au contrat
- dans les deux cas, une déclaration santé est créée

---

### 3) `BeneficiaireDatas`

Tableau des bénéficiaires. Chaque bénéficiaire peut être :

- l’adhérent lui-même (`is_adherent = "oui"`)
- ou une autre personne (`is_adherent = "non"`)

Exemple :

```json
{
  "BeneficiaireDatas": [
    {
      "is_adherent": "oui",
      "created_by": "user-123",
      "civilite": "M",
      "genre": 1,
      "nom": "DIOP",
      "prenoms": "Ali",
      "date_naissance": "1990-05-15",
      "lieunaissance_code": "SN",
      "email": "ali@example.com",
      "mobile": "770000000",
      "telephone": "221000000",
      "numero_piece": "A123456",
      "nni": "1234567890123",
      "nature_piece": "CNI",
      "situation_matrimoniale": "CELIBATAIRE",
      "profession_code": "PROF-01",
      "employeur": "YAKO AFRICA",
      "lieuresidence_code": "DK",
      "pays_code": "SN"
    },
    {
      "is_adherent": "non",
      "created_by": "user-123",
      "civilite": "F",
      "genre": 2,
      "nom": "DIOP",
      "prenoms": "Marieme",
      "date_naissance": "2012-02-04",
      "lieunaissance_code": "SN",
      "email": "marieme@example.com",
      "mobile": "772222222",
      "telephone": "221222222",
      "numero_piece": "C654321",
      "nni": "1112223334445",
      "nature_piece": "CNI",
      "situation_matrimoniale": "CELIBATAIRE",
      "profession_code": "PROF-03",
      "employeur": "",
      "lieuresidence_code": "DK",
      "pays_code": "SN"
    }
  ]
}
```

Règles de traitement :

- si le bénéficiaire est `l'adhérent`, la méthode ne recrée pas de personne, elle crée simplement la relation du contrat avec type `BEN`
- sinon, elle crée un acteur bénéficiaire et l’associe au contrat

---

### 4) `documentDatas`

Liste des documents à rattacher au contrat.

Exemple :

```json
{
  "documentDatas": [
    {
      "nom": "piece_identite.pdf",
      "type": "identite",
      "url": "/storage/documents/piece_identite.pdf",
      "taille": 250000,
      "statut": "actif"
    },
    {
      "nom": "bulletin_sante.pdf",
      "type": "sante",
      "url": "/storage/documents/bulletin_sante.pdf",
      "taille": 310000,
      "statut": "actif"
    }
  ]
}
```

Ces documents sont envoyés au service `DocumentService` via :

```php
$payload = [
    'reference_uuid' => $contratUuid,
    'source' => 'E-SOUSCRIPTION',
    'created_by' => $createdBy,
    'documents' => $DocumentDatas,
];
```

---

### 5) `contratData`

Informations du contrat à enregistrer.

Exemple :

```json
{
  "contratData": {
    "created_by": "user-123",
    "date_effet": "2026-10-06",
    "mode_paiement": "VIREMENT",
    "organisme": "YAKO AFRICA",
    "duree": 12,
    "code_periodicite": "MENSUEL",
    "prime": 150000,
    "prime_principale": 150000,
    "sur_prime": 0,
    "capital": 5000000,
    "frais_adhesion": 2500,
    "montant_rente": 0,
    "periodicite_rente": null,
    "duree_rente": null,
    "code_banque": "SN001",
    "code_guichet": "0001",
    "rib": "12345678901234567890",
    "numero_compte": "00012345678",
    "numecompte_complet": "SN00100012345678",
    "agence_uuid": "7f3f2ed6-c94d-4ddb-9d3d-d4ea0d1fe2ca",
    "code_produit": "PROD-001",
    "libelle_produit": "Assurance Vie",
    "formule_produit_code": "FORM-01",
    "contact_personne_nom": "M. Ba",
    "contact_personne_mobile": "771234567",
    "contact_personne_nom_2": null,
    "contact_personne_mobile_2": null,
    "branch_code": "BRANCH-01",
    "partner_uuid": "7a21d7df-4ca2-4d32-b7de-8b78d2557c91",
    "conseiller_uuid": "c70d9d5e-0002-4627-976b-69b87358d111",
    "is_paid": true,
    "observation": "Souscription initiale",
    "bulletin_num": "BUL-2026-001",
    "formule": "FORMULE_STANDARD"
  }
}
```

---

## Exemple de payload complet

```json
{
  "adherentData": {
    "created_by": "user-123",
    "civilite": "M",
    "genre": 1,
    "nom": "DIOP",
    "prenoms": "Ali",
    "date_naissance": "1990-05-15",
    "lieunaissance_code": "SN",
    "email": "ali@example.com",
    "mobile": "770000000",
    "telephone": "221000000",
    "numero_piece": "A123456",
    "nni": "1234567890123",
    "nature_piece": "CNI",
    "situation_matrimoniale": "CELIBATAIRE",
    "profession_code": "PROF-01",
    "employeur": "YAKO AFRICA",
    "lieuresidence_code": "DK",
    "pays_code": "SN"
  },
  "assurerDatas": [
    {
      "is_adherent": "oui",
      "info": {
        "created_by": "user-123",
        "civilite": "M",
        "genre": 1,
        "nom": "DIOP",
        "prenoms": "Ali",
        "date_naissance": "1990-05-15",
        "lieunaissance_code": "SN",
        "email": "ali@example.com",
        "mobile": "770000000",
        "telephone": "221000000",
        "numero_piece": "A123456",
        "nni": "1234567890123",
        "nature_piece": "CNI",
        "situation_matrimoniale": "CELIBATAIRE",
        "profession_code": "PROF-01",
        "employeur": "YAKO AFRICA",
        "lieuresidence_code": "DK",
        "pays_code": "SN"
      },
      "sante": {
        "taille": 175,
        "poids": 70,
        "tension_min": 10,
        "tension_max": 13,
        "tabagisme": "non",
        "alcool": "non",
        "sport": "oui",
        "accident": "non",
        "traitement": "non",
        "transfusion_sanguine": "non",
        "intervention_chirurgicale": "non",
        "prochaine_intervention_chirurgicale": "non",
        "diabete": "non",
        "hypertension": "non",
        "drepanocytose": "non",
        "cirrhose_foie": "non",
        "maladie_pulmonaire": "non",
        "cancer": "non",
        "anemie": "non",
        "insuffisance_renale": "non",
        "avc": "non"
      }
    }
  ],
  "BeneficiaireDatas": [
    {
      "is_adherent": "oui",
      "created_by": "user-123",
      "civilite": "M",
      "genre": 1,
      "nom": "DIOP",
      "prenoms": "Ali",
      "date_naissance": "1990-05-15",
      "lieunaissance_code": "SN",
      "email": "ali@example.com",
      "mobile": "770000000",
      "telephone": "221000000",
      "numero_piece": "A123456",
      "nni": "1234567890123",
      "nature_piece": "CNI",
      "situation_matrimoniale": "CELIBATAIRE",
      "profession_code": "PROF-01",
      "employeur": "YAKO AFRICA",
      "lieuresidence_code": "DK",
      "pays_code": "SN"
    }
  ],
  "documentDatas": [
    {
      "nom": "piece_identite.pdf",
      "type": "identite",
      "url": "/storage/documents/piece_identite.pdf",
      "taille": 250000,
      "statut": "actif"
    }
  ],
  "contratData": {
    "created_by": "user-123",
    "date_effet": "2026-10-06",
    "mode_paiement": "VIREMENT",
    "organisme": "YAKO AFRICA",
    "duree": 12,
    "code_periodicite": "MENSUEL",
    "prime": 150000,
    "prime_principale": 150000,
    "sur_prime": 0,
    "capital": 5000000,
    "frais_adhesion": 2500,
    "montant_rente": 0,
    "code_banque": "SN001",
    "code_guichet": "0001",
    "rib": "12345678901234567890",
    "numero_compte": "00012345678",
    "numecompte_complet": "SN00100012345678",
    "agence_uuid": "7f3f2ed6-c94d-4ddb-9d3d-d4ea0d1fe2ca",
    "code_produit": "PROD-001",
    "libelle_produit": "Assurance Vie",
    "formule_produit_code": "FORM-01",
    "contact_personne_nom": "M. Ba",
    "contact_personne_mobile": "771234567",
    "branch_code": "BRANCH-01",
    "partner_uuid": "7a21d7df-4ca2-4d32-b7de-8b78d2557c91",
    "conseiller_uuid": "c70d9d5e-0002-4627-976b-69b87358d111",
    "is_paid": true,
    "observation": "Souscription initiale",
    "bulletin_num": "BUL-2026-001",
    "formule": "FORMULE_STANDARD"
  }
}
```

---

## Déroulement fonctionnel

La méthode exécute le flux suivant :

1. Génère un UUID unique pour le contrat
2. Génère une clé d’intégration à partir de `now()->format('Ymdh')`
3. Crée l’acteur adhérent
4. Associe l’adhérent au contrat avec type `ADH`
5. Pour chaque assuré :
   - crée l’acteur si nécessaire
   - crée la déclaration santé
   - associe au contrat avec type `ASS`
6. Pour chaque bénéficiaire :
   - crée l’acteur si nécessaire
   - associe au contrat avec type `BEN`
7. Enregistre les documents
8. Crée le contrat avec ses données financières et commerciales
9. Retourne le `key_integration` et le `contrat_uuid`

---

## Réponse de succès

```json
{
  "success": true,
  "message": "Souscription créée avec succès",
  "code": 200,
  "key_integration": "2026100614",
  "contrat_uuid": "1c9db7b8-6d5a-4d5c-bd1a-7fcec6a08ab1",
  "data": {
    "reference_uuid": "1c9db7b8-6d5a-4d5c-bd1a-7fcec6a08ab1",
    "source": "E-SOUSCRIPTION",
    "created_by": "user-123",
    "documents": [
      {
        "nom": "piece_identite.pdf"
      }
    ]
  }
}
```

---

## Réponse d’erreur

Si une étape échoue, la méthode retourne une erreur JSON :

```json
{
  "success": false,
  "message": "Erreur lors de la création de la souscription : ...",
  "code": 500
}
```

Et la transaction est automatiquement annulée.

---

## Points importants

- le nom du tableau `BeneficiaireDatas` est important : il commence par une majuscule
- les noms `info` et `sante` sont utilisés directement dans le contrôleur
- chaque acteur lié au contrat est enregistré via `contratActeurService`
- les documents sont attachés sur la base du `contrat_uuid`
- les informations de santé sont stockées dans `declaration_sante`

---

## Exemple d’appel curl

```bash
curl --location --request POST 'http://localhost:8000/api/v1/esouscription/store-propositition' \
  --header 'Content-Type: application/json' \
  --data '{
    "adherentData": {
      "created_by": "user-123",
      "civilite": "M",
      "genre": 1,
      "nom": "DIOP",
      "prenoms": "Ali",
      "date_naissance": "1990-05-15",
      "lieunaissance_code": "SN",
      "email": "ali@example.com",
      "mobile": "770000000",
      "telephone": "221000000",
      "numero_piece": "A123456",
      "nni": "1234567890123",
      "nature_piece": "CNI",
      "situation_matrimoniale": "CELIBATAIRE",
      "profession_code": "PROF-01",
      "employeur": "YAKO AFRICA",
      "lieuresidence_code": "DK",
      "pays_code": "SN"
    },
    "assurerDatas": [
      {
        "is_adherent": "oui",
        "info": {
          "created_by": "user-123",
          "civilite": "M",
          "genre": 1,
          "nom": "DIOP",
          "prenoms": "Ali",
          "date_naissance": "1990-05-15",
          "lieunaissance_code": "SN",
          "email": "ali@example.com",
          "mobile": "770000000",
          "telephone": "221000000",
          "numero_piece": "A123456",
          "nni": "1234567890123",
          "nature_piece": "CNI",
          "situation_matrimoniale": "CELIBATAIRE",
          "profession_code": "PROF-01",
          "employeur": "YAKO AFRICA",
          "lieuresidence_code": "DK",
          "pays_code": "SN"
        },
        "sante": {
          "taille": 175,
          "poids": 70,
          "tension_min": 10,
          "tension_max": 13,
          "tabagisme": "non",
          "alcool": "non",
          "sport": "oui",
          "accident": "non",
          "traitement": "non",
          "transfusion_sanguine": "non",
          "intervention_chirurgicale": "non",
          "prochaine_intervention_chirurgicale": "non",
          "diabete": "non",
          "hypertension": "non",
          "drepanocytose": "non",
          "cirrhose_foie": "non",
          "maladie_pulmonaire": "non",
          "cancer": "non",
          "anemie": "non",
          "insuffisance_renale": "non",
          "avc": "non"
        }
      }
    ],
    "BeneficiaireDatas": [
      {
        "is_adherent": "oui",
        "created_by": "user-123",
        "civilite": "M",
        "genre": 1,
        "nom": "DIOP",
        "prenoms": "Ali",
        "date_naissance": "1990-05-15",
        "lieunaissance_code": "SN",
        "email": "ali@example.com",
        "mobile": "770000000",
        "telephone": "221000000",
        "numero_piece": "A123456",
        "nni": "1234567890123",
        "nature_piece": "CNI",
        "situation_matrimoniale": "CELIBATAIRE",
        "profession_code": "PROF-01",
        "employeur": "YAKO AFRICA",
        "lieuresidence_code": "DK",
        "pays_code": "SN"
      }
    ],
    "documentDatas": [
      {
        "nom": "piece_identite.pdf",
        "type": "identite",
        "url": "/storage/documents/piece_identite.pdf",
        "taille": 250000,
        "statut": "actif"
      }
    ],
    "contratData": {
      "created_by": "user-123",
      "date_effet": "2026-10-06",
      "mode_paiement": "VIREMENT",
      "organisme": "YAKO AFRICA",
      "duree": 12,
      "code_periodicite": "MENSUEL",
      "prime": 150000,
      "prime_principale": 150000,
      "sur_prime": 0,
      "capital": 5000000,
      "frais_adhesion": 2500,
      "code_banque": "SN001",
      "code_guichet": "0001",
      "rib": "12345678901234567890",
      "numero_compte": "00012345678",
      "numecompte_complet": "SN00100012345678",
      "agence_uuid": "7f3f2ed6-c94d-4ddb-9d3d-d4ea0d1fe2ca",
      "code_produit": "PROD-001",
      "libelle_produit": "Assurance Vie",
      "formule_produit_code": "FORM-01",
      "contact_personne_nom": "M. Ba",
      "contact_personne_mobile": "771234567",
      "branch_code": "BRANCH-01",
      "partner_uuid": "7a21d7df-4ca2-4d32-b7de-8b78d2557c91",
      "conseiller_uuid": "c70d9d5e-0002-4627-976b-69b87358d111",
      "is_paid": true,
      "observation": "Souscription initiale",
      "bulletin_num": "BUL-2026-001",
      "formule": "FORMULE_STANDARD"
    }
  }'
```

---

## Fichiers liés

- contrôleur : `app/Http/Controllers/Api/Ynov/Esouscription/PropositionController.php`
- route : `routes/api.php`

Ce document décrit exactement l’insertion de la proposition selon le comportement réel de la méthode `storeSouscription()`.
