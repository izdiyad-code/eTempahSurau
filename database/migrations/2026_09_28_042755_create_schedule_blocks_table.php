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
        Schema::create('schedule_blocks', function (Blueprint $table) {
    $table->id();

    $table->string('title');

    $table->enum('type', [
        'prayer',
        'friday_prayer',
        'surau_programme',
        'maintenance',
        'manual_block'
    ]);

    $table->date('block_date');

    $table->time('start_time');
    $table->time('end_time');

    $table->text('reason')->nullable();

    // PIC / Chairman yang create blocked schedule
    $table->foreignId('created_by')
        ->nullable()
        ->constrained('users')
        ->nullOnDelete();

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_blocks');
    }
};
