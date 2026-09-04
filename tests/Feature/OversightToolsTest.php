<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\AuditPermissions;
use Database\Seeders\WorkflowPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OversightToolsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(WorkflowPermissionSeeder::class);
    }

    private function auditor(): User
    {
        $user = User::factory()->create(['account_type' => User::ACCOUNT_STAFF]);
        $user->givePermissionTo([AuditPermissions::LOGS_VIEW, AuditPermissions::SLA_VIEW]);

        return $user;
    }

    public function test_staff_without_permission_cannot_view_audit_log(): void
    {
        $staff = User::factory()->create(['account_type' => User::ACCOUNT_STAFF]);

        $this->actingAs($staff)->get(route('staff.audit-logs.index'))->assertForbidden();
    }

    public function test_staff_without_permission_cannot_view_sla_monitor(): void
    {
        $staff = User::factory()->create(['account_type' => User::ACCOUNT_STAFF]);

        $this->actingAs($staff)->get(route('staff.sla-monitor.index'))->assertForbidden();
    }

    public function test_authorised_staff_can_view_and_filter_the_audit_log(): void
    {
        $auditor = $this->auditor();

        $this->actingAs($auditor);
        activity('workflow')->event('publish')
            ->withProperties(['before' => ['status' => 'under_review'], 'after' => ['status' => 'published']])
            ->log('District published');

        $this->get(route('staff.audit-logs.index'))
            ->assertOk()
            ->assertSee('Audit log')
            ->assertSee('District published');

        // A non-matching event filter hides the record.
        $this->get(route('staff.audit-logs.index', ['event' => 'reject']))
            ->assertOk()
            ->assertDontSee('District published');

        // The matching filter surfaces it again.
        $this->get(route('staff.audit-logs.index', ['event' => 'publish']))
            ->assertOk()
            ->assertSee('District published');
    }

    public function test_authorised_staff_can_view_the_sla_monitor(): void
    {
        $this->actingAs($this->auditor())
            ->get(route('staff.sla-monitor.index'))
            ->assertOk()
            ->assertSee('SLA monitoring')
            ->assertSee('Opportunity approvals');
    }

    public function test_export_requires_the_export_permission(): void
    {
        // The base auditor has view access but not export access.
        $this->actingAs($this->auditor())
            ->get(route('staff.audit-logs.export', ['format' => 'csv']))
            ->assertForbidden();
    }

    public function test_authorised_staff_can_export_the_audit_log_as_csv(): void
    {
        $exporter = $this->auditor();
        $exporter->givePermissionTo(AuditPermissions::LOGS_EXPORT);

        $this->actingAs($exporter);
        activity('workflow')->event('publish')->log('District published');

        $response = $this->get(route('staff.audit-logs.export', ['format' => 'csv']));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('District published', $response->streamedContent());
    }

    public function test_export_rejects_unknown_formats(): void
    {
        $exporter = $this->auditor();
        $exporter->givePermissionTo(AuditPermissions::LOGS_EXPORT);

        $this->actingAs($exporter)
            ->get(route('staff.audit-logs.export', ['format' => 'xml']))
            ->assertNotFound();
    }

    public function test_csv_export_neutralises_spreadsheet_formula_injection(): void
    {
        $exporter = $this->auditor();
        $exporter->givePermissionTo(AuditPermissions::LOGS_EXPORT);

        $this->actingAs($exporter);
        activity('workflow')->event('publish')->log('=HYPERLINK("http://evil.example","click")');

        $content = $this->get(route('staff.audit-logs.export', ['format' => 'csv']))->streamedContent();

        // The dangerous leading '=' must be prefixed so spreadsheets treat it as text.
        $this->assertStringContainsString("'=HYPERLINK", $content);
    }
}
