@extends('layouts.app')
@section('title', 'Reset Password')
@section('content')
<div class="card" style="max-width:560px;margin:auto">
    <h1>Reset Password</h1>
    <form method="POST" action="{{ route('password.update') }}">@csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <label>Email address</label><input type="email" name="email" value="{{ old('email', $email) }}" required>
        <label style="margin-top:14px">New password</label><input type="password" name="password" required minlength="8">
        <label style="margin-top:14px">Confirm new password</label><input type="password" name="password_confirmation" required minlength="8">
        <div class="actions"><button class="btn" type="submit">Save new password</button></div>
    </form>
</div>
@endsection
