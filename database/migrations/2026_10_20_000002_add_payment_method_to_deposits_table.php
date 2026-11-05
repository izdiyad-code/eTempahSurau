<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deposits', function (Blueprint $table) {
            // QR transfer or cash handed to the PIC
            $table->enum('payment_method', ['qr', 'cash'])->nullable()->after('amount');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->index(['facility_id', 'event_date']);
        });
    }

    public function down(): void
    {
        Schema::table('deposits', function (Blueprint $table) {
            $table->dropColumn('payment_method');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['facility_id', 'event_date']);
        });
    }
};
