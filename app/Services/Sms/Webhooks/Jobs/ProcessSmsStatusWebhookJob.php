<?php

namespace App\Services\Sms\Webhooks\Jobs;

use App\Models\SmsDeliveryEvent;
use App\Models\SmsMessage;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\WebhookClient\Jobs\ProcessWebhookJob;

/**
 * Applies a provider delivery report to the matching outbound message.
 */
class ProcessSmsStatusWebhookJob extends ProcessWebhookJob
{
    public function handle(): void
    {
        $provider = $this->provider();
        $payload = (array) ($this->webhookCall->payload ?? []);
        $providerMessageId = $this->providerMessageId($provider, $payload);

        if ($providerMessageId === null) {
            $this->log('warning', 'Callback ignored: no provider message id', ['provider' => $provider]);

            return;
        }

        [$status, $rawStatus, $detail] = $this->normaliseStatus($provider, $payload);

        $message = SmsMessage::query()
            ->where('provider', $provider)
            ->where(function ($query) use ($providerMessageId): void {
                $query->where('provider_message_id', $providerMessageId)
                    ->orWhere('reference', $providerMessageId);
            })
            ->latest('id')
            ->first();

        SmsDeliveryEvent::create([
            'sms_message_id' => $message?->id,
            'provider' => $provider,
            'provider_message_id' => $providerMessageId,
            'status' => $status,
            'raw_status' => Str::limit($rawStatus, 60, ''),
            'detail' => $detail ? Str::limit($detail, 190) : null,
            'webhook_call_id' => $this->webhookCall->id,
            'payload' => $payload,
            'occurred_at' => $this->occurredAt($provider, $payload),
        ]);

        if (! $message) {
            $this->log('warning', 'Callback stored but no matching message was found', [
                'provider' => $provider,
                'provider_message_id' => $providerMessageId,
            ]);

            return;
        }

        // Delivery reports can arrive out of order; never walk a settled message back.
        if ($message->status === SmsMessage::STATUS_DELIVERED && $status !== SmsMessage::STATUS_DELIVERED) {
            return;
        }

        $isFailure = in_array($status, [SmsMessage::STATUS_FAILED, SmsMessage::STATUS_UNDELIVERED], true);

        $message->forceFill([
            'status' => $status,
            'failure_reason' => $isFailure
                ? Str::limit($detail ?: 'The provider reported a delivery failure.', 190)
                : null,
            'sent_at' => $message->sent_at ?: now(),
            'delivered_at' => $status === SmsMessage::STATUS_DELIVERED ? ($message->delivered_at ?: now()) : $message->delivered_at,
            'failed_at' => $isFailure ? ($message->failed_at ?: now()) : $message->failed_at,
        ])->save();

        $this->log('info', 'Delivery report applied', [
            'provider' => $provider,
            'reference' => $message->reference,
            'status' => $status,
        ]);
    }

    private function provider(): string
    {
        return str_contains((string) $this->webhookCall->name, 'twilio') ? 'twilio' : 'frog';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function providerMessageId(string $provider, array $payload): ?string
    {
        $candidates = $provider === 'twilio'
            ? ['MessageSid', 'SmsSid', 'sid']
            : ['msgid', 'message_id', 'reference', 'data.msgid'];

        foreach ($candidates as $key) {
            $value = Arr::get($payload, $key);

            if (filled($value)) {
                return (string) $value;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{0: string, 1: string, 2: ?string}
     */
    private function normaliseStatus(string $provider, array $payload): array
    {
        $raw = (string) ($provider === 'twilio'
            ? Arr::get($payload, 'MessageStatus', Arr::get($payload, 'SmsStatus', ''))
            : Arr::get($payload, 'status', Arr::get($payload, 'delivery_status', '')));

        $detail = (string) (Arr::get($payload, 'ErrorMessage')
            ?: Arr::get($payload, 'reason')
            ?: Arr::get($payload, 'message')
            ?: '');

        $status = match (strtolower($raw)) {
            'accepted', 'acceptd', 'queued', 'waiting', 'sending', 'processing', 'scheduled' => SmsMessage::STATUS_QUEUED,
            'sent' => SmsMessage::STATUS_SENT,
            'delivered', 'delivrd', 'answered', 'success' => SmsMessage::STATUS_DELIVERED,
            'undeliv', 'undelivered', 'expired', 'rejectd', 'rejected', 'busy' => SmsMessage::STATUS_UNDELIVERED,
            'failed', 'fail', 'error', 'deleted', 'unknown' => SmsMessage::STATUS_FAILED,
            default => SmsMessage::STATUS_QUEUED,
        };

        return [$status, $raw, $detail ?: null];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function occurredAt(string $provider, array $payload): \Illuminate\Support\Carbon
    {
        $raw = $provider === 'twilio'
            ? Arr::get($payload, 'Timestamp')
            : Arr::get($payload, 'statusdate');

        try {
            return $raw ? \Illuminate\Support\Carbon::parse((string) $raw) : now();
        } catch (\Throwable) {
            return now();
        }
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function log(string $level, string $message, array $context = []): void
    {
        Log::channel('sms-webhooks')->log($level, $message, $context + ['webhook_call_id' => $this->webhookCall->id]);
    }
}
