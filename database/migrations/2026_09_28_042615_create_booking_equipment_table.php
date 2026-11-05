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
        Schema::create('booking_equipment', function (Blueprint $table) {
    $table->id();

    $table->foreignId('booking_id')
        ->constrained('bookings')
        ->cascadeOnDelete();

    $table->foreignId('equipment_id')
        ->constrained('equipment')
        ->restrictOnDelete();

    $table->unsignedInteger('quantity')->default(1);

    $table->timestamps();

    // Elak equipment sama dimasukkan dua kali dalam booking yang sama
    $table->unique(['booking_id', 'equipment_id']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_equipment');
    }
};
