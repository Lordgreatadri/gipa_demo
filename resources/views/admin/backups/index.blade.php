<x-admin-layout title="Backups">
    <div class="admin-page-heading">
        <div>
            <p class="admin-kicker">Business continuity</p>
            <h1>Backups</h1>
            <p>Database and application archives produced by the scheduled backup job. Archives are stored on the private <code>{{ $disk }}</code> disk and never exposed publicly.</p>
        </div>
        @if($canManage)
            <div class="admin-page-heading__actions">
                <form method="post" action="{{ route('staff.backups.cleanup') }}">@csrf<button class="button button--outline" type="submit">Apply retention policy</button></form>
            </div>
        @endif
    </div>

    <section class="metric-widget-grid" aria-label="Backup overview">
        <x-metric-widget label="Archives stored" :value="number_format(count($archives))" note="On the backup disk" icon="archive" tone="green" />
        <x-metric-widget label="Storage used" :value="number_format($totalBytes / 1048576, 1).' MB'" note="Total archive size" icon="hard-drive" tone="blue" />
        <x-metric-widget label="Latest archive" :value="count($archives) ? $archives[0]['created_at']->diffForHumans() : 'None'" note="Most recent successful run" icon="clock" tone="gold" />
        <x-metric-widget label="Retention" :value="($schedule['keep_all_backups_for_days'] ?? 7).' days'" note="Then thinned to daily/weekly/monthly" icon="calendar" tone="green" />
    </section>

    @if(count($archives) === 0)
        <div class="admin-alert admin-alert--error" role="status">
            <strong>No backups have been stored yet.</strong>
            <span>Run one now, and make sure the scheduler (<code>php artisan schedule:work</code>) and a queue worker are running in production.</span>
        </div>
    @endif

    @if($canManage)
        <section class="reference-section">
            <div class="reference-section__heading"><div><h2>Run a backup</h2><p>The job runs on the queue so this page does not block while the dump is taken.</p></div></div>
            <form class="record-form" method="post" action="{{ route('staff.backups.store') }}">
                @csrf
                <div class="record-form__grid">
                    <label class="field"><span>Scope</span><select name="type" required>@foreach($types as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>
                </div>
                <div class="record-form__actions"><button class="button button--gold" type="submit">Start backup</button></div>
            </form>
        </section>
    @endif

    <section class="reference-section">
        <div class="reference-section__heading">
            <div><h2>Stored archives</h2><p>Download links are permission gated and stream from private storage.</p></div>
            <strong>{{ count($archives) }}</strong>
        </div>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>Archive</th><th>Size</th><th>Created</th><th>Action</th></tr></thead>
                <tbody>
                    @forelse($archives as $archive)
                        @php($token = rtrim(strtr(base64_encode($archive['path']), '+/', '-_'), '='))
                        <tr>
                            <td><strong>{{ $archive['name'] }}</strong><small>{{ $archive['path'] }}</small></td>
                            <td>{{ number_format($archive['size'] / 1048576, 1) }} MB</td>
                            <td>{{ $archive['created_at']->format('j M Y H:i') }}<small>{{ $archive['created_at']->diffForHumans() }}</small></td>
                            <td>
                                @if($canDownload)<a href="{{ route('staff.backups.download', $token) }}">Download</a>@endif
                                @if($canManage)
                                    <form method="post" action="{{ route('staff.backups.destroy', $token) }}" data-confirm="Permanently delete this backup archive?">
                                        @csrf @method('DELETE')
                                        <button class="button button--danger" type="submit">Delete</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td class="table-empty" colspan="4">No archives are stored on the backup disk.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="reference-section">
        <div class="reference-section__heading"><div><h2>Recent runs</h2><p>Backups triggered from this dashboard.</p></div></div>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>Scope</th><th>Status</th><th>Triggered by</th><th>Started</th><th>Result</th></tr></thead>
                <tbody>
                    @forelse($runs as $run)
                        <tr>
                            <td>{{ $run->typeLabel() }}</td>
                            <td><span class="admin-status admin-status--{{ $run->status }}">{{ str($run->status)->title() }}</span></td>
                            <td>{{ $run->trigger?->name ?? 'Scheduler' }}</td>
                            <td>{{ $run->started_at?->format('j M Y H:i') ?? $run->created_at->format('j M Y H:i') }}</td>
                            <td>{{ $run->failure_reason ? \Illuminate\Support\Str::limit($run->failure_reason, 80) : ($run->destination_path ?: '—') }}</td>
                        </tr>
                    @empty
                        <tr><td class="table-empty" colspan="5">No backups have been triggered from this dashboard yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-admin-layout>
