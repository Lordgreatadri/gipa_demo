<?php

namespace Tests\Feature;

use App\Jobs\SendSmsMessage;
use App\Models\SmsMessage;
use App\Models\SmsProviderConfig;
use App\Services\Sms\DataObjects\SmsMessageData;
use App\Services\Sms\SmsGateway;
use App\Services\Sms\SmsProviderRegistry;
use App\Services\Sms\Support\CircuitBreaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SmsDispatchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        app(SmsProviderRegistry::class)->flush();
    }

    private function gateway(): SmsGateway
    {
        app(SmsProviderRegistry::class)->flush();

        return app(SmsGateway::class);
    }

    private function configureFrog(array $overrides = []): SmsProviderConfig
    {
        return SmsProviderConfig::create(array_merge([
            'provider' => 'frog',
            'sender_id' => 'GIPA',
            'is_active' => true,
            'priority' => 10,
            'credentials' => ['username' => 'gipa-ops', 'api_key' => 'frog-key'],
        ], $overrides));
    }

    private function configureTwilio(array $overrides = []): SmsProviderConfig
    {
        return SmsProviderConfig::create(array_merge([
            'provider' => 'twilio',
            'sender_id' => '+15005550006',
            'is_active' => true,
            'priority' => 20,
            'credentials' => ['account_sid' => 'AC123', 'auth_token' => 'token'],
        ], $overrides));
    }

    public function test_frog_receives_bare_international_digits_and_our_reference_as_msgid(): void
    {
        $this->configureFrog();

        Http::fake(['*frogapi*' => Http::response(['status' => 'ACCEPTD', 'message' => 'Message Accepted For Processing'])]);

        $message = $this->gateway()->send(new SmsMessageData(
            recipient: '024 123 4567',
            body: 'Your GIPA certificate is ready.',
            context: 'certificate',
        ));

        $this->assertSame(SmsMessage::STATUS_SENT, $message->status);
        $this->assertSame('frog', $message->provider);
        $this->assertSame($message->reference, $message->provider_message_id);

        Http::assertSent(function ($request) use ($message) {
            return str_contains($request->url(), '/sms/send')
                && $request['senderid'] === 'GIPA'
                && $request['destinations'][0]['destination'] === '233241234567'
                && $request['destinations'][0]['msgid'] === $message->reference
                && $request->header('API-KEY')[0] === 'frog-key'
                && $request->header('USERNAME')[0] === 'gipa-ops';
        });
    }

    public function test_twilio_receives_e164_and_a_status_callback_url(): void
    {
        $this->configureTwilio();

        Http::fake(['*api.twilio.com*' => Http::response(['sid' => 'SM123', 'status' => 'queued'])]);

        $message = $this->gateway()->send(new SmsMessageData(
            recipient: '0241234567',
            body: 'Welcome to the investor portal.',
        ));

        $this->assertSame(SmsMessage::STATUS_SENT, $message->status);
        $this->assertSame('SM123', $message->provider_message_id);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/Messages.json')
                && $request['To'] === '+233241234567'
                && $request['StatusCallback'] === route('webhook-client-twilio-sms');
        });
    }

    public function test_dispatch_fails_over_to_the_next_provider_when_the_first_is_unavailable(): void
    {
        $this->configureFrog();
        $this->configureTwilio();

        Http::fake([
            '*frogapi*' => Http::response(['message' => 'Service unavailable'], 503),
            '*api.twilio.com*' => Http::response(['sid' => 'SM999', 'status' => 'queued']),
        ]);

        $message = $this->gateway()->send(new SmsMessageData(recipient: '0241234567', body: 'Failover test message.'));

        $this->assertSame(SmsMessage::STATUS_SENT, $message->status);
        $this->assertSame('twilio', $message->provider);
        $this->assertSame('SM999', $message->provider_message_id);
        $this->assertSame('PROVIDER_UNAVAILABLE', $message->meta['provider_attempts']['frog']);
    }

    public function test_message_fails_when_no_provider_is_active(): void
    {
        $this->configureFrog(['is_active' => false]);

        $message = $this->gateway()->send(new SmsMessageData(recipient: '0241234567', body: 'Nobody can send this.'));

        $this->assertSame(SmsMessage::STATUS_FAILED, $message->status);
        $this->assertSame('NO_PROVIDER', $message->meta['failure_status']);
        Http::assertNothingSent();
    }

    public function test_an_unusable_number_is_rejected_before_any_provider_call(): void
    {
        $this->configureFrog();
        Http::fake();

        $message = $this->gateway()->send(new SmsMessageData(recipient: '00000', body: 'This should never leave.'));

        $this->assertSame(SmsMessage::STATUS_FAILED, $message->status);
        $this->assertSame('INVALID_RECIPIENT', $message->meta['failure_status']);
        Http::assertNothingSent();
    }

    public function test_queueing_records_the_message_and_defers_transmission(): void
    {
        $this->configureFrog();
        Queue::fake();

        $message = $this->gateway()->queue(new SmsMessageData(recipient: '0241234567', body: 'Queued for the worker.'));

        $this->assertSame(SmsMessage::STATUS_QUEUED, $message->status);
        $this->assertNotNull($message->queued_at);

        Queue::assertPushed(SendSmsMessage::class, fn (SendSmsMessage $job) => $job->smsMessageId === $message->id);
    }

    public function test_repeated_transport_failures_open_the_circuit_for_the_provider(): void
    {
        config()->set('sms.circuit_breaker.threshold', 2);

        $this->configureFrog();
        Http::fake(['*frogapi*' => Http::response([], 500)]);

        $gateway = $this->gateway();

        foreach (range(1, 2) as $ignored) {
            $gateway->send(new SmsMessageData(recipient: '0241234567', body: 'Provider is down right now.'));
        }

        $this->assertTrue(app(CircuitBreaker::class)->isOpen('frog'));

        $message = $gateway->send(new SmsMessageData(recipient: '0241234567', body: 'Should skip the open circuit.'));

        $this->assertSame(SmsMessage::STATUS_FAILED, $message->status);
        $this->assertSame('circuit-open', $message->meta['provider_attempts']['frog']);
    }

    public function test_transmission_can_be_disabled_for_an_environment(): void
    {
        config()->set('sms.enabled', false);
        $this->configureFrog();
        Http::fake();

        $message = $this->gateway()->send(new SmsMessageData(recipient: '0241234567', body: 'Disabled environment message.'));

        $this->assertSame(SmsMessage::STATUS_SKIPPED, $message->status);
        Http::assertNothingSent();
    }

    public function test_long_messages_are_counted_in_segments(): void
    {
        $this->configureFrog();
        Http::fake(['*frogapi*' => Http::response(['status' => 'ACCEPTD', 'message' => 'ok'])]);

        $message = $this->gateway()->send(new SmsMessageData(
            recipient: '0241234567',
            body: str_repeat('a', 200),
        ));

        $this->assertSame(2, $message->segments);
    }
}
