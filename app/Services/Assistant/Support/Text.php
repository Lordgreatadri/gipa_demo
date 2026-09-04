<?php

namespace App\Services\Assistant\Support;

class Text
{
    private const STOP_WORDS = [
        'the', 'and', 'for', 'are', 'but', 'not', 'you', 'your', 'with', 'this', 'that',
        'from', 'have', 'has', 'was', 'were', 'will', 'can', 'could', 'would', 'should',
        'about', 'into', 'what', 'which', 'who', 'whom', 'how', 'when', 'where', 'why',
        'a', 'an', 'of', 'to', 'in', 'on', 'is', 'it', 'as', 'at', 'or', 'be', 'do', 'does',
    ];

    /**
     * Break text into normalised lowercase word tokens (stop words removed).
     *
     * @return array<int, string>
     */
    public static function tokens(string $text): array
    {
        $text = strtolower($text);
        preg_match_all('/[a-z0-9]+/', $text, $matches);

        $tokens = array_filter(
            $matches[0],
            static fn (string $token): bool => strlen($token) >= 2 && ! in_array($token, self::STOP_WORDS, true),
        );

        return array_values($tokens);
    }

    /**
     * Collapse whitespace and trim control characters from user input.
     */
    public static function clean(string $text): string
    {
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $text) ?? $text;

        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    /**
     * Rough token count used for lightweight usage metrics.
     */
    public static function estimateTokens(string $text): int
    {
        return (int) ceil(mb_strlen($text) / 4);
    }
}
