<?php

namespace App\Models;

use Dyrynda\Database\Support\GeneratesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiTokenSession extends Model
{
    use GeneratesUuid;

    protected $fillable = [
        'user_id',
        'access_jti_hash',
        'refresh_token_hash',
        'access_expires_at',
        'refresh_expires_at',
        'last_used_at',
        'revoked_at',
        'ip_address',
        'user_agent',
    ];

    protected $hidden = ['access_jti_hash', 'refresh_token_hash'];

    protected function casts(): array
    {
        return [
            'access_expires_at' => 'datetime',
            'refresh_expires_at' => 'datetime',
            'last_used_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
