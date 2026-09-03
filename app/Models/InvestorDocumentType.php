<?php

namespace App\Models;

use Dyrynda\Database\Support\GeneratesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InvestorDocumentType extends Model
{
    use GeneratesUuid;

    protected $fillable = [
        'code',
        'name',
        'description',
        'applies_to_profile_type',
        'is_required',
        'requires_expiry',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'requires_expiry' => 'boolean',
            'is_active' => 'boolean',
            'uuid' => 'string',
        ];
    }

    public function documents(): HasMany
    {
        return $this->hasMany(InvestorDocument::class, 'document_type_id');
    }
}
