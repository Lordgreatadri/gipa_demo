<?php

namespace App\Services\Sms\Webhooks;

use Illuminate\Http\Request;
use Spatie\WebhookClient\WebhookProfile\WebhookProfile;

/**
 * Skips storing callbacks that carry no message correlation id, which keeps the
 * webhook_calls table free of provider health-check noise.
 */
class SmsWebhookProfile implements WebhookProfile
{
    public function shouldProcess(Request $request): bool
    {
        foreach (['msgid', 'message_id', 'reference', 'MessageSid', 'SmsSid'] as $key) {
            if (filled($request->input($key))) {
                return true;
            }
        }

        return false;
    }
}
