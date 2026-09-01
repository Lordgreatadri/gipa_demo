<x-public-layout :title="$opportunity->title" :description="str($opportunity->overview)->stripTags()->limit(150)">
    <section class="detail-hero">
        <div class="shell">
            <nav class="breadcrumbs" aria-label="Breadcrumb"><a href="{{ route('home') }}">Home</a><span>/</span><a href="{{ route('opportunities.index') }}">Opportunities</a><span>/</span><span aria-current="page">{{ str($opportunity->title)->limit(42) }}</span></nav>
            <div class="detail-hero__grid">
                <div><div class="detail-hero__meta"><span class="status-pill">{{ str($opportunity->workflow_status)->replace('_', ' ')->title() }}</span><span>{{ $opportunity->sector->name }}</span></div><h1>{{ $opportunity->title }}</h1><p><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"></path><circle cx="12" cy="10" r="2.5"></circle></svg>{{ $opportunity->district->name }}, {{ $opportunity->district->region->name }}</p></div>
                <div class="detail-hero__investment"><span>Investment required</span><strong>{{ $opportunity->financial?->amount ? $opportunity->financial->currency.' '.number_format((float) $opportunity->financial->amount) : 'Available on request' }}</strong><small>{{ $opportunity->enterpriseType->name }}</small><a class="button button--gold" href="#inquiry">Express interest</a></div>
            </div>
        </div>
    </section>

    <div class="detail-layout shell">
        <article class="detail-content">
            <section><p class="eyebrow eyebrow--dark">Opportunity brief</p><h2>Overview</h2><p class="detail-lead">{{ $opportunity->overview }}</p>@if($opportunity->location_description)<div class="detail-note"><strong>Location context</strong><p>{{ $opportunity->location_description }}</p></div>@endif</section>
            @foreach(['objectives' => 'Objectives', 'rationale' => 'Investment rationale', 'success_factors' => 'Success factors', 'competitive_advantages' => 'Competitive advantages'] as $field => $heading)@if($opportunity->{$field})<section><h2>{{ $heading }}</h2><p>{{ $opportunity->{$field} }}</p></section>@endif @endforeach
            <section><p class="eyebrow eyebrow--dark">Commercial profile</p><h2>Financial indicators</h2><dl class="financial-grid"><div><dt>Structure</dt><dd>{{ $opportunity->financial?->investmentStructure?->name ?? 'On request' }}</dd></div><div><dt>Expected ROI</dt><dd>{{ $opportunity->financial?->roi_percentage ? number_format((float) $opportunity->financial->roi_percentage, 2).'%' : 'On request' }}</dd></div><div><dt>Expected IRR</dt><dd>{{ $opportunity->financial?->irr_percentage ? number_format((float) $opportunity->financial->irr_percentage, 2).'%' : 'On request' }}</dd></div><div><dt>Payback period</dt><dd>{{ $opportunity->financial?->payback_period_months ? $opportunity->financial->payback_period_months.' months' : 'On request' }}</dd></div></dl></section>
            <section><p class="eyebrow eyebrow--dark">Due diligence</p><h2>Documents</h2>@if($opportunity->media->isEmpty())<p class="muted-copy">Supporting documents are available through the GIPA investment desk after an inquiry is reviewed.</p>@else<div class="document-list">@foreach($opportunity->media as $document)<a href="{{ $document->getUrl() }}" target="_blank" rel="noopener"><span>{{ strtoupper($document->extension) }}</span><div><strong>{{ $document->name }}</strong><small>{{ str($document->collection_name)->replace('_', ' ')->title() }}</small></div><i aria-hidden="true">&darr;</i></a>@endforeach</div>@endif</section>
        </article>

        <aside class="inquiry-panel" id="inquiry">
            @if(session('inquiry_reference'))<div class="inquiry-success" role="status"><strong>Inquiry received</strong><p>Your reference is <b>{{ session('inquiry_reference') }}</b>. Our investment desk will follow up.</p></div>@endif
            <p class="eyebrow eyebrow--dark">Investor support</p><h2>Express your interest</h2><p>Send a secure inquiry to the team responsible for this opportunity.</p>
            <form action="{{ route('opportunities.inquiries.store', $opportunity->uuid) }}" method="post" class="inquiry-form">@csrf
                <label class="field"><span>Full name *</span><input name="name" value="{{ old('name', auth()->user()?->name) }}" required maxlength="255">@error('name')<small class="field-error">{{ $message }}</small>@enderror</label>
                <label class="field"><span>Work email *</span><input type="email" name="email" value="{{ old('email', auth()->user()?->email) }}" required maxlength="255">@error('email')<small class="field-error">{{ $message }}</small>@enderror</label>
                <label class="field"><span>Organization</span><input name="organization" value="{{ old('organization', auth()->user()?->organization) }}" maxlength="255"></label>
                <label class="field"><span>Phone</span><input name="phone" value="{{ old('phone', auth()->user()?->phone) }}" maxlength="32"></label>
                <label class="field"><span>Message *</span><textarea name="message" rows="5" required minlength="20" maxlength="5000">{{ old('message') }}</textarea>@error('message')<small class="field-error">{{ $message }}</small>@enderror</label>
                <label class="consent-field"><input type="checkbox" name="consent" value="1" @checked(old('consent')) required><span>I consent to GIPA using these details to respond to this inquiry.</span></label>@error('consent')<small class="field-error">{{ $message }}</small>@enderror
                <input class="honeypot" type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true">
                <button class="button button--gold" type="submit">Submit inquiry</button>
                <small class="form-assurance">Your inquiry will be recorded against this opportunity for transparent follow-up.</small>
            </form>
            @if($opportunity->contacts->isNotEmpty())<div class="contact-summary"><span>Opportunity contact</span><strong>{{ $opportunity->contacts->first()->name }}</strong><small>{{ $opportunity->contacts->first()->organization }}</small></div>@endif
        </aside>
    </div>
</x-public-layout>