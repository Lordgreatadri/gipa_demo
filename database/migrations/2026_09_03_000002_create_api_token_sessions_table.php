<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_token_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->char('access_jti_hash', 64)->unique();
            $table->char('refresh_token_hash', 64)->unique();
            $table->timestamp('access_expires_at')->index();
            $table->timestamp('refresh_expires_at')->index();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('revoked_at')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'revoked_at', 'refresh_expires_at'], 'api_token_user_active_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_token_sessions');
    }
};
