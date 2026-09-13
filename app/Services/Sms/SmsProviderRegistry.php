<?php

namespace App\Services\Sms;

use App\Models\SmsProviderConfig;
use App\Services\Sms\Contracts\SmsProvider;
use App\Services\Sms\Providers\LogSmsProvider;
use InvalidArgumentException;

/**
 * Builds provider adapters from the database-held configuration.
 *
 * Nothing here reads the environment: the platform owner owns every credential
 * through the admin SMS configuration dashboard.
 */
class SmsProviderRegistry
{
    /** @var array<string, SmsProvider> */
    private array $resolved = [];

    /** @var array<string, SmsProviderConfig>|null */
    private ?array $records = null;

    /**
     * Provider keys declared in config/sms.php.
     *
     * @return array<int, string>
     */
    public function available(): array
    {
        return array_keys((array) config('sms.providers', []));
    }

    public function supports(string $provider): bool
    {
        return in_array($provider, $this->available(), true);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function catalogue(): array
    {
        return (array) config('sms.providers', []);
    }

    public function definition(string $provider): array
    {
        if (! $this->supports($provider)) {
            throw new InvalidArgumentException('Unsupported SMS provider: '.$provider);
        }

        return (array) config('sms.providers.'.$provider);
    }

    public function configFor(string $provider): ?SmsProviderConfig
    {
        return $this->records()[$provider] ?? null;
    }

    /**
     * Configured provider records keyed by provider name.
     *
     * @return array<string, SmsProviderConfig>
     */
    public function records(): array
    {
        return $this->records ??= SmsProviderConfig::query()
            ->orderBy('priority')
            ->get()
            ->keyBy('provider')
            ->all();
    }

    /**
     * Active, fully configured providers in failover order.
     *
     * @return array<int, SmsProviderConfig>
     */
    public function usableConfigs(): array
    {
        return array_values(array_filter(
            $this->records(),
            fn (SmsProviderConfig $config): bool => $config->isUsable() && $this->supports($config->provider),
        ));
    }

    public function make(string $provider): SmsProvider
    {
        if (isset($this->resolved[$provider])) {
            return $this->resolved[$provider];
        }

        $definition = $this->definition($provider);
        $record = $this->configFor($provider) ?? new SmsProviderConfig(['provider' => $provider]);
        $adapter = (string) ($definition['adapter'] ?? LogSmsProvider::class);

        return $this->resolved[$provider] = new $adapter($record);
    }

    public function makeFor(SmsProviderConfig $config): SmsProvider
    {
        $adapter = (string) ($this->definition($config->provider)['adapter'] ?? LogSmsProvider::class);

        return new $adapter($config);
    }

    /**
     * Drop memoised state so a freshly saved configuration is picked up.
     */
    public function flush(): void
    {
        $this->resolved = [];
        $this->records = null;
    }
}
