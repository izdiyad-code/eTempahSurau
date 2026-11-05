@extends('layouts.app')
@section('title', $booking->booking_no)

@section('content')
<h1>{{ $booking->booking_no }} <span class="pill pill-{{ $booking->status }}">{{ __(ucfirst($booking->status)) }}</span></h1>
<p class="lead"><a href="{{ auth()->user()->role === 'pic' ? route('pic.bookings') : route('chairman.bookings.index') }}">&larr; {{ __('Back') }}</a></p>

<div class="card">
    <h2>{{ __('Booking') }}</h2>
    <dl class="detail">
        <dt>{{ __('Event') }}</dt><dd>{{ $booking->event_name }} ({{ $booking->eventType->name() }})</dd>
        <dt>{{ __('Date / time') }}</dt><dd>{{ $booking->event_date->format('l, d F Y') }}, {{ substr($booking->start_time,0,5) }} - {{ substr($booking->end_time,0,5) }}</dd>
        <dt>{{ __('Facility') }}</dt><dd>{{ $booking->facility->name() }}</dd>
        <dt>{{ __('Guests') }}</dt><dd>{{ $booking->expected_guests }}</dd>
        <dt>{{ __('Equipment') }}</dt><dd>@forelse ($booking->equipment as $e){{ $e->name() }} &times; {{ $e->pivot->quantity }}@if(!$loop->last), @endif @empty {{ __('None') }} @endforelse</dd>
        <dt>{{ __('Booker') }}</dt><dd>{{ $booking->user->name }} &middot; {{ $booking->user->phone }} &middot; {{ $booking->user->email }}
            ({{ $booking->user->booker_type === 'non_muslim' ? 'Non-Muslim - needs additional review' : 'Muslim' }})</dd>
        @if ($booking->notes)<dt>{{ __('Notes') }}</dt><dd>{{ $booking->notes }}</dd>@endif
        @if ($booking->rejection_reason)<dt>{{ __('Rejection reason') }}</dt><dd>{{ $booking->rejection_reason }}</dd>@endif
    </dl>

    @if (auth()->user()->role === 'pic' && $booking->status === 'approved' && $booking->event_date->isPast())
        <form method="POST" action="{{ route('pic.bookings.complete', $booking) }}" class="actions">@csrf
            <button class="btn" type="submit">{{ __('Mark event as completed') }}</button>
        </form>
    @endif
</div>

@if ($booking->deposit)
<div class="card">
    <h2>{{ __('Deposit RM') }}{{ number_format($booking->deposit->amount, 2) }} <span class="pill pill-{{ $booking->deposit->status }}">{{ __(str_replace('_', ' ', $booking->deposit->status)) }}</span></h2>
    <p>{{ __('Method:') }} {{ $booking->deposit->payment_method ? strtoupper($booking->deposit->payment_method) : '-' }}
        @if ($booking->deposit->payment_proof) &middot; <a href="{{ route('pic.deposits.proof', $booking->deposit) }}" target="_blank">{{ __('View receipt') }}</a>@endif
    </p>
    @if (auth()->user()->role === 'pic')
        <p><a class="btn btn-secondary btn-sm" href="{{ route('pic.deposits', ['status' => $booking->deposit->status]) }}">{{ __('Manage in Deposits') }}</a></p>
    @endif
</div>
@endif

<div class="card">
    <h2>{{ __('Status history') }}</h2>
    <ul>
        @foreach ($booking->statusLogs->sortBy('id') as $log)
            <li>{{ $log->created_at->format('d M Y H:i') }} &mdash; {{ __(ucfirst($log->new_status)) }}
                @if ($log->changedBy) {{ __('by') }} {{ $log->changedBy->name }}@endif
                @if ($log->remarks)<span class="hint">({{ __($log->remarks) }})</span>@endif</li>
        @endforeach
    </ul>
</div>
@endsection
