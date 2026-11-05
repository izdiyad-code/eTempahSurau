<?php

namespace Database\Seeders;

use App\Models\Equipment;
use App\Models\EventType;
use App\Models\Facility;
use App\Models\ScheduleBlock;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    private static array $demoPasswords = [];

    /** Per-process random fixture secrets; never fixed public login credentials. */
    public static function demoPassword(string $role): string
    {
        return self::$demoPasswords[$role] ??= bin2hex(random_bytes(24));
    }

    public function run(): void
    {
        // Demo accounts have random passwords. Set a private password before interactive use.
        $chairman = User::updateOrCreate(['email' => 'chairman@etempahsurau.test'], [
            'name' => 'Chairman Madrasah Seksyen 11',
            'phone' => '0123456789',
            'role' => 'chairman',
            'booker_type' => 'muslim',
            'preferred_language' => 'en',
            'password' => Hash::make(self::demoPassword('chairman')),
        ]);

        $pic = User::updateOrCreate(['email' => 'pic@etempahsurau.test'], [
            'name' => 'Khairul Salleh (Secretary / PIC)',
            'phone' => '0129876543',
            'role' => 'pic',
            'booker_type' => 'muslim',
            'preferred_language' => 'en',
            'password' => Hash::make(self::demoPassword('pic')),
        ]);

        User::updateOrCreate(['email' => 'booker@etempahsurau.test'], [
            'name' => 'Demo Booker',
            'phone' => '0111234567',
            'role' => 'public',
            'booker_type' => 'muslim',
            'preferred_language' => 'en',
            'password' => Hash::make(self::demoPassword('booker')),
        ]);

        // ---- Facilities ----
        $facilities = [
            ['name_ms' => 'Dewan Solat Utama', 'name_en' => 'Main Prayer Hall',
             'description' => 'Main hall for tahlil, nikah and kenduri.',
             'description_ms' => 'Dewan utama untuk tahlil, nikah dan kenduri.', 'deposit_amount' => 10],
            ['name_ms' => 'Anjung Surau', 'name_en' => 'Surau Veranda',
             'description' => 'Covered veranda for small gatherings.',
             'description_ms' => 'Anjung bertutup untuk perhimpunan kecil.', 'deposit_amount' => 10],
        ];
        foreach ($facilities as $f) {
            Facility::updateOrCreate(['name_en' => $f['name_en']], $f + ['status' => 'active']);
        }

        // ---- Event types (from the questionnaire) ----
        $types = [
            ['Nikah', 'Nikah'], ['Aqiqah', 'Aqiqah'], ['Kenduri', 'Kenduri'],
            ['Tahlil', 'Tahlil'], ['Sewa peralatan', 'Equipment rental'],
        ];
        foreach ($types as [$ms, $en]) {
            EventType::updateOrCreate(['name_en' => $en], ['name_ms' => $ms, 'status' => 'active']);
        }

        // ---- Equipment ----
        $equipment = [
            ['Mikrofon', 'Microphone', 4], ['Meja', 'Table', 20],
            ['Kerusi', 'Chair', 100], ['Pembesar suara', 'Speaker', 2],
        ];
        foreach ($equipment as [$ms, $en, $qty]) {
            Equipment::updateOrCreate(['name_en' => $en], [
                'name_ms' => $ms, 'total_quantity' => $qty, 'status' => 'available',
            ]);
        }

        // ---- Friday prayer blocks for the next 12 weeks ----
        $friday = Carbon::today()->next(Carbon::FRIDAY);
        for ($i = 0; $i < 12; $i++) {
            ScheduleBlock::firstOrCreate([
                'block_date' => $friday->copy()->addWeeks($i)->toDateString(),
                'type' => 'friday_prayer',
            ], [
                'title' => 'Friday Prayer',
                'start_time' => '12:00:00',
                'end_time' => '14:30:00',
                'reason' => 'Weekly Friday prayer.',
                'created_by' => $pic->id,
            ]);
        }
    }
}
