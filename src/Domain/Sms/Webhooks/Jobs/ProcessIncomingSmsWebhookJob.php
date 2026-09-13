<?php

namespace Src\Domain\Sms\Webhooks\Jobs;

use App\Models\TenantSmsMessage;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Spatie\WebhookClient\Jobs\ProcessWebhookJob;

class ProcessIncomingSmsWebhookJob extends ProcessWebhookJob
{
    public function handle(): void
    {
        $provider = $this->resolveProvider();
        $payload = $this->webhookCall->payload ?? [];
        $providerMessageId = $this->extractProviderMessageId($provider, $payload);

        if (! $providerMessageId) {
            Log::channel($this->channel($provider))->warning('SMS webhook ignored: missing provider message id', [
                'provider' => $provider,
                'webhook_call_id' => $this->webhookCall->id,
                'payload' => $payload,
            ]);

            return;
        }

        $message = TenantSmsMessage::query()
            ->where('provider', $provider)
            ->where('provider_message_id', $providerMessageId)
            ->latest('id')
            ->first();

        if (! $message) {
            Log::channel($this->channel($provider))->warning('SMS webhook ignored: message record not found', [
                'provider' => $provider,
                'provider_message_id' => $providerMessageId,
                'webhook_call_id' => $this->webhookCall->id,
            ]);

            return;
        }

        $priorStatus = $message->status;
        [$status, $failureReason] = $this->normalizeStatus($provider, $payload);

        $message->update([
            'status' => $status,
            'sent_at' => in_array($status, [TenantSmsMessage::STATUS_QUEUED, TenantSmsMessage::STATUS_SENT, TenantSmsMessage::STATUS_DELIVERED], true)
                ? ($message->sent_at ?: now())
                : $message->sent_at,
            'delivered_at' => $status === TenantSmsMessage::STATUS_DELIVERED
                ? ($message->delivered_at ?: now())
                : $message->delivered_at,
            'failed_at' => in_array($status, [TenantSmsMessage::STATUS_FAILED, TenantSmsMessage::STATUS_UNDELIVERED], true)
                ? ($message->failed_at ?: now())
                : $message->failed_at,
            'failure_reason' => $failureReason,
            'meta' => array_merge($message->meta ?? [], [
                'provider_callback' => [
                    'provider' => $provider,
                    'received_at' => now()->toIso8601String(),
                    'payload' => $payload,
                ],
            ]),
        ]);

        if ($message->campaign && in_array($status, [TenantSmsMessage::STATUS_FAILED, TenantSmsMessage::STATUS_UNDELIVERED], true)
            && ! in_array($priorStatus, [TenantSmsMessage::STATUS_FAILED, TenantSmsMessage::STATUS_UNDELIVERED], true)) {
            $message->campaign->increment('failed_count');
        }

        Log::channel($this->channel($provider))->info('SMS webhook processed', [
            'provider' => $provider,
            'provider_message_id' => $providerMessageId,
            'status' => $status,
            'webhook_call_id' => $this->webhookCall->id,
        ]);
    }

    private function resolveProvider(): string
    {
        return str_contains($this->webhookCall->name, 'twilio') ? 'twilio' : 'frog';
    }

    private function extractProviderMessageId(string $provider, array $payload): ?string
    {
        $candidates = match ($provider) {
            'twilio' => [
                Arr::get($payload, 'MessageSid'),
                Arr::get($payload, 'SmsSid'),
                Arr::get($payload, 'sid'),
            ],
            default => [
                Arr::get($payload, 'msgid'),
                Arr::get($payload, 'message_id'),
                Arr::get($payload, 'reference'),
                Arr::get($payload, 'data.msgid'),
            ],
        };

        foreach ($candidates as $candidate) {
            if (filled($candidate)) {
                return (string) $candidate;
            }
        }

        return null;
    }

    private function normalizeStatus(string $provider, array $payload): array
    {
        $rawStatus = strtolower((string) match ($provider) {
            'twilio' => Arr::get($payload, 'MessageStatus', Arr::get($payload, 'SmsStatus', 'queued')),
            default => Arr::get($payload, 'status', Arr::get($payload, 'delivery_status', 'queued')),
        });

        return match ($rawStatus) {
            'accepted', 'acceptd', 'queued', 'sending', 'processing' => [TenantSmsMessage::STATUS_QUEUED, null],
            'sent' => [TenantSmsMessage::STATUS_SENT, null],
            'delivered', 'delivrd', 'success' => [TenantSmsMessage::STATUS_DELIVERED, null],
            'undelivered', 'expired', 'rejected' => [
                TenantSmsMessage::STATUS_UNDELIVERED,
                (string) (Arr::get($payload, 'ErrorMessage') ?: Arr::get($payload, 'message') ?: 'Message was not delivered.'),
            ],
            'failed', 'fail', 'error' => [
                TenantSmsMessage::STATUS_FAILED,
                (string) (Arr::get($payload, 'ErrorMessage') ?: Arr::get($payload, 'message') ?: 'Provider reported a failed delivery.'),
            ],
            default => [TenantSmsMessage::STATUS_QUEUED, null],
        };
    }

    private function channel(string $provider): string
    {
        return $provider === 'twilio' ? 'twilioapi' : 'frogapi';
    }
}
