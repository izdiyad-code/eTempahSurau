@extends('layouts.app')
@section('title', __('Notifications'))

@section('content')
<h1>{{ __('Notifications') }}</h1>
<p class="lead">{{ __('Updates about your bookings and reminders.') }}</p>

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
@endsection
