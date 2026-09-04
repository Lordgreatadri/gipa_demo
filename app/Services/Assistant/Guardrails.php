<?php

namespace App\Services\Assistant;

use App\Services\Assistant\Support\Text;

/**
 * Input hygiene and prompt-injection detection for the assistant. Because the
 * offline driver only ever answers from supplied context, injection attempts
 * cannot alter its behaviour — but we still detect and flag them for logging
 * and to deflect pure-manipulation messages.
 */
class Guardrails
{
    private const INJECTION_PATTERNS = [
        '/ignore\s+(all\s+)?(previous|prior|above)\s+(instructions|prompts?)/i',
        '/disregard\s+(the\s+)?(system|previous|above)/i',
        '/you\s+are\s+now\s+/i',
        '/reveal\s+(your\s+)?(system\s+)?(prompt|instructions)/i',
        '/(what|show)\s+(is|me)\s+your\s+(system\s+)?prompt/i',
        '/act\s+as\s+(a\s+)?(dan|jailbreak)/i',
        '/pretend\s+(to\s+be|you\s+are)/i',
    ];

    public function sanitize(string $input): string
    {
        return Text::clean($input);
    }

    public function detectInjection(string $input): bool
    {
        foreach (self::INJECTION_PATTERNS as $pattern) {
            if (preg_match($pattern, $input) === 1) {
                return true;
            }
        }

        return false;
    }
}
