<?php

namespace Src\Domain\Sms\Webhooks\SignatureValidators;

use Illuminate\Http\Request;
use Spatie\WebhookClient\SignatureValidator\SignatureValidator;
use Spatie\WebhookClient\WebhookConfig;

final class FrogSmsWebhookSignatureValidator implements SignatureValidator
{
    public function isValid(Request $request, WebhookConfig $config): bool
    {
        $secret = (string) $config->signingSecret;

        if ($secret === '') {
            return false;
        }

        $provided = trim((string) $request->header($config->signatureHeaderName, ''));
        if ($provided !== '') {
            $rawBody = $request->getContent();
            $hex = hash_hmac('sha256', $rawBody, $secret);
            $base64 = base64_encode(hash_hmac('sha256', $rawBody, $secret, true));

            return hash_equals($hex, $provided) || hash_equals($base64, $provided);
        }

        $token = (string) ($request->query('token') ?: $request->header('X-Webhook-Token', ''));

        return $token !== '' && hash_equals($secret, $token);
    }
}
