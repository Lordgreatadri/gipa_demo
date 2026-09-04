<x-admin-layout title="SLA monitoring">
    <div class="admin-page-heading">
        <div>
            <p class="admin-kicker">Service level oversight</p>
            <h1>SLA monitoring</h1>
            <p>Track approval, publication and onboarding deadlines with live breach and at-risk alerts.</p>
        </div>
        <a class="button button--gold" href="{{ route('staff.dashboard') }}">Back to dashboard</a>
    </div>

    <section class="metric-widget-grid" aria-label="SLA indicators">
        <x-metric-widget label="Breached" :value="number_format($summary['breached'])" note="Past the service deadline" icon="triangle-alert" tone="red" />
        <x-metric-widget label="At risk" :value="number_format($summary['at_risk'])" note="Due within 24 hours" icon="clock-alert" tone="gold" />
        <x-metric-widget label="In review" :value="number_format($summary['in_review'])" note="Active items across queues" icon="calendar-clock" tone="blue" />
        <x-metric-widget label="On track" :value="number_format($summary['on_track'])" note="Within the service window" icon="gauge" tone="green" />
    </section>

    @foreach($domains as $domain)
        <section class="admin-section">
            <div class="admin-section__heading">
                <div>
                    <h2>{{ $domain['label'] }}</h2>
                    <p>{{ number_format($domain['metrics']['in_review']) }} in review · {{ number_format($domain['metrics']['breached']) }} breached · {{ number_format($domain['metrics']['at_risk']) }} at risk</p>
                </div>
                <span @class(['admin-status', 'admin-status--cancelled' => $domain['metrics']['breached'] > 0, 'admin-status--approved' => $domain['metrics']['breached'] === 0])>
                    {{ $domain['metrics']['breached'] > 0 ? 'Action needed' : 'Healthy' }}
                </span>
            </div>

            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead><tr><th>Record</th><th>Context</th><th>Owner</th><th>Deadline</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse($domain['breaches'] as $item)
                            <tr>
                                <td><strong><a href="{{ $item['url'] }}">{{ $item['title'] }}</a></strong></td>
                                <td>{{ $item['subtitle'] ?? '—' }}</td>
                                <td>{{ $item['owner'] }}</td>
                                <td class="is-overdue">Overdue by {{ $item['sla_due_at']->diffForHumans(now(), true) }}</td>
                                <td><span class="admin-status admin-status--cancelled">Breached</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="table-empty">No breached {{ strtolower($domain['label']) }} items.</td></tr>
                        @endforelse
                        @foreach($domain['at_risk'] as $item)
                            <tr>
                                <td><strong><a href="{{ $item['url'] }}">{{ $item['title'] }}</a></strong></td>
                                <td>{{ $item['subtitle'] ?? '—' }}</td>
                                <td>{{ $item['owner'] }}</td>
                                <td>Due {{ $item['sla_due_at']->diffForHumans() }}</td>
                                <td><span class="admin-status admin-status--pending_approval">At risk</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endforeach
</x-admin-layout>
