<?php

namespace App\Services\Assistant\Contracts;

use App\Services\Assistant\Data\ToolResult;

interface AssistantTool
{
    public function name(): string;

    /**
     * Whether this tool can contribute an answer to the given question.
     */
    public function matches(string $question): bool;

    /**
     * Query live platform data and return a grounded result, or null if none applies.
     */
    public function handle(string $question): ?ToolResult;
}
