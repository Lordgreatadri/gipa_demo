<?php

namespace App\Services\Assistant\Tools;

use App\Models\District;
use App\Models\Opportunity;
use App\Models\Region;
use App\Models\Sector;
use App\Services\Assistant\Data\ToolResult;

/**
 * Reports live headline statistics about the platform's published content.
 */
class PlatformStatsTool extends AbstractTool
{
    public function name(): string
    {
        return 'platform_stats';
    }

    /** @var array<int, string> Platform metrics this tool can actually report. */
    private const METRICS = ['opportunit', 'sector', 'district', 'region'];

    protected function triggers(): array
    {
        return ['how many', 'number of', 'statistics', 'stats', 'total', 'count of', 'overview of'];
    }

    public function matches(string $question): bool
    {
        // Only activate when the question expresses a counting/statistics intent
        // AND references a metric this tool can answer, so unrelated questions
        // like "how many employees does GIPA have?" are not answered with
        // platform counts (which would otherwise be reported as grounded).
        return parent::matches($question) && $this->mentionsMetric($question);
    }

    private function mentionsMetric(string $question): bool
    {
        $question = strtolower($question);

        foreach (self::METRICS as $metric) {
            if (str_contains($question, $metric)) {
                return true;
            }
        }

        return false;
    }

    public function handle(string $question): ?ToolResult
    {
        $opportunities = Opportunity::query()->publiclyVisible()->count();
        $sectors = Sector::query()->where('is_active', true)->count();
        $districts = District::query()
            ->where('workflow_status', District::STATUS_PUBLISHED)
            ->whereNotNull('published_at')
            ->count();
        $regions = Region::query()->count();

        return new ToolResult(
            tool: $this->name(),
            summary: "Here is a snapshot of the platform right now:\n"
                ."• {$opportunities} published investment opportunities\n"
                ."• {$sectors} active investment sectors\n"
                ."• {$districts} published districts\n"
                ."• {$regions} regions of Ghana covered",
            sourceLabel: 'Platform statistics',
            reference: route('home'),
            data: compact('opportunities', 'sectors', 'districts', 'regions'),
        );
    }
}
