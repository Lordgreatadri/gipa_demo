<?php

namespace App\Services;

use App\Models\Opportunity;
use App\Models\OpportunityFinancial;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class PublicOpportunitySearch
{
    public const PER_PAGE = 12;

    public function search(array $filters): LengthAwarePaginator
    {
        return Opportunity::query()
            ->select([
                'id',
                'uuid',
                'district_id',
                'sector_id',
                'sub_sector_id',
                'enterprise_type_id',
                'title',
                'location_description',
                'overview',
                'workflow_status',
                'published_at',
            ])
            ->publiclyVisible()
            ->with([
                'district:id,uuid,region_id,name,code',
                'district.region:id,uuid,name,code',
                'sector:id,uuid,name,code',
                'subSector:id,uuid,name',
                'enterpriseType:id,uuid,name',
                'financial:id,opportunity_id,amount,currency',
            ])
            ->when($filters['query'] ?? null, fn (Builder $query, string $term) => $query
                ->where(fn (Builder $search) => $search
                    ->whereLike('title', "%{$term}%")
                    ->orWhereLike('overview', "%{$term}%")
                    ->orWhereHas('district', fn (Builder $district) => $district->whereLike('name', "%{$term}%"))
                    ->orWhereHas('sector', fn (Builder $sector) => $sector->whereLike('name', "%{$term}%"))))
            ->when($filters['region'] ?? null, fn (Builder $query, string $uuid) => $query
                ->whereHas('district.region', fn (Builder $region) => $region->where('uuid', $uuid)))
            ->when($filters['district'] ?? null, fn (Builder $query, string $uuid) => $query
                ->whereHas('district', fn (Builder $district) => $district->where('uuid', $uuid)))
            ->when($filters['sector'] ?? null, fn (Builder $query, string $uuid) => $query
                ->whereHas('sector', fn (Builder $sector) => $sector->where('uuid', $uuid)))
            ->when($filters['type'] ?? null, fn (Builder $query, string $uuid) => $query
                ->whereHas('enterpriseType', fn (Builder $type) => $type->where('uuid', $uuid)))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query
                ->where('workflow_status', $status))
            ->when(
                ($filters['sort'] ?? null) === 'amount_asc',
                fn (Builder $query) => $query->orderBy(
                    OpportunityFinancial::select('amount')->whereColumn('opportunity_id', 'opportunities.id')->limit(1)
                ),
                fn (Builder $query) => $query->when(
                    ($filters['sort'] ?? null) === 'amount_desc',
                    fn (Builder $query) => $query->orderByDesc(
                        OpportunityFinancial::select('amount')->whereColumn('opportunity_id', 'opportunities.id')->limit(1)
                    ),
                    fn (Builder $query) => $query->latest('published_at')
                )
            )
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }
}
