<?php

namespace App\Services\Sms\Providers;

use App\Services\Sms\DataObjects\OutgoingSms;
use App\Services\Sms\DataObjects\ProviderBalance;
use App\Services\Sms\DataObjects\SmsResult;
use App\Services\Sms\Providers\AbstractSmsProvider;

/**
 * Writes messages to the SMS log channel instead of transmitting them.
 *
 * Acts as the safety net when no gateway is active, so a demo or staging
 * environment never silently drops a notification.
 */
class LogSmsProvider extends AbstractSmsProvider
{
    public function name(): string
    {
        return 'log';
    }

    public function send(OutgoingSms $message): SmsResult
    {
        $this->log('info', 'SMS captured by the log provider (not transmitted)', [
            'reference' => $message->reference,
            'recipient' => $message->recipient,
            'sender_id' => $message->senderId,
            'body' => $message->body,
        ]);

        return SmsResult::success('logged', 'Message written to the SMS log channel.', $message->reference);
    }

    public function balance(): ProviderBalance
    {
        return ProviderBalance::unavailable($this->name(), 'The log provider does not consume credit.');
    }
}
