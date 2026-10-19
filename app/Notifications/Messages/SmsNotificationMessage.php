<?php

namespace App\Notifications\Messages;

/**
 * Fluent builder returned from a notification's `toSms()` method.
 */
class SmsNotificationMessage
{
    public string $content = '';

    public string $context = 'transactional';

    public ?string $provider = null;

    public ?string $senderId = null;

    /** @var array<string, mixed> */
    public array $meta = [];

    public static function create(string $content = ''): self
    {
        return (new self)->content($content);
    }

    public function content(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function context(string $context): self
    {
        $this->context = $context;

        return $this;
    }

    public function provider(?string $provider): self
    {
        $this->provider = $provider;

        return $this;
    }

    public function senderId(?string $senderId): self
    {
        $this->senderId = $senderId;

        return $this;
    }

    /** @param array<string, mixed> $meta */
    public function meta(array $meta): self
    {
        $this->meta = $meta;

        return $this;
    }
}
