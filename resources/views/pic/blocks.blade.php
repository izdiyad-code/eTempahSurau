@extends('layouts.app')
@section('title', __('Blocked Dates'))

@section('content')
<h1>{{ __('Blocked Dates') }} &amp; {{ __('Times') }}</h1>
<p class="lead">{{ __('Dates the surau cannot be booked (prayers, programmes, maintenance). They appear on the public calendar.') }}</p>

<form class="card form-grid" method="POST" action="{{ route('pic.blocks.store') }}">@csrf
    <div><label for="title">{{ __('Title') }} <span class="req">*</span></label><input id="title" name="title" value="{{ old('title') }}" required></div>
    <div><label for="type">{{ __('Type') }}</label>
        <select id="type" name="type">
            @foreach (['manual_block' => 'Manual block','surau_programme' => 'Surau programme','maintenance' => 'Maintenance','prayer' => 'Prayer','friday_prayer' => 'Friday prayer'] as $v => $l)
                <option value="{{ $v }}">{{ __($l) }}</option>
            @endforeach
        </select></div>
    <div><label for="block_date">{{ __('Date') }} <span class="req">*</span></label><input type="date" id="block_date" name="block_date" min="{{ today()->toDateString() }}" value="{{ old('block_date') }}" required></div>
    <div></div>
    <div><label for="start_time">{{ __('Start') }} <span class="req">*</span></label><input type="time" id="start_time" name="start_time" value="{{ old('start_time', '08:00') }}" required></div>
    <div><label for="end_time">{{ __('End') }} <span class="req">*</span></label><input type="time" id="end_time" name="end_time" value="{{ old('end_time', '23:00') }}" required></div>
    <div class="full"><label for="reason">{{ __('Reason') }}</label><input id="reason" name="reason" value="{{ old('reason') }}"></div>
    <div class="actions" style="margin:0;"><button class="btn" type="submit">{{ __('Block this time') }}</button></div>
</form>

<div class="card">
<div class="table-wrap"><table>
    <thead><tr><th>{{ __('Date') }}</th><th>{{ __('Time') }}</th><th>{{ __('Title') }}</th><th>{{ __('Type') }}</th><th></th></tr></thead>
    <tbody>
    @forelse ($blocks as $b)
        <tr>
            <td>{{ $b->block_date->format('d M Y') }}</td>
            <td>{{ substr($b->start_time,0,5) }} - {{ substr($b->end_time,0,5) }}</td>
            <td>{{ $b->title }}<br><span class="hint">{{ $b->reason }}</span></td>
            <td>{{ __(str_replace('_', ' ', $b->type)) }}</td>
            <td><form method="POST" action="{{ route('pic.blocks.destroy', $b) }}" onsubmit="return confirm('Remove this block?')">@csrf @method('DELETE')
                <button class="btn btn-danger btn-sm" type="submit">{{ __('Remove') }}</button></form></td>
        </tr>
    @empty
        <tr><td colspan="5">{{ __('No upcoming blocks.') }}</td></tr>
    @endforelse
    </tbody>
</table></div>
<div class="pagination">{{ $blocks->links() }}</div>
</div>
@endsection
