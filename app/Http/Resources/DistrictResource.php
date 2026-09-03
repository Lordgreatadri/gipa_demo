<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DistrictResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'code' => $this->code,
            'name' => $this->name,
            'capital' => $this->capital,
            'readiness_score' => $this->readiness_score,
            'population' => $this->population,
            'area_sq_km' => $this->area_sq_km,
            'infrastructure_quality_score' => $this->infrastructure_quality_score,
            'region' => new RegionResource($this->whenLoaded('region')),
            'published_opportunities_count' => $this->whenCounted('opportunities'),
            'links' => ['web' => route('districts.show', $this->uuid)],
        ];
    }
}
