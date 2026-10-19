<?php

namespace App\Models;

use Dyrynda\Database\Support\GeneratesUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class ContactUs extends Model
{
    use GeneratesUuid, Notifiable, SoftDeletes;

    public const STATUS_NEW = 'new';

    public const STATUS_READ = 'read';

    public const STATUS_RESPONDED = 'responded';

    public const STATUS_CLOSED = 'closed';

    public const STATUSES = [
        self::STATUS_NEW => 'New',
        self::STATUS_READ => 'Read',
        self::STATUS_RESPONDED => 'Responded',
        self::STATUS_CLOSED => 'Closed',
    ];

    protected $table = 'contact_us';

    protected $fillable = [
        'reference',
        'name',
        'email',
        'phone_number',
        'available_time_for_call',
        'subject',
        'message',
        'ip_address',
        'status',
        'read_by',
        'read_at',
        'assigned_to',
        'admin_note',
        'responded_at',
        'responded_by',
        'sms_sent',
        'sms_sent_at',
        'email_sent',
        'email_sent_at',
    ];

    protected $attributes = [
        'status' => self::STATUS_NEW,
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'responded_at' => 'datetime',
            'sms_sent' => 'boolean',
            'sms_sent_at' => 'datetime',
            'email_sent' => 'boolean',
            'email_sent_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function responder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responded_by');
    }

    public function scopeOutstanding(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_NEW, self::STATUS_READ]);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }

    public function routeNotificationForMail(): string
    {
        return $this->email;
    }

    public function routeNotificationForSms(): ?string
    {
        return $this->phone_number;
    }
}
