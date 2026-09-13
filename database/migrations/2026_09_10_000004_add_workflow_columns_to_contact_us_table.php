<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_us', function (Blueprint $table) {
            $table->string('reference', 32)->nullable()->unique()->after('uuid');
            $table->string('ip_address', 45)->nullable()->after('message');
            $table->foreignId('assigned_to')->nullable()->after('read_at')->constrained('users')->nullOnDelete();
            $table->text('admin_note')->nullable()->after('assigned_to');
            $table->timestamp('responded_at')->nullable()->after('admin_note');
            $table->foreignId('responded_by')->nullable()->after('responded_at')->constrained('users')->nullOnDelete();
            $table->boolean('email_sent')->default(false)->after('sms_sent_at');
            $table->timestamp('email_sent_at')->nullable()->after('email_sent');

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('contact_us', function (Blueprint $table) {
            $table->dropIndex(['status', 'created_at']);
            $table->dropConstrainedForeignId('assigned_to');
            $table->dropConstrainedForeignId('responded_by');
            $table->dropColumn(['reference', 'ip_address', 'admin_note', 'responded_at', 'email_sent', 'email_sent_at']);
        });
    }
};
