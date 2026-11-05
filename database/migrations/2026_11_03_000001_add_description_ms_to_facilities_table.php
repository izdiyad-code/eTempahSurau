<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Some databases already have this column, so only add it when it is missing.
        if (!Schema::hasColumn('facilities', 'description_ms')) {
            Schema::table('facilities', function (Blueprint $table) {
                $table->text('description_ms')->nullable()->after('description');
            });
        }

        // English wording for facilities whose description was written in Malay.
        $english = [
            'Canopy Area' => 'Outdoor area for events and tent setups.',
            'Dining Hall' => 'Dining area for events and programmes.',
            'Funeral Room' => 'Room for funeral arrangements.',
            'Learning Hall' => 'Learning space for classes.',
            'Prayer Hall' => 'Prayer hall for surau activities.',
        ];

        foreach ($english as $name => $text) {
            $row = DB::table('facilities')->where('name_en', $name)->first();

            if (!$row) {
                continue;
            }

            // Keep the existing Malay text, and put English in `description`.
            $malay = $row->description_ms ?: $row->description;

            // If a separate Malay text already exists and differs, `description` is already English: leave it.
            $alreadySeparate = $row->description_ms && $row->description && $row->description !== $row->description_ms;

            DB::table('facilities')->where('id', $row->id)->update([
                'description_ms' => $malay,
                'description' => $alreadySeparate ? $row->description : $text,
            ]);
        }

        $malayOnly = [
            'Main Prayer Hall' => 'Dewan utama untuk tahlil, nikah dan kenduri.',
            'Surau Veranda' => 'Anjung bertutup untuk perhimpunan kecil.',
        ];

        foreach ($malayOnly as $name => $text) {
            DB::table('facilities')->where('name_en', $name)->whereNull('description_ms')->update(['description_ms' => $text]);
        }
    }

    public function down(): void
    {
        // Left in place: the column may have existed before this migration.
    }
};
