<?php

namespace App\Services\Sms\Webhooks\SignatureValidators;

use App\Services\Sms\Webhooks\WebhookSecretResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\WebhookClient\SignatureValidator\SignatureValidator;
use Spatie\WebhookClient\WebhookConfig;

/**
 * Implements Twilio's documented X-Twilio-Signature scheme: HMAC-SHA1 of the
 * full callback URL concatenated with every POST parameter sorted by key,
 * keyed with the account auth token.
 *
 * @see https://www.twilio.com/docs/usage/security#validating-requests
 */
class TwilioSmsSignatureValidator implements SignatureValidator
{
    public function isValid(Request $request, WebhookConfig $config): bool
    {
        // Twilio signs with the auth token itself; fall back to the generated
        // webhook secret when the account is not yet fully configured.
        $token = WebhookSecretResolver::credential('twilio', 'auth_token')
            ?: WebhookSecretResolver::secret('twilio');

        $signature = (string) $request->header($config->signatureHeaderName, '');

        if (blank($token) || $signature === '') {
            Log::channel('sms-webhooks')->warning('Twilio webhook rejected: missing auth token or signature header.');

            return false;
        }

        $payload = $request->post();
        ksort($payload);

        $data = $request->fullUrl();

        foreach ($payload as $key => $value) {
            $data .= $key.(is_scalar($value) ? (string) $value : json_encode($value));
        }

        $expected = base64_encode(hash_hmac('sha1', $data, $token, true));

        return hash_equals($expected, $signature);
    }
}
