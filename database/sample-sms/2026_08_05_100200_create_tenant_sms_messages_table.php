<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_sms_messages', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('tenant_id')->nullable();
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained('tenant_bulk_sms_campaigns')->nullOnDelete();
            $table->string('provider', 20);
            $table->string('context', 40)->default('transactional');
            $table->string('sender_id', 20)->nullable();
            $table->string('recipient', 30);
            $table->text('message');
            $table->unsignedSmallInteger('message_units')->default(1);
            $table->string('provider_message_id')->nullable();
            $table->string('status', 20)->default('pending');
            $table->json('meta')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->string('failure_reason')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'context']);
            $table->index(['provider', 'provider_message_id']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnUpdate()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_sms_messages');
    }
};
