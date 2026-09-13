<?php

namespace App\Services\Sms\Providers;

use App\Models\SmsProviderConfig;
use App\Services\Sms\Contracts\SmsProvider;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

abstract class AbstractSmsProvider implements SmsProvider
{
    public function __construct(protected readonly SmsProviderConfig $providerConfig) {}

    public function config(): SmsProviderConfig
    {
        return $this->providerConfig;
    }

    protected function logger(): LoggerInterface
    {
        return Log::channel($this->providerConfig->logChannel());
    }

    protected function http(): PendingRequest
    {
        return Http::acceptJson()
            ->timeout((int) config('sms.http.timeout', 20))
            ->connectTimeout((int) config('sms.http.connect_timeout', 5))
            ->retry(
                (int) config('sms.http.retries', 2),
                (int) config('sms.http.retry_delay_ms', 250),
                throw: false,
            );
    }

    /**
     * Strip credentials and truncate the destination before anything is written
     * to a log file.
     *
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    protected function redact(array $context): array
    {
        $secretKeys = ['api_key', 'apikey', 'auth_token', 'authtoken', 'password', 'token', 'secret', 'authorization', 'api-key'];

        array_walk_recursive($context, function (&$value, $key) use ($secretKeys): void {
            if (is_string($key) && in_array(strtolower($key), $secretKeys, true)) {
                $value = '[redacted]';
            }
        });

        return $context;
    }

    protected function log(string $level, string $message, array $context = []): void
    {
        $this->logger()->log($level, $message, $this->redact([
            'provider' => $this->name(),
            'config_uuid' => $this->providerConfig->uuid,
            ...$context,
        ]));
    }

    protected function baseUrl(): string
    {
        return rtrim((string) $this->providerConfig->credential('base_url'), '/');
    }
}
