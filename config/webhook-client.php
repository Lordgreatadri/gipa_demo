<?php

/*
 * Every entry here is instantiated together the first time a webhook route is
 * hit, so an incomplete placeholder config would break all endpoints. Only
 * fully wired integrations belong in this list.
 */
return [
    'configs' => [

        /*
         * Wigal Frog delivery-report callbacks.
         *
         * The signing secret is held per provider in the `sms_provider_configs`
         * table, so the resolver below reads it from the database instead of the
         * environment. Frog signs the raw body with HMAC-SHA256 when a secret is
         * configured on their console; otherwise a shared bearer token is used.
         */
        [
            'name' => 'frog-sms',
            // Resolved at request time from the encrypted provider record, never
            // from the environment, so `config:cache` cannot freeze a secret.
            'signing_secret' => '',
            'signature_header_name' => 'X-Frog-Signature',
            'signature_validator' => \App\Services\Sms\Webhooks\SignatureValidators\FrogSmsSignatureValidator::class,
            'webhook_profile' => \App\Services\Sms\Webhooks\SmsWebhookProfile::class,
            'webhook_response' => \Spatie\WebhookClient\WebhookResponse\DefaultRespondsTo::class,
            'webhook_model' => \Spatie\WebhookClient\Models\WebhookCall::class,
            'store_headers' => ['X-Frog-Signature', 'User-Agent', 'Content-Type'],
            'store_attachments' => false,
            'process_webhook_job' => \App\Services\Sms\Webhooks\Jobs\ProcessSmsStatusWebhookJob::class,
        ],

        /*
         * Twilio status callbacks. Twilio signs requests with the account auth
         * token using the documented X-Twilio-Signature scheme.
         */
        [
            'name' => 'twilio-sms',
            'signing_secret' => '',
            'signature_header_name' => 'X-Twilio-Signature',
            'signature_validator' => \App\Services\Sms\Webhooks\SignatureValidators\TwilioSmsSignatureValidator::class,
            'webhook_profile' => \App\Services\Sms\Webhooks\SmsWebhookProfile::class,
            'webhook_response' => \Spatie\WebhookClient\WebhookResponse\DefaultRespondsTo::class,
            'webhook_model' => \Spatie\WebhookClient\Models\WebhookCall::class,
            'store_headers' => ['X-Twilio-Signature', 'User-Agent', 'Content-Type'],
            'store_attachments' => false,
            'process_webhook_job' => \App\Services\Sms\Webhooks\Jobs\ProcessSmsStatusWebhookJob::class,
        ],
    ],

    /*
     * The integer amount of days after which models should be deleted.
     *
     * It deletes all records after 30 days. Set to null if no models should be deleted.
     */
    'delete_after_days' => 30,

    /*
     * Should a unique token be added to the route name
     */
    'add_unique_token_to_route_name' => false,
];
