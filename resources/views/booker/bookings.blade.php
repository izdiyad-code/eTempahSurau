@extends('layouts.app')
@section('title', __('My Bookings'))

@section('content')
<h1>{{ __('My Bookings') }}</h1>
<p class="lead">{{ __('Track your requests, pay the deposit and ask to cancel or change a date.') }}</p>

<div class="card">
    <div class="actions" style="margin:0 0 16px;">
        <a class="btn" href="{{ route('home') }}#calendar-section">+ {{ __('New booking (choose a date)') }}</a>
    </div>

    <div class="table-wrap">
    <table>
        <thead>
            <tr><th>{{ __('Reference') }}</th><th>{{ __('Event') }}</th><th>{{ __('Date') }} &amp; {{ __('time') }}</th><th>{{ __('Facility') }}</th><th>{{ __('Status') }}</th><th>{{ __('Deposit') }}</th><th></th></tr>
        </thead>
        <tbody>
        @forelse ($bookings as $b)
            <tr>
                <td>{{ $b->booking_no }}</td>
                <td>{{ $b->event_name }}<br><span class="hint">{{ $b->eventType->name() }}</span></td>
                <td>{{ $b->event_date->format('d M Y') }}<br>{{ substr($b->start_time,0,5) }} - {{ substr($b->end_time,0,5) }}</td>
                <td>{{ $b->facility->name() }}</td>
                <td><span class="pill pill-{{ $b->status }}">{{ __(ucfirst($b->status)) }}</span></td>
                <td>
                    @if ($b->deposit)
                        {{ __('RM') }}{{ number_format($b->deposit->amount, 2) }}<br>
                        <span class="pill pill-{{ $b->deposit->status }}">{{ __(str_replace('_', ' ', $b->deposit->status)) }}</span>
                    @endif
                </td>
                <td><a class="btn btn-sm" href="{{ route('booker.bookings.show', $b) }}">{{ __('Open') }}</a></td>
            </tr>
        @empty
            <tr><td colspan="7">{{ __('You have no bookings yet. Pick a date on the calendar to start.') }}</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>

    <div class="pagination">{{ $bookings->links() }}</div>
</div>
@endsection
