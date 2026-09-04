<?php

namespace App\Services\Assistant\Contracts;

use App\Services\Assistant\Data\AssistantAnswer;
use App\Services\Assistant\Data\AssistantPrompt;

interface ChatProvider
{
    public function generate(AssistantPrompt $prompt): AssistantAnswer;

    public function name(): string;

    public function model(): string;
}
