<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->unique()->after('id');
            $table->string('account_type', 24)->default('investor')->index()->after('password');
            $table->string('status', 24)->default('active')->index()->after('account_type');
            $table->string('organization')->nullable()->after('name');
            $table->string('phone', 32)->nullable()->after('organization');
        });

        DB::table('users')
            ->whereNull('uuid')
            ->orderBy('id')
            ->eachById(function (object $user): void {
                DB::table('users')->where('id', $user->id)->update(['uuid' => (string) Str::uuid()]);
            });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['uuid', 'account_type', 'status', 'organization', 'phone']);
        });
    }
};