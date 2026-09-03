<?php

namespace App\Http\Controllers\PublicPortal;

use App\Http\Controllers\Controller;
use App\Http\Requests\PublicDistrictSearchRequest;
use App\Models\District;
use App\Models\Opportunity;
use App\Models\Region;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class DistrictController extends Controller
{
    public function index(PublicDistrictSearchRequest $request): View
    {
        $filters = $request->validated();
        $districts = District::query()
            ->select(['id', 'uuid', 'region_id', 'code', 'name', 'capital', 'readiness_score', 'population', 'area_sq_km'])
            ->where('workflow_status', District::STATUS_PUBLISHED)
            ->whereNotNull('published_at')
            ->with('region:id,uuid,name')
            ->withCount(['opportunities' => fn (Builder $query) => $query->publiclyVisible()])
            ->when($filters['query'] ?? null, fn (Builder $query, string $term) => $query->where(fn (Builder $search) => $search
                ->whereLike('name', "%{$term}%")
                ->orWhereLike('capital', "%{$term}%")))
            ->when($filters['region'] ?? null, fn (Builder $query, string $uuid) => $query->whereHas('region', fn (Builder $region) => $region->where('uuid', $uuid)))
            ->when(isset($filters['readiness']), fn (Builder $query) => $query->where('readiness_score', '>=', $filters['readiness']))
            ->orderBy('name')
            ->paginate(24)
            ->withQueryString();

        $mapPoints = Opportunity::query()
            ->select(['uuid', 'district_id', 'sector_id', 'title', 'latitude', 'longitude'])
            ->publiclyVisible()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereBetween('latitude', [4.5, 11.5])
            ->whereBetween('longitude', [-3.5, 1.5])
            ->with(['district:id,uuid,name', 'sector:id,name'])
            ->latest('published_at')
            ->limit(500)
            ->get()
            ->map(fn (Opportunity $opportunity) => [
                'title' => $opportunity->title,
                'latitude' => (float) $opportunity->latitude,
                'longitude' => (float) $opportunity->longitude,
                'district' => $opportunity->district->name,
                'sector' => $opportunity->sector->name,
                'url' => route('opportunities.show', $opportunity),
            ]);

        return view('public.districts.index', [
            'districts' => $districts,
            'regions' => Region::query()->select(['uuid', 'name'])->orderBy('name')->get(),
            'mapPoints' => $mapPoints,
        ]);
    }

    public function show(string $district): View
    {
        $district = District::query()
            ->where('uuid', $district)
            ->where('workflow_status', District::STATUS_PUBLISHED)
            ->whereNotNull('published_at')
            ->with('region:id,uuid,name,capital')
            ->firstOrFail();

        $opportunities = $district->opportunities()
            ->select(['id', 'uuid', 'district_id', 'sector_id', 'sub_sector_id', 'enterprise_type_id', 'title', 'overview', 'location_description', 'published_at'])
            ->publiclyVisible()
            ->with(['district:id,uuid,region_id,name', 'district.region:id,uuid,name', 'sector:id,uuid,name', 'subSector:id,uuid,name', 'enterpriseType:id,uuid,name', 'financial:id,opportunity_id,amount,currency'])
            ->latest('published_at')
            ->paginate(12);

        return view('public.districts.show', compact('district', 'opportunities'));
    }
}
