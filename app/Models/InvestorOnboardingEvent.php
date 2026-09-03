<?php

namespace App\Models;

use Dyrynda\Database\Support\GeneratesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class InvestorOnboardingEvent extends Model
{
    use GeneratesUuid;

    public $timestamps = false;

    protected $fillable = [
        'investor_onboarding_case_id',
        'actor_id',
        'action',
        'from_status',
        'to_status',
        'reason',
        'metadata',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Onboarding events are immutable.'));
        static::deleting(fn () => throw new LogicException('Onboarding events are immutable.'));
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function onboardingCase(): BelongsTo
    {
        return $this->belongsTo(InvestorOnboardingCase::class, 'investor_onboarding_case_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
