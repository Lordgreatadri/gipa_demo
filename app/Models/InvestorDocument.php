<?php

namespace App\Models;

use Dyrynda\Database\Support\GeneratesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class InvestorDocument extends Model implements HasMedia
{
    use GeneratesUuid, InteractsWithMedia;

    public const COLLECTION_FILE = 'kyc_file';

    public const STATUS_QUARANTINED = 'quarantined';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_EXPIRED = 'expired';

    public const SCAN_PENDING = 'pending';

    public const SCAN_CLEAN = 'clean';

    public const SCAN_INFECTED = 'infected';

    public const SCAN_FAILED = 'failed';

    protected $attributes = [
        'status' => self::STATUS_QUARANTINED,
        'malware_scan_status' => self::SCAN_PENDING,
    ];

    protected $fillable = [
        'investor_profile_id',
        'investor_onboarding_case_id',
        'document_type_id',
        'status',
        'issued_at',
        'expires_at',
        'verified_at',
        'verified_by',
        'rejection_reason',
        'checksum_sha256',
        'malware_scan_status',
        'malware_scanned_at',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
            'expires_at' => 'date',
            'verified_at' => 'datetime',
            'malware_scanned_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::COLLECTION_FILE)->useDisk('local')->singleFile();
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(InvestorProfile::class, 'investor_profile_id');
    }

    public function onboardingCase(): BelongsTo
    {
        return $this->belongsTo(InvestorOnboardingCase::class, 'investor_onboarding_case_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(InvestorDocumentType::class, 'document_type_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
