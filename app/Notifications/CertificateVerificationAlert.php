<?php

namespace App\Notifications;

use App\Models\CertificateVerification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CertificateVerificationAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly CertificateVerification $verification) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('IOMP certificate verification alert')
            ->line("Certificate {$this->verification->certificate->certificate_number} requires review.")
            ->line('System result: '.str($this->verification->system_result)->replace('_', ' ')->title())
            ->line('Officer decision: '.str($this->verification->officer_decision)->title())
            ->line("Verification reference: {$this->verification->reference}");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'entity_type' => 'certificate_verification',
            'certificate_uuid' => $this->verification->certificate->uuid,
            'certificate_number' => $this->verification->certificate->certificate_number,
            'verification_uuid' => $this->verification->uuid,
            'reference' => $this->verification->reference,
            'system_result' => $this->verification->system_result,
            'officer_decision' => $this->verification->officer_decision,
        ];
    }
}
