<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AdministrativeRegion;
use App\Models\PostalCode;
use App\Models\GeographicBoundary;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StatisticsController extends Controller
{
    /**
     * Get general statistics about the dataset
     */
    public function index(): JsonResponse
    {
        $stats = Cache::remember('api_stats', 3600, function () {
            $totalRegions = AdministrativeRegion::count();
            $totalPostalCodes = PostalCode::count();
            
            $byType = AdministrativeRegion::select('type', DB::raw('count(*) as count'))
                ->groupBy('type')
                ->pluck('count', 'type')
                ->toArray();

            $coverage = $this->calculateCoverage();

            return [
                'total_regions' => $totalRegions,
                'by_type' => $byType,
                'total_postal_codes' => $totalPostalCodes,
                'coverage' => $coverage,
                'last_updated' => now()->toISOString(),
            ];
        });

        return response()->json([
            'data' => $stats,
        ]);
    }

    /**
     * Get statistics for a specific region
     */
    public function regionStats(Request $request, string $regionId): JsonResponse
    {
        $region = AdministrativeRegion::findOrFail($regionId);

        $stats = Cache::remember("region_stats_{$regionId}", 1800, function () use ($region) {
            $childrenCount = $region->children()->count();
            $descendantsCount = count($region->getDescendants());
            $postalCodesCount = $region->postalCodes()->count();
            $hasBoundary = $region->geographicBoundary()->exists();

            $childrenByType = $region->children()
                ->select('type', DB::raw('count(*) as count'))
                ->groupBy('type')
                ->pluck('count', 'type')
                ->toArray();

            return [
                'region_id' => $region->id,
                'region_name' => $region->name,
                'region_type' => $region->type,
                'children_count' => $childrenCount,
                'descendants_count' => $descendantsCount,
                'postal_codes_count' => $postalCodesCount,
                'has_boundary' => $hasBoundary,
                'children_by_type' => $childrenByType,
                'hierarchy_level' => $region->hierarchy_level,
                'area' => $region->area,
                'population' => $region->population,
                'last_updated' => $region->updated_at->toISOString(),
            ];
        });

        return response()->json([
            'data' => $stats,
        ]);
    }

    /**
     * Get information about recent data updates
     */
    public function updates(Request $request): JsonResponse
    {
        $limit = min($request->get('limit', 10), 50);

        $updates = Cache::remember("api_updates_{$limit}", 1800, function () use ($limit) {
            $recentRegions = AdministrativeRegion::select('id', 'name', 'type', 'updated_at')
                ->orderBy('updated_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($region) {
                    return [
                        'entity_type' => 'region',
                        'entity_id' => $region->id,
                        'entity_name' => $region->name,
                        'type' => $region->type,
                        'updated_at' => $region->updated_at->toISOString(),
                    ];
                });

            $recentPostalCodes = PostalCode::select('code', 'area_name', 'updated_at')
                ->orderBy('updated_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($postalCode) {
                    return [
                        'entity_type' => 'postal_code',
                        'entity_id' => $postalCode->code,
                        'entity_name' => $postalCode->area_name,
                        'updated_at' => $postalCode->updated_at->toISOString(),
                    ];
                });

            $recentBoundaries = GeographicBoundary::select('region_id', 'updated_at')
                ->with('region:id,name')
                ->orderBy('updated_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($boundary) {
                    return [
                        'entity_type' => 'boundary',
                        'entity_id' => $boundary->region_id,
                        'entity_name' => $boundary->region ? $boundary->region->name : null,
                        'updated_at' => $boundary->updated_at->toISOString(),
                    ];
                });

            $allUpdates = $recentRegions->concat($recentPostalCodes)->concat($recentBoundaries)
                ->sortByDesc('updated_at')
                ->take($limit)
                ->values();

            return [
                'recent_updates' => $allUpdates,
                'total_updates_today' => $this->getUpdatesCount('today'),
                'total_updates_week' => $this->getUpdatesCount('week'),
                'total_updates_month' => $this->getUpdatesCount('month'),
                'last_full_sync' => $this->getLastFullSyncDate(),
            ];
        });

        return response()->json([
            'data' => $updates,
        ]);
    }

    /**
     * Calculate data coverage statistics
     */
    private function calculateCoverage(): array
    {
        $totalRegions = AdministrativeRegion::count();
        $regionsWithBoundaries = GeographicBoundary::count();
        $regionsWithCoordinates = AdministrativeRegion::whereNotNull('coordinates')->count();
        $regionsWithPostalCodes = AdministrativeRegion::whereNotNull('postal_codes')->count();

        return [
            'with_boundaries' => $totalRegions > 0 ? round(($regionsWithBoundaries / $totalRegions) * 100, 1) : 0,
            'with_coordinates' => $totalRegions > 0 ? round(($regionsWithCoordinates / $totalRegions) * 100, 1) : 0,
            'with_postal_codes' => $totalRegions > 0 ? round(($regionsWithPostalCodes / $totalRegions) * 100, 1) : 0,
        ];
    }

    /**
     * Get count of updates for a specific period
     */
    private function getUpdatesCount(string $period): int
    {
        $query = DB::table('administrative_regions')
            ->union(DB::table('postal_codes'))
            ->union(DB::table('geographic_boundaries'));

        switch ($period) {
            case 'today':
                return $query->whereDate('updated_at', today())->count();
            case 'week':
                return $query->where('updated_at', '>=', now()->subWeek())->count();
            case 'month':
                return $query->where('updated_at', '>=', now()->subMonth())->count();
            default:
                return 0;
        }
    }

    /**
     * Get the last full sync date
     */
    private function getLastFullSyncDate(): ?string
    {
        // This would typically be stored in a configuration or separate table
        // For now, we'll return the most recent update
        $latestRegion = AdministrativeRegion::latest('updated_at')->first();
        $latestPostalCode = PostalCode::latest('updated_at')->first();
        $latestBoundary = GeographicBoundary::latest('updated_at')->first();

        $dates = collect([$latestRegion, $latestPostalCode, $latestBoundary])
            ->filter()
            ->pluck('updated_at')
            ->max();

        return $dates ? $dates->toISOString() : null;
    }
}