<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_sms_configs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('tenant_id');
            $table->string('provider', 20)->default('frog');
            $table->boolean('is_active')->default(true);
            $table->boolean('use_custom_sender_id')->default(false);
            $table->string('custom_sender_id', 16)->nullable();
            $table->timestamps();

            $table->unique('tenant_id');
            $table->index(['provider', 'is_active']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnUpdate()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_sms_configs');
    }
};
