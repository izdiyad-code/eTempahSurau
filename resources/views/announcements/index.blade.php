@extends('layouts.app')
@section('title', __('Announcements'))

@section('content')
@php($isStaff = auth()->check() && in_array(auth()->user()->role, ['pic', 'chairman']))

<h1><x-icon name="bell" /> {{ __('Announcements') }}</h1>
<p class="lead">{{ __('News, events and notices from the surau committee.') }}</p>

@auth
<div class="actions" style="margin:0 0 20px;">
    <a class="btn {{ $tab === 'announcements' ? '' : 'btn-secondary' }}" href="{{ route('announcements.index') }}">
        <x-icon name="megaphone" /> {{ __('Announcements') }}
    </a>
    <a class="btn {{ $tab === 'notifications' ? '' : 'btn-secondary' }}" href="{{ route('announcements.index', ['tab' => 'notifications']) }}">
        <x-icon name="bell" /> {{ __('My notifications') }}
        @if (auth()->user()->unreadNotificationCount() > 0)<span class="badge">{{ auth()->user()->unreadNotificationCount() }}</span>@endif
    </a>
</div>
@endauth

@if ($tab === 'notifications')
    <div class="card">
        @if ($notifications->count())
            <form method="POST" action="{{ route('notifications.readAll') }}">@csrf
                <button class="btn btn-secondary btn-sm" type="submit">{{ __('Mark all as read') }}</button>
            </form>
            <hr style="border:0;border-top:1px solid #e5e7eb;margin:16px 0;">
        @endif

        @forelse ($notifications as $n)
            <div style="padding:12px 0;border-bottom:1px solid #e5e7eb;{{ $n->read_at ? 'opacity:.65;' : '' }}">
                <strong>{{ $n->title }}</strong>
                @unless ($n->read_at)<span class="badge">{{ __('new') }}</span>@endunless
                <div>{{ $n->message }}</div>
                <div class="hint">{{ $n->created_at->diffForHumans() }}</div>
            </div>
        @empty
            <p>{{ __('No notifications yet.') }}</p>
        @endforelse

        <div class="pagination">{{ $notifications->links() }}</div>
    </div>
@else
    @if ($isStaff)
    <form class="card" method="POST" action="{{ route('announcements.store') }}">
        @csrf
        <h2><x-icon name="edit" /> {{ __('Post an announcement or event') }}</h2>
        <div class="form-grid">
            <div class="full"><label for="title">{{ __('Title (English)') }} <span class="req">*</span></label>
                <input id="title" name="title" value="{{ old('title') }}" maxlength="255" required></div>
            <div class="full"><label for="body">{{ __('Message (English)') }} <span class="req">*</span></label>
                <textarea id="body" name="body" required>{{ old('body') }}</textarea></div>
            <div class="full"><label for="title_ms">{{ __('Title (Bahasa Melayu, optional)') }}</label>
                <input id="title_ms" name="title_ms" value="{{ old('title_ms') }}" maxlength="255"></div>
            <div class="full"><label for="body_ms">{{ __('Message (Bahasa Melayu, optional)') }}</label>
                <textarea id="body_ms" name="body_ms">{{ old('body_ms') }}</textarea></div>
            <div><label for="event_date">{{ __('Event date (optional)') }}</label>
                <input type="date" id="event_date" name="event_date" value="{{ old('event_date') }}"></div>
        </div>
        <p class="hint">{{ __('If you leave the Malay version empty, the English text is shown to everyone.') }}</p>
        <div class="actions"><button class="btn" type="submit"><x-icon name="megaphone" /> {{ __('Post announcement') }}</button></div>
    </form>
    @endif

    @forelse ($announcements as $a)
        @php($isNew = $seenAt && $a->created_at->gt($seenAt))
        <div class="card" id="a{{ $a->id }}">
            <h2>{{ $a->localTitle() }} @if ($isNew)<span class="badge">{{ __('new') }}</span>@endif</h2>
            <p class="hint" style="margin-top:-8px;">
                <x-icon name="calendar" /> {{ $a->created_at->locale(app()->getLocale())->translatedFormat('d M Y, H:i') }}
                @if ($a->author) &middot; {{ $a->author->name }}@endif
                @if ($a->event_date) &middot; <strong>{{ __('Event date') }}: {{ $a->event_date->locale(app()->getLocale())->translatedFormat('d M Y') }}</strong>@endif
            </p>
            <p style="white-space:pre-line;margin-bottom:0;">{{ $a->localBody() }}</p>

            @if ($isStaff)
                <details style="margin-top:16px;">
                    <summary style="cursor:pointer;font-weight:bold;"><x-icon name="edit" /> {{ __('Edit') }}</summary>
                    <form method="POST" action="{{ route('announcements.update', $a) }}" class="form-grid" style="margin-top:12px;">
                        @csrf @method('PUT')
                        <div class="full"><label>{{ __('Title (English)') }}</label><input name="title" value="{{ $a->title }}" required></div>
                        <div class="full"><label>{{ __('Message (English)') }}</label><textarea name="body" required>{{ $a->body }}</textarea></div>
                        <div class="full"><label>{{ __('Title (Bahasa Melayu, optional)') }}</label><input name="title_ms" value="{{ $a->title_ms }}"></div>
                        <div class="full"><label>{{ __('Message (Bahasa Melayu, optional)') }}</label><textarea name="body_ms">{{ $a->body_ms }}</textarea></div>
                        <div><label>{{ __('Event date (optional)') }}</label><input type="date" name="event_date" value="{{ optional($a->event_date)->toDateString() }}"></div>
                        <div class="actions full" style="margin:0;"><button class="btn btn-sm" type="submit">{{ __('Save') }}</button></div>
                    </form>
                    <form method="POST" action="{{ route('announcements.destroy', $a) }}" onsubmit="return confirm('{{ __('Delete this announcement?') }}')" style="margin-top:10px;">
                        @csrf @method('DELETE')
                        <button class="btn btn-danger btn-sm" type="submit"><x-icon name="trash" /> {{ __('Delete') }}</button>
                    </form>
                </details>
            @endif
        </div>
    @empty
        <div class="card">{{ __('No announcements yet.') }}</div>
    @endforelse

    <div class="pagination">{{ $announcements->links() }}</div>
@endif
@endsection
