<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\EventType;
use App\Models\Facility;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Tests\TestCase;

class AcceptanceAuditTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_malay_booking_date_and_calendar_controls_are_translated(): void
    {
        $user = User::factory()->create(['role' => 'public', 'booker_type' => 'muslim']);
        $this->actingAs($user)->withSession(['locale' => 'ms'])
            ->get('/booking/create?date=2026-11-29')->assertOk()
            ->assertSee('Ahad, 29 November 2026')->assertDontSee('Sunday, 29 November 2026')
            ->assertSee('kuantiti');
        $this->get('/')->assertOk()->assertSee('Hari ini')->assertSee('Bulan sebelumnya');
        $this->get('/profile')->assertOk()->assertSee('Penempah Awam');
    }

    public function test_logout_preserves_selected_language_on_the_guest_login_page(): void
    {
        $user = User::factory()->create(['role' => 'public', 'preferred_language' => 'ms']);
        $this->actingAs($user)->withSession(['locale' => 'ms'])->post('/logout')
            ->assertRedirect('/login')->assertSessionHas('locale', 'ms');
        $this->assertGuest();
        $this->get('/login')->assertOk()->assertSee('Anda telah berjaya log keluar.')
            ->assertSee('Log Masuk')->assertDontSee('Log in to book the surau and follow your requests.');
    }

    public function test_report_used_for_browser_print_keeps_facility_name_and_malay_labels(): void
    {
        $booker = User::factory()->create(['role' => 'public', 'booker_type' => 'muslim']);
        $facility = Facility::where('status', 'active')->firstOrFail();
        $this->actingAs($booker)->post('/booking', [
            'event_name' => 'Acceptance print check',
            'event_type_id' => EventType::where('status', 'active')->firstOrFail()->id,
            'facility_id' => $facility->id,
            'event_date' => now()->addDays(40)->toDateString(),
            'start_time' => '20:00', 'end_time' => '21:00', 'expected_guests' => 10,
        ])->assertSessionHasNoErrors();
        $booking = Booking::latest('id')->firstOrFail();
        $staff = User::where('role', 'pic')->firstOrFail();
        $this->actingAs($staff)->withSession(['locale' => 'ms'])->get('/staff/reports')
            ->assertOk()->assertSee('Laporan Tempahan')->assertSee('Ringkasan mengikut kemudahan')
            ->assertSee($facility->name_ms)->assertSee($booking->booking_no);
    }

    public function test_main_role_pages_render_in_both_languages_with_shared_footer(): void
    {
        $groups = [
            'public' => ['/', '/my-bookings', '/profile', '/announcements', '/announcements?tab=notifications', '/booking/create?date=' . now()->addDays(40)->toDateString()],
            'pic' => ['/', '/dashboard', '/pic/bookings', '/pic/deposits', '/pic/facilities', '/pic/blocks', '/staff/change-requests', '/staff/users', '/staff/reports', '/profile', '/announcements', '/announcements?tab=notifications'],
            'chairman' => ['/', '/dashboard', '/chairman/bookings', '/booking/create?date=' . now()->addDays(40)->toDateString(), '/staff/change-requests', '/staff/users', '/staff/reports', '/profile', '/announcements', '/announcements?tab=notifications'],
        ];
        foreach ($groups as $role => $paths) {
            $user = User::where('role', $role)->firstOrFail();
            foreach (['en', 'ms'] as $locale) {
                foreach ($paths as $path) {
                    $this->actingAs($user)->withSession(['locale' => $locale])->get($path)
                        ->assertOk()->assertSee('Madrasah Seksyen 11, Shah Alam')
                        ->assertSee($locale === 'ms' ? 'Sistem Pengurusan Tempahan Surau' : 'Surau Booking Management System');
                }
            }
        }
    }
}
