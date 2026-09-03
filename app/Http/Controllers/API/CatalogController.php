<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\DistrictResource;
use App\Http\Resources\RegionResource;
use App\Http\Resources\SectorResource;
use App\Http\Resources\SubSectorResource;
use App\Models\District;
use App\Models\Region;
use App\Models\Sector;
use App\Models\SubSector;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CatalogController extends Controller
{
    public function regions(): AnonymousResourceCollection
    {
        return RegionResource::collection(Region::query()->select(['id', 'uuid', 'code', 'name', 'capital'])->orderBy('name')->paginate(50));
    }

    public function districts(Request $request): AnonymousResourceCollection
    {
        $request->validate(['region' => ['nullable', 'uuid'], 'page' => ['nullable', 'integer', 'min:1']]);

        return DistrictResource::collection(District::query()
            ->select(['id', 'uuid', 'region_id', 'code', 'name', 'capital', 'readiness_score', 'population', 'area_sq_km', 'infrastructure_quality_score'])
            ->where('workflow_status', District::STATUS_PUBLISHED)
            ->whereNotNull('published_at')
            ->with('region:id,uuid,code,name,capital')
            ->withCount(['opportunities' => fn (Builder $query) => $query->publiclyVisible()])
            ->when($request->string('region')->toString(), fn (Builder $query, string $uuid) => $query->whereHas('region', fn (Builder $region) => $region->where('uuid', $uuid)))
            ->orderBy('name')
            ->paginate(50));
    }

    public function sectors(): AnonymousResourceCollection
    {
        return SectorResource::collection(Sector::query()->select(['id', 'uuid', 'code', 'name'])->orderBy('name')->paginate(50));
    }

    public function subSectors(Request $request): AnonymousResourceCollection
    {
        $request->validate(['sector' => ['nullable', 'uuid'], 'page' => ['nullable', 'integer', 'min:1']]);

        return SubSectorResource::collection(SubSector::query()
            ->select(['id', 'uuid', 'sector_id', 'code', 'name'])
            ->with('sector:id,uuid,code,name')
            ->when($request->string('sector')->toString(), fn (Builder $query, string $uuid) => $query->whereHas('sector', fn (Builder $sector) => $sector->where('uuid', $uuid)))
            ->orderBy('name')
            ->paginate(50));
    }
}
