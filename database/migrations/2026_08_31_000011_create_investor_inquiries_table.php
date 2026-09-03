<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investor_inquiries', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('reference', 32)->unique();
            $table->foreignId('opportunity_id')->constrained()->restrictOnDelete();
            $table->foreignId('investor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('organization')->nullable();
            $table->string('email');
            $table->string('phone', 32)->nullable();
            $table->string('country', 2)->nullable();
            $table->string('subject')->nullable();
            $table->text('message');
            $table->string('status', 24)->default('new')->index();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('consent_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->index(['opportunity_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investor_inquiries');
    }
};
