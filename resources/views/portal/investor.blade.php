<x-portal-layout title="Investor dashboard" description="Manage investment matches, inquiries, your investor profile and secure KYC onboarding with GIPA.">
    <section class="portal-band" id="overview">
        <div class="shell portal-heading">
            <div><p class="admin-kicker">Investor workspace</p><h1>Welcome, {{ $profile->display_name }}</h1><p>Complete your profile and submit verified evidence for investment facilitation.</p></div>
            <div class="portal-heading__actions"><span class="admin-status admin-status--{{ $case?->status ?? 'draft' }}">{{ str($case?->status ?? 'not started')->replace('_', ' ')->title() }}</span><form action="{{ route('logout') }}" method="post">@csrf<button class="button button--outline" type="submit">Sign out</button></form></div>
        </div>
    </section>
    <div class="shell portal-shell">
        @if(session('status'))<div class="admin-alert" role="status">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="admin-alert admin-alert--error" role="alert"><strong>Action not completed.</strong><span>{{ $errors->first() }}</span></div>@endif

        <section class="investor-metrics" aria-label="Investor account overview">
            <article><span>Opportunity matches</span><strong>{{ $matches->count() }}</strong><small>{{ $matchPreference ? 'Based on your current mandate' : 'Set your mandate to begin' }}</small></article>
            <article><span>Active inquiries</span><strong>{{ $inquiryCount }}</strong><small>Expressions of interest submitted</small></article>
            <article><span>KYC evidence</span><strong>{{ $case?->documents->count() ?? 0 }}</strong><small>{{ $case?->documents->where('status', 'accepted')->count() ?? 0 }} accepted by review</small></article>
            <article><span>Onboarding</span><strong class="investor-metrics__state">{{ str($case?->status ?? 'not_started')->replace('_', ' ')->title() }}</strong><small>{{ $case?->sla_due_at ? 'Review by '.$case->sla_due_at->format('j M Y') : 'No active review deadline' }}</small></article>
        </section>

        <section class="portal-progress" aria-label="Onboarding progress">
            @foreach([['Profile', true], ['KYC evidence', (bool) $case?->documents->count()], ['Review', in_array($case?->status, ['submitted','under_review','action_required','approved','rejected'])], ['Decision', in_array($case?->status, ['approved','rejected'])]] as [$label, $complete])
                <div @class(['is-complete' => $complete])><i aria-hidden="true"></i><span>{{ $label }}</span></div>
            @endforeach
        </section>

        <section class="portal-analytics" aria-label="Investor analytics">
            <article class="admin-chart-panel" data-chart-panel>
                <div class="portal-chart-heading"><div><p class="admin-kicker">Portfolio alignment</p><h2>Matches by sector</h2></div><span>{{ $matches->count() }} ranked</span></div>
                @if($matches->isNotEmpty())
                    <div class="admin-chart-panel__canvas"><canvas data-dashboard-chart aria-label="Opportunity matches grouped by sector"></canvas></div>
                    <script type="application/json" data-chart-config>@json($matchSectorChart)</script>
                @else
                    <div class="portal-chart-empty"><strong>No match data yet</strong><span>Save an investment mandate to generate your opportunity analysis.</span></div>
                @endif
            </article>
            <article class="admin-chart-panel" data-chart-panel>
                <div class="portal-chart-heading"><div><p class="admin-kicker">Due diligence</p><h2>Evidence review</h2></div><span>{{ $case?->documents->count() ?? 0 }} files</span></div>
                @if($case?->documents->count())
                    <div class="admin-chart-panel__canvas"><canvas data-dashboard-chart aria-label="KYC evidence grouped by review status"></canvas></div>
                    <script type="application/json" data-chart-config>@json($evidenceChart)</script>
                @else
                    <div class="portal-chart-empty"><strong>No evidence uploaded</strong><span>Start KYC onboarding when you are ready to submit secure documents.</span></div>
                @endif
            </article>
        </section>

        <div class="portal-grid">
            <div class="portal-main">
                <section class="portal-section match-workspace" id="matches">
                    <div class="admin-section__heading"><div><p class="admin-kicker">Recommended pipeline</p><h2>Opportunities aligned to your mandate</h2><p>Published projects are ranked using sector, geography, investment range and district readiness.</p></div><a class="button button--outline" href="{{ route('opportunities.index') }}">Explore all</a></div>
                    <div class="match-list">
                        @forelse($matches as $match)
                            <article class="match-card">
                                <div class="match-card__score"><strong>{{ $match['score'] }}</strong><span>match</span></div>
                                <div class="match-card__body"><div class="match-card__meta"><span>{{ $match['opportunity']->sector->name }}</span><span>{{ $match['opportunity']->district->region->name }}</span></div><h3>{{ $match['opportunity']->title }}</h3><p>{{ str($match['opportunity']->overview)->limit(130) }}</p><div class="match-reasons">@foreach($match['reasons'] as $reason)<span>{{ $reason }}</span>@endforeach</div></div>
                                <div class="match-card__action"><strong>{{ $match['opportunity']->financial?->currency }} {{ number_format((float) $match['opportunity']->financial?->amount) }}</strong><a href="{{ route('opportunities.show', $match['opportunity']) }}">View opportunity</a></div>
                            </article>
                        @empty
                            <div class="match-empty"><h3>{{ $matchPreference ? 'No current projects meet every preference' : 'Define your investment mandate' }}</h3><p>{{ $matchPreference ? 'Adjust your sectors, regions or investment range to broaden the published opportunity set.' : 'Select the sectors, regions and capital range that matter to you. Recommendations will remain transparent and under your control.' }}</p></div>
                        @endforelse
                    </div>
                </section>

                <form class="record-form" id="mandate" method="post" action="{{ route('investor.match-preferences.update') }}">
                    @csrf @method('PUT')
                    <section><div class="record-form__heading"><h2>Investment mandate</h2><p>Controls eligibility and ranking for your recommendations.</p></div><div class="record-form__grid">
                        <label class="field field--wide"><span>Preferred sectors</span><select name="sector_ids[]" multiple size="5" required>@foreach($sectors as $sector)<option value="{{ $sector->id }}" @selected(in_array($sector->id, old('sector_ids', $matchPreference?->sectors->modelKeys() ?? [])))>{{ $sector->name }}</option>@endforeach</select><small>Select one or more sectors.</small></label>
                        <label class="field field--wide"><span>Preferred regions</span><select name="region_ids[]" multiple size="5" required>@foreach($regions as $region)<option value="{{ $region->id }}" @selected(in_array($region->id, old('region_ids', $matchPreference?->regions->modelKeys() ?? [])))>{{ $region->name }}</option>@endforeach</select><small>Select one or more regions.</small></label>
                        <label class="field"><span>Minimum investment</span><input type="number" name="minimum_investment" value="{{ old('minimum_investment', $matchPreference?->minimum_investment) }}" min="0" step="0.01"></label>
                        <label class="field"><span>Maximum investment</span><input type="number" name="maximum_investment" value="{{ old('maximum_investment', $matchPreference?->maximum_investment) }}" min="0" step="0.01"></label>
                        <label class="field"><span>Currency</span><select name="currency" required>@foreach(['GHS','USD','EUR','GBP'] as $currency)<option value="{{ $currency }}" @selected(old('currency', $matchPreference?->currency ?? 'GHS') === $currency)>{{ $currency }}</option>@endforeach</select></label>
                        <label class="field"><span>Minimum district readiness</span><input type="number" name="minimum_readiness_score" value="{{ old('minimum_readiness_score', $matchPreference?->minimum_readiness_score) }}" min="0" max="100" step="1" placeholder="Any score"></label>
                    </div></section>
                    <div class="record-form__actions"><button class="button button--gold" type="submit">Refresh recommendations</button></div>
                </form>

                <form class="record-form" id="profile" method="post" action="{{ route('investor.profile.update') }}">
                    @csrf @method('PATCH')
                    <section><div class="record-form__heading"><h2>Investor profile</h2><p>Core contact and representation details.</p></div><div class="record-form__grid">
                        <label class="field"><span>Profile type</span><select name="profile_type" required><option value="individual" @selected(old('profile_type', $profile->profile_type) === 'individual')>Individual investor</option><option value="organization_representative" @selected(old('profile_type', $profile->profile_type) === 'organization_representative')>Organization representative</option></select></label>
                        <label class="field"><span>Display name</span><input name="display_name" value="{{ old('display_name', $profile->display_name) }}" maxlength="255" required></label>
                        <label class="field"><span>Country of residence</span><input name="country_code" value="{{ old('country_code', $profile->country_code) }}" minlength="2" maxlength="2" required></label>
                        <label class="field"><span>Nationality</span><input name="nationality_country_code" value="{{ old('nationality_country_code', $profile->nationality_country_code) }}" minlength="2" maxlength="2" placeholder="GH"></label>
                        <input type="hidden" name="preferred_language" value="en">
                        <label class="field"><span>Preferred contact</span><select name="preferred_contact_channel"><option value="email" @selected($profile->preferred_contact_channel === 'email')>Email</option><option value="phone" @selected($profile->preferred_contact_channel === 'phone')>Phone</option></select></label>
                    </div></section>
                    <div class="record-form__actions"><button class="button button--gold" type="submit">Save profile</button></div>
                </form>

                @unless($case)
                    <section class="portal-empty" id="kyc"><p class="admin-kicker">Secure onboarding</p><h2>Start your KYC application</h2><p>Your evidence is stored privately and is available only to you and authorized GIPA reviewers.</p><form method="post" action="{{ route('investor.onboarding.start') }}">@csrf<button class="button button--gold" type="submit">Start onboarding</button></form></section>
                @else
                    <section class="portal-section" id="kyc"><div class="admin-section__heading"><div><h2>KYC evidence</h2><p>Reference {{ $case->reference }}. New files enter quarantine before review.</p></div><strong>{{ $case->documents->where('status', 'accepted')->count() }}/{{ $documentTypes->where('is_required', true)->count() }} accepted</strong></div>
                        @if(in_array($case->status, ['draft','action_required']))
                            <form class="kyc-upload" method="post" enctype="multipart/form-data" action="{{ route('investor.onboarding.documents.store', $case) }}">@csrf
                                <label class="field"><span>Evidence type</span><select name="document_type" required><option value="">Select document</option>@foreach($documentTypes as $type)<option value="{{ $type->code }}">{{ $type->name }}{{ $type->is_required ? ' *' : '' }}</option>@endforeach</select></label>
                                <label class="field"><span>Private file</span><input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" required><small>PDF, JPEG or PNG. Maximum 10 MB.</small></label>
                                <label class="field"><span>Issued date</span><input type="date" name="issued_at" max="{{ now()->toDateString() }}"></label>
                                <label class="field"><span>Expiry date</span><input type="date" name="expires_at" min="{{ now()->addDay()->toDateString() }}"></label>
                                <button class="button button--outline" type="submit">Upload securely</button>
                            </form>
                        @endif
                        <div class="evidence-list">@forelse($case->documents as $document)<article><div><strong>{{ $document->type->name }}</strong><small>Uploaded {{ $document->created_at->format('j M Y, H:i') }} · {{ strtoupper($document->getFirstMedia()?->extension ?? 'FILE') }}</small>@if($document->rejection_reason)<p>{{ $document->rejection_reason }}</p>@endif</div><span class="admin-status admin-status--{{ $document->status }}">{{ str($document->status)->title() }}</span><a href="{{ route('investor.documents.download', $document) }}">Download</a></article>@empty<p class="table-empty">No KYC evidence uploaded yet.</p>@endforelse</div>
                        @if(in_array($case->status, ['draft','action_required']))<form class="portal-submit" method="post" action="{{ route('investor.onboarding.submit', $case) }}">@csrf<p>All required evidence must be uploaded. Security and KYC acceptance are completed during staff review.</p><button class="button button--gold" type="submit">Submit for review</button></form>@endif
                    </section>
                @endunless
            </div>
            <aside class="portal-aside"><p class="admin-kicker">Application status</p><h2>{{ str($case?->status ?? 'not_started')->replace('_', ' ')->title() }}</h2><dl><div><dt>Profile</dt><dd>{{ str($profile->profile_type)->replace('_', ' ')->title() }}</dd></div><div><dt>Last updated</dt><dd>{{ $case?->updated_at?->diffForHumans() ?? $profile->updated_at->diffForHumans() }}</dd></div><div><dt>Review deadline</dt><dd>{{ $case?->sla_due_at?->format('j M Y, H:i') ?? 'Not active' }}</dd></div></dl>@if($case?->decision_reason)<div class="decision-note"><strong>Reviewer note</strong><p>{{ $case->decision_reason }}</p></div>@endif
                @if($case)<h3>Timeline</h3><ol class="workflow-timeline">@foreach($case->events as $event)<li><i aria-hidden="true"></i><div><strong>{{ str($event->action)->replace('_', ' ')->title() }}</strong><small>{{ $event->occurred_at->format('j M Y, H:i') }}</small></div></li>@endforeach</ol>@endif
            </aside>
        </div>
    </div>
</x-portal-layout>