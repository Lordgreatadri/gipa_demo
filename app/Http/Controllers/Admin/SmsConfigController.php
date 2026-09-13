<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SmsMessage;
use App\Models\SmsProviderConfig;
use App\Services\Sms\DataObjects\SmsMessageData;
use App\Services\Sms\SmsGateway;
use App\Services\Sms\SmsProviderRegistry;
use App\Services\Sms\Support\CircuitBreaker;
use App\Support\SmsPermissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Platform-owner administration of SMS gateways.
 *
 * Credentials are captured here and stored encrypted; nothing is read from the
 * environment, so a provider can be switched without a redeploy.
 */
class SmsConfigController extends Controller
{
    public function __construct(
        private readonly SmsProviderRegistry $registry,
        private readonly SmsGateway $gateway,
        private readonly CircuitBreaker $breaker,
    ) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()->can(SmsPermissions::CONFIG_VIEW), 403);

        $records = $this->registry->records();
        $providers = [];

        foreach ($this->registry->catalogue() as $key => $definition) {
            $config = $records[$key] ?? null;

            $providers[] = [
                'key' => $key,
                'definition' => $definition,
                'config' => $config,
                'configured' => (bool) $config?->isConfigured(),
                'active' => (bool) $config?->is_active,
                'circuit_open' => $this->breaker->isOpen($key),
                'balance' => $config && $config->supportsBalance() && $config->isUsable()
                    ? $this->gateway->balance($config)
                    : null,
            ];
        }

        return view('admin.sms.index', [
            'providers' => $providers,
            'stats' => $this->stats(),
            'canManage' => $request->user()->can(SmsPermissions::CONFIG_MANAGE),
            'canSend' => $request->user()->can(SmsPermissions::MESSAGES_SEND),
        ]);
    }

    public function edit(Request $request, string $provider): View
    {
        abort_unless($request->user()->can(SmsPermissions::CONFIG_MANAGE), 403);
        abort_unless($this->registry->supports($provider), 404);

        return view('admin.sms.form', [
            'provider' => $provider,
            'definition' => $this->registry->definition($provider),
            'config' => $this->registry->configFor($provider),
        ]);
    }

    public function update(Request $request, string $provider): RedirectResponse
    {
        abort_unless($request->user()->can(SmsPermissions::CONFIG_MANAGE), 403);
        abort_unless($this->registry->supports($provider), 404);

        $definition = $this->registry->definition($provider);
        $config = $this->registry->configFor($provider);

        $rules = [
            'label' => ['nullable', 'string', 'max:120'],
            'sender_id' => ['nullable', 'string', 'max:32'],
            'priority' => ['required', 'integer', 'min:1', 'max:999'],
            'is_active' => ['nullable', 'boolean'],
            'rotate_webhook_secret' => ['nullable', 'boolean'],
        ];

        foreach ($definition['credentials'] ?? [] as $key => $meta) {
            // Existing secrets may be left blank on the form to keep the stored value.
            $required = ($meta['required'] ?? false) && ! ($meta['secret'] ?? false);
            $rules['credentials.'.$key] = [$required ? 'required' : 'nullable', 'string', 'max:500'];
        }

        $validated = $request->validate($rules);

        $credentials = $config?->credentials ?? [];

        foreach ($definition['credentials'] ?? [] as $key => $meta) {
            $value = $validated['credentials'][$key] ?? null;

            if (filled($value)) {
                $credentials[$key] = $value;
            } elseif (! ($meta['secret'] ?? false)) {
                // Non-secret fields are editable to blank so defaults apply again.
                unset($credentials[$key]);
            }
        }

        $config ??= new SmsProviderConfig(['provider' => $provider]);
        $config->fill([
            'label' => $validated['label'] ?? null,
            'sender_id' => $validated['sender_id'] ?? null,
            'priority' => (int) $validated['priority'],
            'credentials' => $credentials,
            'updated_by' => $request->user()->id,
        ]);
        $config->created_by ??= $request->user()->id;
        $config->provider = $provider;

        if (blank($config->webhook_secret) || $request->boolean('rotate_webhook_secret')) {
            $config->webhook_secret = Str::random(48);
        }

        $config->is_active = $request->boolean('is_active') && $config->isConfigured();
        $config->save();

        $this->registry->flush();
        $this->breaker->reset($provider);

        $message = $request->boolean('is_active') && ! $config->is_active
            ? 'Configuration saved, but the provider stays inactive until every required credential is supplied.'
            : 'SMS provider configuration saved.';

        return redirect()->route('staff.sms.index')->with('status', $message);
    }

    public function toggle(Request $request, SmsProviderConfig $config): RedirectResponse
    {
        abort_unless($request->user()->can(SmsPermissions::CONFIG_MANAGE), 403);

        if (! $config->is_active && ! $config->isConfigured()) {
            return back()->with('status', 'Cannot activate '.$config->displayName().': missing '.implode(', ', $config->missingCredentials()).'.');
        }

        $config->forceFill([
            'is_active' => ! $config->is_active,
            'updated_by' => $request->user()->id,
        ])->save();

        $this->registry->flush();
        $this->breaker->reset($config->provider);

        return back()->with('status', $config->displayName().' is now '.($config->is_active ? 'active' : 'inactive').'.');
    }

    public function refreshBalance(Request $request, SmsProviderConfig $config): RedirectResponse
    {
        abort_unless($request->user()->can(SmsPermissions::CONFIG_VIEW), 403);

        $balance = $this->gateway->balance($config, fresh: true);

        return back()->with('status', $balance->available
            ? $config->displayName().' balance refreshed.'
            : $config->displayName().' balance unavailable: '.$balance->message);
    }

    public function test(Request $request, SmsProviderConfig $config): RedirectResponse
    {
        abort_unless($request->user()->can(SmsPermissions::MESSAGES_SEND), 403);

        $validated = $request->validate([
            'recipient' => ['required', 'string', 'max:20'],
            'body' => ['required', 'string', 'max:320'],
        ]);

        $message = $this->gateway->send(new SmsMessageData(
            recipient: $validated['recipient'],
            body: $validated['body'],
            context: 'diagnostic',
            preferredProvider: $config->provider,
            meta: ['triggered_by' => $request->user()->uuid],
        ));

        return back()->with('status', $message->status === SmsMessage::STATUS_SENT
            ? 'Test message accepted by '.$config->displayName().' (reference '.$message->reference.').'
            : 'Test message failed: '.($message->failure_reason ?: 'unknown error').'.');
    }

    public function messages(Request $request): View
    {
        abort_unless($request->user()->can(SmsPermissions::MESSAGES_VIEW), 403);

        $filters = $request->validate([
            'status' => ['nullable', Rule::in(array_keys(SmsMessage::STATUSES))],
            'provider' => ['nullable', Rule::in($this->registry->available())],
            'search' => ['nullable', 'string', 'max:120'],
        ]);

        $messages = SmsMessage::query()
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['provider'] ?? null, fn ($query, $provider) => $query->where('provider', $provider))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(function ($inner) use ($search): void {
                $inner->where('reference', 'like', '%'.$search.'%')
                    ->orWhere('recipient', 'like', '%'.$search.'%')
                    ->orWhere('provider_message_id', 'like', '%'.$search.'%');
            }))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.sms.messages', [
            'messages' => $messages,
            'filters' => $filters,
            'statuses' => SmsMessage::STATUSES,
            'providers' => $this->registry->available(),
            'stats' => $this->stats(),
        ]);
    }

    public function show(Request $request, SmsMessage $message): View
    {
        abort_unless($request->user()->can(SmsPermissions::MESSAGES_VIEW), 403);

        return view('admin.sms.show', [
            'message' => $message->load('deliveryEvents'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function stats(): array
    {
        $since = now()->subDays(30);

        $counts = SmsMessage::query()
            ->where('created_at', '>=', $since)
            ->selectRaw('status, count(*) as total, coalesce(sum(segments), 0) as segments')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $total = (int) $counts->sum('total');
        $delivered = (int) ($counts[SmsMessage::STATUS_DELIVERED]->total ?? 0);
        $failed = (int) ($counts[SmsMessage::STATUS_FAILED]->total ?? 0)
            + (int) ($counts[SmsMessage::STATUS_UNDELIVERED]->total ?? 0);

        return [
            'total' => $total,
            'delivered' => $delivered,
            'failed' => $failed,
            'pending' => $total - $delivered - $failed,
            'segments' => (int) $counts->sum('segments'),
            'delivery_rate' => $total > 0 ? round(($delivered / $total) * 100, 1) : 0.0,
        ];
    }
}
