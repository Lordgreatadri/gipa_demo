<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investor_documents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('investor_profile_id')->constrained()->restrictOnDelete();
            $table->foreignId('investor_onboarding_case_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('document_type_id')->constrained('investor_document_types')->restrictOnDelete();
            $table->string('status', 20)->default('quarantined');
            $table->date('issued_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->char('checksum_sha256', 64);
            $table->string('malware_scan_status', 20)->default('pending');
            $table->timestamp('malware_scanned_at')->nullable();
            $table->timestamps();

            $table->index(['investor_onboarding_case_id', 'document_type_id', 'status'], 'onboarding_document_status_index');
            $table->index(['investor_profile_id', 'status']);
            $table->index(['status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investor_documents');
    }
};