<?php

namespace App\Models;

use Dyrynda\Database\Support\GeneratesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class CertificateLifecycleEvent extends Model
{
    use GeneratesUuid;

    public $timestamps = false;

    protected $fillable = [
        'certificate_id',
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
        static::updating(fn () => throw new LogicException('Certificate lifecycle events are immutable.'));
        static::deleting(fn () => throw new LogicException('Certificate lifecycle events are immutable.'));
    }

    public function certificate(): BelongsTo
    {
        return $this->belongsTo(Certificate::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
