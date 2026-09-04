<?php

namespace App\Models;

use Dyrynda\Database\Support\GeneratesUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Opportunity extends Model implements HasMedia
{
    use GeneratesUuid, InteractsWithMedia, SoftDeletes;

    public const PROJECT_STATUS_PROPOSED = 'proposed';

    public const WORKFLOW_DRAFT = 'draft';

    public const WORKFLOW_PENDING_APPROVAL = 'pending_approval';

    public const WORKFLOW_APPROVED = 'approved';

    public const WORKFLOW_ACTIVE = 'active';

    public const WORKFLOW_COMPLETED = 'completed';

    public const WORKFLOW_CANCELLED = 'cancelled';

    public const DOCUMENT_BUSINESS_PLAN = 'business_plan';

    public const DOCUMENT_TECHNICAL_FEASIBILITY = 'technical_feasibility';

    public const DOCUMENT_MARKET_FEASIBILITY = 'market_feasibility';

    public const DOCUMENT_FUNDING_STRUCTURE = 'funding_structure';

    protected $attributes = [
        'project_status' => self::PROJECT_STATUS_PROPOSED,
        'workflow_status' => self::WORKFLOW_DRAFT,
        'version' => 1,
    ];

    protected $fillable = [
        'district_id',
        'sector_id',
        'sub_sector_id',
        'enterprise_type_id',
        'title',
        'location_description',
        'latitude',
        'longitude',
        'location',
        'overview',
        'objectives',
        'rationale',
        'success_factors',
        'competitive_advantages',
        'project_status',
        'workflow_status',
        'reviewer_id',
        'sla_due_at',
        'sla_escalated_at',
        'decision_reason',
        'submitted_at',
        'approved_at',
        'published_at',
        'created_by',
        'updated_by',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'sla_due_at' => 'datetime',
            'sla_escalated_at' => 'datetime',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'published_at' => 'datetime',
            'version' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::DOCUMENT_BUSINESS_PLAN);
        $this->addMediaCollection(self::DOCUMENT_TECHNICAL_FEASIBILITY);
        $this->addMediaCollection(self::DOCUMENT_MARKET_FEASIBILITY);
        $this->addMediaCollection(self::DOCUMENT_FUNDING_STRUCTURE);
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query
            ->whereNotNull('published_at')
            ->whereIn('workflow_status', [
                self::WORKFLOW_APPROVED,
                self::WORKFLOW_ACTIVE,
                self::WORKFLOW_COMPLETED,
                self::WORKFLOW_CANCELLED,
            ])
            ->whereHas('district', fn (Builder $district) => $district
                ->where('workflow_status', District::STATUS_PUBLISHED)
                ->whereNotNull('published_at'));
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }

    public function subSector(): BelongsTo
    {
        return $this->belongsTo(SubSector::class);
    }

    public function enterpriseType(): BelongsTo
    {
        return $this->belongsTo(EnterpriseType::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function financial(): HasOne
    {
        return $this->hasOne(OpportunityFinancial::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(OpportunityContact::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function workflowEvents(): HasMany
    {
        return $this->hasMany(OpportunityWorkflowEvent::class);
    }

    public function inquiries(): HasMany
    {
        return $this->hasMany(InvestorInquiry::class);
    }
}
