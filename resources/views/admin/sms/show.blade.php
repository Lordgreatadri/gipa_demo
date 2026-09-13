<x-admin-layout :title="'SMS '.$message->reference">
    <nav class="admin-breadcrumb" aria-label="Breadcrumb"><a href="{{ route('staff.sms.index') }}">SMS configuration</a><span>/</span><a href="{{ route('staff.sms.messages') }}">Delivery log</a><span>/</span><span>{{ $message->reference }}</span></nav>
    <div class="admin-page-heading">
        <div>
            <p class="admin-kicker">Message record</p>
            <h1>{{ $message->reference }}</h1>
            <p>Provider correspondence and the delivery reports received for this message.</p>
        </div>
        <span class="admin-status admin-status--{{ $message->status }}">{{ $message->statusLabel() }}</span>
    </div>

    <section class="reference-section">
        <div class="reference-section__heading"><div><h2>Summary</h2></div></div>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <tbody>
                    <tr><th>Recipient</th><td>{{ $message->maskedRecipient() }}</td></tr>
                    <tr><th>Provider</th><td>{{ $message->provider }}@if($message->provider_message_id) · gateway id {{ $message->provider_message_id }}@endif</td></tr>
                    <tr><th>Sender ID</th><td>{{ $message->sender_id ?? '—' }}</td></tr>
                    <tr><th>Context</th><td>{{ str($message->context)->replace('_', ' ')->title() }}</td></tr>
                    <tr><th>Billable segments</th><td>{{ $message->segments }}</td></tr>
                    <tr><th>Queued</th><td>{{ $message->queued_at?->format('j M Y H:i') ?? '—' }}</td></tr>
                    <tr><th>Sent</th><td>{{ $message->sent_at?->format('j M Y H:i') ?? '—' }}</td></tr>
                    <tr><th>Delivered</th><td>{{ $message->delivered_at?->format('j M Y H:i') ?? '—' }}</td></tr>
                    @if($message->failure_reason)<tr><th>Failure</th><td>{{ $message->failure_reason }}</td></tr>@endif
                </tbody>
            </table>
        </div>
    </section>

    <section class="reference-section">
        <div class="reference-section__heading"><div><h2>Message body</h2></div></div>
        <p class="assistant-doc-preview">{{ $message->body }}</p>
    </section>

    <section class="reference-section">
        <div class="reference-section__heading">
            <div><h2>Delivery reports</h2><p>Signed callbacks received from the provider.</p></div>
            <strong>{{ $message->deliveryEvents->count() }}</strong>
        </div>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>Received</th><th>Status</th><th>Provider status</th><th>Detail</th></tr></thead>
                <tbody>
                    @forelse($message->deliveryEvents->sortByDesc('occurred_at') as $event)
                        <tr>
                            <td>{{ $event->occurred_at?->format('j M Y H:i') ?? $event->created_at->format('j M Y H:i') }}</td>
                            <td><span class="admin-status admin-status--{{ $event->status }}">{{ str($event->status)->title() }}</span></td>
                            <td>{{ $event->raw_status ?? '—' }}</td>
                            <td>{{ $event->detail ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td class="table-empty" colspan="4">No delivery reports have been received yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-admin-layout>
