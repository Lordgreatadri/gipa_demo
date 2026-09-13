<?php

namespace App\Services\Sms\DataObjects;

final readonly class SmsResult
{
    private function __construct(
        public bool $successful,
        public string $status,
        public string $message,
        public ?string $providerMessageId,
        /** @var array<string, mixed> */
        public array $raw,
        /** True when the failure was a transport fault worth failing over or retrying. */
        public bool $retryable,
    ) {}

    /** @param array<string, mixed> $raw */
    public static function success(string $status, string $message, ?string $providerMessageId = null, array $raw = []): self
    {
        return new self(true, $status, $message, $providerMessageId, $raw, false);
    }

    /** @param array<string, mixed> $raw */
    public static function failure(string $status, string $message, array $raw = [], bool $retryable = false): self
    {
        return new self(false, $status, $message, null, $raw, $retryable);
    }

    /** @param array<string, mixed> $raw */
    public static function transportFailure(string $status, string $message, array $raw = []): self
    {
        return new self(false, $status, $message, null, $raw, true);
    }
}
