<?php

namespace Tests\Feature;

use App\Models\SmsDeliveryEvent;
use App\Models\SmsMessage;
use App\Models\SmsProviderConfig;
use App\Services\Sms\Webhooks\Jobs\ProcessSmsStatusWebhookJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Spatie\WebhookClient\Models\WebhookCall;
use Tests\TestCase;

class SmsWebhookTest extends TestCase
{
    use RefreshDatabase;

    private function frogConfig(string $secret = 'frog-webhook-secret'): SmsProviderConfig
    {
        return SmsProviderConfig::create([
            'provider' => 'frog',
            'is_active' => true,
            'credentials' => ['username' => 'gipa-ops', 'api_key' => 'frog-key'],
            'webhook_secret' => $secret,
        ]);
    }

    private function twilioConfig(string $token = 'twilio-auth-token'): SmsProviderConfig
    {
        return SmsProviderConfig::create([
            'provider' => 'twilio',
            'is_active' => true,
            'credentials' => ['account_sid' => 'AC123', 'auth_token' => $token],
            'webhook_secret' => 'unused',
        ]);
    }

    private function message(array $overrides = []): SmsMessage
    {
        return SmsMessage::create(array_merge([
            'reference' => 'SMS-20260910-ABCDEFGHIJ',
            'provider' => 'frog',
            'context' => 'transactional',
            'sender_id' => 'GIPA',
            'recipient' => '+233241234567',
            'recipient_raw' => '0241234567',
            'body' => 'Delivery report test.',
            'segments' => 1,
            'status' => SmsMessage::STATUS_SENT,
            'provider_message_id' => 'SMS-20260910-ABCDEFGHIJ',
            'sent_at' => now(),
        ], $overrides));
    }

    public function test_frog_callback_without_a_valid_token_is_rejected(): void
    {
        $this->frogConfig();

        $this->postJson(route('webhook-client-frog-sms'), ['msgid' => 'X', 'status' => 'DELIVRD'])
            ->assertStatus(500);

        $this->assertDatabaseCount('webhook_calls', 0);
    }

    public function test_frog_callback_with_a_shared_token_is_accepted_and_applied(): void
    {
        $this->frogConfig();
        $message = $this->message();

        $this->postJson(route('webhook-client-frog-sms').'?token=frog-webhook-secret', [
            'msgid' => $message->reference,
            'status' => 'DELIVRD',
            'reason' => 'DELIVRD',
            'destination' => '233241234567',
            'statusdate' => '2026-09-10T14:59:14.143+00:00',
        ])->assertOk();

        $this->assertDatabaseCount('webhook_calls', 1);

        $message->refresh();
        $this->assertSame(SmsMessage::STATUS_DELIVERED, $message->status);
        $this->assertNotNull($message->delivered_at);
        $this->assertNull($message->failure_reason);

        $event = SmsDeliveryEvent::firstOrFail();
        $this->assertSame('DELIVRD', $event->raw_status);
        $this->assertSame($message->id, $event->sms_message_id);
    }

    public function test_frog_callback_accepts_an_hmac_signature_header(): void
    {
        $this->frogConfig();
        $message = $this->message();

        $payload = ['msgid' => $message->reference, 'status' => 'UNDELIV', 'reason' => 'Absent subscriber'];
        $body = json_encode($payload);

        $this->call(
            'POST',
            route('webhook-client-frog-sms'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_X_FROG_SIGNATURE' => hash_hmac('sha256', $body, 'frog-webhook-secret'),
            ],
            $body,
        )->assertOk();

        $message->refresh();
        $this->assertSame(SmsMessage::STATUS_UNDELIVERED, $message->status);
        $this->assertSame('Absent subscriber', $message->failure_reason);
        $this->assertNotNull($message->failed_at);
    }

    public function test_twilio_callback_requires_a_valid_signature(): void
    {
        $this->twilioConfig();

        $this->post(route('webhook-client-twilio-sms'), ['MessageSid' => 'SM1', 'MessageStatus' => 'delivered'], [
            'X-Twilio-Signature' => 'not-the-right-signature',
        ])->assertStatus(500);

        $this->assertDatabaseCount('webhook_calls', 0);
    }

    public function test_twilio_callback_with_a_valid_signature_updates_the_message(): void
    {
        $this->twilioConfig();
        $message = $this->message(['provider' => 'twilio', 'provider_message_id' => 'SM12345']);

        $url = route('webhook-client-twilio-sms');
        $payload = ['MessageSid' => 'SM12345', 'MessageStatus' => 'delivered'];

        $this->post($url, $payload, ['X-Twilio-Signature' => $this->twilioSignature($url, $payload, 'twilio-auth-token')])
            ->assertOk();

        $message->refresh();
        $this->assertSame(SmsMessage::STATUS_DELIVERED, $message->status);
        $this->assertNotNull($message->delivered_at);
    }

    public function test_a_late_non_delivered_report_cannot_downgrade_a_delivered_message(): void
    {
        $message = $this->message(['status' => SmsMessage::STATUS_DELIVERED, 'delivered_at' => now()]);

        (new ProcessSmsStatusWebhookJob(WebhookCall::create([
            'name' => 'frog-sms',
            'url' => 'https://example.test/webhooks/sms/frog',
            'payload' => ['msgid' => $message->reference, 'status' => 'WAITING'],
        ])))->handle();

        $this->assertSame(SmsMessage::STATUS_DELIVERED, $message->fresh()->status);
    }

    public function test_a_callback_for_an_unknown_message_is_stored_without_error(): void
    {
        (new ProcessSmsStatusWebhookJob(WebhookCall::create([
            'name' => 'frog-sms',
            'url' => 'https://example.test/webhooks/sms/frog',
            'payload' => ['msgid' => 'unknown-reference', 'status' => 'DELIVRD'],
        ])))->handle();

        $event = SmsDeliveryEvent::firstOrFail();
        $this->assertNull($event->sms_message_id);
        $this->assertSame('unknown-reference', $event->provider_message_id);
    }

    public function test_callbacks_without_a_message_id_are_not_stored(): void
    {
        $this->frogConfig();
        Queue::fake();

        $this->postJson(route('webhook-client-frog-sms').'?token=frog-webhook-secret', ['ping' => 'health-check'])
            ->assertOk();

        $this->assertDatabaseCount('webhook_calls', 0);
        Queue::assertNothingPushed();
    }

    /**
     * @param  array<string, string>  $payload
     */
    private function twilioSignature(string $url, array $payload, string $token): string
    {
        ksort($payload);
        $data = $url;

        foreach ($payload as $key => $value) {
            $data .= $key.$value;
        }

        return base64_encode(hash_hmac('sha1', $data, $token, true));
    }
}
