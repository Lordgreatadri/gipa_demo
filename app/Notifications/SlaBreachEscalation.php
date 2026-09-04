<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

/**
 * Alerts the assigned reviewer that a work item has passed its SLA deadline
 * and requires immediate attention.
 */
class SlaBreachEscalation extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $domain,
        public readonly string $title,
        public readonly Carbon $dueAt,
        public readonly ?string $url = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->error()
            ->subject("SLA breach: {$this->title}")
            ->line("A {$this->domain} item you are assigned to has breached its service-level deadline.")
            ->line("Item: {$this->title}")
            ->line('Deadline was: '.$this->dueAt->format('j M Y H:i').' ('.$this->dueAt->diffForHumans().').');

        if ($this->url) {
            $message->action('Review now', $this->url);
        }

        return $message->line('Please action this item as soon as possible.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'domain' => $this->domain,
            'title' => $this->title,
            'due_at' => $this->dueAt->toIso8601String(),
            'url' => $this->url,
        ];
    }
}
