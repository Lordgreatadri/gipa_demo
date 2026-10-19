<?php

namespace App\Jobs;

use App\Models\BackupRun;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Runs spatie/laravel-backup out of process so an admin request never blocks on
 * a database dump.
 */
class RunDatabaseBackup implements ShouldQueue
{
    use Queueable;

    public int $timeout = 1800;

    public int $tries = 1;

    public function __construct(public readonly int $backupRunId) {}

    public function handle(): void
    {
        $run = BackupRun::find($this->backupRunId);

        if (! $run) {
            return;
        }

        $run->forceFill(['status' => BackupRun::STATUS_RUNNING, 'started_at' => now()])->save();

        $options = ['--disable-notifications' => true];

        if ($run->type === BackupRun::TYPE_DATABASE) {
            $options['--only-db'] = true;
        } elseif ($run->type === BackupRun::TYPE_FILES) {
            $options['--only-files'] = true;
        }

        try {
            $exitCode = Artisan::call('backup:run', $options);
            $output = Artisan::output();

            if ($exitCode !== 0) {
                throw new \RuntimeException('backup:run exited with code '.$exitCode);
            }

            $latest = $this->latestArchive();

            $run->forceFill([
                'status' => BackupRun::STATUS_COMPLETED,
                'disk' => $run->disk,
                'destination_path' => $latest['path'] ?? null,
                'size_bytes' => $latest['size'] ?? null,
                'output' => Str::limit($output, 8000),
                'finished_at' => now(),
            ])->save();

            Log::channel('backups')->info('Backup completed', [
                'backup_run' => $run->uuid,
                'path' => $latest['path'] ?? null,
            ]);
        } catch (\Throwable $exception) {
            $run->forceFill([
                'status' => BackupRun::STATUS_FAILED,
                'failure_reason' => Str::limit($exception->getMessage(), 2000),
                'output' => Str::limit(Artisan::output(), 8000),
                'finished_at' => now(),
            ])->save();

            Log::channel('backups')->error('Backup failed', [
                'backup_run' => $run->uuid,
                'exception' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * @return array{path: string, size: int}|array{}
     */
    private function latestArchive(): array
    {
        $disk = Storage::disk(config('backup.backup.destination.disks.0', 'backups'));
        $latest = null;

        foreach ($disk->allFiles() as $file) {
            if (! str_ends_with($file, '.zip')) {
                continue;
            }

            $timestamp = $disk->lastModified($file);

            if ($latest === null || $timestamp > $latest['timestamp']) {
                $latest = ['path' => $file, 'size' => $disk->size($file), 'timestamp' => $timestamp];
            }
        }

        return $latest ? ['path' => $latest['path'], 'size' => $latest['size']] : [];
    }
}
