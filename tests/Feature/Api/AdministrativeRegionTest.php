<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\AdministrativeRegion;
use App\Models\PostalCode;
use App\Models\GeographicBoundary;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdministrativeRegionTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_all_regions(): void
    {
        // Create test data
        $region = AdministrativeRegion::factory()->create([
            'type' => 'provinsi',
            'name' => 'DKI Jakarta',
            'slug' => 'dki-jakarta',
        ]);

        $response = $this->getJson('/api/v1/regions');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'type',
                        'code',
                        'parent_id',
                        'coordinates',
                        'area',
                        'population',
                        'description',
                        'created_at',
                        'updated_at',
                    ]
                ],
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                ],
                'links' => [
                    'first',
                    'last',
                    'prev',
                    'next',
                ],
            ]);
    }

    public function test_can_get_region_by_id(): void
    {
        $region = AdministrativeRegion::factory()->create([
            'id' => 'dki-jakarta',
            'name' => 'DKI Jakarta',
            'type' => 'provinsi',
        ]);

        $response = $this->getJson('/api/v1/regions/dki-jakarta');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => 'dki-jakarta',
                    'name' => 'DKI Jakarta',
                    'type' => 'provinsi',
                ]
            ]);
    }

    public function test_can_filter_regions_by_type(): void
    {
        AdministrativeRegion::factory()->create(['type' => 'provinsi']);
        AdministrativeRegion::factory()->create(['type' => 'kabupaten']);

        $response = $this->getJson('/api/v1/regions?type=provinsi');

        $response->assertStatus(200);
        $this->assertEquals(1, count($response->json('data')));
    }

    public function test_can_search_regions(): void
    {
        AdministrativeRegion::factory()->create(['name' => 'DKI Jakarta']);
        AdministrativeRegion::factory()->create(['name' => 'Jawa Barat']);

        $response = $this->getJson('/api/v1/regions?search=jakarta');

        $response->assertStatus(200);
        $this->assertEquals(1, count($response->json('data')));
    }

    public function test_can_get_region_children(): void
    {
        $parent = AdministrativeRegion::factory()->create([
            'id' => 'dki-jakarta',
            'type' => 'provinsi',
        ]);

        $child = AdministrativeRegion::factory()->create([
            'parent_id' => 'dki-jakarta',
            'type' => 'kota',
        ]);

        $response = $this->getJson('/api/v1/regions/dki-jakarta/children');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_can_get_region_ancestors(): void
    {
        $province = AdministrativeRegion::factory()->create([
            'id' => 'dki-jakarta',
            'type' => 'provinsi',
            'parent_id' => null,
        ]);

        $regency = AdministrativeRegion::factory()->create([
            'id' => 'jakarta-pusat',
            'type' => 'kota',
            'parent_id' => 'dki-jakarta',
        ]);

        $response = $this->getJson('/api/v1/regions/jakarta-pusat/ancestors');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJson([
                'data' => [
                    [
                        'id' => 'dki-jakarta',
                        'level' => 1,
                    ]
                ]
            ]);
    }

    public function test_returns_404_for_nonexistent_region(): void
    {
        $response = $this->getJson('/api/v1/regions/nonexistent');

        $response->assertStatus(404)
            ->assertJson([
                'error' => [
                    'code' => 'NOT_FOUND',
                ]
            ]);
    }

    public function test_can_get_postal_codes(): void
    {
        PostalCode::factory()->create([
            'code' => '10110',
            'area_name' => 'Jakarta Pusat',
        ]);

        $response = $this->getJson('/api/v1/postal-codes');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'code',
                        'region_id',
                        'area_name',
                        'coordinates',
                        'delivery_office',
                        'created_at',
                        'updated_at',
                    ]
                ],
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                ],
            ]);
    }

    public function test_can_get_specific_postal_code(): void
    {
        PostalCode::factory()->create([
            'code' => '10110',
            'area_name' => 'Jakarta Pusat',
        ]);

        $response = $this->getJson('/api/v1/postal-codes/10110');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'code' => '10110',
                    'area_name' => 'Jakarta Pusat',
                ]
            ]);
    }

    public function test_can_search_postal_codes(): void
    {
        PostalCode::factory()->create(['area_name' => 'Jakarta Pusat']);
        PostalCode::factory()->create(['area_name' => 'Bandung']);

        $response = $this->getJson('/api/v1/postal-codes?search=jakarta');

        $response->assertStatus(200);
        $this->assertEquals(1, count($response->json('data')));
    }

    public function test_can_get_statistics(): void
    {
        AdministrativeRegion::factory()->count(5)->create();
        PostalCode::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_regions',
                    'by_type',
                    'total_postal_codes',
                    'coverage',
                    'last_updated',
                ]
            ]);
    }

    public function test_can_search_globally(): void
    {
        AdministrativeRegion::factory()->create(['name' => 'DKI Jakarta']);
        PostalCode::factory()->create(['area_name' => 'Jakarta Pusat']);

        $response = $this->getJson('/api/v1/search?q=jakarta');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'regions',
                    'postal_codes',
                ],
                'meta' => [
                    'total_results',
                    'query',
                    'search_time',
                ]
            ]);
    }

    public function test_can_get_autocomplete_suggestions(): void
    {
        AdministrativeRegion::factory()->create(['name' => 'DKI Jakarta']);
        AdministrativeRegion::factory()->create(['name' => 'Jawa Barat']);

        $response = $this->getJson('/api/v1/autocomplete?q=jak');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'type',
                        'full_name',
                        'hierarchy_level',
                    ]
                ],
                'meta' => [
                    'query',
                    'total_suggestions',
                ]
            ]);
    }
}