@extends('layouts.app')
@section('title', __('Deposits'))

@section('content')
<h1>{{ __('Deposits') }}</h1>
<p class="lead">{{ __('Verify QR or cash payments, then settle after the event.') }}</p>

<form class="card filters" method="GET">
    <div><label for="status">{{ __('Status') }}</label>
        <select id="status" name="status" onchange="this.form.submit()">
            <option value="">{{ __('All') }}</option>
            @foreach (['pending','proof_submitted','verified','refunded','partially_forfeited','forfeited'] as $s)
                <option value="{{ $s }}" @selected(request('status') === $s)>{{ __(str_replace('_', ' ', ucfirst($s))) }}</option>
            @endforeach
        </select></div>
</form>

@forelse ($deposits as $d)
<div class="card">
    <h2>{{ $d->booking->booking_no }} &mdash; {{ __('RM') }}{{ number_format($d->amount, 2) }}
        <span class="pill pill-{{ $d->status }}">{{ __(str_replace('_', ' ', $d->status)) }}</span></h2>
    <p>{{ $d->booking->event_name }} &middot; {{ $d->booking->user->name }} ({{ $d->booking->user->phone }}) &middot;
       {{ $d->booking->event_date->format('d M Y') }} &middot; {{ __('booking') }} <span class="pill pill-{{ $d->booking->status }}">{{ __($d->booking->status) }}</span></p>
    <p>{{ __('Method:') }} {{ $d->payment_method ? strtoupper($d->payment_method) : '-' }}
        @if ($d->payment_proof) &middot; <a href="{{ route('pic.deposits.proof', $d) }}" target="_blank">{{ __('View receipt') }}</a>@endif
        @if ($d->verifier) &middot; {{ __('verified by') }} {{ $d->verifier->name }}@endif
        @if ($d->remarks) <br><span class="hint">{{ __('Remarks:') }} {{ $d->remarks }}</span>@endif
    </p>

    @if ($d->status === 'proof_submitted')
        <form method="POST" action="{{ route('pic.deposits.verify', $d) }}" class="form-grid">@csrf
            <div class="full"><label for="remarks{{ $d->id }}">{{ __('Remarks (required if returning to booker)') }}</label>
                <input id="remarks{{ $d->id }}" name="remarks"></div>
            <div class="actions" style="margin:0;">
                <button class="btn" name="decision" value="verify" type="submit">{{ __('Verify payment') }}</button>
                <button class="btn btn-danger" name="decision" value="reject" type="submit">{{ __('Return to booker') }}</button>
            </div>
        </form>
    @elseif ($d->status === 'verified' && $d->booking->status === 'completed')
        <form method="POST" action="{{ route('pic.deposits.settle', $d) }}" class="form-grid">@csrf
            <div><label for="outcome{{ $d->id }}">{{ __('After-event condition') }}</label>
                <select id="outcome{{ $d->id }}" name="outcome">
                    <option value="refunded">{{ __('All good - refund in full') }}</option>
                    <option value="partially_forfeited">{{ __('Partly forfeited (e.g. rubbish left)') }}</option>
                    <option value="forfeited">{{ __('Fully forfeited (e.g. appliances left on)') }}</option>
                </select></div>
            <div><label for="forfeit{{ $d->id }}">{{ __('Forfeit amount (RM, partial only)') }}</label>
                <input type="number" step="0.01" min="0" max="{{ $d->amount }}" id="forfeit{{ $d->id }}" name="forfeit_amount"></div>
            <div class="full"><label for="rm{{ $d->id }}">{{ __('Remarks') }}</label><input id="rm{{ $d->id }}" name="remarks"></div>
            <div class="actions" style="margin:0;"><button class="btn" type="submit">{{ __('Settle deposit') }}</button></div>
        </form>
    @elseif ($d->status === 'verified')
        <p class="hint">{{ __('Settlement opens after the booking is marked completed.') }}</p>
    @endif
</div>
@empty
<div class="card">{{ __('No deposits found.') }}</div>
@endforelse

<div class="pagination">{{ $deposits->links() }}</div>
@endsection
