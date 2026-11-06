@extends('layouts.app')
@section('title', $isChairman ? __('Users & PIC') : __('Users'))

@section('content')
<h1>{{ $isChairman ? __('Users & PIC') : __('Users') }}</h1>
<p class="lead">{{ $isChairman ? __('All registered bookers and Secretary / PIC accounts.') : __('All registered public bookers.') }}</p>

<form class="card filters" method="GET">
    <div><label for="q">{{ __('Search') }}</label><input id="q" name="q" value="{{ request('q') }}" placeholder="{{ __('Name, email or phone') }}"></div>
    @if ($isChairman)
    <div><label for="role">{{ __('Role') }}</label>
        <select id="role" name="role">
            <option value="">{{ __('All') }}</option>
            <option value="pic" @selected(request('role') === 'pic')>{{ __('Secretary / PIC') }}</option>
            <option value="public" @selected(request('role') === 'public')>{{ __('Public Booker') }}</option>
        </select></div>
    @endif
    <div class="actions" style="margin:0;"><button class="btn" type="submit">{{ __('Apply') }}</button>
        <a class="btn btn-secondary" href="{{ route('staff.users') }}">{{ __('Reset') }}</a></div>
</form>

<div class="card">
<div class="table-wrap"><table>
    <thead><tr><th>{{ __('Name') }}</th><th>{{ __('Email') }}</th><th>{{ __('Phone number') }}</th><th>{{ __('Role') }}</th><th>{{ __('Category') }}</th><th>{{ __('Bookings') }}</th><th>{{ __('Joined') }}</th>@if ($isChairman)<th></th>@endif</tr></thead>
    <tbody>
    @forelse ($users as $u)
        <tr>
            <td>{{ $u->name }}</td><td>{{ $u->email }}</td><td>{{ $u->phone }}</td>
            <td>{{ $u->role === 'pic' ? __('Secretary / PIC') : __('Public Booker') }}</td>
            <td>{{ $u->role === 'public' ? ($u->booker_type === 'non_muslim' ? __('Non-Muslim') : __('Muslim')) : '-' }}</td>
            <td>{{ $u->bookings_count }}</td>
            <td>{{ $u->created_at?->locale(app()->getLocale())->translatedFormat('d M Y') }}</td>
            @if ($isChairman)
                <td>@if ($u->role === 'pic')
                    <form method="POST" action="{{ route('chairman.pics.destroy', $u) }}" onsubmit="return confirm('{{ __('Remove this PIC account?') }}')">@csrf @method('DELETE')
                        <button class="btn btn-danger btn-sm" type="submit">{{ __('Remove') }}</button></form>
                @endif</td>
            @endif
        </tr>
    @empty
        <tr><td colspan="8">{{ __('No users found.') }}</td></tr>
    @endforelse
    </tbody>
</table></div>
<div class="pagination">{{ $users->links() }}</div>
</div>

@if ($isChairman)
<form class="card" method="POST" action="{{ route('chairman.pics.store') }}">
    @csrf
    <h2>{{ __('Add Secretary / PIC') }}</h2>
    <div class="form-grid">
        <div><label for="pic_name">{{ __('Full name') }}</label><input id="pic_name" name="name" required></div>
        <div><label for="pic_email">{{ __('Email') }}</label><input type="email" id="pic_email" name="email" required></div>
        <div><label for="pic_phone">{{ __('Phone number') }}</label><input id="pic_phone" name="phone" required></div>
        <div></div>
        <div><label for="pic_password">{{ __('Password') }}</label><input type="password" id="pic_password" name="password" autocomplete="new-password" required></div>
        <div><label for="pic_password_confirmation">{{ __('Confirm password') }}</label><input type="password" id="pic_password_confirmation" name="password_confirmation" autocomplete="new-password" required></div>
    </div>
    <div class="actions"><button class="btn" type="submit">{{ __('Create PIC account') }}</button></div>
</form>
@endif
@endsection
