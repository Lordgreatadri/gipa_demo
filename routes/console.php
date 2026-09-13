<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Spatie\WebhookClient\Models\WebhookCall;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('assistant:prune-conversations')->daily();

// Enforce the audit-log retention window (config/activitylog.php).
Schedule::command('activitylog:clean')->dailyAt('01:00');

// Notify assigned reviewers when items breach their SLA deadline.
Schedule::command('sla:escalate')->hourly();

// Nightly database and file backups, then apply the retention policy.
Schedule::command('backup:clean')->dailyAt('01:30')->withoutOverlapping();
Schedule::command('backup:run')->dailyAt('02:00')->withoutOverlapping();
Schedule::command('backup:monitor')->dailyAt('03:00');

// Discard processed provider callbacks once past the webhook retention window.
Schedule::command('model:prune', ['--model' => [WebhookCall::class]])->daily();
