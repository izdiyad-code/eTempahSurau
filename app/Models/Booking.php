<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_no',
        'user_id',
        'facility_id',
        'event_type_id',
        'event_name',
        'event_date',
        'start_time',
        'end_time',
        'expected_guests',
        'status',
        'additional_review_required',
        'chairman_id',
        'decision_at',
        'rejection_reason',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'decision_at' => 'datetime',
            'additional_review_required' => 'boolean',
        ];
    }

    /**
     * Public user yang membuat booking.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Facility yang ditempah.
     */
    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    /**
     * Jenis majlis / event.
     */
    public function eventType()
    {
        return $this->belongsTo(EventType::class);
    }

    /**
     * Chairman yang approve / reject booking.
     */
    public function chairman()
    {
        return $this->belongsTo(User::class, 'chairman_id');
    }

    /**
     * Equipment yang diminta untuk booking.
     */
    public function equipment()
    {
        return $this->belongsToMany(
            Equipment::class,
            'booking_equipment'
        )
        ->withPivot('quantity')
        ->withTimestamps();
    }

    /**
     * Deposit untuk booking.
     */
    public function deposit()
    {
        return $this->hasOne(Deposit::class);
    }

    /**
     * Cancellation / reschedule requests.
     */
    public function changeRequests()
    {
        return $this->hasMany(BookingChangeRequest::class);
    }

    /**
     * History perubahan status booking.
     */
    public function statusLogs()
    {
        return $this->hasMany(BookingStatusLog::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}