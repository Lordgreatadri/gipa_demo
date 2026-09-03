<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opportunities', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('district_id')->constrained()->restrictOnDelete();
            $table->foreignId('sector_id')->constrained()->restrictOnDelete();
            $table->foreignId('sub_sector_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('enterprise_type_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->text('location_description')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->geometry('location', 'point', 4326)->nullable();
            $table->longText('overview');
            $table->longText('objectives')->nullable();
            $table->longText('rationale')->nullable();
            $table->longText('success_factors')->nullable();
            $table->longText('competitive_advantages')->nullable();
            $table->string('project_status', 32)->default('proposed')->index();
            $table->string('workflow_status', 32)->default('draft')->index();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sla_due_at')->nullable()->index();
            $table->text('decision_reason')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['district_id', 'workflow_status']);
            $table->index(['sector_id', 'enterprise_type_id']);
            $table->index(['latitude', 'longitude']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opportunities');
    }
};
