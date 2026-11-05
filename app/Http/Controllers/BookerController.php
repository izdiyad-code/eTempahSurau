<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use App\Models\Booking;
use App\Models\BookingChangeRequest;
use App\Models\BookingStatusLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Services\AvailabilityService;
use App\Services\BookingNotifier;

/**
 * Everything a Public Booker does after logging in:
 * view bookings, pay the deposit, request cancel/reschedule,
 * profile and notifications (FR5, FR6, FR7, FR8, FR12).
 */
class BookerController extends Controller
{
    public function index(Request $request)
    {
        $bookings = $request->user()->bookings()
            ->with(['facility', 'eventType', 'deposit'])
            ->orderByDesc('event_date')
            ->paginate(10);

        return view('booker.bookings', compact('bookings'));
    }

    public function show(Request $request, Booking $booking)
    {
        $this->authorizeOwner($request, $booking);

        $booking->load(['facility', 'eventType', 'deposit', 'equipment', 'changeRequests', 'statusLogs']);

        return view('booker.show', [
            'booking' => $booking,
            'hasQr' => file_exists(public_path('images/deposit-qr.png')),
        ]);
    }

    /** Upload payment proof (QR transfer) or declare cash payment. */
    public function submitDeposit(Request $request, Booking $booking)
    {
        $this->authorizeOwner($request, $booking);

        if (!in_array($booking->status, ['pending', 'approved'], true)) {
            abort(403, __('Deposit cannot be paid for this booking.'));
        }

        $deposit = $booking->deposit;

        if (!$deposit || $deposit->status === 'verified') {
            return back()->with('error', __('This deposit has already been verified.'));
        }

        $data = $request->validate([
            'payment_method' => ['required', Rule::in(['qr', 'cash'])],
            'payment_proof' => [
                Rule::requiredIf($request->input('payment_method') === 'qr' && !$deposit->payment_proof),
                'nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096',
            ],
        ], [
            'payment_proof.required' => __('Please upload your receipt for QR payment.'),
            'payment_proof.mimes' => 'The receipt must be a JPG, PNG or PDF file.',
            'payment_proof.max' => __('The receipt must not be larger than 4 MB.'),
        ]);

        $path = $deposit->payment_proof;

        if ($request->hasFile('payment_proof')) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }
            $path = $request->file('payment_proof')->store('deposit-proofs', 'public');
        }

        $deposit->update([
            'payment_method' => $data['payment_method'],
            'payment_proof' => $path,
            'payment_date' => today(),
            'status' => 'proof_submitted',
            'remarks' => null,
        ]);

        BookingNotifier::toPics(
            $booking, 'deposit_submitted', 'Deposit submitted',
            ':ref: deposit by :method is waiting for verification.',
            ['ref' => $booking->booking_no, 'method' => strtoupper($data['payment_method'])]
        );

        return back()->with('success', __('Deposit submitted. The PIC will verify it soon.'));
    }

    /** The booker may open their own receipt. */
    public function proof(Request $request, Booking $booking)
    {
        $this->authorizeOwner($request, $booking);
        $path = optional($booking->deposit)->payment_proof;

        abort_unless($path && Storage::disk('public')->exists($path), 404);

        return response()->file(Storage::disk('public')->path($path));
    }

    /** Cancellation or reschedule request (FR5). */
    public function requestChange(Request $request, Booking $booking)
    {
        $this->authorizeOwner($request, $booking);

        if (!in_array($booking->status, ['pending', 'approved'], true)) {
            return back()->with('error', __('Only pending or approved bookings can be changed.'));
        }

        if ($booking->changeRequests()->where('status', 'pending')->exists()) {
            return back()->with('error', __('You already have a pending request for this booking.'));
        }

        $data = $request->validate([
            'type' => ['required', Rule::in(['cancel', 'reschedule'])],
            'reason' => ['required', 'string', 'max:2000'],
            'new_date' => ['required_if:type,reschedule', 'nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
            'new_start_time' => ['required_if:type,reschedule', 'nullable', 'date_format:H:i'],
            'new_end_time' => ['required_if:type,reschedule', 'nullable', 'date_format:H:i', 'after:new_start_time'],
        ]);

        if ($data['type'] === 'reschedule') {
            $message = AvailabilityService::conflict(
                $booking->facility_id, $data['new_date'], $data['new_start_time'], $data['new_end_time'], $booking->id
            );

            if ($message) {
                throw ValidationException::withMessages(['new_date' => $message]);
            }
        }

        BookingChangeRequest::create([
            'booking_id' => $booking->id,
            'user_id' => $request->user()->id,
            'type' => $data['type'],
            'new_date' => $data['type'] === 'reschedule' ? $data['new_date'] : null,
            'new_start_time' => $data['type'] === 'reschedule' ? $data['new_start_time'] : null,
            'new_end_time' => $data['type'] === 'reschedule' ? $data['new_end_time'] : null,
            'reason' => $data['reason'],
            'status' => 'pending',
        ]);

        BookingNotifier::toPics($booking, 'change_request', 'Change request', $data['type'] === 'cancel' ? ':ref: booker asked to cancel.' : ':ref: booker asked to reschedule.', ['ref' => $booking->booking_no]);

        return back()->with('success', __('Your request has been sent for review.'));
    }

    /** Profile (FR6). */
    public function profile(Request $request)
    {
        return view('booker.profile', ['user' => $request->user()]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'password' => ['nullable', 'confirmed', 'min:8'],
        ]);

        $user->name = $data['name'];
        $user->phone = $data['phone'];

        if (!empty($data['password'])) {
            $user->password = $data['password']; // hashed by the model cast
        }

        $user->save();

        return back()->with('success', __('Profile updated.'));
    }

    /** Notifications for any logged-in role (FR12). */
    public function notifications(Request $request)
    {
        $notifications = $request->user()->appNotifications()->latest()->paginate(15);

        return view('partials.notifications', compact('notifications'));
    }

    public function markAllRead(Request $request)
    {
        $request->user()->appNotifications()->whereNull('read_at')->update(['read_at' => now()]);

        return back()->with('success', __('All notifications marked as read.'));
    }

    private function authorizeOwner(Request $request, Booking $booking): void
    {
        abort_unless($booking->user_id === $request->user()->id, 403, __('This is not your booking.'));
    }
}
