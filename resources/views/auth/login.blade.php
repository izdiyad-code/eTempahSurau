@extends('layouts.app')
@section('title', __('Login'))

@section('content')
<div style="max-width:480px;margin:0 auto;">
    <h1>{{ __('Login') }}</h1>
    <p class="lead">{{ __('Log in to book the surau and follow your requests.') }}</p>

    <form class="card" method="POST" action="{{ route('login.store') }}">
        @csrf
        <div style="margin-bottom:16px;">
            <label for="email">{{ __('Email') }}</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
        </div>
        <div style="margin-bottom:16px;">
            <label for="password">{{ __('Password') }}</label>
            <input type="password" id="password" name="password" autocomplete="current-password" required>
        </div>
        <label style="font-weight:normal;"><input type="checkbox" name="remember" value="1"> {{ __('Remember me') }}</label>
        <div class="actions"><button class="btn" type="submit" style="width:100%;">{{ __('Login') }}</button></div>
        <p style="text-align:center;margin-bottom:0;">{{ __("Don't have an account?") }} <a href="{{ route('register') }}">{{ __('Register') }}</a></p>
    </form>
</div>
@endsection
