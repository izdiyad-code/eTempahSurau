<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Announcements / events posted by the PIC or Chairman, visible to everyone.
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->string('title_ms')->nullable();
            $table->text('body_ms')->nullable();
            $table->date('event_date')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        if (!Schema::hasColumn('users', 'announcements_seen_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('announcements_seen_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('announcements_seen_at');
        });

        Schema::dropIfExists('announcements');
    }
};
