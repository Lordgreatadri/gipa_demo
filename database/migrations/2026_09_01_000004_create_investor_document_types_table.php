<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investor_document_types', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('applies_to_profile_type', 40)->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('requires_expiry')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'is_required', 'sort_order'], 'kyc_document_requirement_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investor_document_types');
    }
};
