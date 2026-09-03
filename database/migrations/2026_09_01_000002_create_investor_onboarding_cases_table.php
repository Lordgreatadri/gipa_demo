<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investor_onboarding_cases', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('reference', 32)->unique();
            $table->foreignId('investor_profile_id')->constrained()->restrictOnDelete();
            $table->string('case_type', 32)->default('initial_onboarding');
            $table->string('status', 24)->default('draft');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('review_started_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamp('sla_due_at')->nullable();
            $table->text('decision_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->index(['status', 'sla_due_at']);
            $table->index(['assigned_to', 'status', 'updated_at']);
            $table->index(['investor_profile_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investor_onboarding_cases');
    }
};
