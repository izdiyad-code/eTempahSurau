@extends('layouts.app')
@section('title', $booking->booking_no)

@section('content')
<h1>{{ __('Booking') }} {{ $booking->booking_no }}</h1>
<p class="lead"><a href="{{ route('booker.bookings.index') }}">&larr; {{ __('Back to My Bookings') }}</a></p>

<div class="card">
    <h2>{{ __('Details') }} <span class="pill pill-{{ $booking->status }}">{{ __(ucfirst($booking->status)) }}</span></h2>
    <dl class="detail">
        <dt>{{ __('Event') }}</dt><dd>{{ $booking->event_name }} ({{ $booking->eventType->name() }})</dd>
        <dt>{{ __('Date') }}</dt><dd>{{ $booking->event_date->format('l, d F Y') }}</dd>
        <dt>{{ __('Time') }}</dt><dd>{{ substr($booking->start_time,0,5) }} - {{ substr($booking->end_time,0,5) }}</dd>
        <dt>{{ __('Facility') }}</dt><dd>{{ $booking->facility->name() }}</dd>
        <dt>{{ __('Guests') }}</dt><dd>{{ $booking->expected_guests }}</dd>
        <dt>{{ __('Equipment') }}</dt>
        <dd>
            @forelse ($booking->equipment as $item)
                {{ $item->name() }} &times; {{ $item->pivot->quantity }}@if(!$loop->last), @endif
            @empty {{ __('None') }} @endforelse
        </dd>
        @if ($booking->notes)<dt>{{ __('Notes') }}</dt><dd>{{ $booking->notes }}</dd>@endif
        @if ($booking->status === 'rejected')<dt>{{ __('Rejection reason') }}</dt><dd>{{ $booking->rejection_reason }}</dd>@endif
    </dl>
</div>

@php($deposit = $booking->deposit)

@if ($deposit && in_array($booking->status, ['pending', 'approved', 'completed']))
<div class="card">
    <h2>{{ __('Deposit: RM') }}{{ number_format($deposit->amount, 2) }}
        <span class="pill pill-{{ $deposit->status }}">{{ __(str_replace('_', ' ', $deposit->status)) }}</span>
    </h2>

    @if ($deposit->remarks && $deposit->status === 'pending')
        <div class="alert-box alert-error">{{ __('The PIC returned your proof:') }} {{ $deposit->remarks }}</div>
    @endif

    @if (in_array($deposit->status, ['verified', 'refunded', 'partially_forfeited', 'forfeited']))
        <p>
            @if ($deposit->status === 'verified')
                {{ __('Your deposit has been verified. It will be settled after your event.') }}
            @else
                {{ __('Refunded: RM') }}{{ number_format($deposit->refund_amount, 2) }} &middot;
                {{ __('Forfeited: RM') }}{{ number_format($deposit->forfeit_amount, 2) }}
                @if ($deposit->remarks) <br><span class="hint">{{ $deposit->remarks }}</span>@endif
            @endif
        </p>
    @elseif ($booking->status === 'pending')
        <p class="hint">{{ __('You can pay the deposit now or after the Chairman approves your request.') }}</p>
    @endif

    @if (in_array($deposit->status, ['pending', 'proof_submitted']) && in_array($booking->status, ['pending', 'approved']))
        @if ($deposit->status === 'proof_submitted')
            <p>{{ __('Waiting for the PIC to verify your') }} <strong>{{ strtoupper($deposit->payment_method) }}</strong> {{ __('payment.') }}
               @if ($deposit->payment_proof) <a href="{{ route('booker.deposit.proof', $booking) }}" target="_blank">{{ __('View my receipt') }}</a>@endif
            </p>
        @endif

        <form method="POST" action="{{ route('booker.deposit.submit', $booking) }}" enctype="multipart/form-data" class="form-grid">
            @csrf
            <div>
                <label for="payment_method">{{ __('How are you paying?') }}</label>
                <select id="payment_method" name="payment_method" onchange="document.getElementById('qrBox').style.display = this.value === 'qr' ? 'block' : 'none'">
                    <option value="qr" @selected(old('payment_method', $deposit->payment_method) !== 'cash')>{{ __('QR transfer (upload receipt)') }}</option>
                    <option value="cash" @selected(old('payment_method', $deposit->payment_method) === 'cash')>{{ __('Cash to the PIC') }}</option>
                </select>
            </div>

            <div id="qrBox" style="display: {{ old('payment_method', $deposit->payment_method) === 'cash' ? 'none' : 'block' }};">
                @if ($hasQr)
                    <img src="{{ asset('images/deposit-qr.png') }}" alt="{{ __('Surau payment QR code') }}" style="max-width:220px;border:1px solid #e5e7eb;border-radius:10px;">
                @else
                    <p class="hint">{{ __('The surau QR code will be shown here. Ask the PIC for the account details.') }}</p>
                @endif
                <label for="payment_proof" style="margin-top:10px;">{{ __('Upload receipt (JPG, PNG or PDF, max 4 MB)') }}</label>
                <input id="payment_proof" type="file" name="payment_proof" accept=".jpg,.jpeg,.png,.pdf">
            </div>

            <div class="full actions" style="margin-top:0;">
                <button class="btn" type="submit">{{ $deposit->status === 'proof_submitted' ? 'Update payment' : 'Submit payment' }}</button>
            </div>
        </form>
        <p class="hint">{{ __('For cash payments, hand the money to the Secretary. The PIC will mark it as received.') }}</p>
    @endif
</div>
@endif

@if (in_array($booking->status, ['pending', 'approved']))
<div class="card">
    <h2>{{ __('Cancel or change date') }}</h2>

    @if ($booking->changeRequests->where('status', 'pending')->count())
        <p>{{ __('You have a pending request. Please wait for the review.') }}</p>
    @else
        <form method="POST" action="{{ route('booker.change.store', $booking) }}" class="form-grid">
            @csrf
            <div class="full">
                <label for="type">{{ __('What do you want to do?') }}</label>
                <select id="type" name="type" onchange="document.getElementById('rescheduleBox').style.display = this.value === 'reschedule' ? 'grid' : 'none'">
                    <option value="cancel" @selected(old('type') !== 'reschedule')>{{ __('Cancel this booking') }}</option>
                    <option value="reschedule" @selected(old('type') === 'reschedule')>{{ __('Move to another date or time') }}</option>
                </select>
            </div>

            <div id="rescheduleBox" class="full form-grid" style="display: {{ old('type') === 'reschedule' ? 'grid' : 'none' }};">
                <div><label for="new_date">{{ __('New date') }}</label><input type="date" id="new_date" name="new_date" min="{{ today()->toDateString() }}" value="{{ old('new_date') }}"></div>
                <div></div>
                <div><label for="new_start_time">{{ __('New start') }}</label><input type="time" id="new_start_time" name="new_start_time" value="{{ old('new_start_time') }}"></div>
                <div><label for="new_end_time">{{ __('New end') }}</label><input type="time" id="new_end_time" name="new_end_time" value="{{ old('new_end_time') }}"></div>
            </div>

            <div class="full">
                <label for="reason">{{ __('Reason') }} <span class="req">*</span></label>
                <textarea id="reason" name="reason" required>{{ old('reason') }}</textarea>
            </div>
            <div class="full actions" style="margin-top:0;"><button class="btn btn-danger" type="submit">{{ __('Send request') }}</button></div>
        </form>
    @endif

    @foreach ($booking->changeRequests->sortByDesc('id') as $cr)
        <p class="hint">{{ __(ucfirst($cr->type)) }} {{ __('request:') }} <span class="pill pill-{{ $cr->status }}">{{ __($cr->status) }}</span>
            @if ($cr->review_remarks) &mdash; {{ $cr->review_remarks }} @endif</p>
    @endforeach
</div>
@endif

<div class="card">
    <h2>{{ __('History') }}</h2>
    <ul>
        @foreach ($booking->statusLogs->sortBy('id') as $log)
            <li>{{ $log->created_at->format('d M Y H:i') }} &mdash; {{ __(ucfirst($log->new_status)) }}
                @if ($log->remarks)<span class="hint">({{ __($log->remarks) }})</span>@endif</li>
        @endforeach
    </ul>
</div>
@endsection
