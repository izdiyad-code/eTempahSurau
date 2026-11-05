<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class BookingMessageMail extends Mailable
{
    public function __construct(
        public string $heading,
        public string $body,
        public ?Booking $booking = null
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: '[e-TempahSurau] ' . $this->heading);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.booking-message');
    }
}
