<?php

namespace App\Services\Assistant\Providers;

use App\Services\Assistant\Contracts\EmbeddingProvider;
use App\Services\Assistant\Support\Text;

/**
 * Deterministic, dependency-free embedding model.
 *
 * Produces a hashed bag-of-words vector with term-frequency weighting and L2
 * normalisation. Because it is fully deterministic it works offline, requires
 * no API keys, and yields stable results for tests while still ranking chunks
 * by genuine lexical overlap with the query.
 */
class OfflineEmbeddingProvider implements EmbeddingProvider
{
    private readonly int $dimensions;

    public function __construct(?int $dimensions = null)
    {
        $this->dimensions = $dimensions ?? (int) config('assistant.embedding.dimensions', 256);
    }

    public function embed(string $text): array
    {
        $vector = array_fill(0, $this->dimensions, 0.0);
        $tokens = Text::tokens($text);

        if ($tokens === []) {
            return $vector;
        }

        foreach ($tokens as $token) {
            $bucket = crc32($token) % $this->dimensions;
            $vector[$bucket] += 1.0;
        }

        $magnitude = sqrt(array_sum(array_map(static fn (float $v): float => $v * $v, $vector)));

        if ($magnitude <= 0.0) {
            return $vector;
        }

        return array_map(static fn (float $v): float => $v / $magnitude, $vector);
    }

    public function model(): string
    {
        return 'offline-hashed-bow-'.$this->dimensions;
    }
}
