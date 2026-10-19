<?php

namespace Src\Domain\Sms\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Src\Domain\Sms\Contracts\SmsProviderInterface;
use Src\Domain\Sms\DTOs\PreparedSmsMessage;
use Src\Domain\Sms\DTOs\SmsResult;

final class TwilioSmsProvider implements SmsProviderInterface
{
    private const ACCEPTED_STATUSES = ['queued', 'accepted', 'sending', 'sent'];

    public function __construct(private readonly array $config = [])
    {
    }

    public function name(): string
    {
        return 'twilio';
    }

    public function send(PreparedSmsMessage $message): SmsResult
    {
        $baseUrl = rtrim((string) ($this->config['base_url'] ?? 'https://api.twilio.com/2010-04-01'), '/');
        $sid = (string) ($this->config['sid'] ?? '');
        $token = (string) ($this->config['token'] ?? '');

        if ($sid === '' || $token === '') {
            return SmsResult::failure(
                'CONFIGURATION_MISSING',
                'Twilio SMS credentials are not configured.',
                [
                    'sid_present' => $sid !== '',
                    'token_present' => $token !== '',
                ],
            );
        }

        $endpoint = $baseUrl.'/Accounts/'.$sid.'/Messages.json';

        $payload = [
            'From' => $message->senderId,
            'To' => $message->recipient,
            'Body' => $message->message,
        ];

        if ($message->callbackUrl) {
            $payload['StatusCallback'] = $message->callbackUrl;
        }

        $response = Http::asForm()
            ->timeout(20)
            ->withBasicAuth($sid, $token)
            ->post($endpoint, $payload);

        Log::channel('twilioapi')->info('Twilio SMS API request processed', [
            'endpoint' => $endpoint,
            'payload' => $payload,
            'status_code' => $response->status(),
            'response' => $response->json(),
        ]);

        if ($response->failed()) {
            Log::channel('twilioapi')->warning('Twilio SMS failed', [
                'response' => $response->json(),
                'status_code' => $response->status(),
            ]);

            return SmsResult::failure('REQUEST_FAILED', 'Twilio SMS request failed.', $response->json() ?? []);
        }

        $body = $response->json() ?? [];
        $status = strtolower((string) ($body['status'] ?? ''));

        if (! in_array($status, self::ACCEPTED_STATUSES, true)) {
            return SmsResult::failure(
                (string) ($body['status'] ?? 'ERROR'),
                (string) ($body['message'] ?? 'Provider rejected message.'),
                $body,
            );
        }

        return SmsResult::success(
            (string) ($body['status'] ?? 'queued'),
            (string) ($body['message'] ?? 'Message accepted by Twilio.'),
            $body['sid'] ?? null,
            $body,
        );
    }

    public function balance(): array
    {
        $baseUrl = rtrim((string) ($this->config['base_url'] ?? 'https://api.twilio.com/2010-04-01'), '/');
        $sid = (string) ($this->config['sid'] ?? '');
        $token = (string) ($this->config['token'] ?? '');

        if ($sid === '' || $token === '') {
            return [
                'provider' => 'twilio',
                'status' => 'error',
                'message' => 'Twilio SMS credentials are not configured.',
                'raw' => [],
            ];
        }

        $endpoint = $baseUrl.'/Accounts/'.$sid.'/Balance.json';

        $response = Http::timeout(20)
            ->withBasicAuth($sid, $token)
            ->get($endpoint);

        Log::channel('twilioapi')->info('Twilio SMS balance request processed', [
            'endpoint' => $endpoint,
            'status_code' => $response->status(),
            'response' => $response->json(),
        ]);

        if ($response->failed()) {
            return [
                'provider' => 'twilio',
                'status' => 'error',
                'message' => 'Could not fetch Twilio balance.',
                'raw' => $response->json() ?? [],
            ];
        }

        $body = $response->json() ?? [];

        return [
            'provider' => 'twilio',
            'status' => 'ok',
            'cashBalance' => (float) ($body['balance'] ?? 0),
            'currency' => (string) ($body['currency'] ?? 'USD'),
            'raw' => $body,
        ];
    }

    public function topUp(array $payload = []): array
    {
        return [
            'provider' => 'twilio',
            'status' => 'unsupported',
            'message' => 'Twilio balance top-up is not implemented for this provider adapter yet.',
            'payload' => $payload,
        ];
    }
}
