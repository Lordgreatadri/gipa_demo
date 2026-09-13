<x-admin-layout title="SMS configuration">
    <div class="admin-page-heading">
        <div>
            <p class="admin-kicker">Platform messaging</p>
            <h1>SMS configuration</h1>
            <p>Capture gateway credentials, choose which provider carries traffic, and watch bundle balances. Credentials are stored encrypted and never read from server environment files.</p>
        </div>
        <div class="admin-page-heading__actions">
            <a class="button button--outline" href="{{ route('staff.sms.messages') }}">Delivery log</a>
        </div>
    </div>

    <section class="metric-widget-grid" aria-label="SMS delivery overview">
        <x-metric-widget label="Messages (30 days)" :value="number_format($stats['total'])" note="All outbound traffic" icon="message-square" tone="green" />
        <x-metric-widget label="Delivered" :value="number_format($stats['delivered'])" :note="$stats['delivery_rate'].'% delivery rate'" icon="check-circle" tone="blue" />
        <x-metric-widget label="Failed" :value="number_format($stats['failed'])" note="Rejected or undelivered" icon="alert-triangle" tone="gold" />
        <x-metric-widget label="Billable segments" :value="number_format($stats['segments'])" note="Units charged by providers" icon="layers" tone="green" />
    </section>

    @php($activeCount = collect($providers)->where('active', true)->count())
    @if($activeCount === 0)
        <div class="admin-alert admin-alert--error" role="status">
            <strong>No SMS gateway is active.</strong>
            <span>Outbound messages are written to the SMS log only. Configure and activate a provider below to start transmitting.</span>
        </div>
    @endif

    <section class="reference-section">
        <div class="reference-section__heading">
            <div><h2>Providers</h2><p>The active provider with the lowest priority number carries traffic first; the next active provider is used automatically if it is unreachable.</p></div>
            <strong>{{ $activeCount }} active</strong>
        </div>

        <div class="reference-list">
            @foreach($providers as $provider)
                @php($config = $provider['config'])
                <details class="reference-row" @if($provider['active']) open @endif>
                    <summary>
                        <span>
                            <strong>{{ $config?->displayName() ?? $provider['definition']['label'] }}</strong>
                            <small>
                                {{ $provider['key'] }}
                                @if($config) · priority {{ $config->priority }} @endif
                                @if($config?->sender_id) · sender “{{ $config->sender_id }}” @endif
                                @if($config?->last_success_at) · last success {{ $config->last_success_at->diffForHumans() }} @endif
                            </small>
                        </span>
                        <span class="admin-status admin-status--{{ $provider['active'] ? 'active' : ($provider['configured'] ? 'draft' : 'suspended') }}">
                            {{ $provider['active'] ? 'Active' : ($provider['configured'] ? 'Configured' : 'Not configured') }}
                        </span>
                    </summary>
                    <div class="reference-edit">
                        @if($provider['circuit_open'])
                            <p class="admin-alert admin-alert--error" role="status"><strong>Temporarily bypassed.</strong> Repeated transport failures tripped the circuit breaker; traffic fails over to the next provider until the cooldown expires.</p>
                        @endif

                        @if($config && ! $provider['configured'])
                            <p>Missing required credentials: <strong>{{ implode(', ', $config->missingCredentials()) }}</strong>.</p>
                        @endif

                        @if($config?->last_failure_at)
                            <p><small>Last failure {{ $config->last_failure_at->diffForHumans() }}: {{ $config->last_failure_reason }} ({{ $config->consecutive_failures }} consecutive).</small></p>
                        @endif

                        @if($provider['balance'])
                            @php($balance = $provider['balance'])
                            <div class="sms-balance @if($balance->available && $balance->isLow()) sms-balance--low @endif">
                                @if($balance->available)
                                    <p>
                                        <strong>{{ number_format((float) $balance->creditBalance, 2) }} {{ $balance->currency }}</strong> cash balance
                                        @if($balance->smsUnits !== null) · <strong>{{ number_format($balance->smsUnits) }}</strong> SMS bundle units remaining @endif
                                        <small>Checked {{ $config->balance_checked_at?->diffForHumans() ?? 'just now' }}</small>
                                    </p>
                                    @if($balance->bundles)
                                        <table class="admin-table">
                                            <thead><tr><th>Bundle</th><th>Remaining</th><th>Expires</th></tr></thead>
                                            <tbody>
                                                @foreach($balance->bundles as $bundle)
                                                    <tr>
                                                        <td>{{ $bundle['service'] }}</td>
                                                        <td>{{ number_format((int) $bundle['remaining']) }}</td>
                                                        <td>{{ $bundle['expires_on'] ?? '—' }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    @endif
                                    @if($balance->isLow())
                                        <p><strong>Balance is low.</strong> Top up with the provider to avoid interrupted notifications.</p>
                                    @endif
                                @else
                                    <p><small>Balance unavailable: {{ $balance->message }}</small></p>
                                @endif
                            </div>
                        @endif

                        @if($config?->callbackUrl())
                            <p><small>Delivery-report callback URL: <code>{{ $config->callbackUrl() }}?token={{ str_repeat('•', 12) }}</code> — the full URL with the token is shown on the edit screen.</small></p>
                        @endif

                        <div class="reference-edit__actions">
                            @if($canManage)
                                <a class="button button--outline" href="{{ route('staff.sms.edit', $provider['key']) }}">{{ $config ? 'Edit credentials' : 'Configure' }}</a>
                            @endif
                            @if($config)
                                @if($canManage)
                                    <form method="post" action="{{ route('staff.sms.toggle', $config) }}">
                                        @csrf
                                        <button class="button button--outline" type="submit">{{ $config->is_active ? 'Deactivate' : 'Activate' }}</button>
                                    </form>
                                @endif
                                @if($config->supportsBalance() && $config->isUsable())
                                    <form method="post" action="{{ route('staff.sms.balance', $config) }}">
                                        @csrf
                                        <button class="button button--outline" type="submit">Refresh balance</button>
                                    </form>
                                @endif
                            @endif
                        </div>

                        @if($canSend && $config?->isUsable())
                            <form class="record-form" method="post" action="{{ route('staff.sms.test', $config) }}">
                                @csrf
                                <div class="record-form__grid">
                                    <label class="field"><span>Test recipient</span><input name="recipient" placeholder="024 123 4567" maxlength="20" required></label>
                                    <label class="field field--wide"><span>Test message</span><input name="body" value="IOMP test message from the SMS configuration dashboard." maxlength="320" required></label>
                                </div>
                                <div class="record-form__actions"><button class="button button--gold" type="submit">Send test message</button></div>
                            </form>
                        @endif
                    </div>
                </details>
            @endforeach
        </div>
    </section>
</x-admin-layout>
