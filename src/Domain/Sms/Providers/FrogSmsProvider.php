<?php

namespace Src\Domain\Sms\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Src\Domain\Sms\Contracts\SmsProviderInterface;
use Src\Domain\Sms\DTOs\PreparedSmsMessage;
use Src\Domain\Sms\DTOs\SmsResult;

final class FrogSmsProvider implements SmsProviderInterface
{
    public function __construct(private readonly array $config = [])
    {
    }

    public function name(): string
    {
        return 'frog';
    }

    public function send(PreparedSmsMessage $message): SmsResult
    {
        $endpoint = rtrim((string) ($this->config['base_url'] ?? 'https://frogapi.wigal.com.gh/api/v3'), '/').'/sms/send';
        $apiKey = (string) ($this->config['api_key'] ?? '');
        $username = (string) ($this->config['username'] ?? '');

        if ($apiKey === '' || $username === '') {
            return SmsResult::failure(
                'CONFIGURATION_MISSING',
                'Frog SMS credentials are not configured.',
                [
                    'api_key_present' => $apiKey !== '',
                    'username_present' => $username !== '',
                ],
            );
        }

        $payload = [
            'senderid' => $message->senderId,
            'destinations' => [[
                'destination' => $message->recipient,
                'msgid' => $message->reference,
            ]],
            'message' => $message->message,
            'smstype' => 'text',
        ];

        $response = Http::timeout(20)
            ->acceptJson()
            ->withHeaders([
                'API-KEY' => $apiKey,
                'USERNAME' => $username,
            ])
            ->post($endpoint, $payload);

        Log::channel('frogapi')->info('Frog SMS API request processed', [
            'endpoint' => $endpoint,
            'payload' => $payload,
            'status_code' => $response->status(),
            'response' => $response->json(),
        ]);

        if ($response->failed()) {
            Log::channel('frogapi')->warning('Frog SMS failed', [
                'response' => $response->json(),
                'status_code' => $response->status(),
            ]);

            return SmsResult::failure('REQUEST_FAILED', 'Frog SMS request failed.', $response->json() ?? []);
        }

        $body = $response->json() ?? [];
        $accepted = strtoupper((string) ($body['status'] ?? '')) === 'ACCEPTD';

        if (! $accepted) {
            return SmsResult::failure(
                (string) ($body['status'] ?? 'ERROR'),
                (string) ($body['message'] ?? 'Provider rejected message.'),
                $body,
            );
        }

        return SmsResult::success(
            (string) ($body['status'] ?? 'ACCEPTD'),
            (string) ($body['message'] ?? 'Message accepted by Frog.'),
            $payload['destinations'][0]['msgid'],
            $body,
        );
    }

    public function balance(): array
    {
        $endpoint = rtrim((string) ($this->config['base_url'] ?? 'https://frogapi.wigal.com.gh/api/v3'), '/').'/balance';
        $apiKey = (string) ($this->config['api_key'] ?? '');
        $username = (string) ($this->config['username'] ?? '');

        if ($apiKey === '' || $username === '') {
            return [
                'provider' => 'frog',
                'status' => 'error',
                'message' => 'Frog SMS credentials are not configured.',
                'raw' => [],
            ];
        }

        $response = Http::timeout(20)
            ->acceptJson()
            ->withHeaders([
                'API-KEY' => $apiKey,
                'USERNAME' => $username,
            ])
            ->get($endpoint);

        Log::channel('frogapi')->info('Frog SMS balance request processed', [
            'endpoint' => $endpoint,
            'status_code' => $response->status(),
            'response' => $response->json(),
        ]);

        if ($response->failed()) {
            return [
                'provider' => 'frog',
                'status' => 'error',
                'message' => 'Could not fetch Frog balance.',
                'raw' => $response->json() ?? [],
            ];
        }

        $body = $response->json() ?? [];
        $data = $body['data'] ?? [];

        return [
            'provider' => 'frog',
            'status' => (string) ($body['status'] ?? 'unknown'),
            'cashBalance' => (float) ($data['cashbalance'] ?? 0),
            'paidBalance' => (float) ($data['paidcashbalance'] ?? 0),
            'bundles' => $data['bundles'] ?? [],
            'activeBundleInvoices' => $data['activebundleinvoices'] ?? [],
            'raw' => $body,
        ];
    }

    public function topUp(array $payload = []): array
    {
        return [
            'provider' => 'frog',
            'status' => 'unsupported',
            'message' => 'Frog SMS top-up is not implemented for this provider adapter yet.',
            'payload' => $payload,
        ];
    }
}
