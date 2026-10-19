<?php

namespace Src\Domain\Sms\Providers;

use Illuminate\Support\Facades\Log;
use Src\Domain\Sms\Contracts\SmsProviderInterface;
use Src\Domain\Sms\DTOs\PreparedSmsMessage;
use Src\Domain\Sms\DTOs\SmsResult;

final class LogSmsProvider implements SmsProviderInterface
{
    public function name(): string
    {
        return 'log';
    }

    public function send(PreparedSmsMessage $message): SmsResult
    {
        Log::channel('bulk-sms')->info('Log SMS provider accepted message', [
            'reference' => $message->reference,
            'recipient' => $message->recipient,
            'sender_id' => $message->senderId,
            'message' => $message->message,
            'meta' => $message->meta,
        ]);

        return SmsResult::success(
            status: 'logged',
            message: 'Message accepted by log SMS provider.',
            providerId: $message->reference,
            raw: ['provider' => 'log'],
        );
    }

    public function balance(): array
    {
        return [
            'provider' => 'log',
            'status' => 'ok',
            'message' => 'Log SMS provider does not track balances.',
        ];
    }

    public function topUp(array $payload = []): array
    {
        return [
            'provider' => 'log',
            'status' => 'unsupported',
            'message' => 'Log SMS provider does not support top-up.',
            'payload' => $payload,
        ];
    }
}
