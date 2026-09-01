<?php

namespace App\Services;

use App\Models\District;
use App\Models\EnterpriseType;
use App\Models\Opportunity;
use App\Models\Region;
use App\Models\Sector;
use Illuminate\Support\Facades\Cache;

class PublicOpportunityFilters
{
    public function options(): array
    {
        return Cache::remember('public-opportunity-filters:v1', now()->addHour(), fn () => [
            'regions' => Region::query()->select('id', 'uuid', 'name')->orderBy('name')->get(),
            'districts' => District::query()
                ->select('id', 'uuid', 'region_id', 'name')
                ->with('region:id,uuid')
                ->where('workflow_status', District::STATUS_PUBLISHED)
                ->whereNotNull('published_at')
                ->orderBy('name')
                ->get(),
            'sectors' => Sector::query()->select('id', 'uuid', 'name')->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'types' => EnterpriseType::query()->select('id', 'uuid', 'name')->where('is_active', true)->orderBy('name')->get(),
            'statuses' => [
                Opportunity::WORKFLOW_APPROVED => 'Approved',
                Opportunity::WORKFLOW_ACTIVE => 'Active',
                Opportunity::WORKFLOW_COMPLETED => 'Completed',
                Opportunity::WORKFLOW_CANCELLED => 'Cancelled',
            ],
        ]);
    }
}
