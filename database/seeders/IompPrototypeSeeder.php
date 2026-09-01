<?php

namespace Database\Seeders;

use App\Models\District;
use App\Models\EnterpriseType;
use App\Models\InvestmentStructure;
use App\Models\Opportunity;
use App\Models\Region;
use App\Models\Sector;
use App\Models\SubSector;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IompPrototypeSeeder extends Seeder
{
    public function run(): void
    {
        $regions = collect([
            ['code' => 'AS', 'name' => 'Ashanti', 'capital' => 'Kumasi'],
            ['code' => 'AA', 'name' => 'Greater Accra', 'capital' => 'Accra'],
            ['code' => 'NP', 'name' => 'Northern', 'capital' => 'Tamale'],
        ])->mapWithKeys(fn (array $data) => [$data['code'] => Region::firstOrCreate(['code' => $data['code']], $data)]);

        $districts = collect([
            ['code' => 'GH-AS-024', 'name' => 'Ejisu', 'region' => 'AS', 'readiness_score' => 78.40],
            ['code' => 'GH-AA-026', 'name' => 'Tema Metropolitan', 'region' => 'AA', 'readiness_score' => 88.20],
            ['code' => 'GH-NP-013', 'name' => 'Savelugu Municipal', 'region' => 'NP', 'readiness_score' => 70.80],
        ])->mapWithKeys(function (array $data) use ($regions) {
            $district = District::firstOrNew([
                'region_id' => $regions[$data['region']]->id,
                'name' => $data['name'],
            ]);
            $district->fill([
                'readiness_score' => $data['readiness_score'],
                'workflow_status' => District::STATUS_PUBLISHED,
                'published_at' => now()->subMonths(3),
            ]);
            $district->code ??= $data['code'];
            $district->save();

            return [$data['code'] => $district];
        });

        $sectors = collect([
            ['code' => 'AGR', 'name' => 'Agriculture and agro-processing', 'sort_order' => 1],
            ['code' => 'ENE', 'name' => 'Renewable energy', 'sort_order' => 2],
            ['code' => 'LOG', 'name' => 'Transport and logistics', 'sort_order' => 3],
            ['code' => 'MAN', 'name' => 'Manufacturing', 'sort_order' => 4],
            ['code' => 'TOU', 'name' => 'Tourism and hospitality', 'sort_order' => 5],
            ['code' => 'ICT', 'name' => 'Information and communication technology', 'sort_order' => 6],
            ['code' => 'HEA', 'name' => 'Healthcare and pharmaceuticals', 'sort_order' => 7],
            ['code' => 'HOU', 'name' => 'Housing and real estate', 'sort_order' => 8],
            ['code' => 'MIN', 'name' => 'Mining and mineral processing', 'sort_order' => 9],
            ['code' => 'EDU', 'name' => 'Education and skills development', 'sort_order' => 10],
        ])->mapWithKeys(fn (array $data) => [$data['code'] => Sector::updateOrCreate(['code' => $data['code']], $data)]);

        $subSectors = collect([
            ['code' => 'AGR-CROP', 'name' => 'Crop production', 'sector' => 'AGR'],
            ['code' => 'AGR-PROC', 'name' => 'Food and agro-processing', 'sector' => 'AGR'],
            ['code' => 'AGR-LIVE', 'name' => 'Livestock and poultry', 'sector' => 'AGR'],
            ['code' => 'ENE-SOLAR', 'name' => 'Solar power', 'sector' => 'ENE'],
            ['code' => 'ENE-BIO', 'name' => 'Bioenergy and waste-to-energy', 'sector' => 'ENE'],
            ['code' => 'LOG-PORT', 'name' => 'Ports and maritime logistics', 'sector' => 'LOG'],
            ['code' => 'LOG-WARE', 'name' => 'Warehousing and cold chain', 'sector' => 'LOG'],
            ['code' => 'LOG-ROAD', 'name' => 'Road and transit infrastructure', 'sector' => 'LOG'],
            ['code' => 'MAN-AUTO', 'name' => 'Automotive and equipment assembly', 'sector' => 'MAN'],
            ['code' => 'MAN-TEXT', 'name' => 'Textiles and apparel', 'sector' => 'MAN'],
            ['code' => 'MAN-PACK', 'name' => 'Packaging and consumer goods', 'sector' => 'MAN'],
            ['code' => 'TOU-ECO', 'name' => 'Eco and cultural tourism', 'sector' => 'TOU'],
            ['code' => 'TOU-HOSP', 'name' => 'Hotels and visitor services', 'sector' => 'TOU'],
            ['code' => 'ICT-FIN', 'name' => 'Financial technology', 'sector' => 'ICT'],
            ['code' => 'ICT-BPO', 'name' => 'Business process outsourcing', 'sector' => 'ICT'],
            ['code' => 'ICT-DATA', 'name' => 'Data centres and cloud services', 'sector' => 'ICT'],
            ['code' => 'HEA-CARE', 'name' => 'Clinical and diagnostic services', 'sector' => 'HEA'],
            ['code' => 'HEA-PHAR', 'name' => 'Pharmaceutical manufacturing', 'sector' => 'HEA'],
            ['code' => 'HOU-AFF', 'name' => 'Affordable housing', 'sector' => 'HOU'],
            ['code' => 'HOU-COM', 'name' => 'Commercial and industrial property', 'sector' => 'HOU'],
            ['code' => 'MIN-GOLD', 'name' => 'Gold and precious minerals', 'sector' => 'MIN'],
            ['code' => 'MIN-IND', 'name' => 'Industrial minerals and processing', 'sector' => 'MIN'],
            ['code' => 'EDU-TVET', 'name' => 'Technical and vocational education', 'sector' => 'EDU'],
            ['code' => 'EDU-DIG', 'name' => 'Digital learning and training', 'sector' => 'EDU'],
        ])->mapWithKeys(function (array $data) use ($sectors) {
            $subSector = SubSector::updateOrCreate(['code' => $data['code']], [
                'sector_id' => $sectors[$data['sector']]->id,
                'name' => $data['name'],
                'sort_order' => 1,
                'is_active' => true,
            ]);

            return [$data['code'] => $subSector];
        });

        $types = collect([
            ['code' => 'PLL', 'name' => 'Private Limited Liability'],
            ['code' => 'JV', 'name' => 'Joint Venture / Partnership'],
            ['code' => 'COOP', 'name' => 'Cooperative Society'],
            ['code' => 'SOE', 'name' => 'State-owned Enterprise'],
            ['code' => 'SPV', 'name' => 'Special Purpose Vehicle'],
            ['code' => 'SOC', 'name' => 'Social Enterprise'],
        ])->mapWithKeys(fn (array $data) => [$data['code'] => EnterpriseType::updateOrCreate(['code' => $data['code']], $data)]);

        $structures = collect([
            ['code' => 'EQUITY', 'name' => 'Equity'],
            ['code' => 'PPP', 'name' => 'Public Private Partnership'],
            ['code' => 'DEBT', 'name' => 'Debt financing'],
            ['code' => 'BLENDED', 'name' => 'Blended finance'],
            ['code' => 'CONCESSION', 'name' => 'Concession / Build-operate-transfer'],
        ])->mapWithKeys(fn (array $data) => [$data['code'] => InvestmentStructure::updateOrCreate(['code' => $data['code']], $data)]);

        $opportunities = [
            ['title' => 'Integrated cassava processing facility', 'district' => 'GH-AS-024', 'sector' => 'AGR', 'type' => 'COOP', 'structure' => 'EQUITY', 'amount' => 24000000, 'currency' => 'GHS', 'status' => Opportunity::WORKFLOW_ACTIVE, 'overview' => 'A scalable processing facility connecting cassava growers to industrial starch and food-grade product markets.', 'roi' => 16.50],
            ['title' => 'Utility-scale solar energy park', 'district' => 'GH-NP-013', 'sector' => 'ENE', 'type' => 'JV', 'structure' => 'PPP', 'amount' => 8200000, 'currency' => 'USD', 'status' => Opportunity::WORKFLOW_ACTIVE, 'overview' => 'A grid-connected solar generation project designed to expand reliable renewable power in northern Ghana.', 'roi' => 13.80],
            ['title' => 'Tema circular manufacturing campus', 'district' => 'GH-AA-026', 'sector' => 'MAN', 'type' => 'PLL', 'structure' => 'EQUITY', 'amount' => 12500000, 'currency' => 'USD', 'status' => Opportunity::WORKFLOW_APPROVED, 'overview' => 'A serviced industrial campus for manufacturers converting recovered materials into export-ready products.', 'roi' => 18.20],
            ['title' => 'Northern grains storage network', 'district' => 'GH-NP-013', 'sector' => 'AGR', 'type' => 'JV', 'structure' => 'DEBT', 'amount' => 36000000, 'currency' => 'GHS', 'status' => Opportunity::WORKFLOW_ACTIVE, 'overview' => 'A network of modern storage and aggregation facilities reducing post-harvest losses for grain producers.', 'roi' => 12.40],
            ['title' => 'Eastern corridor cold-chain hub', 'district' => 'GH-AA-026', 'sector' => 'LOG', 'type' => 'PLL', 'structure' => 'EQUITY', 'amount' => 6800000, 'currency' => 'USD', 'status' => Opportunity::WORKFLOW_ACTIVE, 'overview' => 'Temperature-controlled logistics capacity supporting horticulture, fisheries and pharmaceutical distribution.', 'roi' => 15.60],
            ['title' => 'Ejisu agro-equipment assembly centre', 'district' => 'GH-AS-024', 'sector' => 'MAN', 'type' => 'JV', 'structure' => 'PPP', 'amount' => 18500000, 'currency' => 'GHS', 'status' => Opportunity::WORKFLOW_COMPLETED, 'overview' => 'An assembly and service centre improving access to appropriately scaled agricultural machinery.', 'roi' => 14.10],
        ];

        foreach ($opportunities as $index => $data) {
            $opportunity = Opportunity::updateOrCreate(['title' => $data['title']], [
                'district_id' => $districts[$data['district']]->id,
                'sector_id' => $sectors[$data['sector']]->id,
                'enterprise_type_id' => $types[$data['type']]->id,
                'overview' => $data['overview'],
                'rationale' => 'The project responds to an identified market need and builds on the district’s productive advantages.',
                'success_factors' => 'Reliable infrastructure, an experienced operating partner and strong local supply relationships.',
                'competitive_advantages' => 'Strategic market access, available workforce and support through Ghana’s investment facilitation framework.',
                'workflow_status' => $data['status'],
                'published_at' => now()->subDays($index + 1),
            ]);

            $opportunity->financial()->updateOrCreate([], [
                'investment_structure_id' => $structures[$data['structure']]->id,
                'amount' => $data['amount'],
                'currency' => $data['currency'],
                'roi_percentage' => $data['roi'],
                'payback_period_months' => 60,
            ]);

            $opportunity->contacts()->updateOrCreate(['email' => 'investmentdesk@example.gov.gh'], [
                'name' => 'GIPA Investment Desk',
                'organization' => 'Ghana Investment Promotion Authority',
                'phone' => '+233 00 000 0000',
                'is_primary' => true,
            ]);
        }

        $districtIds = District::query()->orderBy('id')->pluck('id')->all();
        $sectorCodes = $sectors->keys()->values()->all();
        $typeCodes = $types->keys()->values()->all();
        $structureCodes = $structures->keys()->values()->all();
        $subSectorsBySector = $subSectors->groupBy(fn (SubSector $subSector) => $sectors->firstWhere('id', $subSector->sector_id)?->code);
        $workflowStatuses = [
            Opportunity::WORKFLOW_DRAFT,
            Opportunity::WORKFLOW_PENDING_APPROVAL,
            Opportunity::WORKFLOW_APPROVED,
            Opportunity::WORKFLOW_ACTIVE,
            Opportunity::WORKFLOW_COMPLETED,
            Opportunity::WORKFLOW_CANCELLED,
        ];
        $projectThemes = ['processing centre', 'distribution network', 'industrial park', 'service hub', 'production facility'];

        DB::transaction(function () use ($districtIds, $sectorCodes, $typeCodes, $structureCodes, $subSectorsBySector, $sectors, $types, $structures, $workflowStatuses, $projectThemes): void {
            for ($index = 1; $index <= 150; $index++) {
                $sectorCode = $sectorCodes[($index - 1) % count($sectorCodes)];
                $typeCode = $typeCodes[($index - 1) % count($typeCodes)];
                $structureCode = $structureCodes[($index - 1) % count($structureCodes)];
                $status = $workflowStatuses[($index - 1) % count($workflowStatuses)];
                $sectorSubSectors = $subSectorsBySector->get($sectorCode, collect())->values();
                $title = sprintf('Ghana investment sample %03d: %s', $index, $projectThemes[($index - 1) % count($projectThemes)]);
                $isSubmitted = $status !== Opportunity::WORKFLOW_DRAFT;
                $isApproved = in_array($status, [Opportunity::WORKFLOW_APPROVED, Opportunity::WORKFLOW_ACTIVE, Opportunity::WORKFLOW_COMPLETED, Opportunity::WORKFLOW_CANCELLED], true);

                $opportunity = Opportunity::updateOrCreate(['title' => $title], [
                    'district_id' => $districtIds[($index - 1) % count($districtIds)],
                    'sector_id' => $sectors[$sectorCode]->id,
                    'sub_sector_id' => $sectorSubSectors->isNotEmpty() ? $sectorSubSectors[($index - 1) % $sectorSubSectors->count()]->id : null,
                    'enterprise_type_id' => $types[$typeCode]->id,
                    'overview' => 'A deterministic demonstration opportunity for testing staff workflows, public discovery, filtering and reporting at realistic data volumes.',
                    'objectives' => 'Expand productive capacity, create skilled employment and strengthen regional value chains.',
                    'rationale' => 'The project responds to documented market demand and district-level productive potential.',
                    'success_factors' => 'Reliable infrastructure, qualified operators, market access and effective project governance.',
                    'competitive_advantages' => 'Ghana market access, regional resources, workforce availability and investment facilitation support.',
                    'workflow_status' => $status,
                    'submitted_at' => $isSubmitted ? now()->subDays(($index % 45) + 5) : null,
                    'approved_at' => $isApproved ? now()->subDays(($index % 30) + 2) : null,
                    'published_at' => $isApproved ? now()->subDays(($index % 25) + 1) : null,
                    'sla_due_at' => $status === Opportunity::WORKFLOW_PENDING_APPROVAL ? now()->addDays(($index % 7) - 3) : null,
                ]);

                $opportunity->financial()->updateOrCreate([], [
                    'investment_structure_id' => $structures[$structureCode]->id,
                    'amount' => 750000 + ($index * 125000),
                    'currency' => $index % 4 === 0 ? 'USD' : 'GHS',
                    'roi_percentage' => 8 + ($index % 17),
                    'payback_period_months' => 24 + (($index % 9) * 6),
                    'projected_revenue' => 1200000 + ($index * 180000),
                ]);

                $opportunity->contacts()->updateOrCreate(['email' => 'investmentdesk@example.gov.gh'], [
                    'name' => 'GIPA Investment Desk',
                    'organization' => 'Ghana Investment Promotion Authority',
                    'phone' => '+233 00 000 0000',
                    'is_primary' => true,
                ]);

                unset($opportunity);
            }
        });
    }
}
