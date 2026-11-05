@extends('layouts.app')
@section('title', __('Dashboard'))

@section('content')
<h1>{{ auth()->user()->role === 'chairman' ? __('Chairman Dashboard') : __('Secretary / PIC Dashboard') }}</h1>
<p class="lead">{{ __('Welcome, :name. Here is what needs attention.', ['name' => auth()->user()->name]) }}</p>

<div class="grid">
    @if (auth()->user()->role === 'chairman')
        <a class="stat {{ $stats['pending'] ? 'alert' : '' }}" href="{{ route('chairman.bookings.index') }}"><strong>{{ $stats['pending'] }}</strong><span>{{ __('Requests waiting for your decision') }}</span></a>
    @else
        <a class="stat {{ $stats['pending'] ? 'alert' : '' }}" href="{{ route('pic.bookings', ['status' => 'pending']) }}"><strong>{{ $stats['pending'] }}</strong><span>{{ __('Pending bookings') }}</span></a>
        <a class="stat {{ $stats['deposits_waiting'] ? 'alert' : '' }}" href="{{ route('pic.deposits', ['status' => 'proof_submitted']) }}"><strong>{{ $stats['deposits_waiting'] }}</strong><span>{{ __('Deposits to verify') }}</span></a>
    @endif
    <a class="stat {{ $stats['changes_waiting'] ? 'alert' : '' }}" href="{{ route('staff.changes') }}"><strong>{{ $stats['changes_waiting'] }}</strong><span>{{ __('Cancel / reschedule requests') }}</span></a>
    <div class="stat"><strong>{{ $stats['approved_upcoming'] }}</strong><span>{{ __('Upcoming approved events') }}</span></div>
    <div class="stat"><strong>{{ $stats['total'] }}</strong><span>{{ __('All bookings') }}</span></div>
</div>

<div class="card">
    <h2>{{ __('Pending requests') }}</h2>
    <div class="table-wrap"><table>
        <thead><tr><th>{{ __('Ref') }}</th><th>{{ __('Event') }}</th><th>{{ __('Booker') }}</th><th>{{ __('Date') }}</th><th>{{ __('Facility') }}</th><th></th></tr></thead>
        <tbody>
        @forelse ($pendingList as $b)
            <tr>
                <td>{{ $b->booking_no }}</td><td>{{ $b->event_name }}</td><td>{{ $b->user->name }}</td>
                <td>{{ $b->event_date->format('d M Y') }}</td><td>{{ $b->facility->name() }}</td>
                <td><a class="btn btn-sm" href="{{ route('staff.bookings.show', $b) }}">{{ __('View') }}</a></td>
            </tr>
        @empty
            <tr><td colspan="6">{{ __('Nothing pending.') }}</td></tr>
        @endforelse
        </tbody>
    </table></div>
</div>

<div class="card">
    <h2>{{ __('Upcoming approved events') }}</h2>
    <div class="table-wrap"><table>
        <thead><tr><th>{{ __('Date') }}</th><th>{{ __('Time') }}</th><th>{{ __('Event') }}</th><th>{{ __('Facility') }}</th><th>{{ __('Booker') }}</th></tr></thead>
        <tbody>
        @forelse ($upcoming as $b)
            <tr>
                <td>{{ $b->event_date->format('d M Y') }}</td>
                <td>{{ substr($b->start_time,0,5) }} - {{ substr($b->end_time,0,5) }}</td>
                <td><a href="{{ route('staff.bookings.show', $b) }}">{{ $b->event_name }}</a></td>
                <td>{{ $b->facility->name() }}</td><td>{{ $b->user->name }}</td>
            </tr>
        @empty
            <tr><td colspan="5">{{ __('No upcoming events.') }}</td></tr>
        @endforelse
        </tbody>
    </table></div>
</div>
@endsection
