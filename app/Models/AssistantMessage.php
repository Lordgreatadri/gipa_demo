<?php

namespace App\Models;

use Dyrynda\Database\Support\GeneratesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssistantMessage extends Model
{
    use GeneratesUuid;

    public const ROLE_USER = 'user';

    public const ROLE_ASSISTANT = 'assistant';

    protected $fillable = [
        'assistant_conversation_id',
        'role',
        'content',
        'citations',
        'tools_used',
        'provider',
        'model',
        'tokens_input',
        'tokens_output',
        'latency_ms',
        'was_grounded',
        'flagged',
    ];

    protected function casts(): array
    {
        return [
            'citations' => 'array',
            'tools_used' => 'array',
            'tokens_input' => 'integer',
            'tokens_output' => 'integer',
            'latency_ms' => 'integer',
            'was_grounded' => 'boolean',
            'flagged' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AssistantConversation::class, 'assistant_conversation_id');
    }
}
