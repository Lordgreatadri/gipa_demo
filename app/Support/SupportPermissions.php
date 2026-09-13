<?php

namespace App\Support;

final class SupportPermissions
{
    public const CONTACT_VIEW = 'support.contact.view';

    public const CONTACT_MANAGE = 'support.contact.manage';

    public const ALL = [
        self::CONTACT_VIEW,
        self::CONTACT_MANAGE,
    ];
}
