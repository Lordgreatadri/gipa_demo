<?php

namespace App\Models;

use Dyrynda\Database\Support\GeneratesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class DistrictWorkflowEvent extends Model
{
    use GeneratesUuid;

    public $timestamps = false;

    protected $fillable = [
        'district_id',
        'actor_id',
        'assigned_to',
        'action',
        'from_status',
        'to_status',
        'reason',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Workflow events are immutable.'));
        static::deleting(fn () => throw new LogicException('Workflow events are immutable.'));
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
