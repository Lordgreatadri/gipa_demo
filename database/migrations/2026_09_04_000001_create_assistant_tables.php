<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assistant_documents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('category', 64)->index();
            $table->string('source_type', 32)->default('manual');
            $table->text('summary')->nullable();
            $table->longText('body');
            $table->boolean('is_published')->default(true)->index();
            $table->string('checksum', 64)->nullable();
            $table->timestamp('indexed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('assistant_document_chunks', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('assistant_document_id')->constrained('assistant_documents')->cascadeOnDelete();
            $table->unsignedInteger('ordinal')->default(0);
            $table->text('content');
            $table->unsignedInteger('token_estimate')->default(0);
            $table->json('embedding')->nullable();
            $table->string('embedding_model', 64)->nullable();
            $table->timestamps();

            $table->index(['assistant_document_id', 'ordinal']);
        });

        Schema::create('assistant_conversations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('session_token', 64)->nullable()->index();
            $table->string('channel', 32)->default('public');
            $table->string('title')->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();
        });

        Schema::create('assistant_messages', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('assistant_conversation_id')->constrained('assistant_conversations')->cascadeOnDelete();
            $table->string('role', 16);
            $table->longText('content');
            $table->json('citations')->nullable();
            $table->json('tools_used')->nullable();
            $table->string('provider', 32)->nullable();
            $table->string('model', 64)->nullable();
            $table->unsignedInteger('tokens_input')->nullable();
            $table->unsignedInteger('tokens_output')->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->boolean('was_grounded')->default(false);
            $table->boolean('flagged')->default(false);
            $table->timestamps();

            $table->index(['assistant_conversation_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assistant_messages');
        Schema::dropIfExists('assistant_conversations');
        Schema::dropIfExists('assistant_document_chunks');
        Schema::dropIfExists('assistant_documents');
    }
};
