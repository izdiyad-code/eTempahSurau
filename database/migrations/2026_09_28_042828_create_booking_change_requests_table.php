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
        Schema::create('booking_change_requests', function (Blueprint $table) {
    $table->id();

    $table->foreignId('booking_id')
        ->constrained('bookings')
        ->cascadeOnDelete();

    $table->foreignId('user_id')
        ->constrained('users')
        ->cascadeOnDelete();

    $table->enum('type', [
        'cancel',
        'reschedule'
    ]);

    $table->date('new_date')->nullable();
    $table->time('new_start_time')->nullable();
    $table->time('new_end_time')->nullable();

    $table->text('reason');

    $table->enum('status', [
        'pending',
        'approved',
        'rejected'
    ])->default('pending');

    $table->foreignId('reviewed_by')
        ->nullable()
        ->constrained('users')
        ->nullOnDelete();

    $table->timestamp('reviewed_at')->nullable();

    $table->text('review_remarks')->nullable();

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_change_requests');
    }
};
