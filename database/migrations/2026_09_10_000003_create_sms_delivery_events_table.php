<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_delivery_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sms_message_id')->nullable()->constrained('sms_messages')->cascadeOnDelete();
            $table->string('provider', 40);
            $table->string('provider_message_id')->nullable();
            $table->string('status', 24);
            $table->string('raw_status', 64)->nullable();
            $table->string('detail')->nullable();
            $table->unsignedBigInteger('webhook_call_id')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();

            $table->index(['provider', 'provider_message_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_delivery_events');
    }
};
