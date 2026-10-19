<?php

namespace App\Support;

final class BackupPermissions
{
    public const VIEW = 'backups.view';

    public const MANAGE = 'backups.manage';

    public const DOWNLOAD = 'backups.download';

    public const ALL = [
        self::VIEW,
        self::MANAGE,
        self::DOWNLOAD,
    ];
}
