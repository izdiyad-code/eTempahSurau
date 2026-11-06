<?php

namespace App\Services;

use App\Mail\BookingMessageMail;
use App\Models\AppNotification;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Central place for notifications (FR12, FR13).
 * Every message is stored in-app; an email is also attempted.
 * A mail failure never breaks the booking workflow (it is logged instead).
 */
class BookingNotifier
{
    /**
     * $title and $message are translation keys; they are translated into the
     * recipient's own language (EN / BM) before saving and emailing.
     */
    public static function toUser(User $user, ?Booking $booking, string $type, string $title, string $message, bool $email = true, array $params = []): void
    {
        $locale = in_array($user->preferred_language, ['en', 'ms'], true) ? $user->preferred_language : 'en';
        $titleText = __($title, [], $locale);
        $messageText = __($message, $params, $locale);

        AppNotification::notify($user->id, $booking?->id, $type, $titleText, $messageText);

        if ($email && $user->email) {
            self::sendMail($user->email, $titleText, $messageText, $booking, $locale);
        }
    }

    /** Notify every Chairman (new request email - FR13). */
    public static function toChairmen(?Booking $booking, string $type, string $title, string $message, array $params = []): void
    {
        foreach (User::where('role', 'chairman')->get() as $chairman) {
            self::toUser($chairman, $booking, $type, $title, $message, true, $params);
        }
    }

    /** Notify every PIC (in-app only). */
    public static function toPics(?Booking $booking, string $type, string $title, string $message, array $params = []): void
    {
        foreach (User::where('role', 'pic')->get() as $pic) {
            self::toUser($pic, $booking, $type, $title, $message, false, $params);
        }
    }

    private static function sendMail(string $to, string $title, string $message, ?Booking $booking, string $locale = 'en'): void
    {
        $external = !in_array(config('mail.default'), ['array', 'log'], true);
        $allowed = array_map('strtolower', config('mail.allowed_recipients', []));
        if ($external && $allowed !== [] && !in_array(strtolower(trim($to)), $allowed, true)) {
            Log::notice('Skipped external email outside the configured demo recipient list.');
            return;
        }

        // Seeded .test accounts still receive in-app notifications, but have no
        // real mailbox. Do not send them through an external mail transport.
        if ($external && str_ends_with(strtolower(trim($to)), '.test')) {
            Log::notice('Skipped external email for a reserved .test recipient.');
            return;
        }

        try {
            Mail::to($to)->locale($locale)->send(new BookingMessageMail($title, $message, $booking));
        } catch (Throwable $e) {
            Log::warning('Email notification failed: ' . $e->getMessage());
        }
    }
}
