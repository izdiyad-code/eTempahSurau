@extends('layouts.app')
@section('title', __('Booking Approvals'))

@section('content')
<h1>{{ __('Booking Approvals') }}</h1>
<p class="lead">{{ __('Pending requests are listed first. Every decision is sent to the booker.') }}</p>

@forelse ($bookings as $b)
<div class="card">
    <h2>{{ $b->event_name }} <span class="pill pill-{{ $b->status }}">{{ __(ucfirst($b->status)) }}</span>
        @if ($b->additional_review_required)<span class="pill pill-pending">{{ __('non-Muslim booker - extra review') }}</span>@endif</h2>
    <dl class="detail">
        <dt>{{ __('Reference') }}</dt><dd>{{ $b->booking_no }}</dd>
        <dt>{{ __('Booker') }}</dt><dd>{{ $b->user->name }} &middot; {{ $b->user->phone }}</dd>
        <dt>{{ __('When') }}</dt><dd>{{ $b->event_date->format('l, d F Y') }}, {{ substr($b->start_time,0,5) }} - {{ substr($b->end_time,0,5) }}</dd>
        <dt>{{ __('Where') }}</dt><dd>{{ $b->facility->name() }} &middot; {{ $b->expected_guests }} {{ __('guests') }} &middot; {{ $b->eventType->name() }}</dd>
        @if ($b->notes)<dt>{{ __('Notes') }}</dt><dd>{{ $b->notes }}</dd>@endif
        @if ($b->rejection_reason)<dt>{{ __('Rejection reason') }}</dt><dd>{{ $b->rejection_reason }}</dd>@endif
    </dl>

    <div class="actions">
        <a class="btn btn-secondary" href="{{ route('staff.bookings.show', $b) }}">{{ __('Full details') }}</a>
        @if ($b->status === 'pending')
            <form method="POST" action="{{ route('chairman.bookings.approve', $b) }}" class="inline-form">@csrf
                <button class="btn" type="submit">{{ __('Approve') }}</button></form>
        @endif
    </div>

    @if ($b->status === 'pending')
        <form method="POST" action="{{ route('chairman.bookings.reject', $b) }}" class="form-grid" style="margin-top:16px;">@csrf
            <div class="full"><label for="rej{{ $b->id }}">{{ __('Reason for rejection') }}</label>
                <input id="rej{{ $b->id }}" name="rejection_reason" required></div>
            <div class="actions" style="margin:0;"><button class="btn btn-danger" type="submit">{{ __('Reject') }}</button></div>
        </form>
    @endif
</div>
@empty
<div class="card">{{ __('No booking requests yet.') }}</div>
@endforelse

<div class="pagination">{{ $bookings->links() }}</div>
@endsection
