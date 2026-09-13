<?php

namespace Src\Domain\Sms\Webhooks\SignatureValidators;

use Illuminate\Http\Request;
use Spatie\WebhookClient\SignatureValidator\SignatureValidator;
use Spatie\WebhookClient\WebhookConfig;

final class TwilioSmsWebhookSignatureValidator implements SignatureValidator
{
    public function isValid(Request $request, WebhookConfig $config): bool
    {
        $provided = (string) $request->header($config->signatureHeaderName, '');
        $secret = (string) $config->signingSecret;

        if ($provided === '' || $secret === '') {
            return false;
        }

        $payload = $request->all();
        ksort($payload);

        $signedData = $request->fullUrl();

        foreach ($payload as $key => $value) {
            $signedData .= $key.$value;
        }

        $expected = base64_encode(hash_hmac('sha1', $signedData, $secret, true));

        return hash_equals($expected, $provided);
    }
}
