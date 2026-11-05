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
        Schema::create('bookings', function (Blueprint $table) {
    $table->id();

    // Unique booking reference
    $table->string('booking_no')->unique();

    // Booker
    $table->foreignId('user_id')
        ->constrained('users')
        ->cascadeOnDelete();

    // Facility & event type
    $table->foreignId('facility_id')
        ->constrained('facilities')
        ->restrictOnDelete();

    $table->foreignId('event_type_id')
        ->constrained('event_types')
        ->restrictOnDelete();

    // Event information
    $table->string('event_name');
    $table->date('event_date');
    $table->time('start_time');
    $table->time('end_time');
    $table->unsignedInteger('expected_guests')->default(1);

    // Booking status
    $table->enum('status', [
        'pending',
        'approved',
        'rejected',
        'cancelled',
        'completed'
    ])->default('pending');

    // Special review for certain bookings
    $table->boolean('additional_review_required')->default(false);

    // Chairman approval
    $table->foreignId('chairman_id')
        ->nullable()
        ->constrained('users')
        ->nullOnDelete();

    $table->timestamp('decision_at')->nullable();
    $table->text('rejection_reason')->nullable();

    // Extra information from the user
    $table->text('notes')->nullable();

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
