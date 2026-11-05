<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingStatusLog;
use App\Services\BookingNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChairmanController extends Controller
{
    /**
     * Display booking requests for the Chairman.
     */
    public function index(Request $request)
    {
        $this->ensureChairman($request);

        $bookings = Booking::with([
            'user',
            'facility',
            'eventType',
            'deposit',
        ])
            ->orderByRaw(
                "CASE WHEN status = 'pending' THEN 0 ELSE 1 END"
            )
            ->orderBy('event_date')
            ->paginate(10);

        return view('chairman.bookings.index', compact('bookings'));
    }

    /**
     * Approve a pending booking.
     */
    public function approve(Request $request, Booking $booking)
    {
        $this->ensureChairman($request);

        DB::transaction(function () use ($request, $booking) {
            $currentBooking = Booking::whereKey($booking->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($currentBooking->status !== 'pending') {
                throw ValidationException::withMessages([
                    'booking' =>
                        __('Only pending bookings can be approved.'),
                ]);
            }

            $oldStatus = $currentBooking->status;

            $currentBooking->status = 'approved';
            $currentBooking->chairman_id = $request->user()->id;
            $currentBooking->decision_at = now();
            $currentBooking->rejection_reason = null;
            $currentBooking->save();

            BookingStatusLog::create([
                'booking_id' => $currentBooking->id,
                'old_status' => $oldStatus,
                'new_status' => 'approved',
                'changed_by' => $request->user()->id,
                'remarks' => __('Booking approved by Chairman.'),
            ]);
        });

        $booking->refresh()->load('user');

        BookingNotifier::toUser(
            $booking->user, $booking, 'approved', 'Booking approved',
            'Your booking :ref has been approved. Please pay the deposit (QR or cash) from My Bookings.',
            true, ['ref' => $booking->booking_no]
        );
        BookingNotifier::toPics($booking, 'approved', 'Booking approved', ':ref approved by the Chairman.', ['ref' => $booking->booking_no]);

        return redirect()
            ->route('chairman.bookings.index')
            ->with('success', __('Booking approved successfully.'));
    }

    /**
     * Reject a pending booking.
     */
    public function reject(Request $request, Booking $booking)
    {
        $this->ensureChairman($request);

        $validated = $request->validate([
            'rejection_reason' => [
                'required',
                'string',
                'max:2000',
            ],
        ]);

        DB::transaction(function () use (
            $request,
            $booking,
            $validated
        ) {
            $currentBooking = Booking::whereKey($booking->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($currentBooking->status !== 'pending') {
                throw ValidationException::withMessages([
                    'booking' =>
                        __('Only pending bookings can be rejected.'),
                ]);
            }

            $oldStatus = $currentBooking->status;

            $currentBooking->status = 'rejected';
            $currentBooking->chairman_id = $request->user()->id;
            $currentBooking->decision_at = now();
            $currentBooking->rejection_reason =
                $validated['rejection_reason'];

            $currentBooking->save();

            BookingStatusLog::create([
                'booking_id' => $currentBooking->id,
                'old_status' => $oldStatus,
                'new_status' => 'rejected',
                'changed_by' => $request->user()->id,
                'remarks' => $validated['rejection_reason'],
            ]);
        });

        $booking->refresh()->load('user');

        BookingNotifier::toUser(
            $booking->user, $booking, 'rejected', 'Booking rejected',
            'Your booking :ref was rejected. Reason: :reason',
            true, ['ref' => $booking->booking_no, 'reason' => $validated['rejection_reason']]
        );

        return redirect()
            ->route('chairman.bookings.index')
            ->with('success', __('Booking rejected successfully.'));
    }

    /**
     * Allow access only to Chairman accounts.
     */
    private function ensureChairman(Request $request): void
    {
        abort_unless(
            $request->user() && $request->user()->isChairman(),
            403,
            __('Only the Chairman can manage booking approvals.')
        );
    }
}
