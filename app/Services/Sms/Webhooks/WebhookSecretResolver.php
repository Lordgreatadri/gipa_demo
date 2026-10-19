<?php

namespace App\Services\Sms\Webhooks;

use App\Models\SmsProviderConfig;

/**
 * Resolves the per-provider webhook secret from the encrypted database record.
 *
 * Secrets deliberately never live in config/env so `config:cache` cannot bake a
 * credential into a build artefact.
 */
class WebhookSecretResolver
{
    public static function secret(string $provider): ?string
    {
        $config = SmsProviderConfig::query()
            ->where('provider', $provider)
            ->first();

        if (! $config) {
            return null;
        }

        return blank($config->webhook_secret) ? null : (string) $config->webhook_secret;
    }

    public static function credential(string $provider, string $key): ?string
    {
        return SmsProviderConfig::query()
            ->where('provider', $provider)
            ->first()
            ?->credential($key);
    }
}
