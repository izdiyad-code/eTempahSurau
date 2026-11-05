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
        Schema::create('booking_status_logs', function (Blueprint $table) {
    $table->id();

    $table->foreignId('booking_id')
        ->constrained('bookings')
        ->cascadeOnDelete();

    $table->string('old_status')->nullable();

    $table->string('new_status');

    // User/PIC/Chairman yang buat perubahan
    $table->foreignId('changed_by')
        ->nullable()
        ->constrained('users')
        ->nullOnDelete();

    $table->text('remarks')->nullable();

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_status_logs');
    }
};
