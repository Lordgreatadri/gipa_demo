<?php

namespace Src\Domain\Sms\Contracts;

use Src\Domain\Sms\DTOs\PreparedSmsMessage;
use Src\Domain\Sms\DTOs\SmsResult;

interface SmsProviderInterface
{
    public function name(): string;

    public function send(PreparedSmsMessage $message): SmsResult;

    public function balance(): array;

    public function topUp(array $payload = []): array;
}
