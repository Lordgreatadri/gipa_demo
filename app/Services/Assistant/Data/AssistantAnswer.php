<?php

namespace App\Services\Assistant\Data;

/**
 * A generated assistant answer plus the metadata used for governance and logging.
 */
final class AssistantAnswer
{
    /**
     * @param  array<int, array{type: string, label: string, category: string|null, reference: string|null}>  $citations
     * @param  array<int, string>  $toolsUsed
     */
    public function __construct(
        public readonly string $content,
        public readonly array $citations = [],
        public readonly array $toolsUsed = [],
        public readonly bool $grounded = false,
        public readonly string $provider = 'offline',
        public readonly string $model = 'offline',
        public readonly int $tokensInput = 0,
        public readonly int $tokensOutput = 0,
    ) {}
}
