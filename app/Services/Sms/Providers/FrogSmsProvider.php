<?php

namespace App\Services\Sms\Providers;

use App\Services\Sms\DataObjects\OutgoingSms;
use App\Services\Sms\DataObjects\ProviderBalance;
use App\Services\Sms\DataObjects\SmsResult;
use App\Services\Sms\Providers\AbstractSmsProvider;

/**
 * Wigal Frog adapter.
 *
 * @see https://frogdocs.wigal.com.gh/send_general.html
 * @see https://frogdocs.wigal.com.gh/get_account_balance.html
 */
class FrogSmsProvider extends AbstractSmsProvider
{
    private const ACCEPTED_STATUSES = ['ACCEPTD', 'SUCCESS'];

    public function name(): string
    {
        return 'frog';
    }

    public function send(OutgoingSms $message): SmsResult
    {
        $credentials = $this->credentials();

        if ($credentials === null) {
            return SmsResult::failure('CONFIGURATION_MISSING', 'Frog credentials are incomplete.');
        }

        $endpoint = $this->baseUrl().'/sms/send';
        $payload = [
            'senderid' => $message->senderId,
            'destinations' => [[
                'destination' => $message->recipient,
                'msgid' => $message->reference,
            ]],
            'message' => $message->body,
            'smstype' => 'text',
        ];

        $response = $this->http()->withHeaders($credentials)->post($endpoint, $payload);
        $body = $response->json() ?? [];

        $this->log($response->successful() ? 'info' : 'warning', 'Frog send request completed', [
            'endpoint' => $endpoint,
            'reference' => $message->reference,
            'status_code' => $response->status(),
            'response' => $body,
        ]);

        if ($response->serverError() || $response->status() === 429) {
            return SmsResult::transportFailure('PROVIDER_UNAVAILABLE', 'Frog returned HTTP '.$response->status().'.', $body);
        }

        if ($response->failed()) {
            return SmsResult::failure(
                (string) ($body['status'] ?? 'REQUEST_FAILED'),
                (string) ($body['message'] ?? 'Frog rejected the request.'),
                $body,
            );
        }

        $status = strtoupper((string) ($body['status'] ?? ''));

        if (! in_array($status, self::ACCEPTED_STATUSES, true)) {
            return SmsResult::failure(
                $status ?: 'REJECTED',
                (string) ($body['message'] ?? 'Frog rejected the message.'),
                $body,
            );
        }

        // Frog echoes no gateway id; our own msgid is the correlation key on callbacks.
        return SmsResult::success($status, (string) ($body['message'] ?? 'Accepted for processing.'), $message->reference, $body);
    }

    public function balance(): ProviderBalance
    {
        $credentials = $this->credentials();

        if ($credentials === null) {
            return ProviderBalance::unavailable($this->name(), 'Frog credentials are incomplete.');
        }

        $endpoint = $this->baseUrl().'/balance';
        $response = $this->http()->withHeaders($credentials)->get($endpoint);
        $body = $response->json() ?? [];

        $this->log($response->successful() ? 'info' : 'warning', 'Frog balance request completed', [
            'endpoint' => $endpoint,
            'status_code' => $response->status(),
            'response' => $body,
        ]);

        if ($response->failed()) {
            return ProviderBalance::unavailable($this->name(), 'Frog balance lookup returned HTTP '.$response->status().'.');
        }

        $data = (array) ($body['data'] ?? []);
        $bundles = (array) ($data['bundles'] ?? []);

        return new ProviderBalance(
            provider: $this->name(),
            available: true,
            creditBalance: (float) ($data['cashbalance'] ?? 0),
            currency: 'GHS',
            smsUnits: isset($bundles['SMS']) ? (int) $bundles['SMS'] : null,
            bundles: $this->formatBundles($bundles, (array) ($data['activebundleinvoices'] ?? [])),
            message: (string) ($body['message'] ?? 'SUCCESS'),
            raw: $body,
        );
    }

    /**
     * @param  array<string, mixed>  $bundles
     * @param  array<int, array<string, mixed>>  $invoices
     * @return array<int, array<string, mixed>>
     */
    private function formatBundles(array $bundles, array $invoices): array
    {
        $expiry = [];

        foreach ($invoices as $invoice) {
            foreach ((array) ($invoice['details'] ?? []) as $detail) {
                $service = (string) ($detail['service'] ?? '');
                if ($service !== '') {
                    $expiry[$service] = $invoice['enddate'] ?? null;
                }
            }
        }

        $formatted = [];

        foreach ($bundles as $service => $quantity) {
            $formatted[] = [
                'service' => (string) $service,
                'remaining' => (int) $quantity,
                'expires_on' => $expiry[$service] ?? null,
            ];
        }

        return $formatted;
    }

    /**
     * @return array<string, string>|null
     */
    private function credentials(): ?array
    {
        $apiKey = $this->providerConfig->credential('api_key');
        $username = $this->providerConfig->credential('username');

        if (blank($apiKey) || blank($username)) {
            return null;
        }

        return ['API-KEY' => $apiKey, 'USERNAME' => $username];
    }
}
