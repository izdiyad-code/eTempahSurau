<?php

namespace App\Console\Commands;

use App\Models\AppNotification;
use App\Models\Booking;
use App\Services\BookingNotifier;
use Illuminate\Console\Command;

class SendBookingReminders extends Command
{
    protected $signature = 'bookings:send-reminders';

    protected $description = 'Remind bookers (and the PIC) about approved events happening tomorrow.';

    public function handle(): int
    {
        $tomorrow = now()->addDay()->toDateString();

        $bookings = Booking::with('user')
            ->where('status', 'approved')
            ->whereDate('event_date', $tomorrow)
            ->get();

        $sent = 0;

        foreach ($bookings as $booking) {
            $already = AppNotification::where('booking_id', $booking->id)
                ->where('type', 'reminder')->exists();

            if ($already) {
                continue;
            }

            BookingNotifier::toUser(
                $booking->user, $booking, 'reminder', 'Event reminder',
                'Your event ":event" is tomorrow. Please keep the surau clean and switch off all electrical appliances after use.',
                true, ['event' => $booking->event_name]
            );

            BookingNotifier::toPics($booking, 'reminder', 'Upcoming event tomorrow', ':event (:ref)', ['event' => $booking->event_name, 'ref' => $booking->booking_no]);
            $sent++;
        }

        $this->info("Reminders sent: {$sent}");

        return self::SUCCESS;
    }
}
