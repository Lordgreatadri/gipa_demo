<x-admin-layout title="SMS delivery log">
    <nav class="admin-breadcrumb" aria-label="Breadcrumb"><a href="{{ route('staff.sms.index') }}">SMS configuration</a><span>/</span><span>Delivery log</span></nav>
    <div class="admin-page-heading">
        <div>
            <p class="admin-kicker">Platform messaging</p>
            <h1>SMS delivery log</h1>
            <p>Every outbound message with its provider, billable segments and delivery outcome. Destination numbers are partially masked.</p>
        </div>
    </div>

    <section class="metric-widget-grid" aria-label="Delivery overview">
        <x-metric-widget label="Messages (30 days)" :value="number_format($stats['total'])" note="All outbound traffic" icon="message-square" tone="green" />
        <x-metric-widget label="Delivered" :value="number_format($stats['delivered'])" :note="$stats['delivery_rate'].'% delivery rate'" icon="check-circle" tone="blue" />
        <x-metric-widget label="Failed" :value="number_format($stats['failed'])" note="Rejected or undelivered" icon="alert-triangle" tone="gold" />
        <x-metric-widget label="In flight" :value="number_format($stats['pending'])" note="Queued or awaiting report" icon="clock" tone="green" />
    </section>

    <form class="admin-filterbar" method="get">
        <label><span>Status</span><select name="status" data-auto-submit><option value="">All statuses</option>@foreach($statuses as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></label>
        <label><span>Provider</span><select name="provider" data-auto-submit><option value="">All providers</option>@foreach($providers as $value)<option value="{{ $value }}" @selected(request('provider') === $value)>{{ $value }}</option>@endforeach</select></label>
        <label><span>Search</span><input name="search" value="{{ request('search') }}" maxlength="120" placeholder="Reference or number"></label>
        <button class="button button--outline" type="submit">Filter</button>
        <a href="{{ route('staff.sms.messages') }}">Reset</a>
    </form>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead><tr><th>Reference</th><th>Recipient</th><th>Provider</th><th>Context</th><th>Segments</th><th>Status</th><th>Sent</th><th>Action</th></tr></thead>
            <tbody>
                @forelse($messages as $message)
                    <tr>
                        <td><strong>{{ $message->reference }}</strong><small>{{ \Illuminate\Support\Str::limit($message->body, 48) }}</small></td>
                        <td>{{ $message->maskedRecipient() }}</td>
                        <td>{{ $message->provider }}</td>
                        <td>{{ str($message->context)->replace('_', ' ')->title() }}</td>
                        <td>{{ $message->segments }}</td>
                        <td><span class="admin-status admin-status--{{ $message->status }}">{{ $message->statusLabel() }}</span>@if($message->failure_reason)<small>{{ \Illuminate\Support\Str::limit($message->failure_reason, 40) }}</small>@endif</td>
                        <td>{{ $message->sent_at?->format('j M Y H:i') ?? '—' }}<small>{{ $message->created_at->diffForHumans() }}</small></td>
                        <td><a href="{{ route('staff.sms.messages.show', $message) }}">Open</a></td>
                    </tr>
                @empty
                    <tr><td class="table-empty" colspan="8">No messages match this filter.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="admin-pagination">{{ $messages->links() }}</div>
</x-admin-layout>
