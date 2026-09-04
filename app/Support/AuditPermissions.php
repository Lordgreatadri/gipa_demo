<?php

namespace App\Support;

/**
 * Permissions for oversight tooling: the activity audit log viewer and the
 * SLA monitoring dashboard. Restricted to elevated staff (super admin and the
 * reviewer/approver "admin" role).
 */
final class AuditPermissions
{
    public const LOGS_VIEW = 'audit.logs.view';

    public const LOGS_EXPORT = 'audit.logs.export';

    public const SLA_VIEW = 'sla.monitor.view';

    public const ALL = [
        self::LOGS_VIEW,
        self::LOGS_EXPORT,
        self::SLA_VIEW,
    ];
}
