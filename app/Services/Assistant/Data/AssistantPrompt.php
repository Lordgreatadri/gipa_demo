<?php

namespace App\Services\Assistant\Data;

/**
 * The fully assembled context handed to a chat provider for generation.
 */
final class AssistantPrompt
{
    /**
     * @param  array<int, array{role: string, content: string}>  $history
     * @param  array<int, ToolResult>  $toolResults
     * @param  array<int, RetrievedChunk>  $chunks
     */
    public function __construct(
        public readonly string $question,
        public readonly array $history = [],
        public readonly array $toolResults = [],
        public readonly array $chunks = [],
        public readonly bool $flagged = false,
    ) {}

    public function hasContext(): bool
    {
        return $this->toolResults !== [] || $this->chunks !== [];
    }
}
