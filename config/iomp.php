<?php

return [
    'default_system_user' => [
        'name' => env('DEFAULT_SYSTEM_USER_NAME'),
        'email' => env('DEFAULT_SYSTEM_USER_EMAIL'),
        'password' => env('DEFAULT_SYSTEM_USER_PASSWORD'),
    ],

    'workflow' => [
        'district_review_hours' => (int) env('DISTRICT_REVIEW_SLA_HOURS', 72),
        'opportunity_review_hours' => (int) env('OPPORTUNITY_REVIEW_SLA_HOURS', 72),
    ],
];
