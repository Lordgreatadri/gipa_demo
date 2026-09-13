<?php

namespace App\Services;

use App\Models\ContactUs;
use App\Models\User;
use App\Notifications\ContactEnquiryAcknowledged;
use App\Notifications\ContactEnquiryReceived;
use App\Support\SupportPermissions;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/**
 * Stores public enquiries and fans out the acknowledgement/alert notifications.
 */
class ContactEnquiryService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function submit(array $data, ?string $ipAddress = null): ContactUs
    {
        $enquiry = ContactUs::create([
            'reference' => $this->reference(),
            'name' => $data['name'],
            'email' => $data['email'],
            'phone_number' => $data['phone_number'] ?? null,
            'available_time_for_call' => $data['available_time_for_call'] ?? null,
            'subject' => $data['subject'],
            'message' => $data['message'],
            'ip_address' => $ipAddress,
            'status' => ContactUs::STATUS_NEW,
        ]);

        $this->notifySender($enquiry);
        $this->notifyTeam($enquiry);

        return $enquiry;
    }

    public function markRead(ContactUs $enquiry, User $user): ContactUs
    {
        if ($enquiry->status === ContactUs::STATUS_NEW) {
            $enquiry->forceFill([
                'status' => ContactUs::STATUS_READ,
                'read_by' => $user->name,
                'read_at' => now(),
            ])->save();
        }

        return $enquiry;
    }

    public function markResponded(ContactUs $enquiry, User $user, ?string $note = null): ContactUs
    {
        $enquiry->forceFill([
            'status' => ContactUs::STATUS_RESPONDED,
            'responded_at' => now(),
            'responded_by' => $user->id,
            'admin_note' => $note ?: $enquiry->admin_note,
        ])->save();

        return $enquiry;
    }

    public function close(ContactUs $enquiry, ?string $note = null): ContactUs
    {
        $enquiry->forceFill([
            'status' => ContactUs::STATUS_CLOSED,
            'admin_note' => $note ?: $enquiry->admin_note,
        ])->save();

        return $enquiry;
    }

    public function assign(ContactUs $enquiry, ?User $assignee): ContactUs
    {
        $enquiry->forceFill(['assigned_to' => $assignee?->id])->save();

        return $enquiry;
    }

    private function notifySender(ContactUs $enquiry): void
    {
        $enquiry->notify(new ContactEnquiryAcknowledged($enquiry));

        $enquiry->forceFill([
            'email_sent' => true,
            'email_sent_at' => now(),
            'sms_sent' => filled($enquiry->phone_number),
            'sms_sent_at' => filled($enquiry->phone_number) ? now() : null,
        ])->save();
    }

    private function notifyTeam(ContactUs $enquiry): void
    {
        $recipients = User::query()
            ->where('account_type', User::ACCOUNT_STAFF)
            ->permission(SupportPermissions::CONTACT_VIEW)
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new ContactEnquiryReceived($enquiry));
    }

    private function reference(): string
    {
        return 'CON-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
    }
}
