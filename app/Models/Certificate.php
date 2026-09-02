<?php

namespace App\Models;

use App\Support\CertificatePermissions;
use Dyrynda\Database\Support\GeneratesUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class Certificate extends Model
{
    use GeneratesUuid;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_REVOKED = 'revoked';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_SUPERSEDED = 'superseded';

    public const ARTIFACT_PENDING = 'pending';

    public const ARTIFACT_READY = 'ready';

    public const ARTIFACT_FAILED = 'failed';

    private const SIGNED_FIELDS = [
        'certificate_number',
        'certificate_type_id',
        'investor_profile_id',
        'opportunity_id',
        'district_id',
        'holder_name_snapshot',
        'organization_name_snapshot',
        'project_name_snapshot',
        'issued_at',
        'expires_at',
        'canonicalization_version',
        'signed_payload',
        'payload_hash',
        'signature_algorithm',
        'signing_key_id',
        'digital_signature',
        'supersedes_certificate_id',
    ];

    protected $attributes = [
        'status' => self::STATUS_DRAFT,
        'artifact_status' => self::ARTIFACT_PENDING,
        'version' => 1,
    ];

    protected $hidden = [
        'public_token',
        'public_token_hash',
        'digital_signature',
        'artifact_status',
        'qr_code_path',
        'pdf_path',
        'artifacts_generated_at',
    ];

    protected $fillable = [
        'certificate_number',
        'public_token_hash',
        'public_token',
        'certificate_type_id',
        'investor_profile_id',
        'opportunity_id',
        'district_id',
        'status',
        'holder_name_snapshot',
        'organization_name_snapshot',
        'project_name_snapshot',
        'issued_at',
        'expires_at',
        'issued_by',
        'canonicalization_version',
        'signed_payload',
        'payload_hash',
        'signature_algorithm',
        'signing_key_id',
        'digital_signature',
        'supersedes_certificate_id',
        'created_by',
        'updated_by',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'expires_at' => 'datetime',
            'public_token' => 'encrypted',
            'canonicalization_version' => 'integer',
            'signed_payload' => 'array',
            'artifacts_generated_at' => 'datetime',
            'version' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (Certificate $certificate): void {
            if ($certificate->getOriginal('status') !== self::STATUS_DRAFT && $certificate->isDirty(self::SIGNED_FIELDS)) {
                throw new LogicException('Issued certificate snapshots are immutable.');
            }
        });
        static::deleting(function (Certificate $certificate): void {
            if ($certificate->status !== self::STATUS_DRAFT) {
                throw new LogicException('Issued certificates cannot be deleted.');
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function scopeAccessibleTo(Builder $query, User $user): Builder
    {
        if ($user->can(CertificatePermissions::ISSUE)
            || $user->can(CertificatePermissions::SUSPEND)
            || $user->can(CertificatePermissions::REVOKE)) {
            return $query;
        }

        return $query->whereExists(fn ($assignments) => $assignments
            ->selectRaw('1')
            ->from('staff_district_assignments')
            ->whereColumn('staff_district_assignments.district_id', 'certificates.district_id')
            ->where('staff_district_assignments.user_id', $user->id)
            ->where('staff_district_assignments.starts_at', '<=', now())
            ->where(fn ($active) => $active
                ->whereNull('staff_district_assignments.ends_at')
                ->orWhere('staff_district_assignments.ends_at', '>', now())));
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(CertificateType::class, 'certificate_type_id');
    }

    public function investorProfile(): BelongsTo
    {
        return $this->belongsTo(InvestorProfile::class);
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function lifecycleEvents(): HasMany
    {
        return $this->hasMany(CertificateLifecycleEvent::class);
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(CertificateVerification::class);
    }
}
