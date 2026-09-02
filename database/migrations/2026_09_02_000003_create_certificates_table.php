<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('certificate_number', 64)->unique();
            $table->char('public_token_hash', 64)->nullable()->unique();
            $table->text('public_token')->nullable();
            $table->foreignId('certificate_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('investor_profile_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('opportunity_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('district_id')->constrained()->restrictOnDelete();
            $table->string('status', 24)->default('draft')->index();
            $table->string('holder_name_snapshot');
            $table->string('organization_name_snapshot')->nullable();
            $table->string('project_name_snapshot')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('canonicalization_version')->nullable();
            $table->json('signed_payload')->nullable();
            $table->char('payload_hash', 64)->nullable();
            $table->string('signature_algorithm', 40)->nullable();
            $table->string('signing_key_id', 80)->nullable();
            $table->text('digital_signature')->nullable();
            $table->string('artifact_status', 16)->default('pending');
            $table->string('qr_code_path')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamp('artifacts_generated_at')->nullable();
            $table->foreignId('supersedes_certificate_id')->nullable()->constrained('certificates')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->index(['district_id', 'status', 'expires_at']);
            $table->index(['investor_profile_id', 'status']);
            $table->index(['certificate_type_id', 'status']);
            $table->index('issued_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
