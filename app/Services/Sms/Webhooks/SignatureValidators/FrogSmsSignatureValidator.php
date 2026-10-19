<?php

namespace App\Services\Sms\Webhooks\SignatureValidators;

use App\Services\Sms\Webhooks\WebhookSecretResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\WebhookClient\SignatureValidator\SignatureValidator;
use Spatie\WebhookClient\WebhookConfig;

/**
 * Validates Wigal Frog delivery-report callbacks.
 *
 * Frog does not publish a signature scheme, so the platform owner generates a
 * shared secret on the SMS configuration dashboard and pastes the resulting
 * callback URL (which carries the secret as a token) into their Frog console.
 * An HMAC-SHA256 header is accepted as well for the day Frog adds one.
 */
class FrogSmsSignatureValidator implements SignatureValidator
{
    public function isValid(Request $request, WebhookConfig $config): bool
    {
        $secret = WebhookSecretResolver::secret('frog');

        if (blank($secret)) {
            Log::channel('sms-webhooks')->warning('Frog webhook rejected: no signing secret is configured.');

            return false;
        }

        $signature = trim((string) $request->header($config->signatureHeaderName, ''));

        if ($signature !== '') {
            $body = $request->getContent();
            $hex = hash_hmac('sha256', $body, $secret);
            $base64 = base64_encode(hash_hmac('sha256', $body, $secret, true));

            return hash_equals($hex, $signature) || hash_equals($base64, $signature);
        }

        $token = (string) ($request->header('X-Webhook-Token') ?: $request->query('token', ''));

        if ($token === '') {
            Log::channel('sms-webhooks')->warning('Frog webhook rejected: no signature or token supplied.');

            return false;
        }

        return hash_equals($secret, $token);
    }
}
