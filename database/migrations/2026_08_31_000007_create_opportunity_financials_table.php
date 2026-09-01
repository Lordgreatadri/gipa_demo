<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opportunity_financials', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('opportunity_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('investment_structure_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 20, 2)->nullable();
            $table->char('currency', 3)->default('GHS');
            $table->decimal('roi_percentage', 8, 4)->nullable();
            $table->decimal('irr_percentage', 8, 4)->nullable();
            $table->decimal('npv', 20, 2)->nullable();
            $table->unsignedInteger('payback_period_months')->nullable();
            $table->decimal('projected_revenue', 20, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opportunity_financials');
    }
};