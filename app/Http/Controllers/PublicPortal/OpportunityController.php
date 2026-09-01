<?php

namespace App\Http\Controllers\PublicPortal;

use App\Http\Controllers\Controller;
use App\Http\Requests\PublicOpportunitySearchRequest;
use App\Http\Requests\StoreInvestorInquiryRequest;
use App\Http\Resources\OpportunityResource;
use App\Models\Opportunity;
use App\Services\InvestorInquiryService;
use App\Services\PublicOpportunityFilters;
use App\Services\PublicOpportunitySearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\View\View;

class OpportunityController extends Controller
{
    public function index(
        PublicOpportunitySearchRequest $request,
        PublicOpportunitySearch $search,
        PublicOpportunityFilters $filters,
    ): View|AnonymousResourceCollection {
        $opportunities = $search->search($request->validated());

        if ($request->expectsJson()) {
            return OpportunityResource::collection($opportunities);
        }

        return view('public.opportunities.index', [
            'opportunities' => $opportunities,
            'filters' => $filters->options(),
        ]);
    }

    public function show(PublicOpportunitySearchRequest $request, string $opportunity): View|OpportunityResource
    {
        $opportunity = Opportunity::query()
            ->publiclyVisible()
            ->where('uuid', $opportunity)
            ->with([
                'district.region',
                'sector',
                'subSector',
                'enterpriseType',
                'financial.investmentStructure',
                'contacts' => fn ($query) => $query->orderByDesc('is_primary')->orderBy('name'),
                'media',
            ])
            ->firstOrFail();

        if ($request->expectsJson()) {
            return new OpportunityResource($opportunity);
        }

        return view('public.opportunities.show', compact('opportunity'));
    }

    public function storeInquiry(
        StoreInvestorInquiryRequest $request,
        string $opportunity,
        InvestorInquiryService $inquiries,
    ): RedirectResponse|JsonResponse {
        $opportunity = Opportunity::query()->publiclyVisible()->where('uuid', $opportunity)->firstOrFail();
        $inquiry = $inquiries->submit($opportunity, $request->safe()->except(['consent', 'website']), $request->user());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Your inquiry has been received.',
                'reference' => $inquiry->reference,
            ], 201);
        }

        return to_route('opportunities.show', $opportunity->uuid)
            ->with('inquiry_reference', $inquiry->reference);
    }
}
