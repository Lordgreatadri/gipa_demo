<x-admin-layout :title="'Enquiry '.$enquiry->reference">
    <nav class="admin-breadcrumb" aria-label="Breadcrumb"><a href="{{ route('staff.contact-messages.index') }}">Contact enquiries</a><span>/</span><span>{{ $enquiry->reference }}</span></nav>
    <div class="admin-page-heading">
        <div>
            <p class="admin-kicker">Public support</p>
            <h1>{{ $enquiry->subject }}</h1>
            <p>Reference {{ $enquiry->reference }} · received {{ $enquiry->created_at->format('j M Y H:i') }}</p>
        </div>
        <span class="admin-status admin-status--{{ $enquiry->status }}">{{ $enquiry->statusLabel() }}</span>
    </div>

    <section class="reference-section">
        <div class="reference-section__heading"><div><h2>Sender</h2></div></div>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <tbody>
                    <tr><th>Name</th><td>{{ $enquiry->name }}</td></tr>
                    <tr><th>Email</th><td><a href="mailto:{{ $enquiry->email }}">{{ $enquiry->email }}</a></td></tr>
                    <tr><th>Phone</th><td>@if($enquiry->phone_number)<a href="tel:{{ $enquiry->phone_number }}">{{ $enquiry->phone_number }}</a>@else — @endif</td></tr>
                    <tr><th>Best time to call</th><td>{{ $enquiry->available_time_for_call ?? '—' }}</td></tr>
                    <tr><th>Acknowledgements</th><td>Email {{ $enquiry->email_sent ? 'sent '.$enquiry->email_sent_at?->diffForHumans() : 'not sent' }} · SMS {{ $enquiry->sms_sent ? 'sent '.$enquiry->sms_sent_at?->diffForHumans() : 'not sent' }}</td></tr>
                    <tr><th>Opened by</th><td>{{ $enquiry->read_by ? $enquiry->read_by.' · '.$enquiry->read_at?->diffForHumans() : 'Not yet opened' }}</td></tr>
                    <tr><th>Assigned to</th><td>{{ $enquiry->assignee?->name ?? 'Unassigned' }}</td></tr>
                    @if($enquiry->responded_at)<tr><th>Responded</th><td>{{ $enquiry->responder?->name }} · {{ $enquiry->responded_at->format('j M Y H:i') }}</td></tr>@endif
                </tbody>
            </table>
        </div>
    </section>

    <section class="reference-section">
        <div class="reference-section__heading"><div><h2>Message</h2></div></div>
        <p class="assistant-doc-preview">{{ $enquiry->message }}</p>
    </section>

    @if($enquiry->admin_note)
        <section class="reference-section">
            <div class="reference-section__heading"><div><h2>Internal note</h2></div></div>
            <p class="assistant-doc-preview">{{ $enquiry->admin_note }}</p>
        </section>
    @endif

    @if($canManage)
        <form class="record-form" method="post" action="{{ route('staff.contact-messages.update', $enquiry) }}">
            @csrf @method('PATCH')
            <section>
                <div class="record-form__heading"><h2>Handle this enquiry</h2><p>Record the outcome once the team has replied to the sender by email or phone.</p></div>
                <div class="record-form__grid">
                    <label class="field"><span>Action</span><select name="action" required><option value="respond">Mark as responded</option><option value="assign">Assign owner</option><option value="close">Close</option><option value="reopen">Reopen</option></select></label>
                    <label class="field"><span>Assign to</span><select name="assigned_to"><option value="">Unassigned</option>@foreach($assignees as $assignee)<option value="{{ $assignee->uuid }}" @selected($enquiry->assigned_to === $assignee->id)>{{ $assignee->name }}</option>@endforeach</select></label>
                    <label class="field field--wide"><span>Internal note</span><textarea name="admin_note" rows="4" maxlength="2000">{{ old('admin_note', $enquiry->admin_note) }}</textarea><small>Visible to staff only; never sent to the enquirer.</small></label>
                </div>
            </section>
            <div class="record-form__actions">
                <a class="button button--outline" href="mailto:{{ $enquiry->email }}?subject={{ rawurlencode('Re: '.$enquiry->subject.' ['.$enquiry->reference.']') }}">Reply by email</a>
                <button class="button button--gold" type="submit">Save</button>
            </div>
        </form>

        <form class="record-form__actions" method="post" action="{{ route('staff.contact-messages.destroy', $enquiry) }}" data-confirm="Archive this enquiry?">
            @csrf @method('DELETE')
            <button class="button button--danger" type="submit">Archive enquiry</button>
        </form>
    @endif
</x-admin-layout>
