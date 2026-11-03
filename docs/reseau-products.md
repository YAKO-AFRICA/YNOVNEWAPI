# Produits commercialises par reseau

Les routes sont disponibles sous le prefixe `/api/param`.

## Liste des parametrages

`GET /api/param/get-reseau-product`

Filtres optionnels : `reseau_uuid`, `product_uuid`, `formule_uuid` et `formule_key`. La reponse contient une pagination de 10 parametrages et chaque element expose notamment `formule_key`, `frais_adhesion`, `tag`, `popular`, `display_name`, `description` et `avantage`.

Exemple :

```http
GET /api/param/get-reseau-product?reseau_uuid=reseau-uuid
```

## Ajouter un produit a un reseau

`POST /api/param/store-product-reseau`

Champs requis : `reseau_uuid`, `product_uuid`, `formule_uuid`, `formule_key` (chaines) et `frais_adhesion` (entier).

Champs optionnels : `tag` (chaine ou `null`), `popular` (booleen), `display_name`, `description` et `avantage` (chaines ou `null`). Le parametrage est cree avec `etat: "actif"`. Les champs optionnels non transmis sont initialises a `null`, sauf `popular` qui vaut `false`.

Exemple de corps :

```json
{
  "reseau_uuid": "reseau-uuid",
  "product_uuid": "product-uuid",
  "formule_uuid": "formule-uuid",
  "formule_key": "FORMULE_STANDARD",
  "frais_adhesion": 2500,
  "tag": "recommande",
  "popular": true,
  "display_name": "Offre standard",
  "description": "Description de l'offre",
  "avantage": "Avantage inclus"
}
```

## Modifier un parametrage

`PUT /api/param/update-product-reseau/{uuid}`

Tous les champs acceptes lors de la creation sont modifiables. Chaque champ est facultatif : seuls les champs transmis sont mis a jour. Les valeurs `null` sont acceptees pour `tag`, `display_name`, `description` et `avantage`.

Exemple de corps :

```json
{
  "display_name": "Nouvelle offre",
  "popular": false,
  "avantage": null
}
```

## Produits retournes avec leur parametrage reseau

`POST /api/param/get-product-by-reseau` accepte `reseau_uuid`. Chaque produit retourne inclut aussi `formule_key`, `frais_adhesion`, `tag`, `popular`, `display_name`, `description` et `avantage`. Le detail complet du parametrage est egalement present dans `details_reseau_product`.

`POST /api/param/get-product-by-partner` accepte `code_partner` et expose ces memes champs sur chaque produit retourne.
