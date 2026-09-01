<?php

namespace App\Models;

use App\Models\Opportunity;
use Dyrynda\Database\Support\GeneratesUuid;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use GeneratesUuid, HasFactory, HasRoles, Notifiable;

    public const ACCOUNT_INVESTOR = 'investor';

    public const ACCOUNT_STAFF = 'staff';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    protected $attributes = [
        'account_type' => self::ACCOUNT_INVESTOR,
        'status' => self::STATUS_ACTIVE,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'organization',
        'phone',
        'account_type',
        'status',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function isStaff(): bool
    {
        return $this->account_type === self::ACCOUNT_STAFF;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function createdRegions(): HasMany
    {
        return $this->hasMany(Region::class, 'created_by');
    }

    public function createdDistricts(): HasMany
    {
        return $this->hasMany(District::class, 'created_by');
    }

    public function reviewedDistricts(): HasMany
    {
        return $this->hasMany(District::class, 'reviewer_id');
    }

    public function createdOpportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class, 'created_by');
    }

    public function reviewedOpportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class, 'reviewer_id');
    }

    public function investorInquiries(): HasMany
    {
        return $this->hasMany(InvestorInquiry::class, 'investor_id');
    }

    public function assignedInquiries(): HasMany
    {
        return $this->hasMany(InvestorInquiry::class, 'assigned_to');
    }
}
