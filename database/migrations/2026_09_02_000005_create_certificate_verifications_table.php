<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificate_verifications', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('reference', 40)->unique();
            $table->foreignId('certificate_id')->constrained()->restrictOnDelete();
            $table->foreignId('officer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('district_id')->constrained()->restrictOnDelete();
            $table->string('system_result', 32);
            $table->string('officer_decision', 24);
            $table->text('notes')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('accuracy_metres', 8, 2)->nullable();
            $table->string('connectivity', 16)->default('online');
            $table->timestamp('registry_checked_at');
            $table->timestamp('client_recorded_at')->nullable();
            $table->string('idempotency_key', 80)->nullable()->unique();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['certificate_id', 'created_at']);
            $table->index(['officer_id', 'created_at']);
            $table->index(['district_id', 'created_at']);
            $table->index(['system_result', 'created_at']);
            $table->index(['officer_decision', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_verifications');
    }
};
