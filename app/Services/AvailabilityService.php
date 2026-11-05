<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\ScheduleBlock;

/**
 * Single source of truth for slot availability (NFR3 - no double booking).
 */
class AvailabilityService
{
    /**
     * Return an error message if the slot cannot be used, otherwise null.
     */
    public static function conflict(int $facilityId, string $date, string $start, string $end, ?int $ignoreBookingId = null): ?string
    {
        $query = Booking::where('facility_id', $facilityId)
            ->whereDate('event_date', $date)
            ->whereIn('status', ['pending', 'approved'])
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start);

        if ($ignoreBookingId) {
            $query->where('id', '!=', $ignoreBookingId);
        }

        if ($query->exists()) {
            return __('This facility already has a booking during that time. Please choose another time.');
        }

        $blocked = ScheduleBlock::whereDate('block_date', $date)
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->first();

        if ($blocked) {
            return __('The surau is not available at that time (:title). Please choose another time.', ['title' => __($blocked->title)]);
        }

        return null;
    }
}
