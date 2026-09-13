<?php

namespace App\Services\Sms\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Short-circuits a gateway that is repeatedly failing so queued messages fail
 * over quickly instead of waiting on a dead endpoint.
 */
class CircuitBreaker
{
    private const PREFIX = 'sms:circuit:';

    public function isOpen(string $provider): bool
    {
        if (! config('sms.circuit_breaker.enabled', true)) {
            return false;
        }

        return (bool) Cache::get(self::PREFIX.$provider.':open', false);
    }

    public function recordSuccess(string $provider): void
    {
        Cache::forget(self::PREFIX.$provider.':failures');
        Cache::forget(self::PREFIX.$provider.':open');
    }

    public function recordFailure(string $provider): void
    {
        if (! config('sms.circuit_breaker.enabled', true)) {
            return;
        }

        $key = self::PREFIX.$provider.':failures';
        $cooldown = (int) config('sms.circuit_breaker.cooldown_seconds', 300);
        $failures = (int) Cache::get($key, 0) + 1;

        Cache::put($key, $failures, $cooldown);

        if ($failures >= (int) config('sms.circuit_breaker.threshold', 5)) {
            Cache::put(self::PREFIX.$provider.':open', true, $cooldown);
        }
    }

    public function reset(string $provider): void
    {
        $this->recordSuccess($provider);
    }
}
