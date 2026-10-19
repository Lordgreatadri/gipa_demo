<?php

namespace App\Services\Sms\DataObjects;

/**
 * A message that has been normalised and is ready for transport.
 */
final readonly class OutgoingSms
{
    public function __construct(
        public string $reference,
        public string $recipient,
        public string $body,
        public string $senderId,
        public ?string $callbackUrl = null,
        /** @var array<string, mixed> */
        public array $meta = [],
    ) {}
}
