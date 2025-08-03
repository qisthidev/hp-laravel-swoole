<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Grimzy\LaravelMysqlSpatial\Eloquent\SpatialTrait;

class AdministrativeRegion extends Model
{
    use HasFactory, SpatialTrait;

    protected $fillable = [
        'id',
        'name',
        'slug',
        'type',
        'code',
        'parent_id',
        'postal_codes',
        'coordinates',
        'boundaries',
        'area',
        'population',
        'description',
        'dataset_url',
    ];

    protected $casts = [
        'postal_codes' => 'array',
        'coordinates' => 'array',
        'boundaries' => 'array',
        'area' => 'float',
        'population' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $spatialFields = [
        'coordinates',
        'boundaries',
    ];

    // Region types
    const TYPE_PROVINSI = 'provinsi';
    const TYPE_KABUPATEN = 'kabupaten';
    const TYPE_KOTA = 'kota';
    const TYPE_KECAMATAN = 'kecamatan';
    const TYPE_KELURAHAN = 'kelurahan';
    const TYPE_DESA = 'desa';

    public static function getTypes(): array
    {
        return [
            self::TYPE_PROVINSI,
            self::TYPE_KABUPATEN,
            self::TYPE_KOTA,
            self::TYPE_KECAMATAN,
            self::TYPE_KELURAHAN,
            self::TYPE_DESA,
        ];
    }

    // Relationships
    public function parent(): BelongsTo
    {
        return $this->belongsTo(AdministrativeRegion::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(AdministrativeRegion::class, 'parent_id');
    }

    public function postalCodes(): HasMany
    {
        return $this->hasMany(PostalCode::class, 'region_id');
    }

    public function geographicBoundary(): HasOne
    {
        return $this->hasOne(GeographicBoundary::class, 'region_id');
    }

    // Scopes
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByParent($query, string $parentId)
    {
        return $query->where('parent_id', $parentId);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('slug', 'like', "%{$search}%");
        });
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        if ($this->parent) {
            return "{$this->name}, {$this->parent->name}";
        }
        return $this->name;
    }

    public function getHierarchyLevelAttribute(): int
    {
        $levels = [
            self::TYPE_PROVINSI => 1,
            self::TYPE_KABUPATEN => 2,
            self::TYPE_KOTA => 2,
            self::TYPE_KECAMATAN => 3,
            self::TYPE_KELURAHAN => 4,
            self::TYPE_DESA => 4,
        ];

        return $levels[$this->type] ?? 0;
    }

    // Methods
    public function getAncestors(): array
    {
        $ancestors = [];
        $current = $this;

        while ($current->parent) {
            $ancestors[] = $current->parent;
            $current = $current->parent;
        }

        return array_reverse($ancestors);
    }

    public function getDescendants(): array
    {
        $descendants = [];
        
        foreach ($this->children as $child) {
            $descendants[] = $child;
            $descendants = array_merge($descendants, $child->getDescendants());
        }

        return $descendants;
    }

    public function isProvince(): bool
    {
        return $this->type === self::TYPE_PROVINSI;
    }

    public function isRegency(): bool
    {
        return $this->type === self::TYPE_KABUPATEN;
    }

    public function isCity(): bool
    {
        return $this->type === self::TYPE_KOTA;
    }

    public function isDistrict(): bool
    {
        return $this->type === self::TYPE_KECAMATAN;
    }

    public function isVillage(): bool
    {
        return in_array($this->type, [self::TYPE_KELURAHAN, self::TYPE_DESA]);
    }
}