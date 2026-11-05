<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable();
            $table->enum('role', ['public', 'pic', 'chairman'])->default('public');
            $table->enum('booker_type', ['muslim', 'non_muslim'])->nullable();
            $table->enum('preferred_language', ['ms', 'en'])->default('ms');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'role',
                'booker_type',
                'preferred_language'
            ]);
        });
    }
};