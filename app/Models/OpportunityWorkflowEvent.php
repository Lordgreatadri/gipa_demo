<?php

namespace App\Models;

use App\Models\Opportunity;
use Dyrynda\Database\Support\GeneratesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class OpportunityWorkflowEvent extends Model
{
    use GeneratesUuid;

    public $timestamps = false;

    protected $fillable = [
        'opportunity_id',
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

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
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