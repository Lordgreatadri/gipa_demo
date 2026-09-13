<x-public-layout title="Contact us" description="Contact the Ghana Investment Promotion Authority about investment opportunities, onboarding or certificate verification.">
    <section class="detail-hero">
        <div class="shell">
            <nav class="breadcrumbs" aria-label="Breadcrumb"><a href="{{ route('home') }}">Home</a><span>/</span><span aria-current="page">Contact us</span></nav>
            <div class="detail-hero__grid">
                <div>
                    <div class="detail-hero__meta"><span class="status-pill">Investment services</span></div>
                    <h1>Contact us</h1>
                    <p>Tell us what you need and our investment services team will get back to you. You will receive an email — and a text message if you give us a phone number — confirming that we have your enquiry.</p>
                </div>
            </div>
        </div>
    </section>

    <div class="detail-layout shell">
        <article class="detail-content">
            <section>
                <p class="eyebrow eyebrow--dark">Before you write</p>
                <h2>You may find your answer faster here</h2>
                <p class="muted-copy">Many enquiries are answered by our self-service pages.</p>
                <div class="document-list">
                    <a href="{{ route('opportunities.index') }}"><span>1</span><div><strong>Browse investment opportunities</strong><small>Verified, district-level opportunities with financial indicators</small></div><i aria-hidden="true">&rarr;</i></a>
                    <a href="{{ route('platform.guide') }}"><span>2</span><div><strong>Read the investor guide</strong><small>Registration, onboarding and document requirements</small></div><i aria-hidden="true">&rarr;</i></a>
                    <a href="{{ route('districts.index') }}"><span>3</span><div><strong>Explore districts</strong><small>Regional profiles and the national investment map</small></div><i aria-hidden="true">&rarr;</i></a>
                </div>
            </section>

            <section>
                <p class="eyebrow eyebrow--dark">Other channels</p>
                <h2>Reach the team directly</h2>
                <dl class="financial-grid">
                    <div><dt>Email</dt><dd><a href="mailto:info@example.gov.gh">info@example.gov.gh</a></dd></div>
                    <div><dt>Telephone</dt><dd><a href="tel:+233000000000">+233 (0) 00 000 0000</a></dd></div>
                    <div><dt>Office</dt><dd>Accra, Ghana</dd></div>
                    <div><dt>Response time</dt><dd>Within 3 working days</dd></div>
                </dl>
            </section>
        </article>

        <aside class="inquiry-panel" id="contact-form">
            @if(session('contact_reference'))
                <div class="inquiry-success" role="status">
                    <strong>Enquiry received</strong>
                    <p>Your reference is <b>{{ session('contact_reference') }}</b>. We have sent you a confirmation and our team will be in touch soon.</p>
                </div>
            @endif

            <p class="eyebrow eyebrow--dark">Send a message</p>
            <h2>How can we help?</h2>
            <p>All fields marked * are required.</p>

            <form action="{{ route('contact.store') }}" method="post" class="inquiry-form">
                @csrf
                <label class="field"><span>Full name *</span><input name="name" placeholder="John Doe" value="{{ old('name', auth()->user()?->name) }}" required maxlength="255">@error('name')<small class="field-error">{{ $message }}</small>@enderror</label>
                <label class="field"><span>Email *</span><input type="email" name="email" placeholder="john.doe@example.com" value="{{ old('email', auth()->user()?->email) }}" required maxlength="255">@error('email')<small class="field-error">{{ $message }}</small>@enderror</label>
                <label class="field"><span>Phone</span><input name="phone_number" value="{{ old('phone_number') }}" maxlength="20" placeholder="024 123 4567">@error('phone_number')<small class="field-error">{{ $message }}</small>@enderror</label>
                <label class="field">
                    <span>Best time to call</span>
                    {{-- <input name="available_time_for_call" value="{{ old('available_time_for_call') }}" maxlength="255" placeholder="Weekday mornings, 9am – 12pm">
                    @error('available_time_for_call')<small class="field-error">{{ $message }}</small>@enderror --}}
                    <div>
                        {{-- <label for="available_time_for_call" class="block text-sm font-semibold text-slate-700">Best time to call (Optional)</label> --}}
                        <select id="available_time_for_call" name="available_time_for_call" class="mt-1.5 w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm focus:border-teal-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-200">
                            <option value="">Select a time (Africa/Accra GMT)</option>
                            <option value="Mornings 8am–12pm" @selected(old('available_time_for_call') === 'Mornings 8am–12pm')>Mornings (8am – 12pm)</option>
                            <option value="Afternoons 12pm–3pm" @selected(old('available_time_for_call') === 'Afternoons 12pm–3pm')>Afternoons (12pm – 3pm)</option>
                            <option value="Evenings 3pm–5pm" @selected(old('available_time_for_call') === 'Evenings 3pm–5pm')>Evenings (3pm – 5pm)</option>
                            <option value="Anytime" @selected(old('available_time_for_call') === 'Anytime')>Anytime during business hours</option>
                        </select>
                    </div>
                </label>
                <label class="field"><span>Subject *</span><input name="subject" value="{{ old('subject') }}" required maxlength="255">@error('subject')<small class="field-error">{{ $message }}</small>@enderror</label>
                <label class="field"><span>Message *</span><textarea name="message" rows="6" required minlength="20" maxlength="5000">{{ old('message') }}</textarea>@error('message')<small class="field-error">{{ $message }}</small>@enderror</label>
                <label class="consent-field"><input type="checkbox" name="consent" value="1" @checked(old('consent')) required><span>I consent to GIPA using these details to respond to my enquiry.</span></label>@error('consent')<small class="field-error">{{ $message }}</small>@enderror
                <input class="honeypot" type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true">
                <button class="button button--gold" type="submit">Send enquiry</button>
                <small class="form-assurance">We use your details only to respond to this enquiry. Do not send passwords or payment details.</small>
            </form>
        </aside>
    </div>
</x-public-layout>
