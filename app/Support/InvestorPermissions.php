<?php

namespace App\Support;

final class InvestorPermissions
{
    public const VIEW = 'investors.view';
    public const REVIEW = 'investors.review';
    public const REASSIGN = 'investors.reassign';
    public const COMPLIANCE_MANAGE = 'investors.compliance.manage';
    public const AFTERCARE_VIEW = 'investors.aftercare.view';
    public const AFTERCARE_MANAGE = 'investors.aftercare.manage';
    public const REFERENCE_DATA_MANAGE = 'investors.reference-data.manage';
    public const EXPORT = 'investors.export';

    public const ALL = [
        self::VIEW,
        self::REVIEW,
        self::REASSIGN,
        self::COMPLIANCE_MANAGE,
        self::AFTERCARE_VIEW,
        self::AFTERCARE_MANAGE,
        self::REFERENCE_DATA_MANAGE,
        self::EXPORT,
    ];
}