<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Audit log export</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { color: #1f2a26; font-size: 10px; }
        h1 { font-size: 16px; margin: 0 0 2px; }
        .meta { color: #5d6b66; font-size: 9px; margin: 0 0 12px; }
        .meta strong { color: #1f2a26; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d5ddd9; padding: 4px 6px; text-align: left; vertical-align: top; }
        th { background: #0a5c3b; color: #fff; font-size: 9px; text-transform: uppercase; letter-spacing: .04em; }
        tr:nth-child(even) td { background: #f4f8f6; }
        .props { color: #5d6b66; font-size: 8px; word-break: break-word; }
    </style>
</head>
<body>
    <h1>Audit log export</h1>
    <p class="meta">
        Generated {{ $generatedAt->format('j M Y H:i') }} ·
        {{ $activities->count() }} record(s)
        @if(collect($filters)->filter()->isNotEmpty())
            · Filters:
            @foreach(collect($filters)->filter() as $key => $value)
                <strong>{{ str($key)->title() }}</strong>={{ $value }}@if(! $loop->last), @endif
            @endforeach
        @endif
    </p>

    <table>
        <thead>
            <tr><th>Timestamp</th><th>Actor</th><th>Log</th><th>Event</th><th>Description</th><th>Subject</th></tr>
        </thead>
        <tbody>
            @forelse($activities as $activity)
                <tr>
                    <td>{{ $activity->created_at?->format('Y-m-d H:i:s') }}</td>
                    <td>{{ $activity->causer?->name ?? 'System' }}</td>
                    <td>{{ $activity->log_name }}</td>
                    <td>{{ $activity->event ? str($activity->event)->replace('_', ' ')->title() : '—' }}</td>
                    <td>
                        {{ $activity->description }}
                        @if($activity->properties->isNotEmpty())
                            <div class="props">{{ $activity->properties->toJson(JSON_UNESCAPED_SLASHES) }}</div>
                        @endif
                    </td>
                    <td>@if($activity->subject_type){{ class_basename($activity->subject_type) }} #{{ $activity->subject_id }}@else — @endif</td>
                </tr>
            @empty
                <tr><td colspan="6">No activity matched these filters.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
