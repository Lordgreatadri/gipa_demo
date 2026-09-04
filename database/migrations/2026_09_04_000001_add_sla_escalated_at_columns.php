<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Records when an SLA breach escalation was last sent so reviewers are not
     * re-notified on every scheduler run for the same breach.
     */
    public function up(): void
    {
        foreach (['districts', 'opportunities', 'investor_onboarding_cases'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->timestamp('sla_escalated_at')->nullable()->after('sla_due_at');
            });
        }
    }

    public function down(): void
    {
        foreach (['districts', 'opportunities', 'investor_onboarding_cases'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn('sla_escalated_at');
            });
        }
    }
};
