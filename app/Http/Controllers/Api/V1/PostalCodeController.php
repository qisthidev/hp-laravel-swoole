<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PostalCode;
use App\Http\Resources\PostalCodeResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Illuminate\Support\Facades\Cache;

class PostalCodeController extends Controller
{
    /**
     * Get postal codes with optional filtering
     */
    public function index(Request $request): JsonResponse
    {
        $query = QueryBuilder::for(PostalCode::class)
            ->allowedFilters([
                AllowedFilter::exact('code'),
                AllowedFilter::exact('region_id'),
                AllowedFilter::scope('region_type'),
                AllowedFilter::partial('search', ['area_name']),
            ])
            ->allowedSorts(['code', 'area_name'])
            ->defaultSort('code');

        // Handle coordinate-based search
        if ($request->filled('coordinates')) {
            $coordinates = explode(',', $request->coordinates);
            if (count($coordinates) === 2) {
                $latitude = (float) $coordinates[0];
                $longitude = (float) $coordinates[1];
                $radius = min((float) $request->get('radius', 5), 50);
                
                $query->nearCoordinates($latitude, $longitude, $radius);
            }
        }

        $query->with('region');

        $postalCodes = $query->paginate(50);

        return response()->json([
            'data' => PostalCodeResource::collection($postalCodes),
            'meta' => [
                'current_page' => $postalCodes->currentPage(),
                'per_page' => $postalCodes->perPage(),
                'total' => $postalCodes->total(),
                'last_page' => $postalCodes->lastPage(),
            ],
        ]);
    }

    /**
     * Get detailed information for a specific postal code
     */
    public function show(Request $request, string $code): JsonResponse
    {
        $cacheKey = "postal_code_{$code}";

        $postalCode = Cache::remember($cacheKey, 3600, function () use ($code) {
            return PostalCode::with('region')->where('code', $code)->firstOrFail();
        });

        return response()->json([
            'data' => new PostalCodeResource($postalCode),
        ]);
    }

    /**
     * Lookup multiple postal codes in a single request
     */
    public function bulkLookup(Request $request): JsonResponse
    {
        $request->validate([
            'codes' => 'required|array|min:1|max:100',
            'codes.*' => 'string|size:5',
            'include_boundaries' => 'boolean',
        ]);

        $codes = $request->codes;
        $includeBoundaries = $request->boolean('include_boundaries');

        $query = PostalCode::with('region')->whereIn('code', $codes);

        if ($includeBoundaries) {
            $query->with('region.geographicBoundary');
        }

        $postalCodes = $query->get();

        // Calculate distances if coordinates provided
        if ($request->filled('coordinates')) {
            $coordinates = explode(',', $request->coordinates);
            if (count($coordinates) === 2) {
                $latitude = (float) $coordinates[0];
                $longitude = (float) $coordinates[1];

                $postalCodes->each(function ($postalCode) use ($latitude, $longitude) {
                    $postalCode->distance = $postalCode->calculateDistance($latitude, $longitude);
                });

                $postalCodes = $postalCodes->sortBy('distance');
            }
        }

        return response()->json([
            'data' => PostalCodeResource::collection($postalCodes),
            'meta' => [
                'total_found' => $postalCodes->count(),
                'total_requested' => count($codes),
                'missing_codes' => array_diff($codes, $postalCodes->pluck('code')->toArray()),
            ],
        ]);
    }
}