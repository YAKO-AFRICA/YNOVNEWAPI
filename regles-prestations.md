# Règles d'éligibilité des prestations contractuelles

Ce document décrit les règles métier régissant l'accès aux différentes prestations selon le produit d'assurance et l'état du contrat. Il est conçu pour être réutilisable dans d'autres projets utilisant une logique similaire (produit → seuil → autorisation).

## 1. Données préalables au calcul

Avant d'évaluer l'éligibilité d'une prestation, il faut calculer pour le contrat concerné :

| Variable | Description |
|---|---|
| `NbrencConfirmer` | Nombre d'encaissements confirmés |
| `Duree` | Durée de cotisation attendue, convertie selon la périodicité (Mensuel / Trimestriel / Semestriel / Annuel / Unique) |
| `cumulCotisationTerme` | Montant total attendu à terme (`Duree × prime`) |
| `contisationPourcentage` | 15 % du cumul attendu à terme |
| `TotalEncaissement` | Somme réellement encaissée à ce jour |
| `dureeCotisation` | Durée déjà cotisée, convertie en années selon la périodicité |

### Conversion de la périodicité en durée attendue

| Code périodicité | Calcul |
|---|---|
| `M` (Mensuel) | Durée en années × 12 |
| `T` (Trimestriel) | Durée en mois / 3 |
| `S` (Semestriel) | Durée en mois / 6 |
| `A` (Annuel) | Durée en mois / 12 |
| `U` (Unique) | Nombre d'encaissements confirmés |

## 2. Familles de produits

| Famille | Codes produit |
|---|---|
| Épargne | `CADENCE`, `DOIHOO`, `CAD_EDUCPLUS`, `PFA_IND` |
| Obsèques groupe 1 | `YKE_2008`, `YKE_2018` |
| Obsèques groupe 2 | `YKS_2008`, `YKS_2018` |

## 3. Codes de prestations concernés

| Code | Libellé | Impact |
|---|---|---|
| 20 | Avance | 0 |
| 21 | Dénonciation | 0 |
| 22 | Renonciation | 1 |
| 23 | Rachat total | 1 |
| 24 | Rachat partiel | 0 |
| 25 | Remboursement (trop perçu après fin cotisation YKF, YKS) | 0 |
| 27 | Terme | 1 |
| 31 | Remboursement (trop perçu après arrêt contrat) | 0 |
| 36 | Résiliation | 1 |
| 37 | Terme Cotisations YAKO | 1 |
| 40 | Remboursement (trop perçu pendant cotisation) | 0 |
| 41 | Remboursement (trop perçu après fin cotisation) | 0 |
| 44 | Remboursement (trop perçu après sinistre) | 0 |
| 48 | Remboursement (trop perçu après fin avance) | 0 |

**Famille "Remboursement"** (traitée comme un bloc homogène, mêmes règles pour tous) : codes 25, 31, 40, 41, 44, 48.

**Groupe "Terme"** (traité comme un bloc homogène, mêmes règles pour les deux) : codes 27 et 37.

## 4. Règles d'éligibilité par famille de produit

### Famille Épargne

- Toute prestation à `impact = 0`, **sauf** celle appartenant à la famille Remboursement : **bloquée si le nombre d'encaissements confirmés est ≤ 24**.
- Prestations de la famille Remboursement : **toujours autorisées**, aucune condition d'ancienneté.
- Rachat total (code 23, `impact = 1`) : **bloqué si le total encaissé est ≤ 15 % du cumul attendu à terme**.

### Famille Obsèques groupe 1

- Avance (code 20, `impact = 0`) : **bloquée si le nombre d'encaissements confirmés est ≤ 13**.
- Rachat partiel (code 24, `impact = 0`) : **bloqué si la durée de cotisation prévue au contrat (en années) est supérieure ou égale à la durée déjà cotisée**.
- Prestations de la famille Remboursement : **toujours autorisées**.
- Terme (code 27) et Terme Cotisations YAKO (code 37) : **autorisés uniquement si la durée de cotisation prévue au contrat (en années) est supérieure ou égale à la durée déjà cotisée** (calculée à partir de `NbrencConfirmer`) — même condition que le Rachat partiel.
- Renonciation (22) et Résiliation (36) : **toujours autorisées**, aucune condition spécifique.

### Famille Obsèques groupe 2

- Toutes les prestations, quel que soit leur `impact`, sont **toujours autorisées**, sans condition d'ancienneté ni de montant.

### Cas particulier : `impact = 'Autre'`

- Toujours autorisé, indépendamment du produit. Redirection vers un parcours dédié.

## 5. Tableau récapitulatif

| Famille produit | Prestation (code) | Condition de blocage |
|---|---|---|
| Épargne | Tout `impact=0` hors famille Remboursement | Bloqué si encaissements confirmés ≤ 24 |
| Épargne | Rachat total (23) | Bloqué si total encaissé ≤ 15 % du cumul à terme |
| Épargne | Famille Remboursement (25, 31, 40, 41, 44, 48) | Jamais bloqué |
| Obsèques groupe 1 | Avance (20) | Bloqué si encaissements confirmés ≤ 13 |
| Obsèques groupe 1 | Rachat partiel (24) | Bloqué si durée contractuelle ≥ durée déjà cotisée |
| Obsèques groupe 1 | Famille Remboursement | Jamais bloqué |
| Obsèques groupe 1 | Terme (27), Terme YAKO (37) | Bloqué si durée contractuelle < durée déjà cotisée |
| Obsèques groupe 1 | Renonciation (22), Résiliation (36) | Jamais bloqué |
| Obsèques groupe 2 | Toutes prestations | Jamais bloqué |
| Toutes familles | `impact='Autre'` | Jamais bloqué |

