<?php

namespace App\Services\Sms\DataObjects;

/**
 * The caller's intent: who to text, what to say, and why.
 */
final readonly class SmsMessageData
{
    public function __construct(
        public string $recipient,
        public string $body,
        public string $context = 'transactional',
        public ?string $senderId = null,
        public ?string $preferredProvider = null,
        public ?string $countryCode = null,
        public ?string $notifiableType = null,
        public ?int $notifiableId = null,
        /** @var array<string, mixed> */
        public array $meta = [],
    ) {}

    public function withProvider(string $provider): self
    {
        return new self(
            recipient: $this->recipient,
            body: $this->body,
            context: $this->context,
            senderId: $this->senderId,
            preferredProvider: $provider,
            countryCode: $this->countryCode,
            notifiableType: $this->notifiableType,
            notifiableId: $this->notifiableId,
            meta: $this->meta,
        );
    }
}
