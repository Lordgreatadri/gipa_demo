<?php

namespace App\Models;

use App\Models\SmsDeliveryEvent;
use Dyrynda\Database\Support\GeneratesUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SmsMessage extends Model
{
    use GeneratesUuid;

    public const STATUS_PENDING = 'pending';

    public const STATUS_QUEUED = 'queued';

    public const STATUS_SENT = 'sent';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_UNDELIVERED = 'undelivered';

    public const STATUS_FAILED = 'failed';

    public const STATUS_SKIPPED = 'skipped';

    public const STATUSES = [
        self::STATUS_PENDING => 'Pending',
        self::STATUS_QUEUED => 'Queued',
        self::STATUS_SENT => 'Sent',
        self::STATUS_DELIVERED => 'Delivered',
        self::STATUS_UNDELIVERED => 'Undelivered',
        self::STATUS_FAILED => 'Failed',
        self::STATUS_SKIPPED => 'Skipped',
    ];

    /** Statuses that consumed provider credit. */
    public const BILLABLE_STATUSES = [
        self::STATUS_QUEUED,
        self::STATUS_SENT,
        self::STATUS_DELIVERED,
    ];

    protected $fillable = [
        'reference',
        'provider',
        'context',
        'sender_id',
        'recipient',
        'recipient_raw',
        'body',
        'segments',
        'status',
        'provider_message_id',
        'failure_reason',
        'attempts',
        'notifiable_type',
        'notifiable_id',
        'meta',
        'queued_at',
        'sent_at',
        'delivered_at',
        'failed_at',
    ];

    protected function casts(): array
    {
        return [
            'segments' => 'integer',
            'attempts' => 'integer',
            'meta' => 'array',
            'queued_at' => 'datetime',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    public function deliveryEvents(): HasMany
    {
        return $this->hasMany(SmsDeliveryEvent::class);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function isSettled(): bool
    {
        return in_array($this->status, [self::STATUS_DELIVERED, self::STATUS_FAILED, self::STATUS_UNDELIVERED, self::STATUS_SKIPPED], true);
    }

    public function scopeBillable(Builder $query): Builder
    {
        return $query->whereIn('status', self::BILLABLE_STATUSES);
    }

    /**
     * Never expose the full destination number in staff listings.
     */
    public function maskedRecipient(): string
    {
        $value = (string) $this->recipient;
        $length = strlen($value);

        if ($length <= 5) {
            return $value;
        }

        return substr($value, 0, $length - 6).str_repeat('•', 3).substr($value, -3);
    }
}
