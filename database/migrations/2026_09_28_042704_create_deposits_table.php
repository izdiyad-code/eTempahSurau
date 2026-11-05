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
        Schema::create('deposits', function (Blueprint $table) {
    $table->id();

    $table->foreignId('booking_id')
        ->constrained('bookings')
        ->cascadeOnDelete();

    $table->decimal('amount', 10, 2);

    // File path untuk receipt / proof of payment
    $table->string('payment_proof')->nullable();

    $table->date('payment_date')->nullable();

    $table->enum('status', [
        'pending',
        'proof_submitted',
        'verified',
        'refunded',
        'partially_forfeited',
        'forfeited'
    ])->default('pending');

    // PIC yang verify payment
    $table->foreignId('verified_by')
        ->nullable()
        ->constrained('users')
        ->nullOnDelete();

    $table->timestamp('verified_at')->nullable();

    // Selepas event
    $table->decimal('refund_amount', 10, 2)->default(0);
    $table->decimal('forfeit_amount', 10, 2)->default(0);

    $table->text('remarks')->nullable();

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deposits');
    }
};
