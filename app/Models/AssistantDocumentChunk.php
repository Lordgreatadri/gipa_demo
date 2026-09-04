<?php

namespace App\Models;

use Dyrynda\Database\Support\GeneratesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssistantDocumentChunk extends Model
{
    use GeneratesUuid;

    protected $fillable = [
        'assistant_document_id',
        'ordinal',
        'content',
        'token_estimate',
        'embedding',
        'embedding_model',
    ];

    protected function casts(): array
    {
        return [
            'ordinal' => 'integer',
            'token_estimate' => 'integer',
            'embedding' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(AssistantDocument::class, 'assistant_document_id');
    }
}
