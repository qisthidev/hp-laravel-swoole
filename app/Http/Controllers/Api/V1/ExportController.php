<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AdministrativeRegion;
use App\Models\PostalCode;
use App\Models\GeographicBoundary;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

class ExportController extends Controller
{
    /**
     * Export data in various formats
     */
    public function export(Request $request): Response|JsonResponse
    {
        $request->validate([
            'format' => 'required|in:csv,json,geojson,shapefile',
            'region_ids' => 'nullable|string',
            'type' => 'nullable|in:' . implode(',', AdministrativeRegion::getTypes()),
            'include_boundaries' => 'boolean',
            'compressed' => 'boolean',
        ]);

        $format = $request->get('format');
        $regionIds = $request->filled('region_ids') ? explode(',', $request->region_ids) : null;
        $type = $request->get('type');
        $includeBoundaries = $request->boolean('include_boundaries');
        $compressed = $request->boolean('compressed');

        // Build query
        $query = QueryBuilder::for(AdministrativeRegion::class)
            ->allowedFilters([
                AllowedFilter::exact('type'),
            ]);

        if ($regionIds) {
            $query->whereIn('id', $regionIds);
        }

        if ($type) {
            $query->where('type', $type);
        }

        if ($includeBoundaries) {
            $query->with('geographicBoundary');
        }

        $regions = $query->get();

        // Generate export based on format
        switch ($format) {
            case 'csv':
                return $this->exportAsCsv($regions, $includeBoundaries, $compressed);
            case 'json':
                return $this->exportAsJson($regions, $includeBoundaries, $compressed);
            case 'geojson':
                return $this->exportAsGeoJson($regions, $compressed);
            case 'shapefile':
                return $this->exportAsShapefile($regions, $compressed);
            default:
                return response()->json(['error' => 'Unsupported format'], 400);
        }
    }

    /**
     * Export as CSV
     */
    private function exportAsCsv($regions, bool $includeBoundaries, bool $compressed): Response
    {
        $filename = 'indonesia_regions_' . now()->format('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'id', 'name', 'slug', 'type', 'code', 'parent_id', 'postal_codes',
            'latitude', 'longitude', 'area', 'population', 'description', 'dataset_url'
        ];

        if ($includeBoundaries) {
            $headers[] = 'boundary_geometry';
        }

        $csvContent = $this->arrayToCsv($headers);

        foreach ($regions as $region) {
            $row = [
                $region->id,
                $region->name,
                $region->slug,
                $region->type,
                $region->code,
                $region->parent_id,
                $region->postal_codes ? implode(';', $region->postal_codes) : '',
                $region->coordinates['latitude'] ?? '',
                $region->coordinates['longitude'] ?? '',
                $region->area,
                $region->population,
                $region->description,
                $region->dataset_url,
            ];

            if ($includeBoundaries && $region->geographicBoundary) {
                $row[] = json_encode($region->geographicBoundary->geometry);
            }

            $csvContent .= $this->arrayToCsv($row);
        }

        if ($compressed) {
            $zipFilename = str_replace('.csv', '.zip', $filename);
            $zip = new \ZipArchive();
            $zip->open(storage_path("app/temp/{$zipFilename}"), \ZipArchive::CREATE);
            $zip->addFromString($filename, $csvContent);
            $zip->close();

            return response()->download(
                storage_path("app/temp/{$zipFilename}"),
                $zipFilename,
                ['Content-Type' => 'application/zip']
            )->deleteFileAfterSend();
        }

        return response($csvContent)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Export as JSON
     */
    private function exportAsJson($regions, bool $includeBoundaries, bool $compressed): Response
    {
        $filename = 'indonesia_regions_' . now()->format('Y-m-d_H-i-s') . '.json';
        
        $data = $regions->map(function ($region) use ($includeBoundaries) {
            $regionData = $region->toArray();
            
            if ($includeBoundaries && $region->geographicBoundary) {
                $regionData['boundary'] = $region->geographicBoundary->toArray();
            }

            return $regionData;
        });

        $jsonContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if ($compressed) {
            $zipFilename = str_replace('.json', '.zip', $filename);
            $zip = new \ZipArchive();
            $zip->open(storage_path("app/temp/{$zipFilename}"), \ZipArchive::CREATE);
            $zip->addFromString($filename, $jsonContent);
            $zip->close();

            return response()->download(
                storage_path("app/temp/{$zipFilename}"),
                $zipFilename,
                ['Content-Type' => 'application/zip']
            )->deleteFileAfterSend();
        }

        return response($jsonContent)
            ->header('Content-Type', 'application/json')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Export as GeoJSON
     */
    private function exportAsGeoJson($regions, bool $compressed): Response
    {
        $filename = 'indonesia_regions_' . now()->format('Y-m-d_H-i-s') . '.geojson';
        
        $features = $regions->map(function ($region) {
            $feature = [
                'type' => 'Feature',
                'id' => $region->id,
                'properties' => [
                    'id' => $region->id,
                    'name' => $region->name,
                    'slug' => $region->slug,
                    'type' => $region->type,
                    'code' => $region->code,
                    'parent_id' => $region->parent_id,
                    'postal_codes' => $region->postal_codes,
                    'area' => $region->area,
                    'population' => $region->population,
                    'description' => $region->description,
                    'dataset_url' => $region->dataset_url,
                ],
                'geometry' => null,
            ];

            if ($region->geographicBoundary && $region->geographicBoundary->geometry) {
                $feature['geometry'] = $region->geographicBoundary->geometry;
            } elseif ($region->coordinates) {
                $feature['geometry'] = [
                    'type' => 'Point',
                    'coordinates' => [
                        $region->coordinates['longitude'],
                        $region->coordinates['latitude'],
                    ],
                ];
            }

            return $feature;
        });

        $geojson = [
            'type' => 'FeatureCollection',
            'features' => $features,
        ];

        $jsonContent = json_encode($geojson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if ($compressed) {
            $zipFilename = str_replace('.geojson', '.zip', $filename);
            $zip = new \ZipArchive();
            $zip->open(storage_path("app/temp/{$zipFilename}"), \ZipArchive::CREATE);
            $zip->addFromString($filename, $jsonContent);
            $zip->close();

            return response()->download(
                storage_path("app/temp/{$zipFilename}"),
                $zipFilename,
                ['Content-Type' => 'application/zip']
            )->deleteFileAfterSend();
        }

        return response($jsonContent)
            ->header('Content-Type', 'application/geo+json')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Export as Shapefile
     */
    private function exportAsShapefile($regions, bool $compressed): Response
    {
        // This is a simplified implementation
        // In a real implementation, you would use a library like GDAL or similar
        $filename = 'indonesia_regions_' . now()->format('Y-m-d_H-i-s') . '.shp';
        
        // For now, we'll return a placeholder response
        $content = "Shapefile export not implemented in this version";
        
        if ($compressed) {
            $zipFilename = str_replace('.shp', '.zip', $filename);
            $zip = new \ZipArchive();
            $zip->open(storage_path("app/temp/{$zipFilename}"), \ZipArchive::CREATE);
            $zip->addFromString($filename, $content);
            $zip->close();

            return response()->download(
                storage_path("app/temp/{$zipFilename}"),
                $zipFilename,
                ['Content-Type' => 'application/zip']
            )->deleteFileAfterSend();
        }

        return response($content)
            ->header('Content-Type', 'application/octet-stream')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Convert array to CSV row
     */
    private function arrayToCsv(array $row): string
    {
        $csv = [];
        foreach ($row as $field) {
            $csv[] = '"' . str_replace('"', '""', $field) . '"';
        }
        return implode(',', $csv) . "\n";
    }
}