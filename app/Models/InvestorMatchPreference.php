<?php

namespace App\Models;

use Dyrynda\Database\Support\GeneratesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class InvestorMatchPreference extends Model
{
    use GeneratesUuid;

    protected $fillable = [
        'investor_profile_id',
        'minimum_investment',
        'maximum_investment',
        'currency',
        'minimum_readiness_score',
    ];

    protected function casts(): array
    {
        return [
            'minimum_investment' => 'decimal:2',
            'maximum_investment' => 'decimal:2',
            'minimum_readiness_score' => 'decimal:2',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(InvestorProfile::class, 'investor_profile_id');
    }

    public function sectors(): BelongsToMany
    {
        return $this->belongsToMany(Sector::class, 'investor_match_preference_sector');
    }

    public function regions(): BelongsToMany
    {
        return $this->belongsToMany(Region::class, 'investor_match_preference_region');
    }
}
