<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'e-TempahSurau') | {{ __('e-TempahSurau') }}</title>

    <style>
        * { box-sizing: border-box; font-family: Arial, Helvetica, sans-serif; }
        body { margin: 0; background: #f5f7f5; color: #1f2937; font-size: 17px; line-height: 1.55; }
        a { color: #166534; }

        .navbar { background: #14532d; color: #fff; padding: 14px 5%; display: flex; flex-wrap: wrap;
                  align-items: center; justify-content: space-between; gap: 12px; }
        .brand { color: #fff; text-decoration: none; font-size: 22px; font-weight: bold; }
        .nav-links { display: flex; flex-wrap: wrap; align-items: center; gap: 6px 16px; }
        .nav-links a, .nav-links button.link { color: #fff; text-decoration: none; font-size: 16px; padding: 8px 4px;
                  background: none; border: 0; cursor: pointer; }
        .nav-links a:hover, .nav-links a.active { text-decoration: underline; }
        .badge { background: #facc15; color: #422006; border-radius: 99px; padding: 1px 8px; font-size: 13px; font-weight: bold; }
        .role-chip { background: rgba(255,255,255,.18); padding: 3px 10px; border-radius: 99px; font-size: 13px; }

        .container { max-width: 1150px; margin: 28px auto 60px; padding: 0 20px; }
        h1 { color: #14532d; font-size: 32px; margin: 0 0 6px; }
        h2 { color: #14532d; font-size: 22px; margin: 0 0 14px; }
        .lead { color: #64748b; margin: 0 0 24px; }

        .card { background: #fff; border-radius: 14px; box-shadow: 0 6px 20px rgba(0,0,0,.06); padding: 24px; margin-bottom: 22px; }
        .grid { display: grid; gap: 18px; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); margin-bottom: 22px; }
        .stat { background: #fff; border-radius: 14px; padding: 20px; box-shadow: 0 6px 20px rgba(0,0,0,.06); text-decoration: none; color: inherit; display: block; }
        .stat strong { display: block; font-size: 34px; color: #14532d; }
        .stat span { color: #64748b; font-size: 15px; }
        .stat.alert strong { color: #b45309; }

        .alert-box { padding: 14px 18px; border-radius: 10px; margin-bottom: 18px; }
        .alert-success { background: #dcfce7; color: #166534; }
        .alert-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .alert-error ul { margin: 6px 0 0 18px; padding: 0; }

        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 12px 10px; border-bottom: 1px solid #e5e7eb; vertical-align: top; font-size: 15px; }
        th { background: #f0fdf4; color: #14532d; }
        .table-wrap { overflow-x: auto; }

        .btn { display: inline-block; padding: 12px 20px; border: 0; border-radius: 8px; text-decoration: none;
               font-size: 16px; font-weight: bold; cursor: pointer; background: #15803d; color: #fff; }
        .btn:hover { background: #166534; }
        .btn-secondary { background: #f0fdf4; color: #14532d; border: 1px solid #bbf7d0; }
        .btn-secondary:hover { background: #dcfce7; }
        .btn-danger { background: #b91c1c; }
        .btn-danger:hover { background: #991b1b; }
        .btn-sm { padding: 8px 14px; font-size: 14px; }

        label { display: block; font-weight: bold; margin-bottom: 6px; }
        input, select, textarea { width: 100%; padding: 13px; border: 1px solid #cbd5e1; border-radius: 9px; font-size: 16px; background: #fff; }
        input:focus, select:focus, textarea:focus { outline: 3px solid #86efac; border-color: #15803d; }
        input[type=checkbox], input[type=radio] { width: auto; }
        textarea { min-height: 100px; resize: vertical; }
        .form-grid { display: grid; gap: 18px 20px; grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .full { grid-column: 1 / -1; }
        .field-error { color: #b91c1c; font-size: 14px; }
        .req { color: #dc2626; }
        .hint { color: #64748b; font-size: 14px; }
        .actions { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 18px; }
        .inline-form { display: inline; }

        .pill { display: inline-block; padding: 3px 12px; border-radius: 99px; font-size: 13px; font-weight: bold; }
        .pill-pending, .pill-proof_submitted { background: #fef3c7; color: #92400e; }
        .pill-approved, .pill-verified { background: #dcfce7; color: #166534; }
        .pill-rejected, .pill-forfeited, .pill-cancelled { background: #fee2e2; color: #991b1b; }
        .pill-completed, .pill-refunded { background: #dbeafe; color: #1e40af; }
        .pill-partially_forfeited { background: #ffedd5; color: #9a3412; }

        .detail { display: grid; grid-template-columns: 180px 1fr; gap: 8px 16px; }
        .detail dt { font-weight: bold; color: #475569; }
        .detail dd { margin: 0; }
        .filters { display: flex; flex-wrap: wrap; gap: 12px; align-items: end; margin-bottom: 18px; }
        .filters > div { min-width: 150px; flex: 1; }
        .pagination { margin-top: 18px; }
        .pagination nav svg { width: 18px; height: 18px; }
        footer { text-align: center; color: #64748b; padding: 26px 10px; font-size: 14px; }

        @media (max-width: 700px) {
            .form-grid { grid-template-columns: 1fr; }
            .detail { grid-template-columns: 1fr; }
            h1 { font-size: 26px; }
            .container { padding: 0 14px; }
        }

        .lang-switch { display: inline-flex; border: 1px solid rgba(255,255,255,.5); border-radius: 99px; overflow: hidden; }
        .lang-switch a { padding: 4px 14px !important; font-size: 14px !important; text-decoration: none !important; }
        .lang-switch a.on { background: #fff; color: #14532d; font-weight: bold; }
        /* ---- top bar, slide-in menu and footer ---- */
        .topbar-actions { display: flex; align-items: center; gap: 8px; }
        .top-btn { color: #fff; text-decoration: none; font-weight: bold; padding: 10px 18px; border-radius: 10px; border: 1px solid rgba(255,255,255,.55); }
        .top-btn:hover { background: rgba(255,255,255,.15); }
        .top-btn-fill { background: #facc15; color: #422006; border-color: #facc15; }
        .top-btn-fill:hover { background: #fde047; }
        .avatar-sm { width: 32px; height: 32px; border-radius: 50%; background: #facc15; color: #422006; font-weight: bold; display: flex; align-items: center; justify-content: center; font-size: 15px; }
        .icon-btn { position: relative; display: inline-flex; align-items: center; justify-content: center; width: 46px; height: 46px;
                    border-radius: 12px; background: rgba(255,255,255,.14); color: #fff; font-size: 22px; text-decoration: none; cursor: pointer; border: 0; }
        .icon-btn:hover { background: rgba(255,255,255,.26); }
        .icon-btn .badge { position: absolute; top: 2px; right: 2px; font-size: 11px; padding: 0 6px; }
        #menuToggle { position: absolute; opacity: 0; pointer-events: none; }
        .drawer-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.5); opacity: 0; visibility: hidden; transition: opacity .25s; z-index: 90; }
        .drawer { position: fixed; top: 0; right: 0; bottom: 0; width: 330px; max-width: 88vw; background: #fff; z-index: 100; overflow-y: auto;
                  transform: translateX(100%); transition: transform .28s ease; box-shadow: -8px 0 30px rgba(0,0,0,.2); display: flex; flex-direction: column; }
        #menuToggle:checked ~ .drawer-overlay { opacity: 1; visibility: visible; }
        #menuToggle:checked ~ .drawer { transform: translateX(0); }
        #menuToggle:focus-visible ~ .drawer { outline: 3px solid #86efac; }
        .drawer-head { background: #14532d; color: #fff; padding: 20px 20px 18px; display: flex; gap: 14px; align-items: center; position: relative; }
        .avatar { width: 52px; height: 52px; border-radius: 50%; background: #facc15; color: #422006; font-weight: bold; font-size: 22px;
                  display: flex; align-items: center; justify-content: center; flex: none; }
        .drawer-head strong { display: block; font-size: 17px; line-height: 1.25; }
        .drawer-head .role-chip { display: inline-block; margin-top: 4px; }
        .drawer-close { position: absolute; top: 10px; right: 12px; color: #fff; font-size: 26px; cursor: pointer; line-height: 1; padding: 4px 8px; }
        .drawer-section { padding: 14px 12px 4px; }
        .drawer-section h4 { margin: 0 8px 6px; font-size: 12px; letter-spacing: .08em; text-transform: uppercase; color: #64748b; }
        .drawer a.item, .drawer button.item { display: flex; align-items: center; gap: 14px; width: 100%; padding: 12px 10px; border-radius: 10px; color: #1f2937;
                  text-decoration: none; font-size: 16px; background: none; border: 0; cursor: pointer; text-align: left; }
        .drawer a.item:hover, .drawer button.item:hover { background: #f0fdf4; }
        .drawer a.item.current { background: #dcfce7; font-weight: bold; }
        .drawer .ico { width: 28px; text-align: center; font-size: 20px; flex: none; }
        .drawer .item .badge { margin-left: auto; }
        .drawer .lang-row { display: flex; align-items: center; gap: 14px; padding: 10px; }
        .drawer .lang-switch { border-color: #15803d; margin-left: auto; }
        .drawer .lang-switch a { color: #14532d; }
        .drawer .lang-switch a.on { background: #15803d; color: #fff; }
        .drawer-foot { margin-top: auto; padding: 12px; border-top: 1px solid #e5e7eb; }
        .drawer-guest { padding: 16px; display: grid; gap: 10px; }
        html { scroll-behavior: smooth; }
        .ic { width: 1.15em; height: 1.15em; vertical-align: -0.2em; flex: none; }
        .site-footer { background: #14532d; color: #dcfce7; margin-top: 50px; padding: 34px 5% 18px; }
        .footer-inner { max-width: 1150px; margin: 0 auto; display: flex; flex-wrap: wrap; gap: 20px 40px; align-items: center; justify-content: space-between; }
        .footer-brand strong { display: block; color: #fff; font-size: 22px; }
        .footer-brand span { font-size: 14px; opacity: .85; }
        .site-links { display: flex; flex-wrap: wrap; align-items: center; gap: 8px 24px; }
        .site-links a { color: #fff; text-decoration: none; font-size: 16px; display: inline-flex; align-items: center; gap: 8px; padding: 6px 2px; }
        .site-links a:hover { text-decoration: underline; }
        .site-menu { display: inline-flex; align-items: center; gap: 8px; background: #facc15; color: #422006; font-weight: bold; padding: 10px 18px; border-radius: 10px; cursor: pointer; }
        .site-menu:hover { background: #fde047; }
        .navbar .site-links { margin-left: auto; margin-right: 12px; }
        .footer-copy { max-width: 1150px; margin: 24px auto 0; padding-top: 14px; border-top: 1px solid rgba(255,255,255,.2); text-align: center; font-size: 13px; opacity: .8; }
        @media (max-width: 700px) {
            .brand { font-size: 19px; }
        .navbar .site-links a { display: none; }
        .navbar .site-links { margin-left: auto; }
        .top-btn { padding: 8px 12px; font-size: 15px; }
        .footer-inner { flex-direction: column; align-items: flex-start; }
            th, td { font-size: 14px; padding: 10px 8px; }
        }
    </style>
    @stack('head')
</head>
<body>
@php($u = auth()->user())

<input type="checkbox" id="menuToggle" aria-label="{{ __('Menu') }}">

<nav class="navbar">
    <a class="brand" href="{{ route('home') }}">{{ __('e-TempahSurau') }}</a>

    @include('partials.site-links')

    <div class="topbar-actions">
        @guest
            <a class="top-btn" href="{{ route('login') }}">{{ __('Login') }}</a>
            <a class="top-btn top-btn-fill" href="{{ route('register') }}">{{ __('Register') }}</a>
        @endguest

        <a class="icon-btn" href="{{ route('announcements.index') }}" title="{{ __('Announcements') }}" aria-label="{{ __('Announcements') }}">
            <x-icon name="bell" size="22" />
            @auth
                @if ($u->bellCount() > 0)<span class="badge">{{ $u->bellCount() }}</span>@endif
            @endauth
        </a>

    </div>
</nav>

<label for="menuToggle" class="drawer-overlay" aria-hidden="true"></label>

<aside class="drawer" aria-label="{{ __('Menu') }}">
    @auth
        <div class="drawer-head">
            <div class="avatar">{{ mb_strtoupper(mb_substr($u->name, 0, 1)) }}</div>
            <div>
                <strong>{{ $u->name }}</strong>
                <span class="role-chip">{{ __($u->role === 'public' ? 'Public Booker' : ($u->role === 'pic' ? 'Secretary / PIC' : 'Chairman')) }}</span>
            </div>
            <label for="menuToggle" class="drawer-close" aria-label="{{ __('Close menu') }}"><x-icon name="close" size="24" /></label>
        </div>
    @else
        <div class="drawer-head">
            <div class="avatar"><x-icon name="building" size="28" /></div>
            <div><strong>{{ __('e-TempahSurau') }}</strong><span class="role-chip">{{ __('Welcome') }}</span></div>
            <label for="menuToggle" class="drawer-close" aria-label="{{ __('Close menu') }}"><x-icon name="close" size="24" /></label>
        </div>
        <div class="drawer-guest">
            <a class="btn" href="{{ route('login') }}" style="text-align:center;"><x-icon name="login" /> {{ __('Login') }}</a>
            <a class="btn btn-secondary" href="{{ route('register') }}" style="text-align:center;"><x-icon name="user-plus" /> {{ __('Register') }}</a>
        </div>
    @endauth

    <div class="drawer-section">
        <h4>{{ __('Explore') }}</h4>
        <a class="item" href="{{ route('home') }}"><span class="ico"><x-icon name="home" /></span>{{ __('Home') }}</a>
        <a class="item" href="{{ route('home') }}#calendar-section"><span class="ico"><x-icon name="calendar" /></span>{{ __('Calendar') }}</a>
        <a class="item" href="{{ route('home') }}#facilities"><span class="ico"><x-icon name="building" /></span>{{ __('Facilities') }}</a>
        <a class="item" href="{{ route('home') }}#about"><span class="ico"><x-icon name="info" /></span>{{ __('About the Surau') }}</a>
        <a class="item {{ request()->routeIs('announcements.index') ? 'current' : '' }}" href="{{ route('announcements.index') }}">
            <span class="ico"><x-icon name="bell" /></span>{{ __('Announcements') }}
            @auth @if ($u->bellCount() > 0)<span class="badge">{{ $u->bellCount() }}</span>@endif @endauth
        </a>
    </div>

    @auth
        <div class="drawer-section">
            <h4>{{ $u->role === 'public' ? __('My Bookings') : __('Manage') }}</h4>
            @if ($u->role === 'public')
                <a class="item" href="{{ route('home') }}#calendar-section"><span class="ico"><x-icon name="edit" /></span>{{ __('Book a date') }}</a>
                <a class="item {{ request()->routeIs('booker.*') ? 'current' : '' }}" href="{{ route('booker.bookings.index') }}"><span class="ico"><x-icon name="list" /></span>{{ __('My Bookings') }}</a>
            @elseif ($u->role === 'pic')
                <a class="item {{ request()->routeIs('dashboard') ? 'current' : '' }}" href="{{ route('dashboard') }}"><span class="ico"><x-icon name="grid" /></span>{{ __('Dashboard') }}</a>
                <a class="item {{ request()->routeIs('pic.bookings') ? 'current' : '' }}" href="{{ route('pic.bookings') }}"><span class="ico"><x-icon name="list" /></span>{{ __('Bookings') }}</a>
                <a class="item {{ request()->routeIs('pic.deposits') ? 'current' : '' }}" href="{{ route('pic.deposits') }}"><span class="ico"><x-icon name="wallet" /></span>{{ __('Deposits') }}</a>
                <a class="item {{ request()->routeIs('staff.changes') ? 'current' : '' }}" href="{{ route('staff.changes') }}"><span class="ico"><x-icon name="refresh" /></span>{{ __('Changes') }}</a>
                <a class="item {{ request()->routeIs('pic.facilities') ? 'current' : '' }}" href="{{ route('pic.facilities') }}"><span class="ico"><x-icon name="sliders" /></span>{{ __('Manage facilities') }}</a>
                <a class="item {{ request()->routeIs('pic.blocks') ? 'current' : '' }}" href="{{ route('pic.blocks') }}"><span class="ico"><x-icon name="ban" /></span>{{ __('Blocked dates') }}</a>
                <a class="item {{ request()->routeIs('staff.users') ? 'current' : '' }}" href="{{ route('staff.users') }}"><span class="ico"><x-icon name="users" /></span>{{ __('Users') }}</a>
                <a class="item {{ request()->routeIs('staff.reports') ? 'current' : '' }}" href="{{ route('staff.reports') }}"><span class="ico"><x-icon name="chart" /></span>{{ __('Reports') }}</a>
            @else
                <a class="item {{ request()->routeIs('dashboard') ? 'current' : '' }}" href="{{ route('dashboard') }}"><span class="ico"><x-icon name="grid" /></span>{{ __('Dashboard') }}</a>
                <a class="item" href="{{ route('home') }}#calendar-section"><span class="ico"><x-icon name="plus" /></span>{{ __('Add booking') }}</a>
                <a class="item {{ request()->routeIs('chairman.bookings.*') ? 'current' : '' }}" href="{{ route('chairman.bookings.index') }}"><span class="ico"><x-icon name="check" /></span>{{ __('Approvals') }}</a>
                <a class="item {{ request()->routeIs('staff.changes') ? 'current' : '' }}" href="{{ route('staff.changes') }}"><span class="ico"><x-icon name="refresh" /></span>{{ __('Changes') }}</a>
                <a class="item {{ request()->routeIs('staff.users') ? 'current' : '' }}" href="{{ route('staff.users') }}"><span class="ico"><x-icon name="users" /></span>{{ __('Users & PIC') }}</a>
                <a class="item {{ request()->routeIs('staff.reports') ? 'current' : '' }}" href="{{ route('staff.reports') }}"><span class="ico"><x-icon name="chart" /></span>{{ __('Reports') }}</a>
            @endif
        </div>

        <div class="drawer-section">
            <h4>{{ __('Account') }}</h4>
            <a class="item {{ request()->routeIs('profile.*') ? 'current' : '' }}" href="{{ route('profile.show') }}"><span class="ico"><x-icon name="user" /></span>{{ __('Profile') }}</a>
        </div>
    @endauth

    <div class="drawer-section" @guest style="margin-top:auto" @endguest>
        @guest <h4>{{ __('Account') }}</h4> @endguest
        <div class="lang-row"><span class="ico"><x-icon name="globe" /></span><span>{{ __('Language') }}</span>
            <span class="lang-switch" role="group" aria-label="{{ __('Language') }}">
                <a href="{{ route('lang.switch', 'en') }}" class="{{ app()->getLocale() === 'en' ? 'on' : '' }}" data-keep>EN</a>
                <a href="{{ route('lang.switch', 'ms') }}" class="{{ app()->getLocale() === 'ms' ? 'on' : '' }}" data-keep>BM</a>
            </span>
        </div>
    </div>

    @auth
        <div class="drawer-foot">
            <form method="POST" action="{{ route('logout') }}">@csrf
                <button type="submit" class="item"><span class="ico"><x-icon name="logout" /></span>{{ __('Logout') }}</button>
            </form>
        </div>
    @endauth
</aside>

@hasSection('fullwidth')
    @yield('fullwidth')
@else
<main class="container">
    @if (session('success'))
        <div class="alert-box alert-success" role="status">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert-box alert-error" role="alert">{{ session('error') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert-box alert-error" role="alert">
            <strong>{{ __('Please check the following:') }}</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @yield('content')
</main>
@endif

<footer class="site-footer">
    <div class="footer-inner">
        <div class="footer-brand">
            <strong>{{ __('e-TempahSurau') }}</strong>
            <span>{{ __('Madrasah Seksyen 11, Shah Alam') }}</span>
        </div>

        @include('partials.site-links')
    </div>
    <div class="footer-copy">{{ __('Madrasah Seksyen 11, Shah Alam') }} &middot; {{ __('e-TempahSurau') }} &middot; {{ __('Surau Booking Management System') }}</div>
</footer>

<script>
    (function () {
        var t = document.getElementById('menuToggle');
        // Esc closes the slide-in menu; choosing a link closes it too
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') t.checked = false; });
        document.querySelectorAll('.drawer a.item').forEach(function (a) { a.addEventListener('click', function () { t.checked = false; }); });
        document.querySelectorAll('.site-menu').forEach(function (l) {
            l.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); t.checked = !t.checked; } });
        });
    })();
</script>
</body>
</html>
