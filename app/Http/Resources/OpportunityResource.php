<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OpportunityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'title' => $this->title,
            'overview' => $this->overview,
            'location_description' => $this->location_description,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'status' => $this->workflow_status,
            'published_at' => $this->published_at,
            'district' => $this->whenLoaded('district', fn () => [
                'uuid' => $this->district->uuid,
                'name' => $this->district->name,
                'region' => $this->district->relationLoaded('region') ? [
                    'uuid' => $this->district->region->uuid,
                    'name' => $this->district->region->name,
                ] : null,
            ]),
            'sector' => $this->whenLoaded('sector', fn () => ['uuid' => $this->sector->uuid, 'name' => $this->sector->name]),
            'sub_sector' => $this->whenLoaded('subSector', fn () => $this->subSector ? ['uuid' => $this->subSector->uuid, 'name' => $this->subSector->name] : null),
            'enterprise_type' => $this->whenLoaded('enterpriseType', fn () => ['uuid' => $this->enterpriseType->uuid, 'name' => $this->enterpriseType->name]),
            'financial' => $this->whenLoaded('financial', fn () => $this->financial ? [
                'amount' => $this->financial->amount,
                'currency' => $this->financial->currency,
                'roi_percentage' => $this->financial->roi_percentage,
                'irr_percentage' => $this->financial->irr_percentage,
                'npv' => $this->financial->npv,
                'payback_period_months' => $this->financial->payback_period_months,
                'projected_revenue' => $this->financial->projected_revenue,
            ] : null),
            'links' => ['web' => route('opportunities.show', $this->uuid)],
        ];
    }
}
