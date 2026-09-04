<?php

namespace App\Services\Assistant\Data;

/**
 * A knowledge-base chunk retrieved for a query, with its similarity score.
 */
final class RetrievedChunk
{
    public function __construct(
        public readonly string $documentTitle,
        public readonly string $documentSlug,
        public readonly string $category,
        public readonly string $content,
        public readonly float $score,
    ) {}
}
