<?php

namespace App\Models;

use App\Models\Opportunity;
use Dyrynda\Database\Support\GeneratesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpportunityFinancial extends Model
{
    use GeneratesUuid;

    protected $attributes = [
        'currency' => 'GHS',
    ];

    protected $fillable = [
        'opportunity_id',
        'investment_structure_id',
        'amount',
        'currency',
        'roi_percentage',
        'irr_percentage',
        'npv',
        'payback_period_months',
        'projected_revenue',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'roi_percentage' => 'decimal:4',
            'irr_percentage' => 'decimal:4',
            'npv' => 'decimal:2',
            'payback_period_months' => 'integer',
            'projected_revenue' => 'decimal:2',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function investmentStructure(): BelongsTo
    {
        return $this->belongsTo(InvestmentStructure::class);
    }
}