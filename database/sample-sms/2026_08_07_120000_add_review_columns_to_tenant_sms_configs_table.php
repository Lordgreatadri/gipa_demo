<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_sms_configs', function (Blueprint $table) {
            $table->string('review_status', 20)->default('pending')->after('custom_sender_id');
            $table->text('review_reason')->nullable()->after('review_status');
            $table->foreignId('reviewed_by_user_id')->nullable()->after('review_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by_user_id');

            $table->index('review_status');
        });
    }

    public function down(): void
    {
        Schema::table('tenant_sms_configs', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by_user_id']);
            $table->dropIndex(['review_status']);
            $table->dropColumn(['review_status', 'review_reason', 'reviewed_by_user_id', 'reviewed_at']);
        });
    }
};
