<?php

namespace App\Models;

use Dyrynda\Database\Support\GeneratesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class CertificateVerification extends Model implements HasMedia
{
    use GeneratesUuid, InteractsWithMedia;

    public const DECISION_VALID = 'valid';

    public const DECISION_SUSPICIOUS = 'suspicious';

    public const DECISION_INVALID = 'invalid';

    public const EVIDENCE_COLLECTION = 'field_evidence';

    public $timestamps = false;

    protected $fillable = [
        'reference',
        'certificate_id',
        'officer_id',
        'district_id',
        'system_result',
        'officer_decision',
        'notes',
        'latitude',
        'longitude',
        'accuracy_metres',
        'connectivity',
        'registry_checked_at',
        'client_recorded_at',
        'idempotency_key',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'accuracy_metres' => 'decimal:2',
            'registry_checked_at' => 'datetime',
            'client_recorded_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Certificate verifications are immutable.'));
        static::deleting(fn () => throw new LogicException('Certificate verifications are immutable.'));
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::EVIDENCE_COLLECTION)->useDisk(config('filesystems.default'));
    }

    public function certificate(): BelongsTo
    {
        return $this->belongsTo(Certificate::class);
    }

    public function officer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'officer_id');
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }
}
