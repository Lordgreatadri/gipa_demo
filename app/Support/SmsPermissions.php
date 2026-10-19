<?php

namespace App\Support;

final class SmsPermissions
{
    public const CONFIG_VIEW = 'sms.config.view';

    public const CONFIG_MANAGE = 'sms.config.manage';

    public const MESSAGES_VIEW = 'sms.messages.view';

    public const MESSAGES_SEND = 'sms.messages.send';

    public const ALL = [
        self::CONFIG_VIEW,
        self::CONFIG_MANAGE,
        self::MESSAGES_VIEW,
        self::MESSAGES_SEND,
    ];
}
