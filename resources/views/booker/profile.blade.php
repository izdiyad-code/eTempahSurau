@extends('layouts.app')
@section('title', __('Profile'))

@section('content')
<h1>{{ __('My Profile') }}</h1>
<p class="lead">{{ __('Keep your contact details up to date.') }}</p>

<form class="card" method="POST" action="{{ route('profile.update') }}">
    @csrf
    @method('PUT')
    <div class="form-grid">
        <div><label for="name">{{ __('Full name') }}</label><input id="name" name="name" value="{{ old('name', $user->name) }}" required></div>
        <div><label for="phone">{{ __('Phone number') }}</label><input id="phone" name="phone" value="{{ old('phone', $user->phone) }}" required></div>
        <div><label>{{ __('Email') }}</label><input value="{{ $user->email }}" disabled></div>
        <div><label>{{ __('Role') }}</label><input value="{{ __(ucfirst($user->role)) }}" disabled></div>
        <div><label for="password">{{ __('New password (optional)') }}</label><input type="password" id="password" name="password" autocomplete="new-password"></div>
        <div><label for="password_confirmation">{{ __('Confirm new password') }}</label><input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password"></div>
    </div>
    <div class="actions"><button class="btn" type="submit">{{ __('Save changes') }}</button></div>
</form>
@endsection
