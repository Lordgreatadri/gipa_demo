<?php

namespace App\Services\Sms\Contracts;

use App\Models\SmsProviderConfig;
use App\Services\Sms\DataObjects\OutgoingSms;
use App\Services\Sms\DataObjects\ProviderBalance;
use App\Services\Sms\DataObjects\SmsResult;

/**
 * Contract every SMS gateway adapter must satisfy.
 *
 * Adding a provider is a two-step job: implement this interface, then register
 * the adapter under a new key in config/sms.php.
 */
interface SmsProvider
{
    public function name(): string;

    public function send(OutgoingSms $message): SmsResult;

    public function balance(): ProviderBalance;

    public function config(): SmsProviderConfig;
}
