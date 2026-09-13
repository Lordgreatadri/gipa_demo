<x-admin-layout title="Contact enquiries">
    <div class="admin-page-heading">
        <div>
            <p class="admin-kicker">Public support</p>
            <h1>Contact enquiries</h1>
            <p>Messages submitted through the public Contact us page. Senders receive an automatic email and SMS acknowledgement on submission.</p>
        </div>
    </div>

    <section class="metric-widget-grid" aria-label="Enquiry overview">
        <x-metric-widget label="New" :value="number_format($stats['new'])" note="Not yet opened" icon="inbox" tone="gold" />
        <x-metric-widget label="Outstanding" :value="number_format($stats['outstanding'])" note="Awaiting a response" icon="clock" tone="green" />
        <x-metric-widget label="Responded" :value="number_format($stats['responded'])" note="Team has replied" icon="check-circle" tone="blue" />
        <x-metric-widget label="Total" :value="number_format($stats['total'])" note="All enquiries received" icon="messages-square" tone="green" />
    </section>

    <form class="admin-filterbar" method="get">
        <label><span>Status</span><select name="status" data-auto-submit><option value="">All statuses</option>@foreach($statuses as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></label>
        <label><span>Search</span><input name="search" value="{{ request('search') }}" maxlength="120" placeholder="Reference, name, email or subject"></label>
        <button class="button button--outline" type="submit">Filter</button>
        <a href="{{ route('staff.contact-messages.index') }}">Reset</a>
    </form>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead><tr><th>Reference</th><th>From</th><th>Subject</th><th>Assigned</th><th>Status</th><th>Received</th><th>Action</th></tr></thead>
            <tbody>
                @forelse($messages as $message)
                    <tr>
                        <td><strong>{{ $message->reference }}</strong>@if($message->phone_number)<small>{{ $message->phone_number }}</small>@endif</td>
                        <td>{{ $message->name }}<small>{{ $message->email }}</small></td>
                        <td>{{ \Illuminate\Support\Str::limit($message->subject, 48) }}<small>{{ \Illuminate\Support\Str::limit($message->message, 60) }}</small></td>
                        <td>{{ $message->assignee?->name ?? 'Unassigned' }}</td>
                        <td><span class="admin-status admin-status--{{ $message->status }}">{{ $message->statusLabel() }}</span></td>
                        <td>{{ $message->created_at->format('j M Y H:i') }}<small>{{ $message->created_at->diffForHumans() }}</small></td>
                        <td><a href="{{ route('staff.contact-messages.show', $message) }}">Open</a></td>
                    </tr>
                @empty
                    <tr><td class="table-empty" colspan="7">No enquiries match this filter.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="admin-pagination">{{ $messages->links() }}</div>
</x-admin-layout>
