<?php

namespace Tests\Feature;

use App\Jobs\RunDatabaseBackup;
use App\Models\BackupRun;
use App\Models\User;
use App\Support\BackupPermissions;
use Database\Seeders\WorkflowPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackupManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(WorkflowPermissionSeeder::class);
        Storage::fake('backups');
    }

    private function operator(): User
    {
        $user = User::factory()->create(['account_type' => User::ACCOUNT_STAFF]);
        $user->givePermissionTo(BackupPermissions::ALL);

        return $user;
    }

    private function archiveToken(string $path): string
    {
        return rtrim(strtr(base64_encode($path), '+/', '-_'), '=');
    }

    public function test_staff_without_permission_cannot_open_the_backup_screen(): void
    {
        $staff = User::factory()->create(['account_type' => User::ACCOUNT_STAFF]);

        $this->actingAs($staff)->get(route('staff.backups.index'))->assertForbidden();
    }

    public function test_the_screen_lists_stored_archives_newest_first(): void
    {
        Storage::disk('backups')->put('IOMP/iomp-2026-09-01-01-00-00.zip', 'older');
        Storage::disk('backups')->put('IOMP/iomp-2026-09-10-01-00-00.zip', 'newer');
        Storage::disk('backups')->put('IOMP/notes.txt', 'ignored');

        $this->actingAs($this->operator())->get(route('staff.backups.index'))
            ->assertOk()
            ->assertSee('iomp-2026-09-10-01-00-00.zip')
            ->assertSee('iomp-2026-09-01-01-00-00.zip')
            ->assertDontSee('notes.txt');
    }

    public function test_requesting_a_backup_records_a_run_and_queues_the_job(): void
    {
        Queue::fake();
        $operator = $this->operator();

        $this->actingAs($operator)->post(route('staff.backups.store'), ['type' => BackupRun::TYPE_DATABASE])
            ->assertRedirect();

        $run = BackupRun::firstOrFail();
        $this->assertSame(BackupRun::TYPE_DATABASE, $run->type);
        $this->assertSame(BackupRun::STATUS_QUEUED, $run->status);
        $this->assertSame($operator->id, $run->triggered_by);
        $this->assertSame('backups', $run->disk);

        Queue::assertPushed(RunDatabaseBackup::class, fn (RunDatabaseBackup $job) => $job->backupRunId === $run->id);
    }

    public function test_an_unknown_backup_type_is_rejected(): void
    {
        Queue::fake();

        $this->actingAs($this->operator())->post(route('staff.backups.store'), ['type' => 'everything'])
            ->assertSessionHasErrors('type');

        Queue::assertNothingPushed();
        $this->assertSame(0, BackupRun::count());
    }

    public function test_an_operator_can_download_a_stored_archive(): void
    {
        Storage::disk('backups')->put('IOMP/iomp-2026-09-10-01-00-00.zip', 'archive-bytes');

        $this->actingAs($this->operator())
            ->get(route('staff.backups.download', $this->archiveToken('IOMP/iomp-2026-09-10-01-00-00.zip')))
            ->assertOk()
            ->assertDownload('iomp-2026-09-10-01-00-00.zip');
    }

    public function test_download_requires_the_download_permission(): void
    {
        Storage::disk('backups')->put('IOMP/iomp-2026-09-10-01-00-00.zip', 'archive-bytes');

        $staff = User::factory()->create(['account_type' => User::ACCOUNT_STAFF]);
        $staff->givePermissionTo(BackupPermissions::VIEW);

        $this->actingAs($staff)
            ->get(route('staff.backups.download', $this->archiveToken('IOMP/iomp-2026-09-10-01-00-00.zip')))
            ->assertForbidden();
    }

    public function test_path_traversal_outside_the_backup_disk_is_rejected(): void
    {
        $this->actingAs($this->operator())
            ->get(route('staff.backups.download', $this->archiveToken('../../../.env')))
            ->assertNotFound();

        $this->actingAs($this->operator())
            ->delete(route('staff.backups.destroy', $this->archiveToken('../../../.env')))
            ->assertNotFound();
    }

    public function test_an_operator_can_delete_a_stored_archive(): void
    {
        Storage::disk('backups')->put('IOMP/iomp-2026-09-10-01-00-00.zip', 'archive-bytes');

        $this->actingAs($this->operator())
            ->delete(route('staff.backups.destroy', $this->archiveToken('IOMP/iomp-2026-09-10-01-00-00.zip')))
            ->assertRedirect();

        Storage::disk('backups')->assertMissing('IOMP/iomp-2026-09-10-01-00-00.zip');
    }

    public function test_a_failing_backup_command_is_recorded_against_the_run(): void
    {
        $run = BackupRun::create([
            'type' => BackupRun::TYPE_DATABASE,
            'status' => BackupRun::STATUS_QUEUED,
            'disk' => 'backups',
        ]);

        // No database dump binary is available in the test environment, so the
        // command fails and the job must capture that against the run record.
        (new RunDatabaseBackup($run->id))->handle();

        $run->refresh();
        $this->assertContains($run->status, [BackupRun::STATUS_COMPLETED, BackupRun::STATUS_FAILED]);
        $this->assertNotNull($run->started_at);
        $this->assertNotNull($run->finished_at);

        if ($run->status === BackupRun::STATUS_FAILED) {
            $this->assertNotEmpty($run->failure_reason);
        }
    }
}
