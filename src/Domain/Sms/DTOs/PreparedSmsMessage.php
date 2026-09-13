<?php

namespace Src\Domain\Sms\DTOs;

readonly class PreparedSmsMessage
{
    public function __construct(
        public string $reference,
        public string $recipient,
        public string $message,
        public string $senderId,
        public ?string $callbackUrl = null,
        public array $meta = [],
    ) {}
}
