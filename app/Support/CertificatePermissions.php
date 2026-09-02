<?php

namespace App\Support;

final class CertificatePermissions
{
    public const VIEW = 'certificates.view';

    public const ISSUE = 'certificates.issue';

    public const VERIFY = 'certificates.verify';

    public const SUSPEND = 'certificates.suspend';

    public const REVOKE = 'certificates.revoke';

    public const EVIDENCE_VIEW = 'certificates.evidence.view';

    public const AUDIT_VIEW = 'certificates.audit.view';

    public const REFERENCE_DATA_MANAGE = 'certificates.reference-data.manage';

    public const ALL = [
        self::VIEW,
        self::ISSUE,
        self::VERIFY,
        self::SUSPEND,
        self::REVOKE,
        self::EVIDENCE_VIEW,
        self::AUDIT_VIEW,
        self::REFERENCE_DATA_MANAGE,
    ];
}
