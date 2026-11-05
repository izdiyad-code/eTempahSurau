<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>eTempahSurau - Booking Report</title>
<style>
body{font-family:Arial,sans-serif;color:#1f2937;margin:28px;font-size:12px}h1{color:#14532d;margin:0 0 5px}.muted{color:#64748b;margin-bottom:18px}.summary{display:flex;gap:12px;flex-wrap:wrap;margin:18px 0}.box{border:1px solid #cbd5e1;border-radius:8px;padding:10px 14px;min-width:135px}.box strong{display:block;font-size:20px;color:#14532d}table{border-collapse:collapse;width:100%;margin-top:18px}th,td{border:1px solid #d1d5db;padding:7px;text-align:left}th{background:#f0fdf4;color:#14532d}.footer{margin-top:20px;color:#64748b}@media print{button{display:none}body{margin:10mm}}
</style></head><body>
<h1>e-TempahSurau - Booking Report</h1>
<div class="muted">Generated: {{ $printedAt->format('d M Y, h:i A') }} · Total records: {{ $summary['total'] }}</div>
<div class="summary"><div class="box"><strong>{{ $summary['total'] }}</strong>Total bookings</div><div class="box"><strong>{{ $summary['upcoming'] }}</strong>Upcoming approved</div><div class="box"><strong>RM{{ number_format($summary['deposit_verified'],2) }}</strong>Verified / settled deposit</div><div class="box"><strong>RM{{ number_format($summary['forfeited'],2) }}</strong>Forfeited deposit</div></div>
<table><thead><tr><th>Reference</th><th>Event</th><th>Booker</th><th>Facility</th><th>Date</th><th>Time</th><th>Guests</th><th>Status</th><th>Deposit</th></tr></thead><tbody>
@forelse($bookings as $booking)<tr><td>{{ $booking->booking_no }}</td><td>{{ $booking->event_name }}</td><td>{{ $booking->user?->name }}</td><td>{{ $booking->facility?->localized_name }}</td><td>{{ $booking->event_date->format('d M Y') }}</td><td>{{ substr($booking->start_time,0,5) }}–{{ substr($booking->end_time,0,5) }}</td><td>{{ $booking->expected_guests }}</td><td>{{ ucfirst($booking->status) }}</td><td>@if($booking->deposit)RM{{ number_format($booking->deposit->amount,2) }} ({{ str_replace('_',' ',$booking->deposit->status) }})@else—@endif</td></tr>@empty<tr><td colspan="9">No bookings match the selected filters.</td></tr>@endforelse
</tbody></table>
<div class="footer">Madrasah Seksyen 11, Shah Alam · e-TempahSurau</div>
<script src="{{ asset('js/ets-language.js') }}"></script>
<script>window.addEventListener('load',()=>window.print());</script>
</body></html>
