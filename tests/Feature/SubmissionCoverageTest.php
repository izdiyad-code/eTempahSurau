<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Equipment;
use App\Models\EventType;
use App\Models\Facility;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SubmissionCoverageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function publicUser(): User
    {
        return User::factory()->create(['role' => 'public', 'booker_type' => 'muslim']);
    }

    private function staff(string $role = 'pic'): User
    {
        return User::where('role', $role)->firstOrFail();
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'event_name' => 'FYP coverage Alpha',
            'event_type_id' => EventType::where('status', 'active')->firstOrFail()->id,
            'facility_id' => Facility::where('status', 'active')->firstOrFail()->id,
            'event_date' => now()->addDays(20)->toDateString(),
            'start_time' => '20:00', 'end_time' => '21:00', 'expected_guests' => 10,
        ], $override);
    }

    private function booking(User $user, array $override = []): Booking
    {
        $this->actingAs($user)->post('/booking', $this->payload($override))->assertRedirect()->assertSessionHasNoErrors();
        return Booking::latest('id')->firstOrFail();
    }

    public function test_profile_update_persists_and_empty_name_is_rejected(): void
    {
        $user = $this->publicUser();
        $this->actingAs($user)->get('/profile')->assertOk();
        $this->put('/profile', ['name' => 'FYP Profile Updated', 'phone' => '0100000000'])->assertSessionHasNoErrors();
        $this->get('/profile')->assertSee('FYP Profile Updated');
        $this->assertSame('FYP Profile Updated', $user->fresh()->name);
        $this->put('/profile', ['name' => '', 'phone' => '0100000000'])->assertSessionHasErrors('name');
        $this->assertSame('FYP Profile Updated', $user->fresh()->name);
    }

    public function test_login_success_message_matches_saved_malay_language(): void
    {
        $user = User::factory()->create([
            'role' => 'pic', 'preferred_language' => 'ms',
            'password' => \Illuminate\Support\Facades\Hash::make('FypTestOnly123!'),
        ]);
        $this->post('/login', ['email' => $user->email, 'password' => 'FypTestOnly123!'])
            ->assertRedirect('/dashboard')
            ->assertSessionHas('success', 'Selamat datang ke panel Setiausaha / PIC.');
        $this->get('/dashboard')->assertOk()->assertSee('Papan Pemuka Setiausaha / PIC')
            ->assertDontSee('Welcome to the Secretary / PIC panel.');
    }

    public function test_equipment_management_updates_booking_choices(): void
    {
        $this->actingAs($this->staff())->post('/pic/equipment', [
            'name_en' => 'FYP Projector', 'name_ms' => 'Projektor Ujian', 'total_quantity' => 2,
        ])->assertSessionHasNoErrors();
        $equipment = Equipment::where('name_en', 'FYP Projector')->firstOrFail();
        $this->put('/pic/equipment/' . $equipment->id, [
            'name_en' => 'FYP Projector Updated', 'name_ms' => 'Projektor Ujian', 'total_quantity' => 3, 'active' => 1,
        ])->assertSessionHasNoErrors();
        $this->assertEquals(3, $equipment->fresh()->total_quantity);
        $this->actingAs($this->publicUser())->get('/booking/create?date=' . now()->addDays(20)->toDateString())
            ->assertOk()->assertSee('FYP Projector Updated');
    }

    public function test_facility_capacity_removed_and_booking_guest_count_retained(): void
    {
        $this->actingAs($this->staff())->get('/pic/facilities')->assertOk()->assertDontSee('name="capacity"', false);
        $this->actingAs($this->publicUser())->get('/booking/create?date=' . now()->addDays(20)->toDateString())
            ->assertOk()->assertSee('name="expected_guests"', false);
    }

    public function test_booking_equipment_is_saved_and_excess_quantity_rejected(): void
    {
        $item = Equipment::firstOrFail();
        $booking = $this->booking($this->publicUser(), ['equipment' => [$item->id => 1]]);
        $this->assertSame(1, $booking->equipment()->firstOrFail()->pivot->quantity);
        $this->actingAs($this->publicUser())->post('/booking', $this->payload([
            'event_date' => now()->addDays(21)->toDateString(),
            'equipment' => [$item->id => $item->total_quantity + 1],
        ]))->assertSessionHasErrors('equipment');
        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_own_history_has_current_statuses_and_excludes_other_bookers(): void
    {
        $owner = $this->publicUser();
        $first = $this->booking($owner, ['event_name' => 'FYP Owner Pending']);
        $second = $this->booking($owner, ['event_name' => 'FYP Owner Approved', 'event_date' => now()->addDays(21)->toDateString()]);
        $second->update(['status' => 'approved']);
        $other = $this->booking($this->publicUser(), ['event_name' => 'FYP Other Private', 'event_date' => now()->addDays(22)->toDateString()]);
        // Simulate a new session when changing users; do not retain the other user's success flash.
        $this->flushSession();
        $this->actingAs($owner)->get('/my-bookings')->assertOk()
            ->assertSee($first->booking_no)->assertSee($second->booking_no)->assertDontSee($other->booking_no);
        $this->get('/my-bookings/' . $first->id)->assertSee('Pending');
        $this->get('/my-bookings/' . $second->id)->assertSee('Approved');
    }

    public function test_staff_dashboard_counts_match_known_records(): void
    {
        $booking = $this->booking($this->publicUser());
        $booking->deposit->update(['status' => 'proof_submitted']);
        foreach (['pic', 'chairman'] as $role) {
            $response = $this->actingAs($this->staff($role))->get('/dashboard')->assertOk();
            $this->assertSame(1, $response->viewData('stats')['pending']);
            $this->assertSame(1, $response->viewData('stats')['total']);
            $this->assertSame(1, $response->viewData('stats')['deposits_waiting']);
            $this->assertSame(0, $response->viewData('stats')['approved_upcoming']);
        }
    }

    public function test_calendar_hides_booker_contact_and_event_name(): void
    {
        $user = $this->publicUser();
        $booking = $this->booking($user, ['event_name' => 'PRIVATE EVENT MUST NOT LEAK']);
        auth()->logout();
        $response = $this->getJson('/calendar/events')->assertOk();
        $response->assertDontSee($user->email)->assertDontSee('PRIVATE EVENT MUST NOT LEAK')->assertDontSee($booking->booking_no);
        foreach ($response->json() as $event) {
            $this->assertSame(['title', 'start', 'end', 'color'], array_keys($event));
        }
    }

    public function test_reschedule_submission_and_approval_update_history(): void
    {
        $owner = $this->publicUser();
        $booking = $this->booking($owner);
        $date = now()->addDays(22)->toDateString();
        $this->post('/my-bookings/' . $booking->id . '/change', [
            'type' => 'reschedule', 'reason' => 'FYP change workflow',
            'new_date' => $date, 'new_start_time' => '20:00', 'new_end_time' => '21:00',
        ])->assertSessionHasNoErrors();
        $change = $booking->changeRequests()->firstOrFail();
        $this->assertSame('pending', $change->status);
        $this->actingAs($this->staff())->post('/staff/change-requests/' . $change->id, ['decision' => 'approve'])
            ->assertSessionHasNoErrors();
        $this->assertSame($date, $booking->fresh()->event_date->toDateString());
        $this->assertSame('approved', $change->fresh()->status);
        $this->assertTrue($booking->statusLogs()->where('remarks', 'like', 'Rescheduled to%')->exists());
    }

    public function test_report_combined_filters_and_csv_match_exact_record(): void
    {
        $matching = $this->booking($this->publicUser());
        $matching->update(['status' => 'approved']);
        $matching->deposit->update(['status' => 'verified']);
        $other = $this->booking($this->publicUser(), ['event_name' => 'FYP coverage Beta', 'event_date' => now()->addDays(21)->toDateString()]);
        $query = http_build_query([
            'q' => 'Alpha', 'facility_id' => $matching->facility_id,
            'event_type_id' => $matching->event_type_id, 'status' => 'approved',
            'from' => $matching->event_date->toDateString(), 'to' => $matching->event_date->toDateString(),
        ]);
        $response = $this->actingAs($this->staff())->get('/staff/reports?' . $query)->assertOk();
        $this->assertSame([$matching->id], $response->viewData('bookings')->pluck('id')->all());
        $this->assertEquals(10, $response->viewData('summary')['deposit_verified']);
        $csv = $this->get('/staff/reports/export?' . $query)->assertOk()->streamedContent();
        $lines = preg_split('/\r?\n/', trim($csv));
        $this->assertCount(2, $lines);
        $row = str_getcsv($lines[1]);
        $this->assertSame($matching->booking_no, $row[0]);
        $this->assertSame('approved', $row[9]);
        $this->assertSame('10.00', $row[10]);
        $this->assertSame('verified', $row[11]);
        $this->assertStringNotContainsString($other->booking_no, $csv);
        $this->assertCount(2, $this->get('/staff/reports')->viewData('bookings'));
    }

    public function test_all_report_sort_options_work_in_both_directions(): void
    {
        $a = $this->booking($this->publicUser(), ['event_name' => 'Alpha', 'expected_guests' => 5]);
        $b = $this->booking($this->publicUser(), ['event_name' => 'Zulu', 'expected_guests' => 20,
            'facility_id' => Facility::orderBy('id')->skip(1)->firstOrFail()->id,
            'event_type_id' => EventType::orderBy('id')->skip(1)->firstOrFail()->id,
            'event_date' => now()->addDays(21)->toDateString()]);
        $b->update(['status' => 'approved']);
        $this->actingAs($this->staff());
        $keys = [
            'date' => fn ($x) => $x->event_date->toDateString(),
            'facility' => fn ($x) => $x->facility->name_en,
            'event_type' => fn ($x) => $x->eventType->name_en,
            'event' => fn ($x) => $x->event_name,
            'booker' => fn ($x) => $x->user->name,
            'status' => fn ($x) => $x->status,
            'guests' => fn ($x) => $x->expected_guests,
            'ref' => fn ($x) => $x->booking_no,
        ];
        foreach (['date', 'facility', 'event_type', 'event', 'booker', 'status', 'guests', 'ref'] as $sort) {
            foreach (['asc', 'desc'] as $dir) {
                $result = $this->get('/staff/reports?sort=' . $sort . '&dir=' . $dir)->assertOk();
                $expected = [$a, $b];
                usort($expected, fn ($left, $right) => ($keys[$sort]($left) <=> $keys[$sort]($right)) * ($dir === 'asc' ? 1 : -1));
                $this->assertSame(array_map(fn ($x) => $x->id, $expected), $result->viewData('bookings')->pluck('id')->all());
            }
        }
        foreach (['event', 'guests', 'date'] as $sort) {
            $asc = $this->get('/staff/reports?sort=' . $sort . '&dir=asc')->viewData('bookings')->pluck('id')->all();
            $desc = $this->get('/staff/reports?sort=' . $sort . '&dir=desc')->viewData('bookings')->pluck('id')->all();
            $this->assertSame([$a->id, $b->id], $asc);
            $this->assertSame([$b->id, $a->id], $desc);
        }
    }

    public function test_facility_image_upload_and_replacement_use_test_only_directory(): void
    {
        $this->withoutExceptionHandling();
        $testPublic = storage_path('framework/testing/fyp-public');
        $this->app->usePublicPath($testPublic);
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
        $data = ['name_en' => 'FYP Image Hall', 'name_ms' => 'Dewan Gambar Ujian', 'deposit_amount' => 10];
        $this->actingAs($this->staff())->post('/pic/facilities', $data + [
            'image' => UploadedFile::fake()->createWithContent('fixture.png', $png),
        ])->assertRedirect()->assertSessionHasNoErrors();
        $facility = Facility::where('name_en', 'FYP Image Hall')->firstOrFail();
        $old = $facility->image;
        $this->assertFileExists($testPublic . '/images/facilities/' . $old);
        $this->put('/pic/facilities/' . $facility->id, $data + ['active' => 1,
            'image' => UploadedFile::fake()->createWithContent('replacement.png', $png),
        ])->assertSessionHasNoErrors();
        $this->assertNotSame($old, $facility->fresh()->image);
        $this->assertFileDoesNotExist($testPublic . '/images/facilities/' . $old);
        $this->assertFileExists($testPublic . '/images/facilities/' . $facility->fresh()->image);
        auth()->logout();
        $this->get('/')->assertOk()->assertSee($facility->fresh()->image);
    }
}
