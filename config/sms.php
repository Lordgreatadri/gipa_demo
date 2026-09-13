<?php

use App\Services\Sms\Providers\FrogSmsProvider;
use App\Services\Sms\Providers\LogSmsProvider;
use App\Services\Sms\Providers\TwilioSmsProvider;

return [

    /*
    |--------------------------------------------------------------------------
    | Provider catalogue
    |--------------------------------------------------------------------------
    |
    | Every supported gateway is declared here. Credentials are never read from
    | the environment: the platform owner captures them on the admin SMS
    | configuration dashboard and they are stored encrypted in the database.
    | This block only describes *what* each provider needs so the admin form
    | can render itself and the registry knows which adapter to build.
    |
    | To add a future provider: create an adapter implementing
    | App\Services\Sms\Contracts\SmsProvider and append an entry below.
    |
    */

    'providers' => [

        'frog' => [
            'label' => 'Wigal Frog',
            'adapter' => FrogSmsProvider::class,
            'log_channel' => 'sms-frog',
            'phone_format' => 'digits',
            'supports_balance' => true,
            'supports_bundles' => true,
            'supports_delivery_callbacks' => true,
            'webhook_config' => 'frog-sms',
            'docs_url' => 'https://frogdocs.wigal.com.gh/send_general.html',
            'defaults' => [
                'base_url' => 'https://frogapi.wigal.com.gh/api/v3',
            ],
            'credentials' => [
                'username' => ['label' => 'Username', 'secret' => false, 'required' => true, 'hint' => 'Frog account username issued by Wigal.'],
                'api_key' => ['label' => 'API key', 'secret' => true, 'required' => true, 'hint' => 'Sent as the API-KEY header on every request.'],
                'base_url' => ['label' => 'API base URL', 'secret' => false, 'required' => false, 'hint' => 'Override only if Wigal issues a dedicated endpoint.'],
            ],
        ],

        'twilio' => [
            'label' => 'Twilio',
            'adapter' => TwilioSmsProvider::class,
            'log_channel' => 'sms-twilio',
            'phone_format' => 'e164',
            'supports_balance' => true,
            'supports_bundles' => false,
            'supports_delivery_callbacks' => true,
            'webhook_config' => 'twilio-sms',
            'docs_url' => 'https://www.twilio.com/docs/messaging/api',
            'defaults' => [
                'base_url' => 'https://api.twilio.com/2010-04-01',
            ],
            'credentials' => [
                'account_sid' => ['label' => 'Account SID', 'secret' => false, 'required' => true, 'hint' => 'Starts with AC.'],
                'auth_token' => ['label' => 'Auth token', 'secret' => true, 'required' => true, 'hint' => 'Also used to validate inbound status callbacks.'],
                'base_url' => ['label' => 'API base URL', 'secret' => false, 'required' => false, 'hint' => 'Leave blank unless using a regional edge.'],
            ],
        ],

        'log' => [
            'label' => 'Log only (no transmission)',
            'adapter' => LogSmsProvider::class,
            'log_channel' => 'sms',
            'phone_format' => 'e164',
            'supports_balance' => false,
            'supports_bundles' => false,
            'supports_delivery_callbacks' => false,
            'webhook_config' => null,
            'docs_url' => null,
            'defaults' => [],
            'credentials' => [],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Dispatch behaviour
    |--------------------------------------------------------------------------
    */

    'enabled' => env('SMS_ENABLED', true),

    'default_country_code' => env('SMS_DEFAULT_COUNTRY_CODE', '233'),

    'fallback_provider' => 'log',

    'queue' => [
        'connection' => env('SMS_QUEUE_CONNECTION'),
        'name' => env('SMS_QUEUE', 'sms'),
        'tries' => 3,
        'backoff' => [10, 60, 300],
    ],

    'http' => [
        'timeout' => 20,
        'connect_timeout' => 5,
        'retries' => 2,
        'retry_delay_ms' => 250,
    ],

    /*
     * A provider is skipped for this many seconds after consecutive transport
     * failures so a degraded gateway cannot stall the queue. Backed by the
     * cache store (Redis when available).
     */
    'circuit_breaker' => [
        'enabled' => true,
        'threshold' => 5,
        'cooldown_seconds' => 300,
    ],

    /*
     * Cached provider balance lifetime (seconds). The admin dashboard can force
     * a refresh at any time.
     */
    'balance_cache_ttl' => 300,

    /*
     * Bundle/credit levels at which the platform owner should be warned on the
     * SMS configuration dashboard.
     */
    'low_balance_threshold' => env('SMS_LOW_BALANCE_THRESHOLD', 100),

    'max_length' => 1600,

];
