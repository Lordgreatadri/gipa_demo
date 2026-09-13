<?php

namespace App\Notifications;

use App\Models\ContactUs;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Alerts the GIPA support team that a new public enquiry needs attention.
 */
class ContactEnquiryReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly ContactUs $enquiry) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New contact enquiry '.$this->enquiry->reference)
            ->replyTo($this->enquiry->email, $this->enquiry->name)
            ->greeting('A new enquiry has arrived.')
            ->line('Reference: '.$this->enquiry->reference)
            ->line('From: '.$this->enquiry->name.' <'.$this->enquiry->email.'>')
            ->when($this->enquiry->phone_number, fn (MailMessage $mail) => $mail->line('Phone: '.$this->enquiry->phone_number))
            ->when($this->enquiry->available_time_for_call, fn (MailMessage $mail) => $mail->line('Best time to call: '.$this->enquiry->available_time_for_call))
            ->line('Subject: '.$this->enquiry->subject)
            ->line('Message:')
            ->line($this->enquiry->message)
            ->action('Open in the staff workspace', route('staff.contact-messages.show', $this->enquiry))
            ->line('Please respond within the published service standard.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'contact_enquiry',
            'enquiry_uuid' => $this->enquiry->uuid,
            'reference' => $this->enquiry->reference,
            'name' => $this->enquiry->name,
            'subject' => $this->enquiry->subject,
            'received_at' => $this->enquiry->created_at?->toIso8601String(),
        ];
    }
}
