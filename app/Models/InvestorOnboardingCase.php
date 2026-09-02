<?php

namespace App\Models;

use App\Models\InvestorDocument;
use App\Models\InvestorOnboardingEvent;
use App\Models\InvestorProfile;
use App\Models\User;
use Dyrynda\Database\Support\GeneratesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InvestorOnboardingCase extends Model
{
    use GeneratesUuid, HasFactory;

    public const TYPE_INITIAL = 'initial_onboarding';
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_ACTION_REQUIRED = 'action_required';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_WITHDRAWN = 'withdrawn';

    protected $attributes = [
        'case_type' => self::TYPE_INITIAL,
        'status' => self::STATUS_DRAFT,
        'version' => 1,
    ];

    protected $fillable = [
        'reference',
        'investor_profile_id',
        'case_type',
        'status',
        'assigned_to',
        'submitted_at',
        'review_started_at',
        'decided_at',
        'sla_due_at',
        'decision_reason',
        'created_by',
        'updated_by',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'review_started_at' => 'datetime',
            'decided_at' => 'datetime',
            'sla_due_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(InvestorProfile::class, 'investor_profile_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function events(): HasMany
    {
        return $this->hasMany(InvestorOnboardingEvent::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(InvestorDocument::class);
    }
}