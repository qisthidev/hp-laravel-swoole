<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Grimzy\LaravelMysqlSpatial\Eloquent\SpatialTrait;

class GeographicBoundary extends Model
{
    use HasFactory, SpatialTrait;

    protected $fillable = [
        'region_id',
        'geometry',
        'centroid',
        'bbox',
        'precision',
        'source',
    ];

    protected $casts = [
        'centroid' => 'array',
        'bbox' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $spatialFields = [
        'geometry',
        'centroid',
    ];

    // Precision levels
    const PRECISION_HIGH = 'high';
    const PRECISION_MEDIUM = 'medium';
    const PRECISION_LOW = 'low';

    public static function getPrecisionLevels(): array
    {
        return [
            self::PRECISION_HIGH,
            self::PRECISION_MEDIUM,
            self::PRECISION_LOW,
        ];
    }

    // Relationships
    public function region(): BelongsTo
    {
        return $this->belongsTo(AdministrativeRegion::class, 'region_id');
    }

    // Scopes
    public function scopeByRegion($query, string $regionId)
    {
        return $query->where('region_id', $regionId);
    }

    public function scopeByRegionType($query, string $regionType)
    {
        return $query->whereHas('region', function ($q) use ($regionType) {
            $q->where('type', $regionType);
        });
    }

    public function scopeByPrecision($query, string $precision)
    {
        return $query->where('precision', $precision);
    }

    public function scopeInBoundingBox($query, float $minLat, float $minLng, float $maxLat, float $maxLng)
    {
        return $query->whereRaw(
            'ST_Intersects(geometry, ST_GeomFromText(?))',
            ["POLYGON(($minLng $minLat, $maxLng $minLat, $maxLng $maxLat, $minLng $maxLat, $minLng $minLat))"]
        );
    }

    // Accessors
    public function getBoundingBoxArrayAttribute(): array
    {
        if (!$this->bbox) {
            return [];
        }

        return [
            'min_lat' => $this->bbox['min_lat'] ?? null,
            'min_lng' => $this->bbox['min_lng'] ?? null,
            'max_lat' => $this->bbox['max_lat'] ?? null,
            'max_lng' => $this->bbox['max_lng'] ?? null,
        ];
    }

    public function getCentroidArrayAttribute(): array
    {
        if (!$this->centroid) {
            return [];
        }

        return [
            'latitude' => $this->centroid['latitude'] ?? null,
            'longitude' => $this->centroid['longitude'] ?? null,
        ];
    }

    // Methods
    public function getSimplifiedGeometry(float $tolerance = 0.001): array
    {
        if (!$this->geometry) {
            return [];
        }

        // This would typically use a spatial function like ST_Simplify
        // For now, we'll return the original geometry
        return $this->geometry;
    }

    public function calculateArea(): float
    {
        if (!$this->geometry) {
            return 0;
        }

        // This would typically use ST_Area spatial function
        // For now, we'll return 0
        return 0;
    }

    public function intersectsWith(GeographicBoundary $other): bool
    {
        if (!$this->geometry || !$other->geometry) {
            return false;
        }

        // This would typically use ST_Intersects spatial function
        // For now, we'll return false
        return false;
    }

    public function containsPoint(float $latitude, float $longitude): bool
    {
        if (!$this->geometry) {
            return false;
        }

        // This would typically use ST_Contains spatial function
        // For now, we'll return false
        return false;
    }

    public function getDistanceToPoint(float $latitude, float $longitude): float
    {
        if (!$this->centroid) {
            return 0;
        }

        $lat1 = $this->centroid['latitude'];
        $lng1 = $this->centroid['longitude'];
        $lat2 = $latitude;
        $lng2 = $longitude;

        $earthRadius = 6371; // Earth's radius in kilometers

        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lngDelta / 2) * sin($lngDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}