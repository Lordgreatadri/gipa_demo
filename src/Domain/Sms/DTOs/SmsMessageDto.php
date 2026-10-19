<?php

namespace Src\Domain\Sms\DTOs;

readonly class SmsMessageDto
{
    public function __construct(
        public string $recipient,
        public string $message,
        public string $context = 'transactional',
        public ?int $campaignId = null,
        public ?string $senderId = null,
        public ?string $preferredProvider = null,
        public ?string $countryCode = null,
        public array $meta = [],
    ) {}
}
