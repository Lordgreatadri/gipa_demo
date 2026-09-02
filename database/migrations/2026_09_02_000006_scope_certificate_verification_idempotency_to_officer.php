<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificate_verifications', function (Blueprint $table) {
            $table->dropUnique('certificate_verifications_idempotency_key_unique');
            $table->unique(
                ['officer_id', 'idempotency_key'],
                'certificate_verifications_officer_idempotency_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('certificate_verifications', function (Blueprint $table) {
            $table->dropUnique('certificate_verifications_officer_idempotency_unique');
            $table->unique('idempotency_key');
        });
    }
};
