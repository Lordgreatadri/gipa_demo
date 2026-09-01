<?php

namespace App\Services;

use App\Models\InvestorInquiry;
use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Support\Str;

class InvestorInquiryService
{
    public function submit(Opportunity $opportunity, array $data, ?User $investor = null): InvestorInquiry
    {
        return $opportunity->inquiries()->create([
            ...$data,
            'reference' => 'INQ-'.now()->format('Ymd').'-'.Str::upper(Str::random(10)),
            'investor_id' => $investor?->id,
            'consent_at' => now(),
        ]);
    }
}
