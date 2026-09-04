<?php

namespace App\Services\Assistant\Contracts;

interface EmbeddingProvider
{
    /**
     * Return a normalised embedding vector for the given text.
     *
     * @return array<int, float>
     */
    public function embed(string $text): array;

    public function model(): string;
}
