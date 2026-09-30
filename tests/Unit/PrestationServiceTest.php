<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\Api\Ynov\Prestation\PrestationService;
use App\Models\Api\Ynov\parameter\CategoryTypePrestation;
use App\Models\Api\Ynov\parameter\TypePrestation;
use Illuminate\Support\Str;

class PrestationServiceTest extends TestCase
{
    private PrestationService $prestationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prestationService = app(PrestationService::class);
    }

    /**
     * Test creating a category
     */
    public function test_create_category(): void
    {
        $categoryData = [
            'code' => 'TEST_CAT',
            'libelle' => 'Test Category',
            'description' => 'A test category for unit testing',
            'status' => 'actif',
        ];

        $category = $this->prestationService->createCategory($categoryData, 'test-user-uuid');

        $this->assertInstanceOf(CategoryTypePrestation::class, $category);
        $this->assertEquals('TEST_CAT', $category->code);
        $this->assertEquals('Test Category', $category->libelle);
        $this->assertEquals('A test category for unit testing', $category->description);
        $this->assertEquals('actif', $category->status);
    }

    /**
     * Test that category code must be unique
     */
    public function test_category_code_must_be_unique(): void
    {
        $categoryData = [
            'code' => 'DUPLICATE',
            'libelle' => 'First Category',
            'description' => 'First test category',
        ];

        // Create first category
        $this->prestationService->createCategory($categoryData, 'test-user-uuid');

        // Try to create duplicate
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->prestationService->createCategory($categoryData, 'test-user-uuid');
    }

    /**
     * Test creating a type prestation
     */
    public function test_create_type_prestation(): void
    {
        // First create a category
        $category = CategoryTypePrestation::create([
            'uuid_category_type_prestations' => (string) Str::uuid(),
            'code' => 'TECH',
            'libelle' => 'Technique',
            'description' => 'Technical services',
            'status' => 'actif',
            'created_by' => 'test-user',
        ]);

        $typePrestationData = [
            'code' => 'TEST_TYPE',
            'libelle' => 'Test Type',
            'description' => 'A test type prestation',
            'category_uuid' => $category->uuid_category_type_prestations,
            'impact' => '0',
            'delai_traitement' => 7,
            'status' => 'actif',
        ];

        $typePrestation = $this->prestationService->createTypePrestation($typePrestationData, 'test-user-uuid');

        $this->assertInstanceOf(TypePrestation::class, $typePrestation);
        $this->assertEquals('TEST_TYPE', $typePrestation->code);
        $this->assertEquals('Test Type', $typePrestation->libelle);
        $this->assertEquals($category->uuid_category_type_prestations, $typePrestation->category_uuid);
    }

    /**
     * Test getting statistics
     */
    public function test_get_statistics(): void
    {
        $stats = $this->prestationService->getStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('categories_total', $stats);
        $this->assertArrayHasKey('categories_active', $stats);
        $this->assertArrayHasKey('types_total', $stats);
        $this->assertArrayHasKey('types_active', $stats);
        $this->assertArrayHasKey('associations_total', $stats);
        $this->assertArrayHasKey('associations_active', $stats);
    }
}
