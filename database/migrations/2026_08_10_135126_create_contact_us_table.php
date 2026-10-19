<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contact_us', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Sender details
            $table->string('name');
            $table->string('email');
            $table->string('phone_number', 20)->nullable();
            $table->string('available_time_for_call', 255)->nullable(); // e.g. "Mornings 9am-12pm"

            // Enquiry content
            $table->string('subject');
            $table->text('message');

            // Workflow
            $table->enum('status', ['new', 'read', 'responded', 'closed'])->default('new');
            $table->string('read_by')->nullable();   // name of team member who first opened it
            $table->timestamp('read_at')->nullable();

            // Notifications
            $table->boolean('sms_sent')->default(false);
            $table->timestamp('sms_sent_at')->nullable();

            $table->timestamps();
            $table->softDeletes(); // allows "delete" without hard wiping; can be purged later
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_us');
    }
};
