<?php

namespace App\Models;

use Dyrynda\Database\Support\GeneratesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SmsProviderConfig extends Model
{
    use GeneratesUuid, SoftDeletes;

    protected $fillable = [
        'provider',
        'label',
        'sender_id',
        'is_active',
        'priority',
        'credentials',
        'webhook_secret',
        'options',
        'created_by',
        'updated_by',
    ];

    protected $hidden = [
        'credentials',
        'webhook_secret',
    ];

    protected $attributes = [
        'is_active' => false,
        'priority' => 100,
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'priority' => 'integer',
            'credentials' => 'encrypted:array',
            'webhook_secret' => 'encrypted',
            'options' => 'array',
            'balance_snapshot' => 'array',
            'balance_checked_at' => 'datetime',
            'last_success_at' => 'datetime',
            'last_failure_at' => 'datetime',
            'consecutive_failures' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Static catalogue entry describing this provider's adapter and credentials.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return (array) config('sms.providers.'.$this->provider, []);
    }

    public function displayName(): string
    {
        return $this->label ?: (string) ($this->definition()['label'] ?? ucfirst($this->provider));
    }

    public function credential(string $key, ?string $default = null): ?string
    {
        $value = ($this->credentials ?? [])[$key] ?? null;

        if (blank($value)) {
            $value = ($this->definition()['defaults'] ?? [])[$key] ?? $default;
        }

        return $value === null ? null : (string) $value;
    }

    /**
     * True when every required credential for this provider has a value.
     */
    public function isConfigured(): bool
    {
        foreach ($this->definition()['credentials'] ?? [] as $key => $meta) {
            if (($meta['required'] ?? false) && blank($this->credential($key))) {
                return false;
            }
        }

        return true;
    }

    public function isUsable(): bool
    {
        return $this->is_active && $this->isConfigured();
    }

    /**
     * @return array<int, string>
     */
    public function missingCredentials(): array
    {
        $missing = [];

        foreach ($this->definition()['credentials'] ?? [] as $key => $meta) {
            if (($meta['required'] ?? false) && blank($this->credential($key))) {
                $missing[] = (string) ($meta['label'] ?? $key);
            }
        }

        return $missing;
    }

    public function supportsBalance(): bool
    {
        return (bool) ($this->definition()['supports_balance'] ?? false);
    }

    public function supportsBundles(): bool
    {
        return (bool) ($this->definition()['supports_bundles'] ?? false);
    }

    public function logChannel(): string
    {
        return (string) ($this->definition()['log_channel'] ?? 'sms');
    }

    public function webhookRouteName(): ?string
    {
        $config = $this->definition()['webhook_config'] ?? null;

        return $config ? 'webhook-client-'.$config : null;
    }

    public function callbackUrl(): ?string
    {
        $routeName = $this->webhookRouteName();

        if (! $routeName) {
            return null;
        }

        try {
            return route($routeName);
        } catch (\Throwable) {
            return null;
        }
    }

    public function scopeUsable($query)
    {
        return $query->where('is_active', true)->orderBy('priority');
    }
}
