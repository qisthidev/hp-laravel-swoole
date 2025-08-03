<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AdministrativeRegion;
use App\Http\Resources\AdministrativeRegionResource;
use App\Http\Resources\AdministrativeRegionCollection;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Illuminate\Support\Facades\Cache;

class AdministrativeRegionController extends Controller
{
    /**
     * Get all administrative regions with optional filtering
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->get('per_page', 50), 500);
        
        $query = QueryBuilder::for(AdministrativeRegion::class)
            ->allowedFilters([
                AllowedFilter::exact('type'),
                AllowedFilter::exact('parent_id'),
                AllowedFilter::partial('search', ['name', 'slug']),
            ])
            ->allowedSorts(['name', 'type', 'code', 'area', 'population'])
            ->defaultSort('name');

        // Include relationships based on request parameters
        if ($request->boolean('include_boundaries')) {
            $query->with('geographicBoundary');
        }

        if ($request->boolean('include_postal_codes')) {
            $query->with('postalCodes');
        }

        $regions = $query->paginate($perPage);

        return response()->json([
            'data' => AdministrativeRegionResource::collection($regions),
            'meta' => [
                'current_page' => $regions->currentPage(),
                'per_page' => $regions->perPage(),
                'total' => $regions->total(),
                'last_page' => $regions->lastPage(),
            ],
            'links' => [
                'first' => $regions->url(1),
                'last' => $regions->url($regions->lastPage()),
                'prev' => $regions->previousPageUrl(),
                'next' => $regions->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Get a specific administrative region by ID
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $cacheKey = "region_{$id}_" . md5(serialize($request->only(['include_boundaries', 'include_children', 'include_postal_codes'])));

        $region = Cache::remember($cacheKey, 3600, function () use ($request, $id) {
            $query = AdministrativeRegion::where('id', $id);

            if ($request->boolean('include_boundaries')) {
                $query->with('geographicBoundary');
            }

            if ($request->boolean('include_children')) {
                $query->with('children');
            }

            if ($request->boolean('include_postal_codes')) {
                $query->with('postalCodes');
            }

            return $query->firstOrFail();
        });

        return response()->json([
            'data' => new AdministrativeRegionResource($region),
        ]);
    }

    /**
     * Get direct children regions of a specific region
     */
    public function children(Request $request, string $id): JsonResponse
    {
        $region = AdministrativeRegion::findOrFail($id);

        $query = $region->children();

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->boolean('include_boundaries')) {
            $query->with('geographicBoundary');
        }

        if ($request->boolean('include_postal_codes')) {
            $query->with('postalCodes');
        }

        $children = $query->get();

        return response()->json([
            'data' => AdministrativeRegionResource::collection($children),
        ]);
    }

    /**
     * Get the hierarchical path from province to the specified region
     */
    public function ancestors(Request $request, string $id): JsonResponse
    {
        $region = AdministrativeRegion::findOrFail($id);
        $ancestors = $region->getAncestors();

        $ancestorsData = collect($ancestors)->map(function ($ancestor, $index) {
            return [
                'id' => $ancestor->id,
                'name' => $ancestor->name,
                'type' => $ancestor->type,
                'level' => $index + 1,
            ];
        });

        return response()->json([
            'data' => $ancestorsData,
        ]);
    }
}