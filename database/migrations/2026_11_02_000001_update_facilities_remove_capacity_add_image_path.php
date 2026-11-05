<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasCapacity = Schema::hasColumn('facilities', 'capacity');
        $hasImagePath = Schema::hasColumn('facilities', 'image_path');

        Schema::table('facilities', function (Blueprint $table) use ($hasCapacity, $hasImagePath) {
            if ($hasCapacity) $table->dropColumn('capacity');
            if (!$hasImagePath) $table->string('image_path')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        $hasCapacity = Schema::hasColumn('facilities', 'capacity');
        $hasImagePath = Schema::hasColumn('facilities', 'image_path');

        Schema::table('facilities', function (Blueprint $table) use ($hasCapacity, $hasImagePath) {
            if ($hasImagePath) $table->dropColumn('image_path');
            if (!$hasCapacity) $table->unsignedInteger('capacity')->default(0)->after('description');
        });
    }
};
