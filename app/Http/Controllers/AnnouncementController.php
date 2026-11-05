<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\Request;

/**
 * Announcements / events posted by the PIC or Chairman (FR12).
 * Everyone can read them; logged-in users also see their personal booking notifications here.
 */
class AnnouncementController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $tab = ($user && $request->query('tab') === 'notifications') ? 'notifications' : 'announcements';

        $seenAt = $user ? ($user->announcements_seen_at ?? $user->created_at) : null;

        $announcements = Announcement::with('author')->latest()->paginate(8)->withQueryString();

        $notifications = $tab === 'notifications'
            ? $user->appNotifications()->latest()->paginate(12)->withQueryString()
            : null;

        // Opening the announcements tab marks them as read (bell count goes down).
        if ($user && $tab === 'announcements') {
            $user->forceFill(['announcements_seen_at' => now()])->save();
        }

        return view('announcements.index', compact('announcements', 'notifications', 'tab', 'seenAt'));
    }

    public function store(Request $request)
    {
        Announcement::create($this->data($request) + ['created_by' => $request->user()->id]);

        return redirect()->route('announcements.index')->with('success', __('Announcement posted.'));
    }

    public function update(Request $request, Announcement $announcement)
    {
        $announcement->update($this->data($request));

        return redirect()->route('announcements.index')->with('success', __('Announcement updated.'));
    }

    public function destroy(Announcement $announcement)
    {
        $announcement->delete();

        return back()->with('success', __('Announcement deleted.'));
    }

    private function data(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
            'title_ms' => ['nullable', 'string', 'max:255'],
            'body_ms' => ['nullable', 'string', 'max:5000'],
            'event_date' => ['nullable', 'date_format:Y-m-d'],
        ]);
    }
}
