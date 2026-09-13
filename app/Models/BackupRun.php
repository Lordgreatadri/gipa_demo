<?php

namespace App\Models;

use Dyrynda\Database\Support\GeneratesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BackupRun extends Model
{
    use GeneratesUuid;

    public const STATUS_QUEUED = 'queued';

    public const STATUS_RUNNING = 'running';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const TYPE_FULL = 'full';

    public const TYPE_DATABASE = 'database';

    public const TYPE_FILES = 'files';

    public const TYPES = [
        self::TYPE_FULL => 'Database and files',
        self::TYPE_DATABASE => 'Database only',
        self::TYPE_FILES => 'Files only',
    ];

    protected $fillable = [
        'type',
        'status',
        'disk',
        'destination_path',
        'size_bytes',
        'output',
        'failure_reason',
        'triggered_by',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function trigger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? ucfirst($this->type);
    }

    public function durationForHumans(): ?string
    {
        if (! $this->started_at || ! $this->finished_at) {
            return null;
        }

        return $this->started_at->diffForHumans($this->finished_at, ['syntax' => true, 'parts' => 2]);
    }
}
