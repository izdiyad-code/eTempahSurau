<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingStatusLog;
use App\Models\Deposit;
use App\Models\Equipment;
use App\Models\EventType;
use App\Models\Facility;
use App\Models\ScheduleBlock;
use App\Services\AvailabilityService;
use App\Services\BookingNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    /**
     * Booking form. The date comes from the calendar click, so the form
     * no longer asks for it again (FR4).
     */
    public function create(Request $request)
    {
        $date = $request->query('date', $request->old('event_date'));

        if (!$this->isValidFutureDate($date)) {
            return redirect()
                ->to(route('home') . '#calendar-section')
                ->with('error', __('Please choose a date on the calendar first.'));
        }

        return view('bookings.create', [
            'selectedDate' => $date,
            'facilities' => Facility::where('status', 'active')->orderBy('name_en')->get(),
            'eventTypes' => EventType::where('status', 'active')->orderBy('name_en')->get(),
            'equipment' => Equipment::where('status', 'available')->orderBy('name_en')->get(),
            'takenSlots' => Booking::with('facility')
                ->whereDate('event_date', $date)
                ->whereIn('status', ['pending', 'approved'])
                ->orderBy('start_time')->get(),
            'blocks' => ScheduleBlock::whereDate('block_date', $date)->orderBy('start_time')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'event_name' => ['required', 'string', 'max:255'],
            'event_type_id' => ['required', 'integer', 'exists:event_types,id'],
            'facility_id' => ['required', 'integer', 'exists:facilities,id'],
            'event_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'expected_guests' => ['required', 'integer', 'min:1', 'max:1000'],
            'equipment' => ['nullable', 'array'],
            'equipment.*' => ['nullable', 'integer', 'min:0', 'max:500'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

        $facility = Facility::where('id', $validated['facility_id'])->where('status', 'active')->first();

        if (!$facility) {
            throw ValidationException::withMessages(['facility_id' => __('The selected facility is not available.')]);
        }

        if (!EventType::where('id', $validated['event_type_id'])->where('status', 'active')->exists()) {
            throw ValidationException::withMessages(['event_type_id' => __('The selected event type is not available.')]);
        }

        // Equipment rows with quantity > 0 only, checked against stock.
        $equipmentRows = [];
        foreach (($validated['equipment'] ?? []) as $equipmentId => $qty) {
            $qty = (int) $qty;
            if ($qty <= 0) {
                continue;
            }
            $item = Equipment::where('id', $equipmentId)->where('status', 'available')->first();
            if (!$item) {
                continue;
            }
            if ($qty > $item->total_quantity) {
                throw ValidationException::withMessages([
                    'equipment' => $item->name_en . ': only ' . $item->total_quantity . ' available.',
                ]);
            }
            $equipmentRows[$item->id] = ['quantity' => $qty];
        }

        $booking = null;
        $byChairman = $user->isChairman();

        DB::transaction(function () use ($validated, $facility, $user, $equipmentRows, &$booking, $byChairman) {
            // Lock the facility's rows for that date so two people cannot take the same slot.
            Booking::where('facility_id', $facility->id)
                ->whereDate('event_date', $validated['event_date'])
                ->lockForUpdate()->get();

            $message = AvailabilityService::conflict(
                $facility->id, $validated['event_date'], $validated['start_time'], $validated['end_time']
            );

            if ($message) {
                throw ValidationException::withMessages(['start_time' => $message]);
            }

            do {
                $bookingNo = 'ETS-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
            } while (Booking::where('booking_no', $bookingNo)->exists());

            $booking = Booking::create([
                'booking_no' => $bookingNo,
                'user_id' => $user->id,
                'facility_id' => $facility->id,
                'event_type_id' => $validated['event_type_id'],
                'event_name' => $validated['event_name'],
                'event_date' => $validated['event_date'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'expected_guests' => $validated['expected_guests'],
                // The Chairman is the approver, so his own bookings need no permission.
                'status' => $byChairman ? 'approved' : 'pending',
                'chairman_id' => $byChairman ? $user->id : null,
                'decision_at' => $byChairman ? now() : null,
                // Non-Muslim bookings get extra attention from the Chairman (BRD scope)
                'additional_review_required' => !$byChairman && $user->booker_type === 'non_muslim',
                'notes' => $validated['notes'] ?? null,
            ]);

            if ($equipmentRows) {
                $booking->equipment()->attach($equipmentRows);
            }

            if (!$byChairman) {
                Deposit::create([
                    'booking_id' => $booking->id,
                    'amount' => $facility->deposit_amount,
                    'status' => 'pending',
                ]);
            }

            BookingStatusLog::create([
                'booking_id' => $booking->id,
                'old_status' => null,
                'new_status' => $booking->status,
                'changed_by' => $user->id,
                'remarks' => $byChairman ? 'Booking added directly by the Chairman.' : 'Booking request submitted.',
            ]);
        });

        if ($byChairman) {
            BookingNotifier::toPics(
                $booking, 'chairman_booking', 'Booking added by the Chairman',
                ':event on :date (:ref).',
                ['event' => $booking->event_name, 'date' => $booking->event_date->format('d M Y'), 'ref' => $booking->booking_no]
            );

            return redirect()
                ->route('staff.bookings.show', $booking)
                ->with('success', __('Booking added and approved. Reference: :ref', ['ref' => $booking->booking_no]));
        }

        // Email + in-app notice to the Chairman (FR13), confirmation to the booker.
        BookingNotifier::toChairmen(
            $booking, 'new_request', 'New booking request',
            $booking->additional_review_required
                ? ':name requested :event on :date (needs additional review).'
                : ':name requested :event on :date.',
            ['name' => $user->name, 'event' => $booking->event_name, 'date' => $booking->event_date->format('d M Y')]
        );

        BookingNotifier::toUser(
            $user, $booking, 'submitted', 'Booking request received',
            'Your request :ref has been sent to the Chairman for review.', false, ['ref' => $booking->booking_no]
        );

        return redirect()
            ->route('booker.bookings.show', $booking)
            ->with('success', __('Booking submitted. Reference: :ref. Please wait for the Chairman\'s decision.', ['ref' => $booking->booking_no]));
    }

    private function isValidFutureDate($date): bool
    {
        return is_string($date)
            && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
            && checkdate((int) substr($date, 5, 2), (int) substr($date, 8, 2), (int) substr($date, 0, 4))
            && $date >= today()->toDateString();
    }
}
