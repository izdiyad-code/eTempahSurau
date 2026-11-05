@extends('layouts.app')
@section('title', __('Reports'))

@section('content')
<h1>{{ __('Booking Reports') }}</h1>
<p class="lead">{{ __('Filter by facility, event type, status or dates, sort by any column, then print or download.') }}</p>

<form class="card filters" method="GET">
    <div><label for="q">{{ __('Search') }}</label><input id="q" name="q" value="{{ request('q') }}" placeholder="{{ __('Reference, event or booker') }}"></div>
    <div><label for="facility_id">{{ __('Facility') }}</label>
        <select id="facility_id" name="facility_id"><option value="">{{ __('All') }}</option>
            @foreach ($facilities as $f)<option value="{{ $f->id }}" @selected((string) request('facility_id') === (string) $f->id)>{{ $f->name() }}</option>@endforeach
        </select></div>
    <div><label for="event_type_id">{{ __('Event type') }}</label>
        <select id="event_type_id" name="event_type_id"><option value="">{{ __('All') }}</option>
            @foreach ($eventTypes as $t)<option value="{{ $t->id }}" @selected((string) request('event_type_id') === (string) $t->id)>{{ $t->name() }}</option>@endforeach
        </select></div>
    <div><label for="status">{{ __('Status') }}</label>
        <select id="status" name="status"><option value="">{{ __('All') }}</option>
            @foreach (['pending','approved','rejected','cancelled','completed'] as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ __(ucfirst($s)) }}</option>@endforeach
        </select></div>
    <div><label for="from">{{ __('From') }}</label><input type="date" id="from" name="from" value="{{ request('from') }}"></div>
    <div><label for="to">{{ __('To') }}</label><input type="date" id="to" name="to" value="{{ request('to') }}"></div>
    <div><label for="sort">{{ __('Sort by') }}</label>
        <select id="sort" name="sort">
            @foreach (['date' => 'Date', 'facility' => 'Facility', 'event_type' => 'Event type', 'event' => 'Event', 'booker' => 'Booker', 'status' => 'Status', 'guests' => 'Guests', 'ref' => 'Ref'] as $k => $l)
                <option value="{{ $k }}" @selected(request('sort', 'date') === $k)>{{ __($l) }}</option>
            @endforeach
        </select></div>
    <div><label for="dir">{{ __('Order') }}</label>
        <select id="dir" name="dir">
            <option value="asc" @selected(request('dir') === 'asc')>{{ __('Ascending') }}</option>
            <option value="desc" @selected(request('dir', 'desc') === 'desc')>{{ __('Descending') }}</option>
        </select></div>
    <div class="actions" style="margin:0;">
        <button class="btn" type="submit">{{ __('Apply') }}</button>
        <a class="btn btn-secondary" href="{{ route('staff.reports') }}">{{ __('Reset') }}</a>
        <a class="btn btn-secondary" href="{{ route('staff.reports.export', request()->query()) }}">{{ __('Download CSV') }}</a>
        <button class="btn btn-secondary" type="button" onclick="window.print()">{{ __('Print') }}</button>
    </div>
</form>

<div class="grid">
    <div class="stat"><strong>{{ $summary['total'] }}</strong><span>{{ __('Bookings in this report') }}</span></div>
    <div class="stat"><strong>{{ $summary['upcoming'] }}</strong><span>{{ __('Upcoming approved events') }}</span></div>
    <div class="stat"><strong>RM{{ number_format($summary['deposit_verified'], 2) }}</strong><span>{{ __('Deposits received') }}</span></div>
    <div class="stat"><strong>RM{{ number_format($summary['forfeited'], 2) }}</strong><span>{{ __('Deposits forfeited') }}</span></div>
</div>

<div class="card">
    <h2>{{ __('Summary by facility') }}</h2>
    <div class="table-wrap"><table>
        <thead><tr><th>{{ __('Facility') }}</th><th>{{ __('Bookings') }}</th><th>{{ __('Guests') }}</th><th>{{ __('Deposits') }}</th></tr></thead>
        <tbody>
        @forelse ($summary['by_facility'] as $name => $row)
            <tr><td>{{ $name }}</td><td>{{ $row['count'] }}</td><td>{{ $row['guests'] }}</td><td>RM{{ number_format($row['deposit'], 2) }}</td></tr>
        @empty
            <tr><td colspan="4">{{ __('No data.') }}</td></tr>
        @endforelse
        </tbody>
    </table></div>
    <p style="margin-bottom:0;">
        @foreach ($summary['by_status'] as $status => $count)
            <span class="pill pill-{{ $status }}">{{ __(ucfirst($status)) }}: {{ $count }}</span>
        @endforeach
    </p>
</div>

<div class="card">
<div class="table-wrap"><table>
    <thead><tr>
        @include('partials.sort-th', ['key' => 'ref', 'label' => __('Ref')])
        @include('partials.sort-th', ['key' => 'event', 'label' => __('Event')])
        @include('partials.sort-th', ['key' => 'event_type', 'label' => __('Event type')])
        @include('partials.sort-th', ['key' => 'booker', 'label' => __('Booker')])
        @include('partials.sort-th', ['key' => 'facility', 'label' => __('Facility')])
        @include('partials.sort-th', ['key' => 'date', 'label' => __('Date')])
        @include('partials.sort-th', ['key' => 'guests', 'label' => __('Guests')])
        @include('partials.sort-th', ['key' => 'status', 'label' => __('Status')])
        <th>{{ __('Deposit') }}</th>
    </tr></thead>
    <tbody>
    @forelse ($bookings as $b)
        <tr>
            <td>{{ $b->booking_no }}</td><td>{{ $b->event_name }}</td><td>{{ $b->eventType->name() }}</td><td>{{ $b->user->name }}</td>
            <td>{{ $b->facility->name() }}</td><td>{{ $b->event_date->format('d M Y') }}</td><td>{{ $b->expected_guests }}</td>
            <td><span class="pill pill-{{ $b->status }}">{{ __($b->status) }}</span></td>
            <td>@if ($b->deposit)RM{{ number_format($b->deposit->amount,2) }} ({{ __(str_replace('_',' ',$b->deposit->status)) }})@endif</td>
        </tr>
    @empty
        <tr><td colspan="9">{{ __('No bookings match.') }}</td></tr>
    @endforelse
    </tbody>
</table></div>
<p class="hint">{{ __('Showing up to 100 rows. Use the CSV for the full list.') }}</p>
</div>
@endsection
