<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApiInvestorMatchPreferenceRequest;
use App\Models\InvestorMatchPreference;
use App\Models\InvestorProfile;
use App\Models\Region;
use App\Models\Sector;
use App\Models\User;
use App\Services\InvestorOpportunityMatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvestorController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $profile = $this->profile($request)->load(['matchPreference.sectors:id,uuid,name', 'matchPreference.regions:id,uuid,name']);

        return response()->json(['data' => [
            'uuid' => $profile->uuid,
            'display_name' => $profile->display_name,
            'profile_type' => $profile->profile_type,
            'country_code' => $profile->country_code,
            'onboarding_state' => $profile->onboarding_state,
            'status' => $profile->status,
            'preferences' => $profile->matchPreference ? $this->preferenceData($profile->matchPreference) : null,
        ]]);
    }

    public function updatePreferences(ApiInvestorMatchPreferenceRequest $request): JsonResponse
    {
        $profile = $this->profile($request);
        $data = $request->safe()->except(['sector_uuids', 'region_uuids']);
        $preference = DB::transaction(function () use ($profile, $data, $request) {
            $preference = $profile->matchPreference()->updateOrCreate([], $data);
            $preference->sectors()->sync(Sector::query()->whereIn('uuid', $request->validated('sector_uuids'))->pluck('id'));
            $preference->regions()->sync(Region::query()->whereIn('uuid', $request->validated('region_uuids'))->pluck('id'));

            return $preference->load(['sectors:id,uuid,name', 'regions:id,uuid,name']);
        });

        return response()->json(['data' => $this->preferenceData($preference)]);
    }

    public function matches(Request $request, InvestorOpportunityMatcher $matcher): JsonResponse
    {
        $preference = $this->profile($request)->matchPreference;
        if (! $preference) {
            return response()->json(['data' => [], 'meta' => ['message' => 'Set investment preferences to generate matches.']]);
        }

        return response()->json(['data' => $matcher->matches($preference)->map(fn (array $match) => [
            'score' => $match['score'],
            'reasons' => $match['reasons'],
            'opportunity' => [
                'uuid' => $match['opportunity']->uuid,
                'title' => $match['opportunity']->title,
                'district' => $match['opportunity']->district->name,
                'region' => $match['opportunity']->district->region->name,
                'sector' => $match['opportunity']->sector->name,
                'amount' => $match['opportunity']->financial?->amount,
                'currency' => $match['opportunity']->financial?->currency,
                'web_url' => route('opportunities.show', $match['opportunity']),
            ],
        ])]);
    }

    private function profile(Request $request): InvestorProfile
    {
        abort_unless($request->user()?->account_type === User::ACCOUNT_INVESTOR, 403, 'Investor account required.');

        return $request->user()->investorProfile()->firstOrFail();
    }

    private function preferenceData(InvestorMatchPreference $preference): array
    {
        return [
            'minimum_investment' => $preference->minimum_investment,
            'maximum_investment' => $preference->maximum_investment,
            'currency' => $preference->currency,
            'minimum_readiness_score' => $preference->minimum_readiness_score,
            'sectors' => $preference->sectors->map->only(['uuid', 'name']),
            'regions' => $preference->regions->map->only(['uuid', 'name']),
        ];
    }
}
