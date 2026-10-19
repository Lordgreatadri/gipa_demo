<?php

namespace App\Services\Sms\DataObjects;

/**
 * Provider credit/bundle position rendered on the SMS configuration dashboard.
 */
final readonly class ProviderBalance
{
    public function __construct(
        public string $provider,
        public bool $available,
        public ?float $creditBalance = null,
        public ?string $currency = null,
        public ?int $smsUnits = null,
        /** @var array<int, array<string, mixed>> */
        public array $bundles = [],
        public ?string $message = null,
        /** @var array<string, mixed> */
        public array $raw = [],
    ) {}

    public static function unavailable(string $provider, string $message): self
    {
        return new self(provider: $provider, available: false, message: $message);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'provider' => $this->provider,
            'available' => $this->available,
            'credit_balance' => $this->creditBalance,
            'currency' => $this->currency,
            'sms_units' => $this->smsUnits,
            'bundles' => $this->bundles,
            'message' => $this->message,
            'raw' => $this->raw,
        ];
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            provider: (string) ($data['provider'] ?? 'unknown'),
            available: (bool) ($data['available'] ?? false),
            creditBalance: isset($data['credit_balance']) ? (float) $data['credit_balance'] : null,
            currency: $data['currency'] ?? null,
            smsUnits: isset($data['sms_units']) ? (int) $data['sms_units'] : null,
            bundles: (array) ($data['bundles'] ?? []),
            message: $data['message'] ?? null,
            raw: (array) ($data['raw'] ?? []),
        );
    }

    public function isLow(): bool
    {
        $threshold = (float) config('sms.low_balance_threshold', 100);

        if ($this->smsUnits !== null) {
            return $this->smsUnits <= $threshold;
        }

        return $this->creditBalance !== null && $this->creditBalance <= $threshold;
    }
}
