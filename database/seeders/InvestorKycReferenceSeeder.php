<?php

namespace Database\Seeders;

use App\Models\InvestorDocumentType;
use Illuminate\Database\Seeder;

class InvestorKycReferenceSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            [
                'code' => 'government_id',
                'name' => 'Government-issued identification',
                'description' => 'Passport, national identity card, or other government-issued photo identification.',
                'is_required' => true,
                'requires_expiry' => true,
                'sort_order' => 10,
            ],
            [
                'code' => 'proof_of_address',
                'name' => 'Proof of address',
                'description' => 'Recent utility bill, bank statement, or official correspondence showing the residential address.',
                'is_required' => true,
                'requires_expiry' => false,
                'sort_order' => 20,
            ],
            [
                'code' => 'business_registration',
                'name' => 'Business registration',
                'description' => 'Registration or incorporation evidence for an organization represented by the investor.',
                'applies_to_profile_type' => 'organization_representative',
                'is_required' => true,
                'requires_expiry' => false,
                'sort_order' => 30,
            ],
            [
                'code' => 'source_of_funds',
                'name' => 'Source of funds evidence',
                'description' => 'Supporting evidence appropriate to the proposed investment.',
                'is_required' => false,
                'requires_expiry' => false,
                'sort_order' => 40,
            ],
        ] as $type) {
            InvestorDocumentType::query()->updateOrCreate(['code' => $type['code']], $type);
        }
    }
}
