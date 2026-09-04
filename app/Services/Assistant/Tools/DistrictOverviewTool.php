<?php

namespace App\Services\Assistant\Tools;

use App\Models\District;
use App\Services\Assistant\Data\ToolResult;

/**
 * Provides an overview of published districts, or details for a specific
 * district named in the question.
 */
class DistrictOverviewTool extends AbstractTool
{
    public function name(): string
    {
        return 'district_overview';
    }

    protected function triggers(): array
    {
        return ['district', 'region', 'where is', 'located', 'location', 'readiness'];
    }

    public function handle(string $question): ?ToolResult
    {
        $published = District::query()
            ->where('workflow_status', District::STATUS_PUBLISHED)
            ->whereNotNull('published_at');

        $named = (clone $published)
            ->with('region:id,name')
            ->get(['id', 'region_id', 'name', 'capital', 'readiness_score'])
            ->first(fn (District $district): bool => str_contains(strtolower($question), strtolower($district->name)));

        if ($named !== null) {
            $opportunities = $named->opportunities()->publiclyVisible()->count();
            $details = [];
            $details[] = "{$named->name} is a published district".($named->region ? " in the {$named->region->name} region." : '.');
            if ($named->capital) {
                $details[] = "Its capital is {$named->capital}.";
            }
            if ($named->readiness_score !== null) {
                $details[] = "Investment readiness score: {$named->readiness_score}/100.";
            }
            $details[] = $opportunities === 1
                ? 'There is 1 published investment opportunity in this district.'
                : "There are {$opportunities} published investment opportunities in this district.";

            return new ToolResult(
                tool: $this->name(),
                summary: implode(' ', $details),
                sourceLabel: 'District registry',
                reference: route('districts.index'),
                data: ['district' => $named->name, 'opportunities' => $opportunities],
            );
        }

        $count = (clone $published)->count();

        if ($count === 0) {
            return null;
        }

        $top = (clone $published)
            ->orderByDesc('readiness_score')
            ->limit(5)
            ->get(['id', 'name', 'capital', 'readiness_score']);

        $lines = $top->map(fn (District $district): string => '• '.$district->name
            .($district->capital ? " (capital: {$district->capital})" : '')
            .($district->readiness_score !== null ? " — readiness {$district->readiness_score}/100" : ''))
            ->implode("\n");

        return new ToolResult(
            tool: $this->name(),
            summary: "There are {$count} published districts on the platform. Some of the most investment-ready are:\n"
                .$lines."\n\nExplore every district and the interactive investment map on the Districts page.",
            sourceLabel: 'District registry',
            reference: route('districts.index'),
            data: ['count' => $count],
        );
    }
}
