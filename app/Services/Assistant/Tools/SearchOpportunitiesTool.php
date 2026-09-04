<?php

namespace App\Services\Assistant\Tools;

use App\Models\Opportunity;
use App\Models\Sector;
use App\Services\Assistant\Data\ToolResult;

/**
 * Surfaces published, publicly visible investment opportunities, optionally
 * narrowed to a sector mentioned in the question.
 */
class SearchOpportunitiesTool extends AbstractTool
{
    public function name(): string
    {
        return 'search_opportunities';
    }

    protected function triggers(): array
    {
        return ['opportunit', 'invest in', 'project', 'available to invest', 'where can i invest', 'what can i invest'];
    }

    public function handle(string $question): ?ToolResult
    {
        $sector = $this->matchSector($question);

        $query = Opportunity::query()
            ->publiclyVisible()
            ->with(['sector:id,name', 'district:id,name', 'financial:id,opportunity_id,amount,currency'])
            ->latest('published_at');

        if ($sector !== null) {
            $query->where('sector_id', $sector->id);
        }

        $opportunities = $query->limit(5)->get();

        if ($opportunities->isEmpty()) {
            return null;
        }

        $lines = $opportunities->map(function (Opportunity $opportunity): string {
            $parts = [$opportunity->title];

            if ($opportunity->sector) {
                $parts[] = $opportunity->sector->name;
            }

            if ($opportunity->district) {
                $parts[] = $opportunity->district->name;
            }

            if ($opportunity->financial && $opportunity->financial->amount !== null) {
                $parts[] = $opportunity->financial->currency.' '.number_format((float) $opportunity->financial->amount);
            }

            return '• '.implode(' — ', $parts);
        })->implode("\n");

        $intro = $sector
            ? "Here are published investment opportunities in {$sector->name}:"
            : 'Here are some of the latest published investment opportunities on the platform:';

        return new ToolResult(
            tool: $this->name(),
            summary: $intro."\n".$lines."\n\nYou can browse and filter every opportunity on the Opportunities page.",
            sourceLabel: 'Published opportunities registry',
            reference: route('opportunities.index'),
            data: ['count' => $opportunities->count(), 'sector' => $sector?->name],
        );
    }

    private function matchSector(string $question): ?Sector
    {
        $question = strtolower($question);

        return Sector::query()
            ->where('is_active', true)
            ->get(['id', 'name'])
            ->first(fn (Sector $sector): bool => str_contains($question, strtolower($sector->name)));
    }
}
