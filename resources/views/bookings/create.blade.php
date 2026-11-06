@extends('layouts.app')
@section('title', __('New Booking'))

@section('content')
<h1>{{ __('New Booking') }}</h1>
<p class="lead">{{ __('Your request will be reviewed by the Chairman. It is not confirmed until approved.') }}</p>

<form class="card" method="POST" action="{{ route('bookings.store') }}">
    @csrf

    {{-- The date was chosen on the calendar, so it is shown here and sent as a hidden value. --}}
    <input type="hidden" name="event_date" value="{{ $selectedDate }}">

    <div class="alert-box alert-success" style="display:flex; justify-content:space-between; flex-wrap:wrap; gap:10px;">
        <span><strong>{{ __('Selected date:') }}</strong> {{ \Carbon\Carbon::parse($selectedDate)->locale(app()->getLocale())->translatedFormat('l, d F Y') }}</span>
        <a href="{{ route('home') }}#calendar-section">{{ __('Change date') }}</a>
    </div>

    @if ($takenSlots->count() || $blocks->count())
        <div class="alert-box" style="background:#fffbeb;border:1px solid #fde68a;color:#92400e;">
            <strong>{{ __('Already taken on this date:') }}</strong>
            <ul style="margin:6px 0 0 18px;padding:0;">
                @foreach ($takenSlots as $slot)
                    <li>{{ $slot->facility->name() }}: {{ substr($slot->start_time,0,5) }} - {{ substr($slot->end_time,0,5) }} ({{ __($slot->status) }})</li>
                @endforeach
                @foreach ($blocks as $block)
                    <li>{{ $block->title }}: {{ substr($block->start_time,0,5) }} - {{ substr($block->end_time,0,5) }} ({{ __('surau closed)') }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="form-grid">
        <div class="full">
            <label for="event_name">{{ __('Event name') }} <span class="req">*</span></label>
            <input id="event_name" name="event_name" value="{{ old('event_name') }}" maxlength="255" placeholder="{{ __('Example: Tahlil Arwah Family') }}" required>
        </div>

        <div>
            <label for="event_type_id">{{ __('Event type') }} <span class="req">*</span></label>
            <select id="event_type_id" name="event_type_id" required>
                <option value="">{{ __('Select event type') }}</option>
                @foreach ($eventTypes as $type)
                    <option value="{{ $type->id }}" @selected((string) old('event_type_id') === (string) $type->id)>{{ $type->name() }} ({{ $type->name_ms }})</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="facility_id">{{ __('Facility') }} <span class="req">*</span></label>
            <select id="facility_id" name="facility_id" required>
                <option value="">{{ __('Select facility') }}</option>
                @foreach ($facilities as $facility)
                    <option value="{{ $facility->id }}" @selected((string) old('facility_id') === (string) $facility->id)>
                        {{ $facility->name() }}@unless (auth()->user()->isChairman()) - {{ __('deposit RM') }}{{ number_format($facility->deposit_amount, 2) }}@endunless
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="start_time">{{ __('Start time') }} <span class="req">*</span></label>
            <input type="time" id="start_time" name="start_time" value="{{ old('start_time') }}" required>
        </div>

        <div>
            <label for="end_time">{{ __('End time') }} <span class="req">*</span></label>
            <input type="time" id="end_time" name="end_time" value="{{ old('end_time') }}" required>
        </div>

        <div>
            <label for="expected_guests">{{ __('Expected guests') }} <span class="req">*</span></label>
            <input type="number" id="expected_guests" name="expected_guests" min="1" max="1000" value="{{ old('expected_guests') }}" required>
        </div>

        <div class="full">
            <label>{{ __('Equipment needed (optional)') }}</label>
            <div class="grid" style="margin:0;">
                @foreach ($equipment as $item)
                    <div>
                        <span>{{ $item->name() }} <span class="hint">({{ __('max') }} {{ $item->total_quantity }})</span></span>
                        <input type="number" name="equipment[{{ $item->id }}]" min="0" max="{{ $item->total_quantity }}"
                               value="{{ old('equipment.' . $item->id, 0) }}" aria-label="{{ $item->name() }} {{ __('Quantity') }}">
                    </div>
                @endforeach
            </div>
        </div>

        <div class="full">
            <label for="notes">{{ __('Additional notes') }}</label>
            <textarea id="notes" name="notes" maxlength="3000" placeholder="{{ __('Anything the Chairman should know (optional)') }}">{{ old('notes') }}</textarea>
        </div>
    </div>

    <div class="actions">
        <button class="btn" type="submit">{{ __('Submit booking request') }}</button>
        <a class="btn btn-secondary" href="{{ route('home') }}">{{ __('Cancel') }}</a>
    </div>

    <p class="hint" style="margin-top:18px;">
        {{ __('A deposit (shown beside each facility) is paid after the Chairman approves. It is refunded when the surau is left clean and all electrical appliances are switched off.') }}
    </p>
</form>
@endsection
