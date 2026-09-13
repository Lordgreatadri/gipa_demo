<?php

namespace Src\Domain\Sms\DTOs;

readonly class SmsResult
{
    public function __construct(
        public bool    $success,
        public string  $status,
        public string  $message,
        public ?string $providerId,
        public array   $rawResponse,
    ) {}

    public static function success(string $status, string $message, ?string $providerId, array $raw): self
    {
        return new self(
            success: true,
            status: $status,
            message: $message,
            providerId: $providerId,
            rawResponse: $raw,
        );
    }

    public static function failure(string $status, string $message, array $raw = []): self
    {
        return new self(
            success: false,
            status: $status,
            message: $message,
            providerId: null,
            rawResponse: $raw,
        );
    }
}
