<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investor_profiles', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->unique()->constrained()->restrictOnDelete();
            $table->string('profile_type', 40)->default('individual');
            $table->string('display_name');
            $table->char('country_code', 2)->default('GH');
            $table->char('nationality_country_code', 2)->nullable();
            $table->string('preferred_language', 10)->default('en');
            $table->string('preferred_contact_channel', 20)->default('email');
            $table->string('onboarding_state', 24)->default('not_started');
            $table->string('status', 20)->default('active');
            $table->timestamp('last_engaged_at')->nullable();
            $table->timestamp('onboarded_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'last_engaged_at']);
            $table->index(['onboarding_state', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investor_profiles');
    }
};