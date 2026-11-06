@extends('layouts.app')
@section('title', __('Cancel / Reschedule Requests'))

@section('content')
<h1>{{ __('Cancel / Reschedule Requests') }}</h1>
<p class="lead">{{ __('Review requests from bookers. Approving a cancellation frees the date; approving a reschedule moves the booking.') }}</p>

@forelse ($requests as $r)
<div class="card">
    <h2>{{ __(ucfirst($r->type)) }} &mdash; {{ $r->booking->booking_no }} <span class="pill pill-{{ $r->status }}">{{ __($r->status) }}</span></h2>
    <p>{{ $r->booking->event_name }} &middot; {{ $r->user->name }} &middot; {{ __('now') }} {{ $r->booking->event_date->locale(app()->getLocale())->translatedFormat('d M Y') }}
       {{ substr($r->booking->start_time,0,5) }}-{{ substr($r->booking->end_time,0,5) }}</p>
    @if ($r->type === 'reschedule')
        <p><strong>{{ __('Requested:') }}</strong> {{ $r->new_date->locale(app()->getLocale())->translatedFormat('d M Y') }}, {{ substr($r->new_start_time,0,5) }} - {{ substr($r->new_end_time,0,5) }}</p>
    @endif
    <p><strong>{{ __('Reason:') }}</strong> {{ $r->reason }}</p>

    @if ($r->status === 'pending')
        <form method="POST" action="{{ route('staff.changes.review', $r) }}" class="form-grid">@csrf
            <div class="full"><label for="rr{{ $r->id }}">{{ __('Remarks (required when rejecting)') }}</label><input id="rr{{ $r->id }}" name="review_remarks"></div>
            <div class="actions" style="margin:0;">
                <button class="btn" name="decision" value="approve" type="submit">{{ __('Approve') }}</button>
                <button class="btn btn-danger" name="decision" value="reject" type="submit">{{ __('Reject') }}</button>
            </div>
        </form>
    @elseif ($r->review_remarks)
        <p class="hint">{{ __('Remarks:') }} {{ $r->review_remarks }}</p>
    @endif
</div>
@empty
<div class="card">{{ __('No requests.') }}</div>
@endforelse

<div class="pagination">{{ $requests->links() }}</div>
@endsection
