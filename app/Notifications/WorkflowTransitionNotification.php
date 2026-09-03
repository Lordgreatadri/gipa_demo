<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkflowTransitionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $entityType,
        public readonly string $entityTitle,
        public readonly string $action,
        public readonly string $status,
        public readonly ?string $reason = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("IOMP {$this->entityType} workflow update")
            ->line("{$this->entityTitle} has a new workflow update.")
            ->line('Action: '.str($this->action)->replace('_', ' ')->title())
            ->line('Status: '.str($this->status)->replace('_', ' ')->title())
            ->when($this->reason, fn (MailMessage $message) => $message->line('Reason: '.$this->reason));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'entity_type' => $this->entityType,
            'entity_title' => $this->entityTitle,
            'action' => $this->action,
            'status' => $this->status,
            'reason' => $this->reason,
        ];
    }
}
