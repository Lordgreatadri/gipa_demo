<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investor_match_preferences', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('investor_profile_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('minimum_investment', 18, 2)->nullable();
            $table->decimal('maximum_investment', 18, 2)->nullable();
            $table->char('currency', 3)->default('GHS');
            $table->decimal('minimum_readiness_score', 5, 2)->nullable();
            $table->timestamps();

            $table->index(['currency', 'minimum_investment', 'maximum_investment'], 'investor_match_budget_index');
        });

        Schema::create('investor_match_preference_sector', function (Blueprint $table) {
            $table->foreignId('investor_match_preference_id')->constrained(indexName: 'match_sector_preference_fk')->cascadeOnDelete();
            $table->foreignId('sector_id')->constrained(indexName: 'match_sector_sector_fk')->cascadeOnDelete();
            $table->primary(['investor_match_preference_id', 'sector_id'], 'investor_match_sector_primary');
            $table->index('sector_id');
        });

        Schema::create('investor_match_preference_region', function (Blueprint $table) {
            $table->foreignId('investor_match_preference_id')->constrained(indexName: 'match_region_preference_fk')->cascadeOnDelete();
            $table->foreignId('region_id')->constrained(indexName: 'match_region_region_fk')->cascadeOnDelete();
            $table->primary(['investor_match_preference_id', 'region_id'], 'investor_match_region_primary');
            $table->index('region_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investor_match_preference_region');
        Schema::dropIfExists('investor_match_preference_sector');
        Schema::dropIfExists('investor_match_preferences');
    }
};
