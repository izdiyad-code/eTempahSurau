@extends('layouts.app')
@section('title', __('All Bookings'))

@section('content')
<h1>{{ __('All Bookings') }}</h1>
<p class="lead">{{ __('Search, filter and sort the full booking history. Click a column title to sort.') }}</p>

<form class="card filters" method="GET">
    <div><label for="q">{{ __('Search') }}</label><input id="q" name="q" value="{{ request('q') }}" placeholder="{{ __('Reference, event or booker') }}"></div>
    <div><label for="facility_id">{{ __('Facility') }}</label>
        <select id="facility_id" name="facility_id"><option value="">{{ __('All') }}</option>
            @foreach ($facilities as $f)<option value="{{ $f->id }}" @selected((string) request('facility_id') === (string) $f->id)>{{ $f->name() }}</option>@endforeach
        </select></div>
    <div><label for="status">{{ __('Status') }}</label>
        <select id="status" name="status"><option value="">{{ __('All') }}</option>
            @foreach (['pending','approved','rejected','cancelled','completed'] as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ __(ucfirst($s)) }}</option>@endforeach
        </select></div>
    <div><label for="from">{{ __('From') }}</label><input type="date" id="from" name="from" value="{{ request('from') }}"></div>
    <div><label for="to">{{ __('To') }}</label><input type="date" id="to" name="to" value="{{ request('to') }}"></div>
    <div class="actions" style="margin:0;"><button class="btn" type="submit">{{ __('Apply') }}</button>
        <a class="btn btn-secondary" href="{{ route('pic.bookings') }}">{{ __('Reset') }}</a></div>
</form>

<div class="card">
<div class="table-wrap"><table>
    <thead><tr>
        @include('partials.sort-th', ['key' => 'ref', 'label' => __('Ref')])
        @include('partials.sort-th', ['key' => 'event', 'label' => __('Event')])
        @include('partials.sort-th', ['key' => 'booker', 'label' => __('Booker')])
        @include('partials.sort-th', ['key' => 'date', 'label' => __('Date')])
        @include('partials.sort-th', ['key' => 'facility', 'label' => __('Facility')])
        @include('partials.sort-th', ['key' => 'status', 'label' => __('Status')])
        <th>{{ __('Deposit') }}</th><th></th>
    </tr></thead>
    <tbody>
    @forelse ($bookings as $b)
        <tr>
            <td>{{ $b->booking_no }}</td>
            <td>{{ $b->event_name }}@if($b->additional_review_required)<br><span class="pill pill-pending">{{ __('extra review') }}</span>@endif</td>
            <td>{{ $b->user->name }}<br><span class="hint">{{ $b->user->phone }}</span></td>
            <td>{{ $b->event_date->format('d M Y') }}<br>{{ substr($b->start_time,0,5) }} - {{ substr($b->end_time,0,5) }}</td>
            <td>{{ $b->facility->name() }}</td>
            <td><span class="pill pill-{{ $b->status }}">{{ __(ucfirst($b->status)) }}</span></td>
            <td>@if($b->deposit)<span class="pill pill-{{ $b->deposit->status }}">{{ __(str_replace('_',' ',$b->deposit->status)) }}</span>@endif</td>
            <td><a class="btn btn-sm" href="{{ route('staff.bookings.show', $b) }}">{{ __('View') }}</a></td>
        </tr>
    @empty
        <tr><td colspan="8">{{ __('No bookings found.') }}</td></tr>
    @endforelse
    </tbody>
</table></div>
<div class="pagination">{{ $bookings->links() }}</div>
</div>
@endsection
