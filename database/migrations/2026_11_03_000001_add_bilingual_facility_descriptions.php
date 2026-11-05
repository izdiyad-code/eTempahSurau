<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasEn = Schema::hasColumn('facilities', 'description_en');
        $hasMs = Schema::hasColumn('facilities', 'description_ms');

        Schema::table('facilities', function (Blueprint $table) use ($hasEn, $hasMs) {
            if (!$hasEn) $table->text('description_en')->nullable()->after('description');
            if (!$hasMs) $table->text('description_ms')->nullable()->after('description_en');
        });

        // Known starter records receive both descriptions. Other legacy descriptions
        // remain in the original `description` column and are preserved for editing.
        if (Schema::hasTable('facilities')) {
            DB::table('facilities')->where('name_en', 'Main Prayer Hall')->update([
                'description_en' => 'Main hall for tahlil, nikah and kenduri.',
                'description_ms' => 'Dewan utama untuk majlis tahlil, pernikahan dan kenduri.',
            ]);
            DB::table('facilities')->where('name_en', 'Surau Veranda')->update([
                'description_en' => 'Covered veranda for small gatherings.',
                'description_ms' => 'Anjung berbumbung untuk perhimpunan kecil.',
            ]);
        }
    }

    public function down(): void
    {
        $dropEn = Schema::hasColumn('facilities', 'description_en');
        $dropMs = Schema::hasColumn('facilities', 'description_ms');
        Schema::table('facilities', function (Blueprint $table) use ($dropEn, $dropMs) {
            if ($dropMs) $table->dropColumn('description_ms');
            if ($dropEn) $table->dropColumn('description_en');
        });
    }
};
