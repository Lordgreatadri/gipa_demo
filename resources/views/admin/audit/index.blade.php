<x-admin-layout title="Audit log">
    <div class="admin-page-heading">
        <div>
            <p class="admin-kicker">Compliance and traceability</p>
            <h1>Audit log</h1>
            <p>Immutable record of workflow decisions and system activity across the platform.</p>
        </div>
        @if($canExport)
            <div class="admin-page-heading__actions">
                <a class="button button--outline" href="{{ route('staff.audit-logs.export', array_merge(array_filter($filters), ['format' => 'csv'])) }}">Export CSV</a>
                <a class="button button--gold" href="{{ route('staff.audit-logs.export', array_merge(array_filter($filters), ['format' => 'pdf'])) }}">Export PDF</a>
            </div>
        @endif
    </div>

    <form class="admin-filterbar" method="get">
        <label><span>Search</span><input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Description contains…"></label>
        <label><span>Log</span>
            <select name="log">
                <option value="">All logs</option>
                @foreach($logNames as $log)<option value="{{ $log }}" @selected(($filters['log'] ?? null) === $log)>{{ str($log)->title() }}</option>@endforeach
            </select>
        </label>
        <label><span>Event</span>
            <select name="event">
                <option value="">All events</option>
                @foreach($events as $event)<option value="{{ $event }}" @selected(($filters['event'] ?? null) === $event)>{{ str($event)->replace('_',' ')->title() }}</option>@endforeach
            </select>
        </label>
        <label><span>Subject</span>
            <select name="subject">
                <option value="">All subjects</option>
                @foreach($subjectTypes as $subject)<option value="{{ $subject }}" @selected(($filters['subject'] ?? null) === $subject)>{{ str($subject)->afterLast('\\') }}</option>@endforeach
            </select>
        </label>
        <label><span>Actor</span>
            <select name="causer">
                <option value="">All actors</option>
                @foreach($causers as $causer)<option value="{{ $causer->id }}" @selected((string) ($filters['causer'] ?? '') === (string) $causer->id)>{{ $causer->name }}</option>@endforeach
            </select>
        </label>
        <label><span>From</span><input type="date" name="from" value="{{ $filters['from'] ?? '' }}"></label>
        <label><span>To</span><input type="date" name="to" value="{{ $filters['to'] ?? '' }}"></label>
        <button class="button button--gold" type="submit">Filter</button>
        <a href="{{ route('staff.audit-logs.index') }}">Reset</a>
    </form>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead><tr><th>When</th><th>Actor</th><th>Event</th><th>Description</th><th>Subject</th><th>Log</th></tr></thead>
            <tbody>
                @forelse($activities as $activity)
                    <tr>
                        <td><strong>{{ $activity->created_at->format('j M Y') }}</strong><small>{{ $activity->created_at->format('H:i') }}</small></td>
                        <td>{{ $activity->causer?->name ?? 'System' }}</td>
                        <td>@if($activity->event)<span class="admin-status admin-status--under_review">{{ str($activity->event)->replace('_',' ')->title() }}</span>@else — @endif</td>
                        <td>
                            {{ $activity->description }}
                            @if($activity->properties->isNotEmpty())
                                <details class="audit-properties">
                                    <summary>Details</summary>
                                    <dl>
                                        @foreach($activity->properties as $key => $value)
                                            <div><dt>{{ str($key)->replace('_',' ')->title() }}</dt><dd>{{ is_scalar($value) ? $value : json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</dd></div>
                                        @endforeach
                                    </dl>
                                </details>
                            @endif
                        </td>
                        <td>@if($activity->subject_type){{ str($activity->subject_type)->afterLast('\\') }}<small>#{{ $activity->subject_id }}</small>@else — @endif</td>
                        <td>{{ str($activity->log_name)->title() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="table-empty">No activity matches these filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="admin-pagination">{{ $activities->links() }}</div>
</x-admin-layout>
