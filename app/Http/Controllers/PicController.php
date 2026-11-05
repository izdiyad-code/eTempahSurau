<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingChangeRequest;
use App\Models\BookingStatusLog;
use App\Models\Deposit;
use App\Models\Equipment;
use App\Models\EventType;
use App\Models\Facility;
use App\Models\ScheduleBlock;
use App\Models\User;
use App\Services\AvailabilityService;
use App\Services\BookingNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Secretary / PIC management panel (FR8, FR9, FR11) plus change-request
 * review which both PIC and Chairman may do.
 */
class PicController extends Controller
{
    /* ------------------------------------------------------------ bookings */

    public function bookings(Request $request)
    {
        $query = Booking::with(['user', 'facility', 'eventType', 'deposit']);

        $this->applyFilters($query, $request);
        $this->applySort($query, $request);

        return view('pic.bookings', [
            'bookings' => $query->paginate(12)->withQueryString(),
            'facilities' => Facility::orderBy('name_en')->get(),
        ]);
    }

    public function showBooking(Booking $booking)
    {
        $booking->load(['user', 'facility', 'eventType', 'deposit.verifier', 'equipment', 'statusLogs.changedBy', 'changeRequests']);

        return view('pic.booking-show', compact('booking'));
    }

    /** Mark an approved event as finished (after the event date). */
    public function complete(Request $request, Booking $booking)
    {
        if ($booking->status !== 'approved') {
            return back()->with('error', __('Only approved bookings can be completed.'));
        }

        if ($booking->event_date->isFuture()) {
            return back()->with('error', __('The event has not taken place yet.'));
        }

        $this->changeStatus($booking, 'completed', $request->user()->id, 'Event completed.');

        return back()->with('success', __('Booking marked as completed. You can now settle the deposit.'));
    }

    /* ------------------------------------------------------------ deposits */

    public function deposits(Request $request)
    {
        $query = Deposit::with(['booking.user', 'booking.facility', 'verifier']);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $deposits = $query->orderByRaw("CASE WHEN status = 'proof_submitted' THEN 0 ELSE 1 END")
            ->latest()->paginate(12)->withQueryString();

        return view('pic.deposits', compact('deposits'));
    }

    /** Show an uploaded receipt (served through the app so only staff can open it). */
    public function proof(Deposit $deposit)
    {
        abort_unless($deposit->payment_proof && Storage::disk('public')->exists($deposit->payment_proof), 404);

        return response()->file(Storage::disk('public')->path($deposit->payment_proof));
    }

    /** Verify the proof / cash, or send it back to the booker. */
    public function verifyDeposit(Request $request, Deposit $deposit)
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['verify', 'reject'])],
            'remarks' => ['nullable', 'string', 'max:1000', 'required_if:decision,reject'],
        ]);

        if ($deposit->status !== 'proof_submitted') {
            return back()->with('error', __('This deposit is not waiting for verification.'));
        }

        $booking = $deposit->booking()->with('user')->first();

        if ($data['decision'] === 'verify' && in_array($booking->status, ['cancelled', 'rejected'], true)) {
            throw ValidationException::withMessages([
                'decision' => __('Deposits cannot be verified for cancelled or rejected bookings.'),
            ]);
        }

        if ($data['decision'] === 'verify') {
            $deposit->update([
                'status' => 'verified',
                'verified_by' => $request->user()->id,
                'verified_at' => now(),
                'remarks' => $data['remarks'] ?? null,
            ]);

            BookingNotifier::toUser($booking->user, $booking, 'deposit_verified', 'Deposit verified',
                'Your RM:amount deposit for :ref has been verified.', true,
                ['amount' => number_format($deposit->amount, 2), 'ref' => $booking->booking_no]);
        } else {
            $deposit->update([
                'status' => 'pending',
                'remarks' => $data['remarks'],
            ]);

            BookingNotifier::toUser($booking->user, $booking, 'deposit_rejected', 'Deposit needs attention',
                'Your deposit proof for :ref was not accepted: :remarks', true,
                ['ref' => $booking->booking_no, 'remarks' => $data['remarks']]);
        }

        return back()->with('success', __('Deposit updated.'));
    }

    /** After the event: refund or forfeit (negligence, e.g. lights left on / rubbish). */
    public function settleDeposit(Request $request, Deposit $deposit)
    {
        $data = $request->validate([
            'outcome' => ['required', Rule::in(['refunded', 'partially_forfeited', 'forfeited'])],
            'forfeit_amount' => ['nullable', 'numeric', 'min:0', 'max:' . $deposit->amount],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($deposit->status !== 'verified') {
            return back()->with('error', __('Only a verified deposit can be settled.'));
        }

        if ($deposit->booking->status !== 'completed') {
            return back()->with('error', __('Mark the booking as completed first.'));
        }

        $amount = (float) $deposit->amount;
        $forfeit = match ($data['outcome']) {
            'refunded' => 0.0,
            'forfeited' => $amount,
            default => (float) ($data['forfeit_amount'] ?? 0),
        };

        if ($data['outcome'] === 'partially_forfeited' && ($forfeit <= 0 || $forfeit >= $amount)) {
            throw ValidationException::withMessages([
                'forfeit_amount' => __('Partial forfeit must be more than RM0 and less than the deposit.'),
            ]);
        }

        $deposit->update([
            'status' => $data['outcome'],
            'forfeit_amount' => $forfeit,
            'refund_amount' => $amount - $forfeit,
            'remarks' => $data['remarks'] ?? null,
        ]);

        $booking = $deposit->booking()->with('user')->first();

        BookingNotifier::toUser($booking->user, $booking, 'deposit_settled', 'Deposit settled',
            'Deposit for :ref: refund RM:refund, forfeited RM:forfeit.', true,
            ['ref' => $booking->booking_no, 'refund' => number_format($amount - $forfeit, 2), 'forfeit' => number_format($forfeit, 2)]);

        return back()->with('success', __('Deposit settled.'));
    }

    /* ----------------------------------------------------------- facilities */

    public function facilities()
    {
        return view('pic.facilities', [
            'facilities' => Facility::orderBy('name_en')->get(),
            'equipment' => Equipment::orderBy('name_en')->get(),
            'eventTypes' => EventType::orderBy('name_en')->get(),
        ]);
    }

    public function storeFacility(Request $request)
    {
        $data = $this->facilityData($request);
        $data['image'] = $this->saveFacilityImage($request, null);

        Facility::create($data + ['status' => 'active']);

        return back()->with('success', __('Facility added.'));
    }

    public function updateFacility(Request $request, Facility $facility)
    {
        $data = $this->facilityData($request);
        $data['image'] = $this->saveFacilityImage($request, $facility->image);

        $facility->update($data + [
            'status' => $request->boolean('active') ? 'active' : 'inactive',
        ]);

        return back()->with('success', __('Facility updated.'));
    }

    public function storeEventType(Request $request)
    {
        EventType::create($this->eventTypeData($request) + ['status' => 'active']);

        return back()->with('success', __('Event type added.'));
    }

    public function updateEventType(Request $request, EventType $eventType)
    {
        $eventType->update($this->eventTypeData($request) + [
            'status' => $request->boolean('active') ? 'active' : 'inactive',
        ]);

        return back()->with('success', __('Event type updated.'));
    }

    public function storeEquipment(Request $request)
    {
        Equipment::create($this->equipmentData($request) + ['status' => 'available']);

        return back()->with('success', __('Equipment added.'));
    }

    public function updateEquipment(Request $request, Equipment $equipment)
    {
        $equipment->update($this->equipmentData($request) + [
            'status' => $request->boolean('active') ? 'available' : 'unavailable',
        ]);

        return back()->with('success', __('Equipment updated.'));
    }

    /* ------------------------------------------------------ blocked schedule */

    public function blocks()
    {
        return view('pic.blocks', [
            'blocks' => ScheduleBlock::whereDate('block_date', '>=', today())
                ->orderBy('block_date')->orderBy('start_time')->paginate(15),
        ]);
    }

    public function storeBlock(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['prayer', 'friday_prayer', 'surau_programme', 'maintenance', 'manual_block'])],
            'block_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        // Warn instead of silently overlapping an existing booking.
        $clash = Booking::whereIn('status', ['pending', 'approved'])
            ->whereDate('event_date', $data['block_date'])
            ->where('start_time', '<', $data['end_time'])
            ->where('end_time', '>', $data['start_time'])
            ->first();

        if ($clash) {
            throw ValidationException::withMessages([
                'block_date' => __('This time overlaps booking :ref. Resolve that booking first.', ['ref' => $clash->booking_no]),
            ]);
        }

        ScheduleBlock::create($data + ['created_by' => $request->user()->id]);

        return back()->with('success', __('Schedule blocked.'));
    }

    public function destroyBlock(ScheduleBlock $block)
    {
        $block->delete();

        return back()->with('success', __('Block removed.'));
    }

    /* ------------------------------------------- cancel / reschedule requests */

    public function changeRequests()
    {
        $requests = BookingChangeRequest::with(['booking.facility', 'booking.user', 'user'])
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->latest()->paginate(12);

        return view('pic.change-requests', compact('requests'));
    }

    public function reviewChange(Request $request, BookingChangeRequest $changeRequest)
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'reject'])],
            'review_remarks' => ['nullable', 'string', 'max:1000', 'required_if:decision,reject'],
        ]);

        if ($changeRequest->status !== 'pending') {
            return back()->with('error', __('This request was already reviewed.'));
        }

        $booking = $changeRequest->booking()->with('user')->first();

        DB::transaction(function () use ($data, $changeRequest, $booking, $request) {
            if ($data['decision'] === 'approve') {
                if ($changeRequest->type === 'cancel') {
                    $this->changeStatus($booking, 'cancelled', $request->user()->id, 'Cancelled at booker request.');

                    // Unpaid deposit is closed; a verified one is refunded.
                    if ($booking->deposit && $booking->deposit->status === 'verified') {
                        $booking->deposit->update([
                            'status' => 'refunded',
                            'refund_amount' => $booking->deposit->amount,
                            'forfeit_amount' => 0,
                            'remarks' => __('Booking cancelled.'),
                        ]);
                    }
                } else {
                    if ($changeRequest->new_date->toDateString() < today()->toDateString()) {
                        throw ValidationException::withMessages([
                            'decision' => __('The requested date has passed. Please submit a new reschedule request.'),
                        ]);
                    }

                    $message = AvailabilityService::conflict(
                        $booking->facility_id,
                        $changeRequest->new_date->toDateString(),
                        $changeRequest->new_start_time,
                        $changeRequest->new_end_time,
                        $booking->id
                    );

                    if ($message) {
                        throw ValidationException::withMessages(['decision' => $message]);
                    }

                    $booking->update([
                        'event_date' => $changeRequest->new_date,
                        'start_time' => $changeRequest->new_start_time,
                        'end_time' => $changeRequest->new_end_time,
                    ]);

                    BookingStatusLog::create([
                        'booking_id' => $booking->id,
                        'old_status' => $booking->status,
                        'new_status' => $booking->status,
                        'changed_by' => $request->user()->id,
                        'remarks' => 'Rescheduled to ' . $changeRequest->new_date->format('d M Y') . '.',
                    ]);
                }
            }

            $changeRequest->update([
                'status' => $data['decision'] === 'approve' ? 'approved' : 'rejected',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'review_remarks' => $data['review_remarks'] ?? null,
            ]);
        });

        BookingNotifier::toUser($booking->user, $booking, 'change_reviewed',
            $data['decision'] === 'approve' ? 'Your request was approved' : 'Your request was rejected',
            'Booking :ref. :remarks', true,
            ['ref' => $booking->booking_no, 'remarks' => $data['review_remarks'] ?? '']);

        return back()->with('success', __('Request reviewed.'));
    }

    /* --------------------------------------------------------------- reports */

    public function reports(Request $request)
    {
        [$bookings, $summary] = $this->reportData($request);

        return view('pic.reports', [
            'bookings' => $bookings->take(100),
            'summary' => $summary,
            'facilities' => Facility::orderBy('name_en')->get(),
            'eventTypes' => EventType::orderBy('name_en')->get(),
        ]);
    }

    public function exportReport(Request $request)
    {
        [$bookings] = $this->reportData($request);

        return response()->streamDownload(function () use ($bookings) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Reference', 'Event', 'Event type', 'Booker', 'Facility', 'Date', 'Start', 'End', 'Guests', 'Status', 'Deposit', 'Deposit status']);

            foreach ($bookings as $b) {
                fputcsv($out, [
                    $b->booking_no, $b->event_name, $b->eventType->name_en, $b->user->name, $b->facility->name_en,
                    $b->event_date->toDateString(), substr($b->start_time, 0, 5), substr($b->end_time, 0, 5),
                    $b->expected_guests, $b->status,
                    optional($b->deposit)->amount, optional($b->deposit)->status,
                ]);
            }

            fclose($out);
        }, 'booking-report-' . now()->format('Ymd') . '.csv', ['Content-Type' => 'text/csv']);
    }

    /* --------------------------------------------------------------- helpers */

    /**
     * Shared filters for the bookings list and the reports:
     * facility, event type, status, booker, reference/event text and a date range.
     */
    private function applyFilters($query, Request $request): void
    {
        if ($v = $request->query('status')) {
            $query->where('status', $v);
        }
        if ($v = $request->query('facility_id')) {
            $query->where('facility_id', $v);
        }
        if ($v = $request->query('event_type_id')) {
            $query->where('event_type_id', $v);
        }
        if ($v = $request->query('from')) {
            $query->whereDate('event_date', '>=', $v);
        }
        if ($v = $request->query('to')) {
            $query->whereDate('event_date', '<=', $v);
        }
        if ($search = trim((string) $request->query('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('booking_no', 'like', "%{$search}%")
                  ->orWhere('event_name', 'like', "%{$search}%")
                  ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"));
            });
        }
    }

    /** Sort by any column the user clicks (whitelisted). */
    private function applySort($query, Request $request): void
    {
        $dir = $request->query('dir') === 'asc' ? 'asc' : 'desc';

        match ($request->query('sort')) {
            'facility' => $query->orderBy(
                Facility::select('name_en')->whereColumn('facilities.id', 'bookings.facility_id'), $dir),
            'booker' => $query->orderBy(
                User::select('name')->whereColumn('users.id', 'bookings.user_id'), $dir),
            'event_type' => $query->orderBy(
                EventType::select('name_en')->whereColumn('event_types.id', 'bookings.event_type_id'), $dir),
            'event' => $query->orderBy('event_name', $dir),
            'status' => $query->orderBy('status', $dir),
            'guests' => $query->orderBy('expected_guests', $dir),
            'ref' => $query->orderBy('booking_no', $dir),
            default => $query->orderBy('event_date', $request->query('sort') === 'date' ? $dir : 'desc'),
        };

        $query->orderBy('start_time');
    }

    private function reportData(Request $request): array
    {
        $query = Booking::with(['user', 'facility', 'eventType', 'deposit']);

        $this->applyFilters($query, $request);
        $this->applySort($query, $request);

        $bookings = $query->get();

        $summary = [
            'total' => $bookings->count(),
            'by_status' => $bookings->groupBy('status')->map->count(),
            'by_facility' => $bookings->groupBy(fn ($b) => $b->facility->name_en)->map(fn ($g) => [
                'count' => $g->count(),
                'guests' => $g->sum('expected_guests'),
                'deposit' => $g->filter(fn ($b) => $b->deposit)->sum(fn ($b) => $b->deposit->amount),
            ]),
            'upcoming' => $bookings->where('status', 'approved')->filter(fn ($b) => $b->event_date->isFuture() || $b->event_date->isToday())->count(),
            'deposit_verified' => $bookings->filter(fn ($b) => $b->deposit && in_array($b->deposit->status, ['verified', 'refunded', 'partially_forfeited', 'forfeited']))->sum(fn ($b) => $b->deposit->amount),
            'forfeited' => $bookings->filter(fn ($b) => $b->deposit)->sum(fn ($b) => $b->deposit->forfeit_amount),
        ];

        return [$bookings, $summary];
    }

    private function changeStatus(Booking $booking, string $new, int $userId, string $remarks): void
    {
        $old = $booking->status;
        $booking->update(['status' => $new]);

        BookingStatusLog::create([
            'booking_id' => $booking->id,
            'old_status' => $old,
            'new_status' => $new,
            'changed_by' => $userId,
            'remarks' => $remarks,
        ]);
    }

    /** Stores an uploaded facility picture in public/images/facilities (no storage:link needed). */
    private function saveFacilityImage(Request $request, ?string $current): ?string
    {
        if (!$request->hasFile('image')) {
            return $current;
        }

        $dir = public_path('images/facilities');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $file = $request->file('image');
        $name = uniqid('facility_', true) . '.' . $file->getClientOriginalExtension();
        $file->move($dir, $name);

        if ($current && is_file($dir . '/' . $current)) {
            @unlink($dir . '/' . $current);
        }

        return $name;
    }

    private function eventTypeData(Request $request): array
    {
        return $request->validate([
            'name_en' => ['required', 'string', 'max:255'],
            'name_ms' => ['required', 'string', 'max:255'],
        ]);
    }

    private function facilityData(Request $request): array
    {
        $data = $request->validate([
            'name_en' => ['required', 'string', 'max:255'],
            'name_ms' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'description_ms' => ['nullable', 'string', 'max:2000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'deposit_amount' => ['required', 'numeric', 'min:0', 'max:10000'],
        ]);

        unset($data['image']); // handled separately by saveFacilityImage()

        return $data;
    }

    private function equipmentData(Request $request): array
    {
        return $request->validate([
            'name_en' => ['required', 'string', 'max:255'],
            'name_ms' => ['required', 'string', 'max:255'],
            'total_quantity' => ['required', 'integer', 'min:0', 'max:5000'],
        ]);
    }
}
