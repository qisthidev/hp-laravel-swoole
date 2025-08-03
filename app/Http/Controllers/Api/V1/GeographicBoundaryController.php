<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\GeographicBoundary;
use App\Http\Resources\GeographicBoundaryResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Illuminate\Support\Facades\Cache;

class GeographicBoundaryController extends Controller
{
    /**
     * Get geographic boundaries for regions
     */
    public function index(Request $request): JsonResponse
    {
        $query = QueryBuilder::for(GeographicBoundary::class)
            ->allowedFilters([
                AllowedFilter::scope('region_type'),
                AllowedFilter::exact('precision'),
            ])
            ->allowedSorts(['region_id', 'precision'])
            ->with('region');

        // Handle region_ids filter
        if ($request->filled('region_ids')) {
            $regionIds = explode(',', $request->region_ids);
            $query->whereIn('region_id', $regionIds);
        }

        // Handle bounding box filter
        if ($request->filled('bbox')) {
            $bbox = explode(',', $request->bbox);
            if (count($bbox) === 4) {
                $minLat = (float) $bbox[0];
                $minLng = (float) $bbox[1];
                $maxLat = (float) $bbox[2];
                $maxLng = (float) $bbox[3];
                
                $query->inBoundingBox($minLat, $minLng, $maxLat, $maxLng);
            }
        }

        $boundaries = $query->get();

        // Format response based on requested format
        $format = $request->get('format', 'geojson');
        
        if ($format === 'geojson') {
            return $this->formatAsGeoJson($boundaries);
        } elseif ($format === 'topojson') {
            return $this->formatAsTopoJson($boundaries);
        } elseif ($format === 'wkt') {
            return $this->formatAsWKT($boundaries);
        }

        return response()->json([
            'data' => GeographicBoundaryResource::collection($boundaries),
        ]);
    }

    /**
     * Get boundary for a specific region
     */
    public function show(Request $request, string $regionId): JsonResponse
    {
        $cacheKey = "boundary_{$regionId}_" . md5(serialize($request->only(['precision', 'format', 'simplify'])));

        $boundary = Cache::remember($cacheKey, 3600, function () use ($request, $regionId) {
            $query = GeographicBoundary::with('region')->where('region_id', $regionId);

            if ($request->filled('precision')) {
                $query->where('precision', $request->precision);
            }

            return $query->firstOrFail();
        });

        // Apply simplification if requested
        if ($request->filled('simplify')) {
            $tolerance = max(0.0001, min(0.01, (float) $request->simplify));
            $boundary->geometry = $boundary->getSimplifiedGeometry($tolerance);
        }

        $format = $request->get('format', 'geojson');
        
        if ($format === 'geojson') {
            return $this->formatAsGeoJson(collect([$boundary]));
        } elseif ($format === 'topojson') {
            return $this->formatAsTopoJson(collect([$boundary]));
        } elseif ($format === 'wkt') {
            return $this->formatAsWKT(collect([$boundary]));
        }

        return response()->json([
            'data' => new GeographicBoundaryResource($boundary),
        ]);
    }

    /**
     * Format boundaries as GeoJSON
     */
    private function formatAsGeoJson($boundaries): JsonResponse
    {
        $features = $boundaries->map(function ($boundary) {
            return [
                'type' => 'Feature',
                'id' => $boundary->region_id,
                'properties' => [
                    'region_id' => $boundary->region_id,
                    'name' => $boundary->region->name ?? null,
                    'type' => $boundary->region->type ?? null,
                    'code' => $boundary->region->code ?? null,
                    'area' => $boundary->region->area ?? null,
                    'population' => $boundary->region->population ?? null,
                ],
                'geometry' => $boundary->geometry ?? null,
            ];
        });

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features,
        ]);
    }

    /**
     * Format boundaries as TopoJSON
     */
    private function formatAsTopoJson($boundaries): JsonResponse
    {
        // This is a simplified TopoJSON implementation
        // In a real implementation, you would use a library like topojson-server
        $features = $boundaries->map(function ($boundary) {
            return [
                'type' => 'Feature',
                'properties' => [
                    'region_id' => $boundary->region_id,
                    'name' => $boundary->region->name ?? null,
                ],
                'geometry' => $boundary->geometry ?? null,
            ];
        });

        return response()->json([
            'type' => 'Topology',
            'objects' => [
                'regions' => [
                    'type' => 'GeometryCollection',
                    'geometries' => $features,
                ],
            ],
        ]);
    }

    /**
     * Format boundaries as WKT (Well-Known Text)
     */
    private function formatAsWKT($boundaries): JsonResponse
    {
        $wktFeatures = $boundaries->map(function ($boundary) {
            // Convert geometry to WKT format
            $wkt = $this->geometryToWKT($boundary->geometry);
            
            return [
                'region_id' => $boundary->region_id,
                'name' => $boundary->region->name ?? null,
                'wkt' => $wkt,
            ];
        });

        return response()->json([
            'data' => $wktFeatures,
        ]);
    }

    /**
     * Convert geometry array to WKT format
     */
    private function geometryToWKT($geometry): string
    {
        if (!$geometry) {
            return '';
        }

        // This is a simplified conversion
        // In a real implementation, you would use a proper geometry library
        return 'GEOMETRY';
    }
}