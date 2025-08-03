<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AdministrativeRegion;
use App\Models\PostalCode;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    /**
     * Global search across all regions and postal codes
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2',
            'type' => 'nullable|in:region,postal_code',
            'region_type' => 'nullable|in:' . implode(',', AdministrativeRegion::getTypes()),
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $query = $request->get('q');
        $type = $request->get('type');
        $regionType = $request->get('region_type');
        $limit = min($request->get('limit', 20), 100);

        $startTime = microtime(true);

        $results = [
            'regions' => [],
            'postal_codes' => [],
        ];

        // Search regions
        if (!$type || $type === 'region') {
            $regionQuery = AdministrativeRegion::query();

            if ($regionType) {
                $regionQuery->where('type', $regionType);
            }

            $regions = $regionQuery->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('slug', 'like', "%{$query}%")
                  ->orWhere('code', 'like', "%{$query}%");
            })
            ->with('parent')
            ->limit($limit)
            ->get()
            ->map(function ($region) use ($query) {
                $matchScore = $this->calculateMatchScore($region->name, $query);
                if ($region->slug === $query) {
                    $matchScore = 1.0;
                }

                return [
                    'id' => $region->id,
                    'name' => $region->name,
                    'type' => $region->type,
                    'parent_name' => $region->parent ? $region->parent->name : null,
                    'match_score' => $matchScore,
                ];
            })
            ->sortByDesc('match_score')
            ->values();

            $results['regions'] = $regions;
        }

        // Search postal codes
        if (!$type || $type === 'postal_code') {
            $postalQuery = PostalCode::query();

            if ($regionType) {
                $postalQuery->whereHas('region', function ($q) use ($regionType) {
                    $q->where('type', $regionType);
                });
            }

            $postalCodes = $postalQuery->where(function ($q) use ($query) {
                $q->where('code', 'like', "%{$query}%")
                  ->orWhere('area_name', 'like', "%{$query}%");
            })
            ->with('region')
            ->limit($limit)
            ->get()
            ->map(function ($postalCode) use ($query) {
                $matchScore = $this->calculateMatchScore($postalCode->area_name, $query);
                if ($postalCode->code === $query) {
                    $matchScore = 1.0;
                }

                return [
                    'code' => $postalCode->code,
                    'area_name' => $postalCode->area_name,
                    'region_name' => $postalCode->region ? $postalCode->region->name : null,
                    'match_score' => $matchScore,
                ];
            })
            ->sortByDesc('match_score')
            ->values();

            $results['postal_codes'] = $postalCodes;
        }

        $searchTime = round((microtime(true) - $startTime) * 1000, 3);

        return response()->json([
            'data' => $results,
            'meta' => [
                'total_results' => count($results['regions']) + count($results['postal_codes']),
                'query' => $query,
                'search_time' => $searchTime . 'ms',
            ],
        ]);
    }

    /**
     * Get autocomplete suggestions for region names
     */
    public function autocomplete(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:1',
            'type' => 'nullable|in:' . implode(',', AdministrativeRegion::getTypes()),
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        $query = $request->get('q');
        $type = $request->get('type');
        $limit = min($request->get('limit', 10), 50);

        $suggestions = AdministrativeRegion::query()
            ->when($type, function ($q) use ($type) {
                $q->where('type', $type);
            })
            ->where('name', 'like', "{$query}%")
            ->select('id', 'name', 'type', 'parent_id')
            ->with('parent:id,name')
            ->limit($limit)
            ->get()
            ->map(function ($region) {
                return [
                    'id' => $region->id,
                    'name' => $region->name,
                    'type' => $region->type,
                    'full_name' => $region->full_name,
                    'hierarchy_level' => $region->hierarchy_level,
                ];
            });

        return response()->json([
            'data' => $suggestions,
            'meta' => [
                'query' => $query,
                'total_suggestions' => $suggestions->count(),
            ],
        ]);
    }

    /**
     * Calculate match score for search results
     */
    private function calculateMatchScore(string $text, string $query): float
    {
        $text = strtolower($text);
        $query = strtolower($query);

        // Exact match
        if ($text === $query) {
            return 1.0;
        }

        // Starts with query
        if (str_starts_with($text, $query)) {
            return 0.9;
        }

        // Contains query
        if (str_contains($text, $query)) {
            return 0.7;
        }

        // Partial match (word boundaries)
        $words = explode(' ', $text);
        $queryWords = explode(' ', $query);
        
        $matchedWords = 0;
        foreach ($queryWords as $queryWord) {
            foreach ($words as $word) {
                if (str_starts_with($word, $queryWord)) {
                    $matchedWords++;
                    break;
                }
            }
        }

        if ($matchedWords > 0) {
            return 0.5 * ($matchedWords / count($queryWords));
        }

        return 0.0;
    }
}