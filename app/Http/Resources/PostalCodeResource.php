<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostalCodeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'code' => $this->code,
            'region_id' => $this->region_id,
            'area_name' => $this->area_name,
            'coordinates' => $this->coordinates,
            'delivery_office' => $this->delivery_office,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];

        // Include region if loaded
        if ($this->relationLoaded('region') && $this->region) {
            $data['region'] = [
                'id' => $this->region->id,
                'name' => $this->region->name,
                'type' => $this->region->type,
            ];
        }

        // Include distance if calculated
        if (isset($this->distance)) {
            $data['distance'] = $this->distance;
        }

        return $data;
    }
}