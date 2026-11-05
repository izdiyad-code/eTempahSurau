<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Booking;
use App\Models\Facility;
use App\Models\ScheduleBlock;

class HomeController extends Controller
{
    public function index()
    {
        return view('welcome', [
            'facilities' => Facility::where('status', 'active')->orderBy('name_en')->get(),
            'announcements' => Announcement::latest()->limit(3)->get(),
        ]);
    }

    /**
     * Public calendar feed (FR1). Shows only dates/times and status -
     * no personal data of bookers is exposed to guests.
     */
    public function calendarEvents()
    {
        $events = [];

        $bookings = Booking::with('facility')
            ->whereIn('status', ['pending', 'approved'])
            ->whereDate('event_date', '>=', today()->subMonths(1))
            ->get();

        foreach ($bookings as $b) {
            $approved = $b->status === 'approved';
            $events[] = [
                'title' => ($approved ? __('Booked') : __('Pending')) . ' - ' . $b->facility->name(),
                'start' => $b->event_date->toDateString() . 'T' . $b->start_time,
                'end' => $b->event_date->toDateString() . 'T' . $b->end_time,
                'color' => $approved ? '#b91c1c' : '#d97706',
            ];
        }

        $blocks = ScheduleBlock::whereDate('block_date', '>=', today()->subMonths(1))->get();

        foreach ($blocks as $block) {
            $events[] = [
                'title' => __($block->title),
                'start' => $block->block_date->toDateString() . 'T' . $block->start_time,
                'end' => $block->block_date->toDateString() . 'T' . $block->end_time,
                'color' => '#475569',
            ];
        }

        return response()->json($events);
    }
}
