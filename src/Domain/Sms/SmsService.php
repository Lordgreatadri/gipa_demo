<?php

namespace Src\Domain\Sms;

use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\TenantSmsConfig;
use App\Models\TenantSmsMessage;
use Illuminate\Support\Str;
use Src\Domain\Sms\DTOs\PreparedSmsMessage;
use Src\Domain\Sms\DTOs\SmsMessageDto;
use Src\Domain\Sms\DTOs\SmsResult;

final class SmsService
{
    public function __construct(
        private readonly ?Tenant $tenant = null,
        private readonly ?TenantSmsConfig $config = null,
        private readonly ?SmsProviderRegistry $registry = null,
    ) {}

    public static function forTenant(Tenant $tenant): self
    {
        $config = TenantSmsConfig::query()
            ->where('tenant_id', $tenant->id)
            ->first();

        return new self($tenant, $config);
    }

    public static function system(): self
    {
        return new self();
    }

    public function send(SmsMessageDto $message): SmsResult
    {
        $provider = $this->providers()->get($this->activeProviderName($message));
        $senderId = $this->resolveSenderId($provider->name(), $message);
        $units = $this->estimateUnits($message->message);
        $subscription = $this->activeSubscription();
        $recipient = $this->formatRecipient($message->recipient, $provider->name(), $message->countryCode);

        if ($recipient === '') {
            return SmsResult::failure(
                'INVALID_RECIPIENT',
                'The recipient phone number is invalid after normalization.',
                [
                    'recipient' => $message->recipient,
                    'provider' => $provider->name(),
                ],
            );
        }

        if ($this->shouldEnforceQuota($message->context)) {
            $quotaResult = $this->ensureQuotaAvailable($units, $subscription);
            if ($quotaResult !== null) {
                return $quotaResult;
            }
        }

        $record = TenantSmsMessage::create([
            'tenant_id' => $this->tenant?->id,
            'subscription_id' => $subscription?->id,
            'campaign_id' => $message->campaignId,
            'provider' => $provider->name(),
            'context' => $message->context,
            'sender_id' => $senderId,
            'recipient' => $recipient,
            'message' => $message->message,
            'message_units' => $units,
            'status' => TenantSmsMessage::STATUS_PENDING,
            'meta' => array_merge($message->meta, [
                'raw_recipient' => $message->recipient,
                'resolved_provider' => $provider->name(),
            ]),
        ]);

        $prepared = new PreparedSmsMessage(
            reference: (string) Str::uuid(),
            recipient: $recipient,
            message: $message->message,
            senderId: $senderId,
            callbackUrl: $this->callbackUrlFor($provider->name()),
            meta: $message->meta,
        );

        $result = $provider->send($prepared);

        $record->update([
            'provider_message_id' => $result->providerId,
            'status' => $result->success ? TenantSmsMessage::STATUS_QUEUED : TenantSmsMessage::STATUS_FAILED,
            'sent_at' => $result->success ? now() : null,
            'failed_at' => $result->success ? null : now(),
            'failure_reason' => $result->success ? null : $result->message,
            'meta' => array_merge($record->meta ?? [], ['provider_response' => $result->rawResponse]),
        ]);

        if ($record->campaign) {
            if ($result->success) {
                $record->campaign->increment('sent_count');
            } else {
                $record->campaign->increment('failed_count');
            }
        }

        return $result;
    }

    public function balance(?string $providerName = null): array
    {
        $provider = $this->providers()->get($providerName ?: $this->activeProviderName());

        return $provider->balance();
    }

    public function topUp(array $payload = [], ?string $providerName = null): array
    {
        $provider = $this->providers()->get($providerName ?: $this->activeProviderName());

        return $provider->topUp($payload);
    }

    public function providerName(): string
    {
        return $this->activeProviderName();
    }

    private function providers(): SmsProviderRegistry
    {
        return $this->registry ?? SmsProviderRegistry::makeDefault();
    }

    private function activeProviderName(?SmsMessageDto $message = null): string
    {
        if ($message?->preferredProvider && $this->providers()->has($message->preferredProvider)) {
            return $message->preferredProvider;
        }

        if ($this->config?->provider && $this->config->isConfigured() && $this->providers()->has($this->config->provider)) {
            return $this->config->provider;
        }

        $default = (string) config('services.sms.driver', 'frog');
        if ($this->providers()->has($default)) {
            return $default;
        }

        return 'log';
    }

    private function resolveSenderId(string $provider, SmsMessageDto $message): string
    {
        if ($message->senderId) {
            return $message->senderId;
        }

        if ($this->config?->use_custom_sender_id && filled($this->config->custom_sender_id)) {
            return (string) $this->config->custom_sender_id;
        }

        return match ($provider) {
            'twilio' => (string) (config('services.sms.twilio.from')
                ?: config('services.sms.default_sender_id')),
            'frog' => (string) (config('services.sms.frogsms.senderid')
                ?: config('services.sms.default_sender_id')),
            default => (string) config('services.sms.default_sender_id', 'TheLeads'),
        };
    }

    private function formatRecipient(string $recipient, string $provider, ?string $countryCode = null): string
    {
        return format_phone_for_sms(
            phone: $recipient,
            countryCode: $countryCode ?: (string) config('services.sms.default_country_code', '233'),
            provider: $provider,
        );
    }

    private function callbackUrlFor(string $provider): ?string
    {
        $configured = match ($provider) {
            'twilio' => config('services.sms.twilio.status_callback_url'),
            'frog' => config('services.sms.frogsms.status_callback_url'),
            default => null,
        };

        if ($configured) {
            return (string) $configured;
        }

        $routeName = 'webhook-client-'.match ($provider) {
            'twilio' => 'twilio-sms',
            'frog' => 'frog-sms',
            default => '',
        };

        if ($routeName === 'webhook-client-') {
            return null;
        }

        try {
            return route($routeName);
        } catch (\Throwable) {
            return null;
        }
    }

    private function estimateUnits(string $message): int
    {
        $length = mb_strlen($message);

        if ($length <= 160) {
            return 1;
        }

        return (int) ceil($length / 153);
    }

    private function shouldEnforceQuota(string $context): bool
    {
        if (! $this->tenant) {
            return false;
        }

        $contexts = ['bulk_sms', 'order_notification', 'loyalty_reward'];

        return in_array($context, $contexts, true);
    }

    private function ensureQuotaAvailable(int $requiredUnits, ?Subscription $subscription): ?SmsResult
    {
        $allocated = $this->allocatedCredits($subscription);

        if ($allocated < 0) {
            return null;
        }

        $used = $this->usedCredits($subscription);
        $remaining = $allocated - $used;

        if ($remaining >= $requiredUnits) {
            return null;
        }

        return SmsResult::failure(
            'INSUFFICIENT_SMS_CREDITS',
            'Insufficient SMS credits for this tenant subscription package.',
            [
                'allocated' => $allocated,
                'used' => $used,
                'remaining' => max(0, $remaining),
                'required' => $requiredUnits,
            ],
        );
    }

    private function allocatedCredits(?Subscription $subscription): int
    {
        $limits = (array) ($subscription?->plan?->limits ?? []);
        $smsLimit = $limits['sms'] ?? 0;

        return (int) $smsLimit;
    }

    private function usedCredits(?Subscription $subscription): int
    {
        $query = TenantSmsMessage::query()
            ->where('tenant_id', $this->tenant?->id)
            ->whereIn('status', [TenantSmsMessage::STATUS_QUEUED, TenantSmsMessage::STATUS_SENT, TenantSmsMessage::STATUS_DELIVERED]);

        if ($subscription) {
            $query->where('created_at', '>=', $subscription->starts_at ?? now()->startOfMonth());

            if ($subscription->ends_at) {
                $query->where('created_at', '<=', $subscription->ends_at);
            }
        } else {
            $query->where('created_at', '>=', now()->startOfMonth());
        }

        return (int) $query->sum('message_units');
    }

    private function activeSubscription(): ?Subscription
    {
        if (! $this->tenant) {
            return null;
        }

        return Subscription::query()
            ->with('plan')
            ->where('tenant_id', $this->tenant->id)
            ->whereIn('status', ['trialing', 'active'])
            ->latest('id')
            ->first();
    }
}
