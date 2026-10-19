<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\RunDatabaseBackup;
use App\Models\BackupRun;
use App\Support\BackupPermissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Admin management of database and file backups produced by spatie/laravel-backup.
 */
class BackupController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can(BackupPermissions::VIEW), 403);

        $disk = Storage::disk($this->disk());
        $archives = [];

        foreach ($disk->allFiles() as $path) {
            if (! str_ends_with($path, '.zip')) {
                continue;
            }

            $archives[] = [
                'path' => $path,
                'name' => basename($path),
                'size' => $disk->size($path),
                'created_at' => \Illuminate\Support\Carbon::createFromTimestamp($disk->lastModified($path)),
            ];
        }

        usort($archives, fn (array $a, array $b): int => $b['created_at'] <=> $a['created_at']);

        return view('admin.backups.index', [
            'archives' => $archives,
            'runs' => BackupRun::with('trigger')->latest('id')->limit(20)->get(),
            'disk' => $this->disk(),
            'types' => BackupRun::TYPES,
            'totalBytes' => array_sum(array_column($archives, 'size')),
            'canManage' => $request->user()->can(BackupPermissions::MANAGE),
            'canDownload' => $request->user()->can(BackupPermissions::DOWNLOAD),
            'schedule' => config('backup.cleanup.default_strategy'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can(BackupPermissions::MANAGE), 403);

        $validated = $request->validate([
            'type' => ['required', Rule::in(array_keys(BackupRun::TYPES))],
        ]);

        $run = BackupRun::create([
            'type' => $validated['type'],
            'status' => BackupRun::STATUS_QUEUED,
            'disk' => $this->disk(),
            'triggered_by' => $request->user()->id,
        ]);

        RunDatabaseBackup::dispatch($run->id);

        return back()->with('status', 'Backup queued. Refresh this page in a few minutes to see the result.');
    }

    public function download(Request $request, string $archive): StreamedResponse
    {
        abort_unless($request->user()->can(BackupPermissions::DOWNLOAD), 403);

        $path = $this->resolvePath($archive);
        $disk = Storage::disk($this->disk());

        abort_unless($disk->exists($path), 404);

        return $disk->download($path);
    }

    public function destroy(Request $request, string $archive): RedirectResponse
    {
        abort_unless($request->user()->can(BackupPermissions::MANAGE), 403);

        $path = $this->resolvePath($archive);
        $disk = Storage::disk($this->disk());

        abort_unless($disk->exists($path), 404);

        $disk->delete($path);

        return back()->with('status', 'Backup archive deleted.');
    }

    public function cleanup(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can(BackupPermissions::MANAGE), 403);

        Artisan::call('backup:clean', ['--disable-notifications' => true]);

        return back()->with('status', 'Retention policy applied to stored backups.');
    }

    private function disk(): string
    {
        return (string) config('backup.backup.destination.disks.0', 'backups');
    }

    /**
     * Resolve a user-supplied archive name to a path inside the backup disk.
     *
     * The name is base64url encoded in links; any traversal attempt is rejected
     * by matching against the archives the disk actually reports.
     */
    private function resolvePath(string $archive): string
    {
        $decoded = base64_decode(strtr($archive, '-_', '+/'), true);

        abort_if($decoded === false, 404);

        $disk = Storage::disk($this->disk());

        abort_unless(in_array($decoded, $disk->allFiles(), true), 404);

        return $decoded;
    }
}
