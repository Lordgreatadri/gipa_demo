<?php

return [
    'default_system_user' => [
        'name' => env('DEFAULT_SYSTEM_USER_NAME'),
        'email' => env('DEFAULT_SYSTEM_USER_EMAIL'),
        'password' => env('DEFAULT_SYSTEM_USER_PASSWORD'),
    ],

    'demo_users' => [
        'staff_password' => env('DEMO_STAFF_PASSWORD'),
        'investor_password' => env('DEMO_INVESTOR_PASSWORD'),
        'investor_email_pattern' => env('DEMO_INVESTOR_EMAIL_PATTERN'),
        'investor_count' => (int) env('DEMO_INVESTOR_COUNT', 150),
        'roles' => [
            ['role' => 'Super Administrator', 'name' => env('DEMO_SUPER_ADMIN_NAME'), 'email' => env('DEMO_SUPER_ADMIN_EMAIL'), 'type' => 'staff'],
            ['role' => 'Content / Data Manager', 'name' => env('DEMO_CONTENT_MANAGER_NAME'), 'email' => env('DEMO_CONTENT_MANAGER_EMAIL'), 'type' => 'staff'],
            ['role' => 'District Officer', 'name' => env('DEMO_DISTRICT_OFFICER_NAME'), 'email' => env('DEMO_DISTRICT_OFFICER_EMAIL'), 'type' => 'staff'],
            ['role' => 'Field Agent', 'name' => env('DEMO_FIELD_AGENT_NAME'), 'email' => env('DEMO_FIELD_AGENT_EMAIL'), 'type' => 'staff'],
            ['role' => 'Reviewer / Approver', 'name' => env('DEMO_REVIEWER_NAME'), 'email' => env('DEMO_REVIEWER_EMAIL'), 'type' => 'staff'],
            ['role' => 'Investor', 'name' => env('DEMO_INVESTOR_NAME'), 'email' => env('DEMO_INVESTOR_EMAIL'), 'type' => 'investor'],
        ],
    ],

    'workflow' => [
        'district_review_hours' => (int) env('DISTRICT_REVIEW_SLA_HOURS', 72),
        'opportunity_review_hours' => (int) env('OPPORTUNITY_REVIEW_SLA_HOURS', 72),
        'investor_onboarding_review_hours' => (int) env('INVESTOR_ONBOARDING_REVIEW_SLA_HOURS', 72),
    ],
];
