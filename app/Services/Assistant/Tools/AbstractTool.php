<?php

namespace App\Services\Assistant\Tools;

use App\Services\Assistant\Contracts\AssistantTool;

abstract class AbstractTool implements AssistantTool
{
    /**
     * Lowercase keyword fragments that activate this tool.
     *
     * @return array<int, string>
     */
    abstract protected function triggers(): array;

    public function matches(string $question): bool
    {
        $question = strtolower($question);

        foreach ($this->triggers() as $trigger) {
            if (str_contains($question, $trigger)) {
                return true;
            }
        }

        return false;
    }
}
