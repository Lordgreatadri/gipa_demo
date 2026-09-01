<?php

namespace App\Support;

final class WorkflowPermissions
{
    public const DISTRICT_SUBMIT = 'districts.submit';

    public const DISTRICT_REVIEW = 'districts.review';

    public const DISTRICT_REASSIGN = 'districts.reassign';

    public const OPPORTUNITY_SUBMIT = 'opportunities.submit';

    public const OPPORTUNITY_REVIEW = 'opportunities.review';

    public const OPPORTUNITY_REASSIGN = 'opportunities.reassign';

    public const OPPORTUNITY_LIFECYCLE = 'opportunities.lifecycle';

    public const ALL = [
        self::DISTRICT_SUBMIT,
        self::DISTRICT_REVIEW,
        self::DISTRICT_REASSIGN,
        self::OPPORTUNITY_SUBMIT,
        self::OPPORTUNITY_REVIEW,
        self::OPPORTUNITY_REASSIGN,
        self::OPPORTUNITY_LIFECYCLE,
    ];
}