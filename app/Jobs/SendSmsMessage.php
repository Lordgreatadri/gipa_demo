<?php

namespace App\Jobs;

use App\Models\SmsMessage;
use App\Services\Sms\SmsGateway;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendSmsMessage implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $smsMessageId) {}

    public function tries(): int
    {
        return (int) config('sms.queue.tries', 3);
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return (array) config('sms.queue.backoff', [10, 60, 300]);
    }

    public function handle(SmsGateway $gateway): void
    {
        $message = SmsMessage::find($this->smsMessageId);

        if (! $message || $message->isSettled() || $message->status === SmsMessage::STATUS_SENT) {
            return;
        }

        $gateway->deliver($message);
    }

    public function failed(\Throwable $exception): void
    {
        Log::channel('sms')->error('SMS queue job failed', [
            'sms_message_id' => $this->smsMessageId,
            'exception' => $exception->getMessage(),
        ]);

        SmsMessage::where('id', $this->smsMessageId)
            ->whereNotIn('status', [SmsMessage::STATUS_DELIVERED, SmsMessage::STATUS_SENT])
            ->update([
                'status' => SmsMessage::STATUS_FAILED,
                'failure_reason' => \Illuminate\Support\Str::limit($exception->getMessage(), 190),
                'failed_at' => now(),
            ]);
    }
}
