<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role',
        'booker_type',
        'preferred_language',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'announcements_seen_at' => 'datetime',
        ];
    }

    /**
     * Booking yang dibuat oleh user/public booker.
     */
    public function bookings()
    {
        return $this->hasMany(Booking::class, 'user_id');
    }

    /**
     * Booking yang telah direview oleh Chairman.
     */
    public function approvedBookings()
    {
        return $this->hasMany(Booking::class, 'chairman_id');
    }

    /**
     * Deposit yang telah disahkan oleh PIC.
     */
    public function verifiedDeposits()
    {
        return $this->hasMany(Deposit::class, 'verified_by');
    }

    /**
     * Blocked schedule yang dibuat oleh PIC / Chairman.
     */
    public function scheduleBlocks()
    {
        return $this->hasMany(ScheduleBlock::class, 'created_by');
    }

    /**
     * Cancellation / reschedule request yang dibuat oleh user.
     */
    public function bookingChangeRequests()
    {
        return $this->hasMany(BookingChangeRequest::class, 'user_id');
    }

    /**
     * Cancellation / reschedule request yang direview oleh staff.
     */
    public function reviewedChangeRequests()
    {
        return $this->hasMany(BookingChangeRequest::class, 'reviewed_by');
    }

    /**
     * Booking status log yang diubah oleh user / staff.
     */
    public function bookingStatusLogs()
    {
        return $this->hasMany(BookingStatusLog::class, 'changed_by');
    }

    public function appNotifications()
    {
        return $this->hasMany(AppNotification::class);
    }

    /** Announcements posted since the user last opened the announcements page. */
    public function unreadAnnouncementCount(): int
    {
        $since = $this->announcements_seen_at ?? $this->created_at ?? now();

        return Announcement::where('created_at', '>', $since)->count();
    }

    /** Number shown on the bell icon. */
    public function bellCount(): int
    {
        return $this->unreadAnnouncementCount() + $this->unreadNotificationCount();
    }

    public function unreadNotificationCount(): int
    {
        return $this->appNotifications()->whereNull('read_at')->count();
    }

    /**
     * Check sama ada user ialah Public Booker.
     */
    public function isPublic(): bool
    {
        return $this->role === 'public';
    }

    /**
     * Check sama ada user ialah PIC.
     */
    public function isPic(): bool
    {
        return $this->role === 'pic';
    }

    /**
     * Check sama ada user ialah Chairman.
     */
    public function isChairman(): bool
    {
        return $this->role === 'chairman';
    }
}