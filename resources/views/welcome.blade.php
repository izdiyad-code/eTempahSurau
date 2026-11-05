@extends('layouts.app')
@section('title', __('Home'))

@push('head')
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
    <style>
        .flash { max-width: 1100px; margin: 16px auto 0; padding: 14px 18px; border-radius: 10px; }
        .hero { position: relative; color: #fff; padding: 90px 6% 80px; text-align: center;
                background: linear-gradient(135deg, #14532d, #166534); }
        .hero.has-photo { background-size: cover; background-position: center; }
        .hero-inner { max-width: 760px; margin: 0 auto; }
        .hero h1 { color: #fff; font-size: 46px; line-height: 1.2; margin: 14px 0; }
        .hero p { font-size: 19px; opacity: .95; margin: 0 0 26px; }
        .hero-tag { display: inline-block; background: rgba(255,255,255,.18); padding: 6px 16px; border-radius: 99px; font-size: 14px; }
        .hero .btn { background: #facc15; color: #422006; }
        .section { max-width: 1150px; margin: 0 auto; padding: 46px 20px 10px; }
        .section h2 { font-size: 30px; }
        .legend { display: flex; flex-wrap: wrap; gap: 8px 20px; margin: 8px 0 16px; font-size: 14px; color: #475569; }
        .legend i { display: inline-block; width: 14px; height: 14px; border-radius: 4px; margin-right: 6px; vertical-align: -2px; }
        .facility-grid { display: grid; gap: 22px; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); }
        .facility-card { background: #fff; border-radius: 14px; overflow: hidden; box-shadow: 0 6px 20px rgba(0,0,0,.06); }
        .facility-card .photo .ic { width: 64px; height: 64px; color: #15803d; }
        .facility-card .photo { height: 180px; background: #dcfce7 center / cover no-repeat; display: flex; align-items: center; justify-content: center; font-size: 56px; }
        .facility-card .body { padding: 18px 20px 22px; }
        .facility-card h3 { margin: 0 0 6px; color: #14532d; }
        .about { display: grid; gap: 28px; grid-template-columns: 1fr 1fr; align-items: center; }
        .about img { width: 100%; border-radius: 14px; box-shadow: 0 6px 20px rgba(0,0,0,.1); }
        #calendar { font-size: 15px; }
        .fc .fc-event { cursor: pointer; }
        .fc-theme-standard td, .fc-theme-standard th { border-color: #e5e7eb; }

        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.55); z-index: 50;
                         align-items: center; justify-content: center; padding: 16px; }
        .modal-overlay.active { display: flex; }
        .modal { background: #fff; border-radius: 16px; padding: 26px; width: 100%; max-width: 480px; max-height: 90vh; overflow-y: auto; }
        .modal h3 { margin: 0 0 10px; color: #14532d; font-size: 24px; }
        .modal .picked { background: #dcfce7; color: #166534; padding: 12px 14px; border-radius: 9px; font-weight: bold; margin-bottom: 14px; }
        .modal ul { margin: 6px 0 14px 18px; padding: 0; }
        .modal-actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 14px; }

        @media (max-width: 800px) {
            .hero { padding: 56px 18px 50px; }
            .hero h1 { font-size: 30px; }
            .hero p { font-size: 17px; }
            .about { grid-template-columns: 1fr; }
            .section { padding: 32px 14px 6px; }
            .section h2 { font-size: 24px; }
            .fc .fc-toolbar { flex-direction: column; gap: 8px; }
            .fc .fc-toolbar-title { font-size: 20px; }
            .fc .fc-button { padding: 6px 10px; font-size: 14px; }
            .modal-actions .btn { flex: 1; text-align: center; }
        }
    </style>
@endpush

@section('fullwidth')
@php($photo = file_exists(public_path('images/surau.svg')))

@if (session('success') || session('error'))
    <div class="flash {{ session('error') ? 'alert-error' : 'alert-success' }}" role="status">
        {{ session('error') ?? session('success') }}
    </div>
@endif

<section class="hero {{ $photo ? 'has-photo' : '' }}" id="home"
    @if ($photo) style="background-image: linear-gradient(rgba(10,50,25,.62), rgba(10,50,25,.72)), url('{{ asset('images/surau.svg') }}');" @endif>
    <div class="hero-inner">
        <span class="hero-tag">{{ __('Madrasah Seksyen 11, Shah Alam') }}</span>
        <h1>{{ __('Easy to check. Easy to book. Easy to manage.') }}</h1>
        <p>{{ __('e-TempahSurau lets the community check the surau schedule and availability, and request a booking online.') }}</p>
        <a class="btn" href="#calendar-section">{{ __('Check availability') }}</a>
    </div>
</section>

@if ($announcements->count())
<section class="section" id="announcements" style="padding-bottom:0;">
    <h2><x-icon name="megaphone" /> {{ __('Announcements') }}</h2>
    <div class="grid" style="margin-bottom:8px;">
        @foreach ($announcements as $a)
            <a class="card" href="{{ route('announcements.index') }}#a{{ $a->id }}" style="text-decoration:none;color:inherit;margin:0;">
                <strong style="color:#14532d;">{{ $a->localTitle() }}</strong>
                <div class="hint">{{ $a->created_at->format('d M Y') }}@if ($a->event_date) &middot; {{ __('Event date') }}: {{ $a->event_date->format('d M Y') }}@endif</div>
                <div>{{ \Illuminate\Support\Str::limit($a->localBody(), 110) }}</div>
            </a>
        @endforeach
    </div>
    <a href="{{ route('announcements.index') }}">{{ __('See all announcements') }} &rarr;</a>
</section>
@endif

<section class="section" id="calendar-section">
    <h2>{{ __('Availability Calendar') }}</h2>
    <p class="lead" style="margin-bottom:6px;">{{ __('Click a date to see what is already booked and to start a booking.') }}</p>

    <div class="legend">
        <span><i style="background:#b91c1c"></i>{{ __('Booked (approved)') }}</span>
        <span><i style="background:#d97706"></i>{{ __('Requested (waiting for approval)') }}</span>
        <span><i style="background:#475569"></i>{{ __('Surau closed (prayer / programme)') }}</span>
    </div>

    <div class="card" style="padding:14px;"><div id="calendar"></div></div>
</section>

<section class="section" id="facilities">
    <h2>{{ __('Facilities') }}</h2>
    <p class="lead">{{ __('Facilities and equipment that the surau offers for community events.') }}</p>

    <div class="facility-grid">
        @foreach ($facilities as $facility)
            <div class="facility-card">
                <div class="photo" @if ($facility->imageUrl()) style="background-image:url('{{ $facility->imageUrl() }}')" @endif>
                    @unless ($facility->imageUrl()) <x-icon name="building" /> @endunless
                </div>
                <div class="body">
                    <h3>{{ $facility->name() }}</h3>
                    <p style="margin:0 0 6px;">{{ $facility->localDescription() }}</p>
                    <strong>{{ __('Deposit') }}: RM{{ number_format($facility->deposit_amount, 2) }}</strong>
                </div>
            </div>
        @endforeach

        <div class="facility-card">
            <div class="photo"><x-icon name="mic" /></div>
            <div class="body">
                <h3>{{ __('Equipment') }}</h3>
                <p style="margin:0;">{{ __('Microphones, speakers, tables and chairs can be requested together with your booking, subject to availability.') }}</p>
            </div>
        </div>
    </div>
</section>

<section class="section" id="about">
    <div class="about">
        <div>
            <h2>{{ __('About the Surau') }}</h2>
            <p>{{ __('Madrasah Seksyen 11, Shah Alam is a community surau used for prayers, tahlil, nikah, aqiqah and kenduri.') }}</p>
            <p>{{ __('Anyone can check availability on the calendar. To book, create a free account, choose a date and send your request.') }}</p>
            <p>{{ __('Every request is reviewed and approved by the surau Chairman. A small deposit is paid after approval and is refunded when the surau is left clean.') }}</p>
        </div>
        @if ($photo)
            <img src="{{ asset('images/surau.svg') }}" alt="{{ __('Madrasah Seksyen 11') }}">
        @endif
    </div>
</section>

{{-- Date popup: shows who/what is already booked, even for guests --}}
<div class="modal-overlay" id="dateModal">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
        <h3 id="modalTitle">{{ __('Selected date') }}</h3>
        <div class="picked" id="selectedDateDisplay"></div>

        <div id="bookedBox">
            <strong>{{ __('Already booked on this date') }}</strong>
            <ul id="bookedList"></ul>
            <p id="noneBooked" class="hint" style="display:none;">{{ __('Nothing is booked yet. This date is free.') }}</p>
        </div>

        @guest
            <p>{{ __('You need an account to make a booking. Please log in or register first. You will come back to this page afterwards.') }}</p>
            <div class="modal-actions">
                <a href="{{ route('login') }}" class="btn">{{ __('Login') }}</a>
                <a href="{{ route('register') }}" class="btn btn-secondary">{{ __('Register') }}</a>
                <button type="button" class="btn btn-secondary" onclick="closeDateModal()">{{ __('Close') }}</button>
            </div>
        @else
            @if (in_array(auth()->user()->role, ['public', 'chairman']))
                @if (auth()->user()->role === 'chairman')
                    <p class="hint">{{ __('As Chairman, your booking is approved immediately.') }}</p>
                @endif
                <div class="modal-actions">
                    <button type="button" class="btn" onclick="continueBooking()">{{ __('Make Booking') }}</button>
                    <button type="button" class="btn btn-secondary" onclick="closeDateModal()">{{ __('Close') }}</button>
                </div>
            @else
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeDateModal()">{{ __('Close') }}</button>
                </div>
            @endif
        @endguest
    </div>
</div>

<script>
    let selectedBookingDate = null;
    let calendar = null;
    const isMobile = window.matchMedia('(max-width: 800px)').matches;
    const bookUrl = @json(route('bookings.create'));
    const tPast = @json(__('Please choose today or a future date.'));

    document.addEventListener('DOMContentLoaded', function () {
        calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
            initialView: 'dayGridMonth',
            height: 'auto',
            locale: @json(app()->getLocale()),
            firstDay: 1,
            headerToolbar: { left: 'prev,next', center: 'title', right: 'today' },
            selectable: false,
            // On phones show coloured dots so booked days are visible at a glance
            eventDisplay: isMobile ? 'list-item' : 'block',
            dayMaxEvents: isMobile ? 2 : 3,
            moreLinkClick: function (info) { openDateModal(info.date.toLocaleDateString('en-CA')); return 'none'; },
            events: @json(route('calendar.events')),
            dateClick: function (info) { openDateModal(info.dateStr); },
            eventClick: function (info) { openDateModal(info.event.startStr.substring(0, 10)); }
        });
        calendar.render();
    });

    function openDateModal(dateStr) {
        selectedBookingDate = dateStr;

        document.getElementById('selectedDateDisplay').textContent =
            new Date(dateStr + 'T00:00:00').toLocaleDateString(@json(app()->getLocale() === 'ms' ? 'ms-MY' : 'en-MY'),
                { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });

        // list everything already on that day (taken from the calendar feed)
        const list = document.getElementById('bookedList');
        list.innerHTML = '';

        const events = calendar.getEvents().filter(e => e.startStr.substring(0, 10) === dateStr)
            .sort((a, b) => a.start - b.start);

        events.forEach(e => {
            const li = document.createElement('li');
            const fmt = d => d ? d.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' }) : '';
            li.textContent = fmt(e.start) + ' - ' + fmt(e.end) + '  ' + e.title;
            li.style.color = e.backgroundColor;
            list.appendChild(li);
        });

        document.getElementById('noneBooked').style.display = events.length ? 'none' : 'block';
        list.style.display = events.length ? 'block' : 'none';

        document.getElementById('dateModal').classList.add('active');
    }

    function closeDateModal() {
        document.getElementById('dateModal').classList.remove('active');
    }

    function continueBooking() {
        if (selectedBookingDate < new Date().toLocaleDateString('en-CA')) {
            alert(tPast);
            return;
        }
        window.location.href = bookUrl + '?date=' + selectedBookingDate;
    }

    document.getElementById('dateModal').addEventListener('click', function (e) {
        if (e.target === this) closeDateModal();
    });
</script>
@endsection
