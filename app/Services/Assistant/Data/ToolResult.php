<?php

namespace App\Services\Assistant\Data;

/**
 * The structured output of an assistant tool that queried live platform data.
 */
final class ToolResult
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public readonly string $tool,
        public readonly string $summary,
        public readonly string $sourceLabel,
        public readonly ?string $reference = null,
        public readonly array $data = [],
    ) {}
}
