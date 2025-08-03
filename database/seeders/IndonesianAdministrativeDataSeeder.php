<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AdministrativeRegion;
use App\Models\PostalCode;
use App\Models\GeographicBoundary;
use Laravolt\Indonesia\Models\Province;
use Laravolt\Indonesia\Models\Regency;
use Laravolt\Indonesia\Models\District;
use Laravolt\Indonesia\Models\Village;

class IndonesianAdministrativeDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding Indonesian administrative data...');

        // Seed provinces
        $this->seedProvinces();

        // Seed regencies/cities
        $this->seedRegencies();

        // Seed districts
        $this->seedDistricts();

        // Seed villages
        $this->seedVillages();

        // Seed sample postal codes
        $this->seedPostalCodes();

        // Seed sample geographic boundaries
        $this->seedGeographicBoundaries();

        $this->command->info('Indonesian administrative data seeded successfully!');
    }

    /**
     * Seed provinces
     */
    private function seedProvinces(): void
    {
        $this->command->info('Seeding provinces...');

        Province::chunk(100, function ($provinces) {
            foreach ($provinces as $province) {
                AdministrativeRegion::updateOrCreate(
                    ['id' => 'provinsi-' . strtolower(str_replace(' ', '-', $province->name))],
                    [
                        'name' => $province->name,
                        'slug' => strtolower(str_replace(' ', '-', $province->name)),
                        'type' => AdministrativeRegion::TYPE_PROVINSI,
                        'code' => $province->code,
                        'parent_id' => null,
                        'coordinates' => [
                            'latitude' => $this->getProvinceCoordinates($province->name)['latitude'] ?? null,
                            'longitude' => $this->getProvinceCoordinates($province->name)['longitude'] ?? null,
                        ],
                        'area' => $this->getProvinceArea($province->name),
                        'population' => $this->getProvincePopulation($province->name),
                        'description' => "Provinsi {$province->name}",
                    ]
                );
            }
        });
    }

    /**
     * Seed regencies and cities
     */
    private function seedRegencies(): void
    {
        $this->command->info('Seeding regencies and cities...');

        Regency::with('province')->chunk(100, function ($regencies) {
            foreach ($regencies as $regency) {
                $parentId = 'provinsi-' . strtolower(str_replace(' ', '-', $regency->province->name));
                $type = str_contains(strtolower($regency->name), 'kota') 
                    ? AdministrativeRegion::TYPE_KOTA 
                    : AdministrativeRegion::TYPE_KABUPATEN;

                AdministrativeRegion::updateOrCreate(
                    ['id' => 'regency-' . strtolower(str_replace(' ', '-', $regency->name))],
                    [
                        'name' => $regency->name,
                        'slug' => strtolower(str_replace(' ', '-', $regency->name)),
                        'type' => $type,
                        'code' => $regency->code,
                        'parent_id' => $parentId,
                        'coordinates' => [
                            'latitude' => $this->getRegencyCoordinates($regency->name)['latitude'] ?? null,
                            'longitude' => $this->getRegencyCoordinates($regency->name)['longitude'] ?? null,
                        ],
                        'area' => $this->getRegencyArea($regency->name),
                        'population' => $this->getRegencyPopulation($regency->name),
                        'description' => "{$type} {$regency->name}",
                    ]
                );
            }
        });
    }

    /**
     * Seed districts
     */
    private function seedDistricts(): void
    {
        $this->command->info('Seeding districts...');

        District::with('regency.province')->chunk(100, function ($districts) {
            foreach ($districts as $district) {
                $parentId = 'regency-' . strtolower(str_replace(' ', '-', $district->regency->name));

                AdministrativeRegion::updateOrCreate(
                    ['id' => 'district-' . strtolower(str_replace(' ', '-', $district->name))],
                    [
                        'name' => $district->name,
                        'slug' => strtolower(str_replace(' ', '-', $district->name)),
                        'type' => AdministrativeRegion::TYPE_KECAMATAN,
                        'code' => $district->code,
                        'parent_id' => $parentId,
                        'coordinates' => [
                            'latitude' => $this->getDistrictCoordinates($district->name)['latitude'] ?? null,
                            'longitude' => $this->getDistrictCoordinates($district->name)['longitude'] ?? null,
                        ],
                        'area' => $this->getDistrictArea($district->name),
                        'population' => $this->getDistrictPopulation($district->name),
                        'description' => "Kecamatan {$district->name}",
                    ]
                );
            }
        });
    }

    /**
     * Seed villages
     */
    private function seedVillages(): void
    {
        $this->command->info('Seeding villages...');

        Village::with('district.regency.province')->chunk(100, function ($villages) {
            foreach ($villages as $village) {
                $parentId = 'district-' . strtolower(str_replace(' ', '-', $village->district->name));
                $type = $village->meta->district_type === 'Kelurahan' 
                    ? AdministrativeRegion::TYPE_KELURAHAN 
                    : AdministrativeRegion::TYPE_DESA;

                AdministrativeRegion::updateOrCreate(
                    ['id' => 'village-' . strtolower(str_replace(' ', '-', $village->name))],
                    [
                        'name' => $village->name,
                        'slug' => strtolower(str_replace(' ', '-', $village->name)),
                        'type' => $type,
                        'code' => $village->code,
                        'parent_id' => $parentId,
                        'coordinates' => [
                            'latitude' => $this->getVillageCoordinates($village->name)['latitude'] ?? null,
                            'longitude' => $this->getVillageCoordinates($village->name)['longitude'] ?? null,
                        ],
                        'area' => $this->getVillageArea($village->name),
                        'population' => $this->getVillagePopulation($village->name),
                        'description' => "{$type} {$village->name}",
                    ]
                );
            }
        });
    }

    /**
     * Seed sample postal codes
     */
    private function seedPostalCodes(): void
    {
        $this->command->info('Seeding sample postal codes...');

        $samplePostalCodes = [
            ['code' => '10110', 'area_name' => 'Jakarta Pusat', 'region_id' => 'regency-jakarta-pusat'],
            ['code' => '10120', 'area_name' => 'Jakarta Pusat', 'region_id' => 'regency-jakarta-pusat'],
            ['code' => '12110', 'area_name' => 'Jakarta Barat', 'region_id' => 'regency-jakarta-barat'],
            ['code' => '12120', 'area_name' => 'Jakarta Barat', 'region_id' => 'regency-jakarta-barat'],
            ['code' => '13110', 'area_name' => 'Jakarta Utara', 'region_id' => 'regency-jakarta-utara'],
            ['code' => '13120', 'area_name' => 'Jakarta Utara', 'region_id' => 'regency-jakarta-utara'],
            ['code' => '14110', 'area_name' => 'Jakarta Selatan', 'region_id' => 'regency-jakarta-selatan'],
            ['code' => '14120', 'area_name' => 'Jakarta Selatan', 'region_id' => 'regency-jakarta-selatan'],
            ['code' => '15110', 'area_name' => 'Jakarta Timur', 'region_id' => 'regency-jakarta-timur'],
            ['code' => '15120', 'area_name' => 'Jakarta Timur', 'region_id' => 'regency-jakarta-timur'],
        ];

        foreach ($samplePostalCodes as $postalCode) {
            PostalCode::updateOrCreate(
                ['code' => $postalCode['code']],
                [
                    'region_id' => $postalCode['region_id'],
                    'area_name' => $postalCode['area_name'],
                    'coordinates' => [
                        'latitude' => -6.2088 + (rand(-10, 10) / 100),
                        'longitude' => 106.8456 + (rand(-10, 10) / 100),
                    ],
                    'delivery_office' => "Kantor Pos {$postalCode['area_name']}",
                ]
            );
        }
    }

    /**
     * Seed sample geographic boundaries
     */
    private function seedGeographicBoundaries(): void
    {
        $this->command->info('Seeding sample geographic boundaries...');

        $regions = AdministrativeRegion::where('type', AdministrativeRegion::TYPE_PROVINSI)
            ->orWhere('type', AdministrativeRegion::TYPE_KOTA)
            ->limit(10)
            ->get();

        foreach ($regions as $region) {
            GeographicBoundary::updateOrCreate(
                ['region_id' => $region->id],
                [
                    'geometry' => $this->generateSampleGeometry($region->coordinates),
                    'centroid' => $region->coordinates,
                    'bbox' => $this->generateBoundingBox($region->coordinates),
                    'precision' => GeographicBoundary::PRECISION_MEDIUM,
                    'source' => 'sample_data',
                ]
            );
        }
    }

    /**
     * Get province coordinates (sample data)
     */
    private function getProvinceCoordinates(string $provinceName): array
    {
        $coordinates = [
            'DKI Jakarta' => ['latitude' => -6.2088, 'longitude' => 106.8456],
            'Jawa Barat' => ['latitude' => -6.9175, 'longitude' => 107.6191],
            'Jawa Tengah' => ['latitude' => -7.1509, 'longitude' => 110.1403],
            'Jawa Timur' => ['latitude' => -7.5361, 'longitude' => 112.2384],
            'Banten' => ['latitude' => -6.4058, 'longitude' => 106.0644],
        ];

        return $coordinates[$provinceName] ?? ['latitude' => null, 'longitude' => null];
    }

    /**
     * Get province area (sample data)
     */
    private function getProvinceArea(string $provinceName): ?float
    {
        $areas = [
            'DKI Jakarta' => 664.01,
            'Jawa Barat' => 35244.23,
            'Jawa Tengah' => 32800.69,
            'Jawa Timur' => 47921.84,
            'Banten' => 9662.92,
        ];

        return $areas[$provinceName] ?? null;
    }

    /**
     * Get province population (sample data)
     */
    private function getProvincePopulation(string $provinceName): ?int
    {
        $populations = [
            'DKI Jakarta' => 10560000,
            'Jawa Barat' => 48200000,
            'Jawa Tengah' => 36700000,
            'Jawa Timur' => 40600000,
            'Banten' => 12300000,
        ];

        return $populations[$provinceName] ?? null;
    }

    /**
     * Get regency coordinates (sample data)
     */
    private function getRegencyCoordinates(string $regencyName): array
    {
        // Sample coordinates for some regencies
        return ['latitude' => -6.2088 + (rand(-50, 50) / 100), 'longitude' => 106.8456 + (rand(-50, 50) / 100)];
    }

    /**
     * Get regency area (sample data)
     */
    private function getRegencyArea(string $regencyName): ?float
    {
        return rand(100, 5000);
    }

    /**
     * Get regency population (sample data)
     */
    private function getRegencyPopulation(string $regencyName): ?int
    {
        return rand(100000, 5000000);
    }

    /**
     * Get district coordinates (sample data)
     */
    private function getDistrictCoordinates(string $districtName): array
    {
        return ['latitude' => -6.2088 + (rand(-20, 20) / 100), 'longitude' => 106.8456 + (rand(-20, 20) / 100)];
    }

    /**
     * Get district area (sample data)
     */
    private function getDistrictArea(string $districtName): ?float
    {
        return rand(10, 500);
    }

    /**
     * Get district population (sample data)
     */
    private function getDistrictPopulation(string $districtName): ?int
    {
        return rand(10000, 500000);
    }

    /**
     * Get village coordinates (sample data)
     */
    private function getVillageCoordinates(string $villageName): array
    {
        return ['latitude' => -6.2088 + (rand(-10, 10) / 100), 'longitude' => 106.8456 + (rand(-10, 10) / 100)];
    }

    /**
     * Get village area (sample data)
     */
    private function getVillageArea(string $villageName): ?float
    {
        return rand(1, 100);
    }

    /**
     * Get village population (sample data)
     */
    private function getVillagePopulation(string $villageName): ?int
    {
        return rand(1000, 50000);
    }

    /**
     * Generate sample geometry
     */
    private function generateSampleGeometry(?array $coordinates): ?array
    {
        if (!$coordinates) {
            return null;
        }

        $lat = $coordinates['latitude'];
        $lng = $coordinates['longitude'];

        // Generate a simple polygon around the point
        return [
            'type' => 'Polygon',
            'coordinates' => [[
                [$lng - 0.01, $lat - 0.01],
                [$lng + 0.01, $lat - 0.01],
                [$lng + 0.01, $lat + 0.01],
                [$lng - 0.01, $lat + 0.01],
                [$lng - 0.01, $lat - 0.01],
            ]],
        ];
    }

    /**
     * Generate bounding box
     */
    private function generateBoundingBox(?array $coordinates): ?array
    {
        if (!$coordinates) {
            return null;
        }

        $lat = $coordinates['latitude'];
        $lng = $coordinates['longitude'];

        return [
            'min_lat' => $lat - 0.01,
            'min_lng' => $lng - 0.01,
            'max_lat' => $lat + 0.01,
            'max_lng' => $lng + 0.01,
        ];
    }
}