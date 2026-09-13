<?php

namespace Src\Domain\Sms;

use InvalidArgumentException;
use Src\Domain\Sms\Contracts\SmsProviderInterface;
use Src\Domain\Sms\Providers\FrogSmsProvider;
use Src\Domain\Sms\Providers\LogSmsProvider;
use Src\Domain\Sms\Providers\TwilioSmsProvider;

final class SmsProviderRegistry
{
    /** @var array<string, SmsProviderInterface> */
    private array $providers = [];

    /** @param array<int, SmsProviderInterface> $providers */
    public function __construct(array $providers)
    {
        foreach ($providers as $provider) {
            $this->providers[$provider->name()] = $provider;
        }
    }

    public function get(string $name): SmsProviderInterface
    {
        if (! isset($this->providers[$name])) {
            throw new InvalidArgumentException('Unsupported SMS provider: '.$name);
        }

        return $this->providers[$name];
    }

    public function has(string $name): bool
    {
        return isset($this->providers[$name]);
    }

    /** @return array<int, string> */
    public function names(): array
    {
        return array_keys($this->providers);
    }

    public static function makeDefault(): self
    {
        return new self([
            new LogSmsProvider(),
            new FrogSmsProvider((array) config('services.sms.frogsms', [])),
            new TwilioSmsProvider((array) config('services.sms.twilio', [])),
        ]);
    }
}
