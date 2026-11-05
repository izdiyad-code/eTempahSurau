@extends('layouts.app')
@section('title', 'Forgot Password')
@section('content')
<div class="card" style="max-width:560px;margin:auto">
    <h1>Forgot Password</h1>
    <p class="lead">Enter your email address and we will send you a password reset link.</p>
    <form method="POST" action="{{ route('password.email') }}">@csrf
        <label>Email address</label><input type="email" name="email" value="{{ old('email') }}" required autofocus>
        <div class="actions"><button class="btn" type="submit">Send reset link</button><a class="btn btn-secondary" href="{{ route('login') }}">Back to login</a></div>
    </form>
</div>
@endsection
