<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\PublicOpportunitySearchRequest;
use App\Http\Resources\OpportunityResource;
use App\Models\Opportunity;
use App\Services\PublicOpportunitySearch;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OpportunityController extends Controller
{
    public function index(PublicOpportunitySearchRequest $request, PublicOpportunitySearch $search): AnonymousResourceCollection
    {
        return OpportunityResource::collection($search->search($request->validated()));
    }

    public function show(string $opportunity): OpportunityResource
    {
        return new OpportunityResource(Opportunity::query()
            ->publiclyVisible()
            ->where('uuid', $opportunity)
            ->with(['district.region', 'sector', 'subSector', 'enterpriseType', 'financial.investmentStructure'])
            ->firstOrFail());
    }
}
