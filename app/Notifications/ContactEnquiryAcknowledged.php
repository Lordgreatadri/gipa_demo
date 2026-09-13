<?php

namespace App\Notifications;

use App\Models\ContactUs;
use App\Notifications\Messages\SmsNotificationMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Confirms receipt to the member of the public who submitted the enquiry.
 *
 * Sent over email always, and additionally by SMS when a phone number was
 * supplied and a gateway is active.
 */
class ContactEnquiryAcknowledged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly ContactUs $enquiry) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['mail'];

        if (filled($this->enquiry->phone_number)) {
            $channels[] = 'sms';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('We have received your enquiry ('.$this->enquiry->reference.')')
            ->greeting('Hello '.$this->enquiry->name.',')
            ->line('Thank you for contacting the Ghana Investment Promotion Authority.')
            ->line('We have logged your enquiry under reference **'.$this->enquiry->reference.'** and a member of our investment services team will get in touch with you shortly.')
            ->line('Subject: '.$this->enquiry->subject)
            ->line('Please quote the reference above in any follow-up correspondence.')
            ->action('Browse investment opportunities', route('opportunities.index'))
            ->salutation('Warm regards, GIPA Investment Services');
    }

    public function toSms(object $notifiable): SmsNotificationMessage
    {
        return SmsNotificationMessage::create(
            'GIPA: Thank you '.$this->enquiry->name.'. We received your enquiry (ref '
            .$this->enquiry->reference.'). Our team will be in touch soon.'
        )
            ->context('contact_acknowledgement')
            ->meta(['enquiry_uuid' => $this->enquiry->uuid]);
    }
}
