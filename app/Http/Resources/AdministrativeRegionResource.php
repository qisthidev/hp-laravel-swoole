<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdministrativeRegionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'type' => $this->type,
            'code' => $this->code,
            'parent_id' => $this->parent_id,
            'coordinates' => $this->coordinates,
            'area' => $this->area,
            'population' => $this->population,
            'description' => $this->description,
            'dataset_url' => $this->dataset_url,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];

        // Include postal codes if loaded
        if ($this->relationLoaded('postalCodes') || $this->postal_codes) {
            $data['postal_codes'] = $this->postal_codes ?? $this->postalCodes->pluck('code')->toArray();
        }

        // Include boundaries if loaded
        if ($this->relationLoaded('geographicBoundary') && $this->geographicBoundary) {
            $data['boundaries'] = $this->geographicBoundary->geometry;
        }

        // Include children if loaded
        if ($this->relationLoaded('children') && $this->children) {
            $data['children'] = AdministrativeRegionResource::collection($this->children);
        }

        // Include parent if loaded
        if ($this->relationLoaded('parent') && $this->parent) {
            $data['parent'] = new AdministrativeRegionResource($this->parent);
        }

        return $data;
    }
}