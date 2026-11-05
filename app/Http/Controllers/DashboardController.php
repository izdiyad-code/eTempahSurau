<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingChangeRequest;
use App\Models\Deposit;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->isPublic()) {
            return redirect()->route('booker.bookings.index');
        }

        $stats = [
            'pending' => Booking::where('status', 'pending')->count(),
            'approved_upcoming' => Booking::where('status', 'approved')->whereDate('event_date', '>=', today())->count(),
            'rejected' => Booking::where('status', 'rejected')->count(),
            'total' => Booking::count(),
            'deposits_waiting' => Deposit::where('status', 'proof_submitted')->count(),
            'changes_waiting' => BookingChangeRequest::where('status', 'pending')->count(),
        ];

        $upcoming = Booking::with(['facility', 'user'])
            ->where('status', 'approved')
            ->whereDate('event_date', '>=', today())
            ->orderBy('event_date')->orderBy('start_time')
            ->limit(5)->get();

        $pendingList = Booking::with(['facility', 'user'])
            ->where('status', 'pending')
            ->orderBy('event_date')->limit(5)->get();

        return view('pic.dashboard', compact('stats', 'upcoming', 'pendingList'));
    }
}
