<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Grimzy\LaravelMysqlSpatial\Eloquent\SpatialTrait;

class PostalCode extends Model
{
    use HasFactory, SpatialTrait;

    protected $fillable = [
        'code',
        'region_id',
        'area_name',
        'coordinates',
        'delivery_office',
    ];

    protected $casts = [
        'coordinates' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $spatialFields = [
        'coordinates',
    ];

    // Relationships
    public function region(): BelongsTo
    {
        return $this->belongsTo(AdministrativeRegion::class, 'region_id');
    }

    // Scopes
    public function scopeByCode($query, string $code)
    {
        return $query->where('code', $code);
    }

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

    public function scopeSearch($query, string $search)
    {
        return $query->where('area_name', 'like', "%{$search}%");
    }

    public function scopeNearCoordinates($query, float $latitude, float $longitude, float $radius = 5)
    {
        return $query->whereRaw(
            'ST_Distance_Sphere(coordinates, POINT(?, ?)) <= ?',
            [$longitude, $latitude, $radius * 1000] // Convert km to meters
        );
    }

    // Accessors
    public function getFormattedCodeAttribute(): string
    {
        return substr($this->code, 0, 2) . '-' . substr($this->code, 2);
    }

    public function getDistanceAttribute(): ?float
    {
        return $this->attributes['distance'] ?? null;
    }

    // Methods
    public function calculateDistance(float $latitude, float $longitude): float
    {
        if (!$this->coordinates) {
            return 0;
        }

        $lat1 = $this->coordinates['latitude'];
        $lng1 = $this->coordinates['longitude'];
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

    public function isValid(): bool
    {
        return preg_match('/^\d{5}$/', $this->code);
    }
}