<?php

namespace App\Notifications\Channels;

use App\Models\SmsMessage;
use App\Notifications\Messages\SmsNotificationMessage;
use App\Services\Sms\DataObjects\SmsMessageData;
use App\Services\Sms\SmsGateway;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification;

/**
 * Bridges Laravel notifications to the SMS gateway.
 *
 * Add 'sms' to a notification's `via()` and implement `toSms($notifiable)`.
 */
class SmsChannel
{
    public function __construct(private readonly SmsGateway $gateway) {}

    public function send(object $notifiable, Notification $notification): ?SmsMessage
    {
        if (! method_exists($notification, 'toSms')) {
            return null;
        }

        $recipient = $notifiable->routeNotificationFor('sms', $notification)
            ?? ($notifiable->phone_number ?? $notifiable->phone ?? null);

        if (blank($recipient)) {
            return null;
        }

        $message = $notification->toSms($notifiable);

        if (is_string($message)) {
            $message = SmsNotificationMessage::create($message);
        }

        if (! $message instanceof SmsNotificationMessage || blank($message->content)) {
            return null;
        }

        return $this->gateway->queue(new SmsMessageData(
            recipient: (string) $recipient,
            body: $message->content,
            context: $message->context,
            senderId: $message->senderId,
            preferredProvider: $message->provider,
            notifiableType: $notifiable instanceof Model ? $notifiable->getMorphClass() : null,
            notifiableId: $notifiable instanceof Model ? (int) $notifiable->getKey() : null,
            meta: $message->meta + ['notification' => $notification::class],
        ));
    }
}
