<?php

namespace App\Models;

use Dyrynda\Database\Support\GeneratesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class District extends Model
{
    use GeneratesUuid, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_UNDER_REVIEW = 'under_review';

    public const STATUS_PUBLISHED = 'published';

    protected $attributes = [
        'workflow_status' => self::STATUS_DRAFT,
        'version' => 1,
    ];

    protected $fillable = [
        'region_id',
        'code',
        'name',
        'capital',
        'location_description',
        'boundary',
        'centroid',
        'readiness_score',
        'population',
        'area_sq_km',
        'infrastructure_quality_score',
        'economic_data',
        'workflow_status',
        'reviewer_id',
        'sla_due_at',
        'sla_escalated_at',
        'review_reason',
        'published_at',
        'created_by',
        'updated_by',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'readiness_score' => 'decimal:2',
            'population' => 'integer',
            'area_sq_km' => 'decimal:2',
            'infrastructure_quality_score' => 'decimal:2',
            'economic_data' => 'array',
            'sla_due_at' => 'datetime',
            'sla_escalated_at' => 'datetime',
            'published_at' => 'datetime',
            'version' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
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

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class);
    }

    public function staffAssignments(): HasMany
    {
        return $this->hasMany(StaffDistrictAssignment::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function workflowEvents(): HasMany
    {
        return $this->hasMany(DistrictWorkflowEvent::class);
    }
}
