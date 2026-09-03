<?php

namespace App\Services;

use App\Models\InvestorMatchPreference;
use App\Models\Opportunity;
use Illuminate\Support\Collection;

class InvestorOpportunityMatcher
{
    public const CANDIDATE_LIMIT = 200;

    public const RESULT_LIMIT = 12;

    public function matches(InvestorMatchPreference $preference): Collection
    {
        $preference->loadMissing(['sectors:id', 'regions:id']);
        $sectorIds = $preference->sectors->modelKeys();
        $regionIds = $preference->regions->modelKeys();

        return Opportunity::query()
            ->select(['id', 'uuid', 'district_id', 'sector_id', 'sub_sector_id', 'title', 'overview', 'published_at'])
            ->publiclyVisible()
            ->with([
                'district:id,uuid,region_id,name,readiness_score',
                'district.region:id,uuid,name',
                'sector:id,uuid,name',
                'subSector:id,uuid,name',
                'financial:id,opportunity_id,amount,currency,roi_percentage',
            ])
            ->when($sectorIds, fn ($query) => $query->whereIn('sector_id', $sectorIds))
            ->when($regionIds, fn ($query) => $query->whereHas('district', fn ($district) => $district->whereIn('region_id', $regionIds)))
            ->when($preference->minimum_readiness_score !== null, fn ($query) => $query->whereHas('district', fn ($district) => $district->where('readiness_score', '>=', $preference->minimum_readiness_score)))
            ->when($preference->minimum_investment !== null || $preference->maximum_investment !== null, fn ($query) => $query->whereHas('financial', function ($financial) use ($preference): void {
                $financial->where('currency', $preference->currency)
                    ->when($preference->minimum_investment !== null, fn ($query) => $query->where('amount', '>=', $preference->minimum_investment))
                    ->when($preference->maximum_investment !== null, fn ($query) => $query->where('amount', '<=', $preference->maximum_investment));
            }))
            ->latest('published_at')
            ->limit(self::CANDIDATE_LIMIT)
            ->get()
            ->map(fn (Opportunity $opportunity) => $this->score($opportunity, $preference, $sectorIds, $regionIds))
            ->sortByDesc(fn (array $match) => [$match['score'], $match['opportunity']->published_at?->timestamp ?? 0])
            ->take(self::RESULT_LIMIT)
            ->values();
    }

    private function score(Opportunity $opportunity, InvestorMatchPreference $preference, array $sectorIds, array $regionIds): array
    {
        $reasons = [];
        $score = 0;

        if (in_array($opportunity->sector_id, $sectorIds, true)) {
            $score += 40;
            $reasons[] = 'Preferred sector';
        }
        if (in_array($opportunity->district->region_id, $regionIds, true)) {
            $score += 25;
            $reasons[] = 'Preferred region';
        }
        if ($opportunity->financial && $opportunity->financial->currency === $preference->currency
            && ($preference->minimum_investment === null || $opportunity->financial->amount >= $preference->minimum_investment)
            && ($preference->maximum_investment === null || $opportunity->financial->amount <= $preference->maximum_investment)) {
            $score += 25;
            $reasons[] = 'Investment range';
        }

        $readiness = (float) ($opportunity->district->readiness_score ?? 0);
        $score += min(10, (int) round($readiness / 10));
        if ($readiness > 0) {
            $reasons[] = 'District readiness '.number_format($readiness, 0).'%';
        }

        return ['opportunity' => $opportunity, 'score' => min(100, $score), 'reasons' => $reasons];
    }
}
