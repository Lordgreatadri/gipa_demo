<?php

namespace App\Services\Sms;

use App\Jobs\SendSmsMessage;
use App\Models\SmsMessage;
use App\Models\SmsProviderConfig;
use App\Services\Sms\DataObjects\OutgoingSms;
use App\Services\Sms\DataObjects\ProviderBalance;
use App\Services\Sms\DataObjects\SmsMessageData;
use App\Services\Sms\DataObjects\SmsResult;
use App\Services\Sms\Support\CircuitBreaker;
use App\Services\Sms\Support\PhoneNumber;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Single entry point for outbound SMS.
 *
 * Persists every attempt, picks the active gateway (with failover to the next
 * configured provider), and keeps provider balances cached for the dashboard.
 */
class SmsGateway
{
    public function __construct(
        private readonly SmsProviderRegistry $registry,
        private readonly CircuitBreaker $breaker,
    ) {}

    /**
     * Record the message and hand it to the queue worker.
     */
    public function queue(SmsMessageData $data): SmsMessage
    {
        $record = $this->record($data);

        if ($record->status === SmsMessage::STATUS_SKIPPED) {
            return $record;
        }

        $record->forceFill([
            'status' => SmsMessage::STATUS_QUEUED,
            'queued_at' => now(),
        ])->save();

        SendSmsMessage::dispatch($record->id)
            ->onConnection(config('sms.queue.connection') ?: config('queue.default'))
            ->onQueue(config('sms.queue.name', 'sms'));

        return $record;
    }

    /**
     * Record and transmit immediately on the current process.
     */
    public function send(SmsMessageData $data): SmsMessage
    {
        $record = $this->record($data);

        if ($record->status === SmsMessage::STATUS_SKIPPED) {
            return $record;
        }

        return $this->deliver($record);
    }

    /**
     * Transmit an already-persisted message, failing over between providers.
     */
    public function deliver(SmsMessage $record): SmsMessage
    {
        $chain = $this->providerChain($record->provider);

        if ($chain === []) {
            return $this->markFailed($record, 'NO_PROVIDER', 'No SMS provider is active and fully configured.');
        }

        $attempts = [];

        foreach ($chain as $config) {
            if ($this->breaker->isOpen($config->provider)) {
                $attempts[$config->provider] = 'circuit-open';

                continue;
            }

            $recipient = PhoneNumber::forProvider($record->recipient_raw ?: $record->recipient, $config->provider);

            if ($recipient === null) {
                return $this->markFailed($record, 'INVALID_RECIPIENT', 'The recipient number is not a valid phone number.');
            }

            $prepared = new OutgoingSms(
                reference: $record->reference,
                recipient: $recipient,
                body: $record->body,
                senderId: $record->sender_id ?: $this->senderIdFor($config),
                callbackUrl: $config->callbackUrl(),
                meta: (array) ($record->meta ?? []),
            );

            $result = $this->attempt($config, $prepared);
            $attempts[$config->provider] = $result->status;

            if ($result->successful) {
                $this->breaker->recordSuccess($config->provider);
                $config->forceFill([
                    'last_success_at' => now(),
                    'consecutive_failures' => 0,
                ])->save();

                return $this->markSent($record, $config, $recipient, $prepared->senderId, $result, $attempts);
            }

            $config->forceFill([
                'last_failure_at' => now(),
                'last_failure_reason' => Str::limit($result->message, 190),
                'consecutive_failures' => $config->consecutive_failures + 1,
            ])->save();

            if ($result->retryable) {
                $this->breaker->recordFailure($config->provider);

                continue;
            }

            // A provider-level rejection (bad sender id, blocked number) will not
            // be fixed by another gateway attempt of the same kind, but a second
            // configured provider may still accept it, so keep walking the chain.
        }

        $last = end($attempts) ?: 'unknown';

        return $this->markFailed($record, 'ALL_PROVIDERS_FAILED', 'No configured provider accepted the message ('.$last.').', $attempts);
    }

    public function balance(SmsProviderConfig $config, bool $fresh = false): ProviderBalance
    {
        if (! $config->supportsBalance()) {
            return ProviderBalance::unavailable($config->provider, 'This provider does not expose a balance endpoint.');
        }

        $key = 'sms:balance:'.$config->provider;

        if ($fresh) {
            Cache::forget($key);
        }

        $payload = Cache::remember($key, (int) config('sms.balance_cache_ttl', 300), function () use ($config): array {
            $balance = $this->registry->makeFor($config)->balance();

            $config->forceFill([
                'balance_snapshot' => $balance->toArray(),
                'balance_checked_at' => now(),
            ])->save();

            return $balance->toArray();
        });

        return ProviderBalance::fromArray($payload);
    }

    /**
     * Providers to try, in order, starting with the caller's preference.
     *
     * @return array<int, SmsProviderConfig>
     */
    private function providerChain(?string $preferred = null): array
    {
        $configs = $this->registry->usableConfigs();

        if ($preferred) {
            usort($configs, fn (SmsProviderConfig $a, SmsProviderConfig $b): int => ($b->provider === $preferred ? 1 : 0) <=> ($a->provider === $preferred ? 1 : 0));
        }

        if ($configs === []) {
            $fallback = (string) config('sms.fallback_provider', 'log');
            $record = $this->registry->configFor($fallback);

            if ($record?->isUsable()) {
                return [$record];
            }
        }

        return $configs;
    }

    private function attempt(SmsProviderConfig $config, OutgoingSms $prepared): SmsResult
    {
        try {
            return $this->registry->makeFor($config)->send($prepared);
        } catch (\Throwable $exception) {
            Log::channel($config->logChannel())->error('SMS provider threw an exception', [
                'provider' => $config->provider,
                'reference' => $prepared->reference,
                'exception' => $exception->getMessage(),
            ]);

            return SmsResult::transportFailure('EXCEPTION', $exception->getMessage());
        }
    }

    private function record(SmsMessageData $data): SmsMessage
    {
        $normalised = PhoneNumber::format($data->recipient, $data->countryCode);
        $provider = $data->preferredProvider ?: ($this->providerChain()[0]->provider ?? null);
        $body = Str::limit($data->body, (int) config('sms.max_length', 1600), '');

        $record = SmsMessage::create([
            'reference' => 'SMS-'.now()->format('Ymd').'-'.Str::upper(Str::random(10)),
            'provider' => $provider ?: (string) config('sms.fallback_provider', 'log'),
            'context' => $data->context,
            'sender_id' => $data->senderId,
            'recipient' => $normalised ?: Str::limit($data->recipient, 30, ''),
            'recipient_raw' => Str::limit($data->recipient, 60, ''),
            'body' => $body,
            'segments' => PhoneNumber::segments($body),
            'status' => SmsMessage::STATUS_PENDING,
            'notifiable_type' => $data->notifiableType,
            'notifiable_id' => $data->notifiableId,
            'meta' => $data->meta,
        ]);

        if ($normalised === null) {
            return $this->markFailed($record, 'INVALID_RECIPIENT', 'The recipient number is not a valid phone number.');
        }

        if (! config('sms.enabled', true)) {
            $record->forceFill([
                'status' => SmsMessage::STATUS_SKIPPED,
                'failure_reason' => 'SMS transmission is disabled for this environment.',
            ])->save();
        }

        return $record;
    }

    private function senderIdFor(SmsProviderConfig $config): string
    {
        return (string) ($config->sender_id ?: config('app.name', 'IOMP'));
    }

    /** @param array<string, string> $attempts */
    private function markSent(SmsMessage $record, SmsProviderConfig $config, string $recipient, string $senderId, SmsResult $result, array $attempts): SmsMessage
    {
        $record->forceFill([
            'provider' => $config->provider,
            'recipient' => $recipient,
            'sender_id' => $senderId,
            'status' => SmsMessage::STATUS_SENT,
            'provider_message_id' => $result->providerMessageId,
            'failure_reason' => null,
            'attempts' => $record->attempts + 1,
            'sent_at' => now(),
            'meta' => array_merge((array) $record->meta, [
                'provider_attempts' => $attempts,
                'provider_response' => $result->raw,
            ]),
        ])->save();

        return $record;
    }

    /** @param array<string, string> $attempts */
    private function markFailed(SmsMessage $record, string $status, string $reason, array $attempts = []): SmsMessage
    {
        $record->forceFill([
            'status' => SmsMessage::STATUS_FAILED,
            'failure_reason' => Str::limit($reason, 190),
            'attempts' => $record->attempts + 1,
            'failed_at' => now(),
            'meta' => array_merge((array) $record->meta, array_filter([
                'failure_status' => $status,
                'provider_attempts' => $attempts ?: null,
            ])),
        ])->save();

        Log::channel('sms')->warning('SMS dispatch failed', [
            'reference' => $record->reference,
            'status' => $status,
            'reason' => $reason,
            'attempts' => $attempts,
        ]);

        return $record;
    }
}
