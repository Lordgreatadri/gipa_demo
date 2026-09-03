<?php

namespace App\Models;

use Dyrynda\Database\Support\GeneratesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InvestmentStructure extends Model
{
    use GeneratesUuid;

    protected $attributes = [
        'is_active' => true,
    ];

    protected $fillable = [
        'code',
        'name',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function opportunityFinancials(): HasMany
    {
        return $this->hasMany(OpportunityFinancial::class);
    }
}
