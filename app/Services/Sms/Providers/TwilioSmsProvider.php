<?php

namespace App\Services\Sms\Providers;

use App\Services\Sms\DataObjects\OutgoingSms;
use App\Services\Sms\DataObjects\ProviderBalance;
use App\Services\Sms\DataObjects\SmsResult;
use App\Services\Sms\Providers\AbstractSmsProvider;

/**
 * Twilio Programmable Messaging adapter.
 *
 * Uses the REST API directly rather than the SDK so credentials can come from
 * the encrypted database record instead of the environment.
 *
 * @see https://www.twilio.com/docs/messaging/api/message-resource
 */
class TwilioSmsProvider extends AbstractSmsProvider
{
    private const ACCEPTED_STATUSES = ['queued', 'accepted', 'scheduled', 'sending', 'sent'];

    public function name(): string
    {
        return 'twilio';
    }

    public function send(OutgoingSms $message): SmsResult
    {
        [$sid, $token] = $this->credentials();

        if ($sid === null || $token === null) {
            return SmsResult::failure('CONFIGURATION_MISSING', 'Twilio credentials are incomplete.');
        }

        $endpoint = $this->baseUrl().'/Accounts/'.$sid.'/Messages.json';
        $payload = [
            'From' => $message->senderId,
            'To' => $message->recipient,
            'Body' => $message->body,
        ];

        if ($message->callbackUrl) {
            $payload['StatusCallback'] = $message->callbackUrl;
        }

        $response = $this->http()->asForm()->withBasicAuth($sid, $token)->post($endpoint, $payload);
        $body = $response->json() ?? [];

        $this->log($response->successful() ? 'info' : 'warning', 'Twilio send request completed', [
            'endpoint' => $endpoint,
            'reference' => $message->reference,
            'status_code' => $response->status(),
            'response' => $body,
        ]);

        if ($response->serverError() || $response->status() === 429) {
            return SmsResult::transportFailure('PROVIDER_UNAVAILABLE', 'Twilio returned HTTP '.$response->status().'.', $body);
        }

        if ($response->failed()) {
            return SmsResult::failure(
                (string) ($body['code'] ?? 'REQUEST_FAILED'),
                (string) ($body['message'] ?? 'Twilio rejected the request.'),
                $body,
            );
        }

        $status = strtolower((string) ($body['status'] ?? ''));

        if (! in_array($status, self::ACCEPTED_STATUSES, true)) {
            return SmsResult::failure(
                $status ?: 'REJECTED',
                (string) ($body['error_message'] ?? 'Twilio rejected the message.'),
                $body,
            );
        }

        return SmsResult::success($status, 'Accepted by Twilio.', $body['sid'] ?? null, $body);
    }

    public function balance(): ProviderBalance
    {
        [$sid, $token] = $this->credentials();

        if ($sid === null || $token === null) {
            return ProviderBalance::unavailable($this->name(), 'Twilio credentials are incomplete.');
        }

        $endpoint = $this->baseUrl().'/Accounts/'.$sid.'/Balance.json';
        $response = $this->http()->withBasicAuth($sid, $token)->get($endpoint);
        $body = $response->json() ?? [];

        $this->log($response->successful() ? 'info' : 'warning', 'Twilio balance request completed', [
            'endpoint' => $endpoint,
            'status_code' => $response->status(),
            'response' => $body,
        ]);

        if ($response->failed()) {
            return ProviderBalance::unavailable($this->name(), 'Twilio balance lookup returned HTTP '.$response->status().'.');
        }

        return new ProviderBalance(
            provider: $this->name(),
            available: true,
            creditBalance: (float) ($body['balance'] ?? 0),
            currency: (string) ($body['currency'] ?? 'USD'),
            message: 'Account balance retrieved.',
            raw: $body,
        );
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function credentials(): array
    {
        $sid = $this->providerConfig->credential('account_sid');
        $token = $this->providerConfig->credential('auth_token');

        return [blank($sid) ? null : $sid, blank($token) ? null : $token];
    }
}
