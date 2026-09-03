<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('districts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('region_id')->constrained()->restrictOnDelete();
            $table->string('code', 24)->unique();
            $table->string('name');
            $table->string('capital')->nullable();
            $table->text('location_description')->nullable();
            $table->geometry('boundary', 'multipolygon', 4326)->nullable();
            $table->geometry('centroid', 'point', 4326)->nullable();
            $table->decimal('readiness_score', 5, 2)->nullable();
            $table->unsignedBigInteger('population')->nullable();
            $table->decimal('area_sq_km', 12, 2)->nullable();
            $table->decimal('infrastructure_quality_score', 5, 2)->nullable();
            $table->json('economic_data')->nullable();
            $table->string('workflow_status', 24)->default('draft')->index();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sla_due_at')->nullable()->index();
            $table->text('review_reason')->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['region_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('districts');
    }
};
