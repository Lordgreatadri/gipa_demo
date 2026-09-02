<?php

namespace Database\Seeders;

use App\Models\CertificateType;
use Illuminate\Database\Seeder;

class CertificateTypeSeeder extends Seeder
{
    public function run(): void
    {
        CertificateType::query()->updateOrCreate(
            ['code' => 'INVESTMENT_REGISTRATION'],
            ['name' => 'Investment Registration Certificate', 'is_active' => true, 'sort_order' => 10],
        );
    }
}
