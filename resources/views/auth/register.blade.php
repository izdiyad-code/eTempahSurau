@extends('layouts.app')
@section('title', __('Register'))

@section('content')
<div style="max-width:640px;margin:0 auto;">
    <h1>{{ __('Create account') }}</h1>
    <p class="lead">{{ __('Register as a public booker. It only takes a minute.') }}</p>

    <form class="card" method="POST" action="{{ route('register.store') }}">
        @csrf
        <div class="form-grid">
            <div class="full"><label for="name">{{ __('Full name') }}</label>
                <input id="name" name="name" value="{{ old('name') }}" autocomplete="name" required></div>
            <div><label for="email">{{ __('Email') }}</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="email" required></div>
            <div><label for="phone">{{ __('Phone number') }}</label>
                <input id="phone" name="phone" value="{{ old('phone') }}" autocomplete="tel" placeholder="01X-XXXXXXX" required></div>
            <div class="full"><label for="booker_type">{{ __('Booker category') }}</label>
                <select id="booker_type" name="booker_type" required>
                    <option value="">{{ __('Select category') }}</option>
                    <option value="muslim" @selected(old('booker_type') === 'muslim')>{{ __('Muslim') }}</option>
                    <option value="non_muslim" @selected(old('booker_type') === 'non_muslim')>{{ __('Non-Muslim') }}</option>
                </select></div>
            <div><label for="password">{{ __('Password') }}</label>
                <input type="password" id="password" name="password" placeholder="{{ __('Minimum 8 characters') }}" autocomplete="new-password" required></div>
            <div><label for="password_confirmation">{{ __('Confirm password') }}</label>
                <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required></div>
        </div>
        <div class="actions"><button class="btn" type="submit" style="width:100%;">{{ __('Register') }}</button></div>
        <p style="text-align:center;margin-bottom:0;">{{ __('Already have an account?') }} <a href="{{ route('login') }}">{{ __('Login') }}</a></p>
    </form>
</div>
@endsection
