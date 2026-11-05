<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Uses the `notifications` table from the SDD. Named AppNotification to avoid
 * clashing with Laravel's built-in notification classes.
 */
class AppNotification extends Model
{
    protected $table = 'notifications';

    protected $fillable = [
        'user_id', 'booking_id', 'type', 'title', 'message',
        'channel', 'read_at', 'sent_at',
    ];

    protected function casts(): array
    {
        return ['read_at' => 'datetime', 'sent_at' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    /** Create an in-app notification for one user. */
    public static function notify(int $userId, ?int $bookingId, string $type, string $title, string $message): self
    {
        return static::create([
            'user_id' => $userId,
            'booking_id' => $bookingId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'channel' => 'in_app',
            'sent_at' => now(),
        ]);
    }
}
