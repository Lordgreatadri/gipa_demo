<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('assistant:prune-conversations')->daily();

// Enforce the audit-log retention window (config/activitylog.php).
Schedule::command('activitylog:clean')->dailyAt('01:00');

// Notify assigned reviewers when items breach their SLA deadline.
Schedule::command('sla:escalate')->hourly();
