# Environment de Test

## Configuration

L'environnement de test utilise une base de données MySQL dédiée pour les tests.

### Fichiers de configuration

- `.env.testing` - Configuration spécifique pour les tests avec connexion MySQL
- `phpunit.xml` - Configuration PHPUnit avec paramètres de connexion MySQL
- `tests/TestCase.php` - Classe de base pour tous les tests
- `tests/CreatesApplication.php` - Trait pour créer l'application Laravel
- `tests/Traits/WithTestDatabase.php` - Trait pour la gestion de la base de données de test

### Base de données MySQL

- **Hôte** : 51.255.64.8
- **Port** : 3306
- **Base de données** : laloyale_bdynovtest
- **Utilisateur** : tyeeézrygerueazrgyazeza
- **Mot de passe** : zaryztguzerfbuzy

## Exécution des tests

### Tous les tests
```bash
php artisan test
```

### Tests unitaires uniquement
```bash
php artisan test --testsuite=Unit
```

### Tests fonctionnels uniquement
```bash
php artisan test --testsuite=Feature
```

### Un test spécifique
```bash
php artisan test --filter=test_create_category
```

### Avec les scripts dédiés

**Windows:**
```bash
run-tests.bat
```

**Linux/Mac:**
```bash
chmod +x run-tests.sh
./run-tests.sh
```

## Structure des tests

```
tests/
├── Unit/                  # Tests unitaires (logique métier, services, etc.)
│   ├── DatabaseConnectionTest.php
│   ├── PrestationServiceTest.php
│   └── ...
├── Feature/               # Tests fonctionnels (API, routes, etc.)
│   ├── PrestationRouteTest.php
│   └── ...
├── Setup/                 # Classes utilitaires pour la configuration
│   └── DatabaseSetup.php
├── Traits/                # Traits réutilisables pour les tests
│   └── WithTestDatabase.php
├── TestCase.php           # Classe de base pour tous les tests
└── CreatesApplication.php # Trait pour créer l'application
```

## Bonnes pratiques

### 1. Utiliser la base de données MySQL dédiée
Les tests utilisent automatiquement la base de données MySQL `laloyale_bdynovtest` configurée pour les tests.

### 2. Nettoyer après chaque test
Le trait `RefreshDatabase` nettoie automatiquement la base de données après chaque test avec `migrate:fresh`.

### 3. Utiliser les factories et seeders
Pour créer des données de test, utilisez les factories et seeders disponibles.

### 4. Isoler les tests
Chaque test doit être indépendant et ne pas dépendre de l'exécution d'autres tests.

### 5. Nommer les tests de manière descriptive
Utilisez des noms de tests qui décrivent clairement ce qui est testé :
```php
public function test_create_category_with_valid_data(): void
public function test_category_code_must_be_unique(): void
```

## Exemple de test unitaire

```php
<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\Api\Ynov\Prestation\PrestationService;
use App\Models\Api\Ynov\parameter\CategoryTypePrestation;

class PrestationServiceTest extends TestCase
{
    private PrestationService $prestationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prestationService = app(PrestationService::class);
    }

    public function test_create_category(): void
    {
        $categoryData = [
            'code' => 'TEST_CAT',
            'libelle' => 'Test Category',
            'description' => 'A test category',
        ];

        $category = $this->prestationService->createCategory($categoryData, 'test-user-uuid');

        $this->assertInstanceOf(CategoryTypePrestation::class, $category);
        $this->assertEquals('TEST_CAT', $category->code);
        $this->assertEquals('Test Category', $category->libelle);
    }
}
```

## Dépannage

### Erreur de connexion à la base de données
Vérifiez que `.env.testing` est correctement configuré avec SQLite.

### Erreur de migration
Assurez-vous que les fichiers de migration sont à jour et qu'il n'y a pas de conflits.

### Tests lents
Si les tests sont lents, vérifiez que vous utilisez bien SQLite en mémoire et non MySQL.
