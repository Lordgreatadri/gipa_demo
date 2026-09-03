<?php

namespace App\Models;

use Dyrynda\Database\Support\GeneratesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvestorInquiry extends Model
{
    use GeneratesUuid;

    public const STATUS_NEW = 'new';

    public const STATUS_RESPONDED = 'responded';

    protected $attributes = [
        'status' => self::STATUS_NEW,
    ];

    protected $fillable = [
        'reference',
        'opportunity_id',
        'investor_id',
        'name',
        'organization',
        'email',
        'phone',
        'country',
        'subject',
        'message',
        'status',
        'assigned_to',
        'consent_at',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'consent_at' => 'datetime',
            'responded_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function investor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'investor_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
