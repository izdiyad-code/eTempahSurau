<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingChangeRequest;
use App\Models\EventType;
use App\Models\Facility;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Tests\TestCase;

class BookingStateRegressionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function booking(): Booking
    {
        $booker = User::factory()->create(['role' => 'public', 'booker_type' => 'muslim']);
        $this->actingAs($booker)->post('/booking', [
            'event_name' => 'FYP isolated regression',
            'event_type_id' => EventType::where('status', 'active')->firstOrFail()->id,
            'facility_id' => Facility::where('status', 'active')->firstOrFail()->id,
            'event_date' => now()->addDays(20)->toDateString(),
            'start_time' => '20:00', 'end_time' => '21:00', 'expected_guests' => 10,
        ])->assertSessionHasNoErrors();

        return Booking::firstOrFail();
    }

    private function cannotVerify(string $status): void
    {
        $booking = $this->booking();
        $booking->update(['status' => $status]);
        $deposit = $booking->deposit;
        $deposit->update(['status' => 'proof_submitted', 'payment_method' => 'cash']);

        $this->actingAs(User::where('role', 'pic')->firstOrFail())
            ->post(route('pic.deposits.verify', $deposit), ['decision' => 'verify'])
            ->assertSessionHasErrors('decision');

        $this->assertSame('proof_submitted', $deposit->fresh()->status);
        $this->assertNull($deposit->fresh()->verified_at);
    }

    public function test_cancelled_booking_deposit_cannot_be_verified(): void
    {
        $this->cannotVerify('cancelled');
    }

    public function test_rejected_booking_deposit_cannot_be_verified(): void
    {
        $this->cannotVerify('rejected');
    }

    public function test_approved_booking_deposit_can_still_be_verified(): void
    {
        $booking = $this->booking();
        $booking->update(['status' => 'approved']);
        $deposit = $booking->deposit;
        $deposit->update(['status' => 'proof_submitted', 'payment_method' => 'cash']);
        $this->actingAs(User::where('role', 'pic')->firstOrFail())
            ->post(route('pic.deposits.verify', $deposit), ['decision' => 'verify'])
            ->assertSessionHasNoErrors();
        $this->assertSame('verified', $deposit->fresh()->status);
    }

    private function reschedule(Booking $booking, string $date): BookingChangeRequest
    {
        return BookingChangeRequest::create([
            'booking_id' => $booking->id, 'user_id' => $booking->user_id,
            'type' => 'reschedule', 'new_date' => $date,
            'new_start_time' => '20:00', 'new_end_time' => '21:00',
            'reason' => 'Isolated regression fixture', 'status' => 'pending',
        ]);
    }

    public function test_elapsed_reschedule_date_cannot_be_approved(): void
    {
        $booking = $this->booking();
        $originalDate = $booking->event_date->toDateString();
        $change = $this->reschedule($booking, now()->addDays(2)->toDateString());
        $this->travel(3)->days();

        $this->actingAs(User::where('role', 'pic')->firstOrFail())
            ->post(route('staff.changes.review', $change), ['decision' => 'approve'])
            ->assertSessionHasErrors('decision');

        $this->assertSame($originalDate, $booking->fresh()->event_date->toDateString());
        $this->assertSame('pending', $change->fresh()->status);
        $this->assertNull($change->fresh()->reviewed_at);
    }

    public function test_future_reschedule_can_still_be_approved(): void
    {
        $booking = $this->booking();
        $date = now()->addDays(7)->toDateString();
        $change = $this->reschedule($booking, $date);
        $this->actingAs(User::where('role', 'pic')->firstOrFail())
            ->post(route('staff.changes.review', $change), ['decision' => 'approve'])
            ->assertSessionHasNoErrors();
        $this->assertSame($date, $booking->fresh()->event_date->toDateString());
        $this->assertSame('approved', $change->fresh()->status);
    }
}
