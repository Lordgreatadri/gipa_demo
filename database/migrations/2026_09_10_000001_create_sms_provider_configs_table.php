<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_provider_configs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('provider', 40)->unique();
            $table->string('label')->nullable();
            $table->string('sender_id', 32)->nullable();
            $table->boolean('is_active')->default(false);
            $table->unsignedSmallInteger('priority')->default(100);
            // Credentials and the webhook signing secret are encrypted at rest.
            $table->text('credentials')->nullable();
            $table->text('webhook_secret')->nullable();
            $table->json('options')->nullable();
            $table->json('balance_snapshot')->nullable();
            $table->timestamp('balance_checked_at')->nullable();
            $table->timestamp('last_success_at')->nullable();
            $table->timestamp('last_failure_at')->nullable();
            $table->string('last_failure_reason')->nullable();
            $table->unsignedInteger('consecutive_failures')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_provider_configs');
    }
};
