<?php

namespace App\Services\Assistant\Tools;

use App\Models\Sector;
use App\Services\Assistant\Data\ToolResult;

/**
 * Lists the active investment sectors (and their sub-sectors) available on the
 * platform.
 */
class SectorCatalogTool extends AbstractTool
{
    public function name(): string
    {
        return 'sector_catalog';
    }

    protected function triggers(): array
    {
        return ['sector', 'industry', 'industries', 'area of investment', 'areas to invest', 'what fields'];
    }

    public function handle(string $question): ?ToolResult
    {
        $sectors = Sector::query()
            ->where('is_active', true)
            ->withCount(['subSectors' => fn ($query) => $query->where('is_active', true)])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'sort_order']);

        if ($sectors->isEmpty()) {
            return null;
        }

        $lines = $sectors->map(function (Sector $sector): string {
            $suffix = $sector->sub_sectors_count > 0
                ? " ({$sector->sub_sectors_count} sub-sectors)"
                : '';

            return '• '.$sector->name.$suffix;
        })->implode("\n");

        return new ToolResult(
            tool: $this->name(),
            summary: "GIPA currently maps investment opportunities across these active sectors:\n".$lines,
            sourceLabel: 'Sector classification registry',
            reference: route('opportunities.index'),
            data: ['count' => $sectors->count()],
        );
    }
}
