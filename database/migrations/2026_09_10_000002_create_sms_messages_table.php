<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_messages', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('reference', 64)->unique();
            $table->string('provider', 40);
            $table->string('context', 40)->default('transactional');
            $table->string('sender_id', 32)->nullable();
            $table->string('recipient', 32);
            $table->string('recipient_raw', 64)->nullable();
            $table->text('body');
            $table->unsignedSmallInteger('segments')->default(1);
            $table->string('status', 24)->default('pending');
            $table->string('provider_message_id')->nullable();
            $table->string('failure_reason')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->nullableMorphs('notifiable');
            $table->json('meta')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['provider', 'provider_message_id']);
            $table->index(['context', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_messages');
    }
};
