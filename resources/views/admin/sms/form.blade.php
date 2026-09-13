<x-admin-layout :title="$definition['label'].' configuration'">
    <nav class="admin-breadcrumb" aria-label="Breadcrumb"><a href="{{ route('staff.sms.index') }}">SMS configuration</a><span>/</span><span>{{ $definition['label'] }}</span></nav>
    <div class="admin-page-heading">
        <div>
            <p class="admin-kicker">Gateway credentials</p>
            <h1>{{ $definition['label'] }}</h1>
            <p>These values are encrypted before they are written to the database. Secret fields can be left blank to keep the stored value.</p>
        </div>
    </div>

    <form class="record-form" method="post" action="{{ route('staff.sms.update', $provider) }}">
        @csrf @method('PUT')

        <section>
            <div class="record-form__heading"><h2>Routing</h2><p>Controls whether this gateway carries traffic and in what order.</p></div>
            <div class="record-form__grid">
                <label class="field"><span>Display label</span><input name="label" value="{{ old('label', $config?->label) }}" maxlength="120" placeholder="{{ $definition['label'] }}">@error('label')<small class="field-error">{{ $message }}</small>@enderror</label>
                <label class="field"><span>Approved sender ID</span><input name="sender_id" value="{{ old('sender_id', $config?->sender_id) }}" maxlength="32" placeholder="GIPA"><small>Must be an alphanumeric sender ID already approved by the provider, or the purchased number for Twilio.</small>@error('sender_id')<small class="field-error">{{ $message }}</small>@enderror</label>
                <label class="field"><span>Failover priority</span><input type="number" name="priority" value="{{ old('priority', $config?->priority ?? 100) }}" min="1" max="999" required><small>Lower numbers are tried first.</small>@error('priority')<small class="field-error">{{ $message }}</small>@enderror</label>
                <label class="field"><span>Status</span><select name="is_active"><option value="0" @selected(! old('is_active', $config?->is_active))>Inactive</option><option value="1" @selected(old('is_active', $config?->is_active))>Active</option></select><small>A provider can only go active once every required credential is present.</small></label>
            </div>
        </section>

        @if($definition['credentials'])
            <section>
                <div class="record-form__heading"><h2>API credentials</h2><p>Issued by the provider.@if($definition['docs_url']) See the <a href="{{ $definition['docs_url'] }}" rel="noopener noreferrer" target="_blank">provider documentation</a>.@endif</p></div>
                <div class="record-form__grid">
                    @foreach($definition['credentials'] as $key => $meta)
                        @php($stored = $config?->credential($key))
                        <label class="field @if($meta['secret'] ?? false) field--wide @endif">
                            <span>{{ $meta['label'] }} @if($meta['required'] ?? false)*@endif</span>
                            <input
                                type="{{ ($meta['secret'] ?? false) ? 'password' : 'text' }}"
                                name="credentials[{{ $key }}]"
                                value="{{ ($meta['secret'] ?? false) ? '' : old('credentials.'.$key, $stored) }}"
                                maxlength="500"
                                autocomplete="off"
                                @if(($meta['secret'] ?? false) && filled($stored)) placeholder="•••••••• stored — leave blank to keep" @endif
                                @if(($meta['required'] ?? false) && ! ($meta['secret'] ?? false)) required @endif
                            >
                            @if($meta['hint'] ?? null)<small>{{ $meta['hint'] }}</small>@endif
                            @error('credentials.'.$key)<small class="field-error">{{ $message }}</small>@enderror
                        </label>
                    @endforeach
                </div>
            </section>
        @endif

        @if($definition['supports_delivery_callbacks'])
            <section>
                <div class="record-form__heading"><h2>Delivery-report callback</h2><p>Paste this URL into the provider console so delivery statuses flow back into the platform. Every callback is signature-checked before it is stored.</p></div>
                <div class="record-form__grid">
                    <label class="field field--wide">
                        <span>Callback URL</span>
                        <input value="{{ $config?->callbackUrl() ? $config->callbackUrl().($provider === 'frog' ? '?token='.$config->webhook_secret : '') : 'Save this configuration to generate the callback URL.' }}" readonly onfocus="this.select()">
                        <small>@if($provider === 'twilio') Twilio signs callbacks with your auth token, so no extra secret is needed in the URL. @else Frog does not sign callbacks, so the shared token in the query string authenticates the request. Treat this URL as a secret. @endif</small>
                    </label>
                    <label class="field"><span>Rotate callback secret</span><select name="rotate_webhook_secret"><option value="0">Keep current secret</option><option value="1">Generate a new secret</option></select><small>Rotating invalidates the URL already stored in the provider console.</small></label>
                </div>
            </section>
        @endif

        <div class="record-form__actions">
            <a class="button button--outline" href="{{ route('staff.sms.index') }}">Cancel</a>
            <button class="button button--gold" type="submit">Save configuration</button>
        </div>
    </form>
</x-admin-layout>
