<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; color:#1f2937; line-height:1.6;">
    <h2 style="color:#14532d;">{{ $heading }}</h2>
    <p>{{ $body }}</p>

    @if ($booking)
        <table cellpadding="6" style="border-collapse:collapse;">
            <tr><td><strong>{{ __('Reference') }}</strong></td><td>{{ $booking->booking_no }}</td></tr>
            <tr><td><strong>{{ __('Event') }}</strong></td><td>{{ $booking->event_name }}</td></tr>
            <tr><td><strong>{{ __('Date') }}</strong></td><td>{{ $booking->event_date->locale(app()->getLocale())->translatedFormat('d F Y') }}</td></tr>
            <tr><td><strong>{{ __('Time') }}</strong></td><td>{{ substr($booking->start_time, 0, 5) }} - {{ substr($booking->end_time, 0, 5) }}</td></tr>
        </table>
    @endif

    <p>
        <a href="{{ url('/login') }}" style="background:#15803d;color:#fff;padding:10px 18px;border-radius:6px;text-decoration:none;">
            {{ __('Open e-TempahSurau') }}
        </a>
    </p>
    <p style="color:#64748b;font-size:13px;">Madrasah Seksyen 11, Shah Alam</p>
</body>
</html>
