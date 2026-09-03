<?php

namespace App\Models;

use Dyrynda\Database\Support\GeneratesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvestorProfile extends Model
{
    use GeneratesUuid, HasFactory, SoftDeletes;

    public const TYPE_INDIVIDUAL = 'individual';

    public const TYPE_ORGANIZATION_REPRESENTATIVE = 'organization_representative';

    public const ONBOARDING_NOT_STARTED = 'not_started';

    public const ONBOARDING_IN_PROGRESS = 'in_progress';

    public const ONBOARDING_SUBMITTED = 'submitted';

    public const ONBOARDING_VERIFIED = 'verified';

    public const ONBOARDING_ACTION_REQUIRED = 'action_required';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_ARCHIVED = 'archived';

    protected $attributes = [
        'profile_type' => self::TYPE_INDIVIDUAL,
        'country_code' => 'GH',
        'preferred_language' => 'en',
        'preferred_contact_channel' => 'email',
        'onboarding_state' => self::ONBOARDING_NOT_STARTED,
        'status' => self::STATUS_ACTIVE,
        'version' => 1,
    ];

    protected $fillable = [
        'user_id',
        'profile_type',
        'display_name',
        'country_code',
        'nationality_country_code',
        'preferred_language',
        'preferred_contact_channel',
        'onboarding_state',
        'status',
        'last_engaged_at',
        'onboarded_at',
        'created_by',
        'updated_by',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'last_engaged_at' => 'datetime',
            'onboarded_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function onboardingCases(): HasMany
    {
        return $this->hasMany(InvestorOnboardingCase::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(InvestorDocument::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function matchPreference(): HasOne
    {
        return $this->hasOne(InvestorMatchPreference::class);
    }
}
