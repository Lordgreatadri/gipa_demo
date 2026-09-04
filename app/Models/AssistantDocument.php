<?php

namespace App\Models;

use Dyrynda\Database\Support\GeneratesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssistantDocument extends Model
{
    use GeneratesUuid, SoftDeletes;

    public const CATEGORIES = [
        'about' => 'About GIPA',
        'opportunities' => 'Investment opportunities',
        'sectors' => 'Sectors & industries',
        'districts' => 'Districts & regions',
        'onboarding' => 'Investor onboarding & KYC',
        'certificates' => 'Certificates & verification',
        'incentives' => 'Incentives & fees',
        'contacts' => 'Contacts',
        'faq' => 'Frequently asked questions',
        'policy' => 'Policies & guidance',
    ];

    protected $attributes = [
        'source_type' => 'manual',
        'category' => 'faq',
        'is_published' => true,
    ];

    protected $fillable = [
        'title',
        'slug',
        'category',
        'source_type',
        'summary',
        'body',
        'is_published',
        'checksum',
        'indexed_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'indexed_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(AssistantDocumentChunk::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? ucfirst($this->category);
    }

    public function needsIndexing(): bool
    {
        return $this->indexed_at === null || $this->checksum !== md5($this->body);
    }
}
