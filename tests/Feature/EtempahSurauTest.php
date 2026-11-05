<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Deposit;
use App\Models\Facility;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Automated evidence for Task 2 (Testing & Validation).
 * Run:  php artisan test
 * Each test name carries the matching test-case ID from the Test Case document.
 */
class EtempahSurauTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function booker(string $type = 'muslim'): User
    {
        return User::factory()->create(['role' => 'public', 'booker_type' => $type, 'phone' => '0123456789']);
    }

    private function staff(string $role): User
    {
        return User::where('role', $role)->first();
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'event_name' => 'Tahlil Arwah',
            'event_type_id' => 4,
            'facility_id' => Facility::first()->id,
            'event_date' => now()->addDays(10)->toDateString(),
            'start_time' => '20:00',
            'end_time' => '22:00',
            'expected_guests' => 30,
        ], $override);
    }

    /** TC-01 */
    public function test_tc01_guest_can_view_home_and_calendar_feed(): void
    {
        $this->get('/')->assertOk()->assertSee('e-TempahSurau');
        $this->getJson('/calendar/events')->assertOk()->assertJsonIsArray();
    }

    /** TC-02 */
    public function test_tc02_register_creates_public_booker_without_language_field(): void
    {
        $this->post('/register', [
            'name' => 'Ali Bin Abu', 'email' => 'ali@example.com', 'phone' => '0111111111',
            'booker_type' => 'muslim', 'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertRedirect(route('home'));

        $this->assertDatabaseHas('users', ['email' => 'ali@example.com', 'role' => 'public', 'preferred_language' => 'en']);
    }

    /** TC-03 */
    public function test_tc03_register_rejects_duplicate_email_and_short_password(): void
    {
        $this->post('/register', [
            'name' => 'X', 'email' => 'booker@etempahsurau.test', 'phone' => '1',
            'booker_type' => 'muslim', 'password' => 'short', 'password_confirmation' => 'short',
        ])->assertSessionHasErrors(['email', 'password']);
    }

    /** TC-04 */
    public function test_tc04_login_redirects_each_role_correctly(): void
    {
        $this->post('/login', ['email' => 'booker@etempahsurau.test', 'password' => DatabaseSeeder::demoPassword('booker')])->assertRedirect(route('home'));
        auth()->logout();
        $this->post('/login', ['email' => 'chairman@etempahsurau.test', 'password' => DatabaseSeeder::demoPassword('chairman')])->assertRedirect(route('dashboard'));
    }

    /** TC-05 */
    public function test_tc05_wrong_password_is_rejected(): void
    {
        $this->post('/login', ['email' => 'booker@etempahsurau.test', 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /** TC-06 */
    public function test_tc06_guest_is_sent_to_login_when_booking(): void
    {
        $this->get('/booking/create?date=' . now()->addDay()->toDateString())->assertRedirect(route('login'));
    }

    /** TC-07 */
    public function test_tc07_booking_form_uses_calendar_date_and_has_no_date_input(): void
    {
        $date = now()->addDays(5)->toDateString();

        $this->actingAs($this->booker())->get('/booking/create?date=' . $date)
            ->assertOk()
            ->assertSee(\Carbon\Carbon::parse($date)->format('d F Y'))
            ->assertDontSee('type="date"', false);
    }

    /** TC-08 */
    public function test_tc08_booking_form_without_date_goes_back_to_calendar(): void
    {
        $this->actingAs($this->booker())->get('/booking/create')->assertRedirect();
    }

    /** TC-09 */
    public function test_tc09_valid_booking_creates_pending_booking_and_deposit(): void
    {
        $this->actingAs($this->booker())->post('/booking', $this->payload())->assertRedirect();

        $this->assertDatabaseHas('bookings', ['event_name' => 'Tahlil Arwah', 'status' => 'pending']);
        $this->assertDatabaseCount('deposits', 1);
        $this->assertDatabaseHas('notifications', ['type' => 'new_request']); // Chairman notified
    }

    /** TC-10 */
    public function test_tc10_double_booking_is_blocked(): void
    {
        $this->actingAs($this->booker())->post('/booking', $this->payload())->assertRedirect();

        $this->actingAs($this->booker())
            ->post('/booking', $this->payload(['start_time' => '21:00', 'end_time' => '23:00']))
            ->assertSessionHasErrors('start_time');

        $this->assertDatabaseCount('bookings', 1);
    }

    /** TC-11 */
    public function test_tc11_end_time_must_be_after_start_time(): void
    {
        $this->actingAs($this->booker())
            ->post('/booking', $this->payload(['start_time' => '22:00', 'end_time' => '20:00']))
            ->assertSessionHasErrors('end_time');
    }

    /** TC-12 */
    public function test_tc12_past_date_is_rejected(): void
    {
        $this->actingAs($this->booker())
            ->post('/booking', $this->payload(['event_date' => now()->subDay()->toDateString()]))
            ->assertSessionHasErrors('event_date');
    }

    /** TC-13 */
    public function test_tc13_blocked_friday_prayer_time_cannot_be_booked(): void
    {
        $friday = now()->next(\Carbon\Carbon::FRIDAY)->toDateString();

        $this->actingAs($this->booker())
            ->post('/booking', $this->payload(['event_date' => $friday, 'start_time' => '12:30', 'end_time' => '13:30']))
            ->assertSessionHasErrors('start_time');
    }

    /** TC-14 */
    public function test_tc14_non_muslim_booking_is_flagged_for_extra_review(): void
    {
        $this->actingAs($this->booker('non_muslim'))->post('/booking', $this->payload());

        $this->assertDatabaseHas('bookings', ['additional_review_required' => true]);
    }

    /** TC-15 */
    public function test_tc15_chairman_booking_is_approved_immediately_without_deposit(): void
    {
        $this->actingAs($this->staff('chairman'))->post('/booking', $this->payload())->assertRedirect();

        $this->assertDatabaseHas('bookings', ['event_name' => 'Tahlil Arwah', 'status' => 'approved']);
        $this->assertDatabaseCount('deposits', 0);
    }

    private function makeBooking(User $booker): Booking
    {
        $this->actingAs($booker)->post('/booking', $this->payload());

        return Booking::firstOrFail();
    }

    /** TC-16 */
    public function test_tc16_chairman_can_approve_and_booker_is_notified(): void
    {
        $booker = $this->booker();
        $booking = $this->makeBooking($booker);

        $this->actingAs($this->staff('chairman'))
            ->post(route('chairman.bookings.approve', $booking))->assertRedirect();

        $this->assertSame('approved', $booking->fresh()->status);
        $this->assertDatabaseHas('notifications', ['user_id' => $booker->id, 'type' => 'approved']);
    }

    /** TC-17 */
    public function test_tc17_chairman_must_give_reason_to_reject(): void
    {
        $booking = $this->makeBooking($this->booker());

        $this->actingAs($this->staff('chairman'))
            ->post(route('chairman.bookings.reject', $booking), ['rejection_reason' => ''])
            ->assertSessionHasErrors('rejection_reason');

        $this->actingAs($this->staff('chairman'))
            ->post(route('chairman.bookings.reject', $booking), ['rejection_reason' => 'Clash with a programme.']);

        $this->assertSame('rejected', $booking->fresh()->status);
    }

    /** TC-18 */
    public function test_tc18_public_user_cannot_open_chairman_or_pic_pages(): void
    {
        $this->actingAs($this->booker());

        $this->get('/chairman/bookings')->assertForbidden();
        $this->get('/pic/deposits')->assertForbidden();
        $this->get('/staff/reports')->assertForbidden();
    }

    /** TC-19 */
    public function test_tc19_booker_cannot_see_someone_elses_booking(): void
    {
        $booking = $this->makeBooking($this->booker());

        $this->actingAs($this->booker())->get(route('booker.bookings.show', $booking))->assertForbidden();
    }

    /** TC-20 */
    public function test_tc20_booker_uploads_receipt_and_pic_verifies_deposit(): void
    {
        Storage::fake('public');

        $booker = $this->booker();
        $booking = $this->makeBooking($booker);

        $this->actingAs($booker)->post(route('booker.deposit.submit', $booking), [
            'payment_method' => 'qr',
            'payment_proof' => UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf'),
        ])->assertRedirect();

        $deposit = Deposit::firstOrFail();
        $this->assertSame('proof_submitted', $deposit->status);

        $this->actingAs($this->staff('pic'))
            ->post(route('pic.deposits.verify', $deposit), ['decision' => 'verify'])->assertRedirect();

        $this->assertSame('verified', $deposit->fresh()->status);
    }

    /** TC-21 */
    public function test_tc21_qr_payment_requires_a_receipt_file(): void
    {
        $booker = $this->booker();
        $booking = $this->makeBooking($booker);

        $this->actingAs($booker)
            ->post(route('booker.deposit.submit', $booking), ['payment_method' => 'qr'])
            ->assertSessionHasErrors('payment_proof');
    }

    /** TC-22 */
    public function test_tc22_cash_payment_needs_no_file(): void
    {
        $booker = $this->booker();
        $booking = $this->makeBooking($booker);

        $this->actingAs($booker)
            ->post(route('booker.deposit.submit', $booking), ['payment_method' => 'cash'])
            ->assertSessionHasNoErrors();

        $this->assertSame('cash', Deposit::first()->payment_method);
    }

    /** TC-23 */
    public function test_tc23_pdf_or_image_only_for_receipts(): void
    {
        Storage::fake('public');
        $booker = $this->booker();
        $booking = $this->makeBooking($booker);

        $this->actingAs($booker)->post(route('booker.deposit.submit', $booking), [
            'payment_method' => 'qr',
            'payment_proof' => UploadedFile::fake()->create('virus.exe', 10),
        ])->assertSessionHasErrors('payment_proof');
    }

    /** TC-24 */
    public function test_tc24_cancel_request_approved_frees_the_slot(): void
    {
        $booker = $this->booker();
        $booking = $this->makeBooking($booker);

        $this->actingAs($booker)->post(route('booker.change.store', $booking), ['type' => 'cancel', 'reason' => 'Family matter']);

        $change = $booking->changeRequests()->firstOrFail();

        $this->actingAs($this->staff('pic'))
            ->post(route('staff.changes.review', $change), ['decision' => 'approve']);

        $this->assertSame('cancelled', $booking->fresh()->status);

        // the same slot can now be booked again
        $this->actingAs($this->booker())->post('/booking', $this->payload())->assertSessionHasNoErrors();
    }

    /** TC-25 */
    public function test_tc25_reschedule_to_a_taken_slot_is_refused(): void
    {
        $booker = $this->booker();
        $booking = $this->makeBooking($booker);

        $this->actingAs($this->booker())->post('/booking', $this->payload([
            'event_date' => now()->addDays(12)->toDateString(),
        ]));

        $this->actingAs($booker)->post(route('booker.change.store', $booking), [
            'type' => 'reschedule', 'reason' => 'Need another day',
            'new_date' => now()->addDays(12)->toDateString(),
            'new_start_time' => '20:00', 'new_end_time' => '22:00',
        ])->assertSessionHasErrors('new_date');
    }

    /** TC-26 */
    public function test_tc26_pic_can_block_a_date_and_it_shows_on_calendar_feed(): void
    {
        $date = now()->addDays(20)->toDateString();

        $this->actingAs($this->staff('pic'))->post(route('pic.blocks.store'), [
            'title' => 'Maintenance', 'type' => 'maintenance', 'block_date' => $date,
            'start_time' => '08:00', 'end_time' => '17:00',
        ])->assertSessionHasNoErrors();

        $this->getJson('/calendar/events')->assertJsonFragment(['title' => 'Maintenance']);
    }

    /** TC-27 */
    public function test_tc27_reports_and_csv_export_work_for_staff(): void
    {
        $this->makeBooking($this->booker());

        $this->actingAs($this->staff('chairman'))->get('/staff/reports')->assertOk();
        $this->actingAs($this->staff('pic'))->get('/staff/reports/export')->assertOk();
    }

    /** TC-28 */
    public function test_tc28_deposit_settlement_after_event(): void
    {
        $booker = $this->booker();
        $booking = $this->makeBooking($booker);
        $booking->update(['status' => 'completed', 'event_date' => now()->subDay()->toDateString()]);
        $deposit = $booking->deposit;
        $deposit->update(['status' => 'verified']);

        $this->actingAs($this->staff('pic'))->post(route('pic.deposits.settle', $deposit), [
            'outcome' => 'partially_forfeited', 'forfeit_amount' => 4,
        ])->assertSessionHasNoErrors();

        $this->assertEquals(6.00, $deposit->fresh()->refund_amount);
        $this->assertEquals(4.00, $deposit->fresh()->forfeit_amount);
    }

    /** TC-29 */
    public function test_tc29_passwords_are_stored_hashed(): void
    {
        $this->assertNotSame(DatabaseSeeder::demoPassword('booker'), User::where('email', 'booker@etempahsurau.test')->value('password'));
    }

    /** TC-30 */
    public function test_tc30_reminder_command_notifies_for_tomorrows_events(): void
    {
        $booker = $this->booker();
        $this->actingAs($booker)->post('/booking', $this->payload(['event_date' => now()->addDay()->toDateString()]));
        Booking::first()->update(['status' => 'approved']);

        $this->artisan('bookings:send-reminders')->assertSuccessful();

        $this->assertDatabaseHas('notifications', ['user_id' => $booker->id, 'type' => 'reminder']);
    }

    /** TC-31 */
    public function test_tc31_booked_slot_is_visible_to_guests_on_calendar_feed(): void
    {
        $this->actingAs($this->booker())->post('/booking', $this->payload());
        auth()->logout();

        $this->getJson('/calendar/events')->assertJsonFragment(['color' => '#d97706']); // pending = amber
    }

    /** TC-32 */
    public function test_tc32_pic_sees_bookers_only_chairman_sees_pic_and_bookers(): void
    {
        $this->actingAs($this->staff('pic'))->get('/staff/users')
            ->assertOk()->assertSee('Demo Booker')->assertDontSee('Chairman Madrasah');

        $this->actingAs($this->staff('chairman'))->get('/staff/users')
            ->assertOk()->assertSee('Demo Booker')->assertSee('Secretary');
    }

    /** TC-33 */
    public function test_tc33_only_chairman_can_add_a_pic(): void
    {
        $data = ['name' => 'New PIC', 'email' => 'newpic@example.com', 'phone' => '0100000000',
                 'password' => 'password123', 'password_confirmation' => 'password123'];

        $this->actingAs($this->staff('pic'))->post(route('chairman.pics.store'), $data)->assertForbidden();

        $this->actingAs($this->staff('chairman'))->post(route('chairman.pics.store'), $data)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['email' => 'newpic@example.com', 'role' => 'pic']);
    }

    /** TC-34 */
    public function test_tc34_language_switch_changes_the_interface_to_bahasa_melayu(): void
    {
        $this->get('/')->assertSee('Check availability');

        $this->get(route('lang.switch', 'ms'))->assertRedirect();
        $this->withSession(['locale' => 'ms'])->get('/')->assertSee('Semak kekosongan');
    }

    /** TC-35 */
    public function test_tc35_report_can_be_filtered_by_facility_and_sorted(): void
    {
        $this->actingAs($this->booker())->post('/booking', $this->payload());
        $hall = Facility::where('name_en', 'Main Prayer Hall')->first();
        $veranda = Facility::where('name_en', 'Surau Veranda')->first();
        $this->actingAs($this->booker())->post('/booking', $this->payload(['facility_id' => $veranda->id, 'event_name' => 'Kenduri Veranda']));

        $this->actingAs($this->staff('chairman'))
            ->get('/staff/reports?facility_id=' . $veranda->id)
            ->assertOk()->assertSee('Kenduri Veranda')->assertDontSee('Tahlil Arwah');

        $this->actingAs($this->staff('pic'))->get('/staff/reports?sort=facility&dir=asc')->assertOk();
        $this->actingAs($this->staff('pic'))->get('/pic/bookings?sort=booker&dir=desc')->assertOk();
    }

    /** TC-36 */
    public function test_tc36_pic_can_add_and_edit_facility_and_event_type(): void
    {
        $pic = $this->staff('pic');

        $this->actingAs($pic)->post(route('pic.facilities.store'), [
            'name_en' => 'Store Room', 'name_ms' => 'Bilik Stor', 'deposit_amount' => 5,
        ])->assertSessionHasNoErrors();

        $facility = Facility::where('name_en', 'Store Room')->firstOrFail();

        $this->actingAs($pic)->put(route('pic.facilities.update', $facility), [
            'name_en' => 'Store Room', 'name_ms' => 'Bilik Stor', 'deposit_amount' => 8, 'active' => 1,
        ]);
        $this->assertEquals(8, $facility->fresh()->deposit_amount);

        $this->actingAs($pic)->post(route('pic.eventtypes.store'), ['name_en' => 'Doa Selamat', 'name_ms' => 'Doa Selamat']);
        $this->assertDatabaseHas('event_types', ['name_en' => 'Doa Selamat']);
    }

    /** TC-37 */
    public function test_tc37_pic_and_chairman_can_post_announcements_and_guests_can_read_them(): void
    {
        $this->actingAs($this->staff('pic'))->post(route('announcements.store'), [
            'title' => 'Kuliah Maghrib', 'body' => 'Every Friday after Maghrib.', 'title_ms' => 'Kuliah Maghrib', 'body_ms' => 'Setiap Jumaat selepas Maghrib.',
        ])->assertSessionHasNoErrors();

        $this->actingAs($this->staff('chairman'))->post(route('announcements.store'), [
            'title' => 'Gotong-royong', 'body' => 'Cleaning day this Saturday.',
        ])->assertSessionHasNoErrors();

        auth()->logout();

        $this->get('/announcements')->assertOk()->assertSee('Kuliah Maghrib')->assertSee('Gotong-royong');
        $this->get('/')->assertSee('Gotong-royong'); // also shown on the home page
    }

    /** TC-38 */
    public function test_tc38_public_booker_cannot_post_announcements(): void
    {
        $this->actingAs($this->booker())->post(route('announcements.store'), ['title' => 'x', 'body' => 'y'])->assertForbidden();
    }

    /** TC-39 */
    public function test_tc39_announcement_text_follows_the_selected_language(): void
    {
        $this->actingAs($this->staff('pic'))->post(route('announcements.store'), [
            'title' => 'Cleaning day', 'body' => 'Please join us.', 'title_ms' => 'Hari gotong-royong', 'body_ms' => 'Sila sertai kami.',
        ]);
        auth()->logout();

        $this->get('/announcements')->assertSee('Cleaning day')->assertDontSee('Hari gotong-royong');
        $this->withSession(['locale' => 'ms'])->get('/announcements')->assertSee('Hari gotong-royong');
    }

    /** TC-40 */
    public function test_tc40_bell_count_goes_down_after_reading_announcements(): void
    {
        $booker = $this->booker();
        // the booker registered a while ago, so a new announcement counts as unread
        $booker->forceFill(['created_at' => now()->subHour()])->saveQuietly();
        $this->actingAs($this->staff('pic'))->post(route('announcements.store'), ['title' => 'Notice', 'body' => 'Body']);

        $this->assertSame(1, $booker->fresh()->unreadAnnouncementCount());

        $this->actingAs($booker)->get('/announcements')->assertOk();

        $this->assertSame(0, $booker->fresh()->unreadAnnouncementCount());
    }

    /** TC-41 */
    public function test_tc41_facility_description_follows_the_selected_language(): void
    {
        $f = Facility::where('name_en', 'Main Prayer Hall')->first();

        $this->get('/')->assertSee($f->description);
        $this->withSession(['locale' => 'ms'])->get('/')->assertSee($f->description_ms);
    }
}
