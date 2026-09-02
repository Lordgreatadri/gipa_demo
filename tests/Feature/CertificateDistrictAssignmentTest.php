<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\Region;
use App\Models\StaffDistrictAssignment;
use App\Models\User;
use Database\Seeders\WorkflowPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class CertificateDistrictAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_hold_multiple_district_assignments_with_bounded_active_scope(): void
    {
        Carbon::setTestNow('2026-09-02 10:00:00');
        $assigner = User::factory()->create(['account_type' => User::ACCOUNT_STAFF]);
        $officer = User::factory()->create(['account_type' => User::ACCOUNT_STAFF]);
        $region = Region::create(['code' => 'GAR', 'name' => 'Greater Accra']);
        $activeDistrict = District::create([
            'region_id' => $region->id,
            'code' => 'AMA',
            'name' => 'Accra Metropolitan',
        ]);
        $expiredDistrict = District::create([
            'region_id' => $region->id,
            'code' => 'LEKMA',
            'name' => 'Ledzokuku Municipal',
        ]);

        $active = $officer->districtAssignments()->create([
            'district_id' => $activeDistrict->id,
            'assigned_by' => $assigner->id,
            'starts_at' => now()->subDay(),
            'is_primary' => true,
        ]);
        $officer->districtAssignments()->create([
            'district_id' => $expiredDistrict->id,
            'assigned_by' => $assigner->id,
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->subDay(),
        ]);

        $activeAssignments = StaffDistrictAssignment::query()
            ->active()
            ->where('user_id', $officer->id)
            ->get();

        $this->assertCount(2, $officer->districtAssignments);
        $this->assertCount(1, $activeAssignments);
        $this->assertTrue($activeAssignments->sole()->is($active));
        $this->assertTrue($active->district->is($activeDistrict));
        $this->assertTrue($active->assigner->is($assigner));
        $this->assertTrue($activeDistrict->staffAssignments->contains($active));
        $this->assertTrue($active->is_primary);
        $this->assertNotNull($active->uuid);
        $this->assertSame('uuid', $active->getRouteKeyName());
    }

    public function test_only_super_administrator_can_create_and_end_an_audited_assignment(): void
    {
        $this->seed(WorkflowPermissionSeeder::class);
        $administrator = User::factory()->create(['account_type' => User::ACCOUNT_STAFF]);
        $administrator->assignRole('Super Administrator');
        $officer = User::factory()->create(['account_type' => User::ACCOUNT_STAFF]);
        $officer->assignRole('District Officer');
        $region = Region::create(['code' => 'ASH', 'name' => 'Ashanti']);
        $district = District::create(['region_id' => $region->id, 'code' => 'KMA', 'name' => 'Kumasi Metropolitan']);
        $payload = [
            'officer' => $officer->uuid,
            'district' => $district->uuid,
            'starts_at' => now()->subMinute()->format('Y-m-d H:i:s'),
            'is_primary' => '1',
        ];

        $this->actingAs($officer)->post(route('staff.certificate-assignments.store'), $payload)->assertForbidden();
        $this->actingAs($administrator)->post(route('staff.certificate-assignments.store'), $payload)->assertRedirect();

        $assignment = StaffDistrictAssignment::query()->sole();
        $this->assertTrue($assignment->is_primary);
        $this->assertSame(1, Activity::query()->where('log_name', 'access')->count());

        $this->actingAs($administrator)->patch(route('staff.certificate-assignments.end', $assignment))->assertRedirect();
        $this->assertNotNull($assignment->fresh()->ends_at);
        $this->assertSame(2, Activity::query()->where('log_name', 'access')->count());
    }
}
