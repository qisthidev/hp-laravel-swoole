<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GeographicBoundaryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'region_id' => $this->region_id,
            'geometry' => $this->geometry,
            'centroid' => $this->centroid,
            'bbox' => $this->bbox,
            'precision' => $this->precision,
            'source' => $this->source,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];

        // Include region if loaded
        if ($this->relationLoaded('region') && $this->region) {
            $data['region'] = [
                'id' => $this->region->id,
                'name' => $this->region->name,
                'type' => $this->region->type,
                'code' => $this->region->code,
            ];
        }

        return $data;
    }
}