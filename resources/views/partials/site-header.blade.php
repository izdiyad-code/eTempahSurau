@php($navUser = auth()->user())
<header class="ets-header">
    <div class="ets-header-inner">
        <a class="ets-brand" href="{{ route('home') }}" aria-label="e-TempahSurau home">e-TempahSurau</a>
        <div class="ets-header-actions">
            <div class="ets-language-wrap">
                <button type="button" class="ets-icon-button ets-language-button" id="etsLanguageToggle" aria-expanded="false" aria-controls="etsLanguageMenu">
                    <span class="ets-globe" aria-hidden="true">◎</span>
                    <span data-i18n="Language">Language</span>
                    <strong>{{ app()->getLocale() === 'ms' ? 'BM' : 'EN' }}</strong>
                    <span class="ets-chevron" aria-hidden="true">▾</span>
                </button>
                <div class="ets-language-menu" id="etsLanguageMenu" hidden>
                    <a href="{{ route('language.switch', 'en') }}" class="{{ app()->getLocale() === 'en' ? 'is-current' : '' }}">
                        <span aria-hidden="true">🇬🇧</span><span>English</span>
                        @if (app()->getLocale() === 'en') <span class="ets-check" aria-hidden="true">✓</span> @endif
                    </a>
                    <a href="{{ route('language.switch', 'ms') }}" class="{{ app()->getLocale() === 'ms' ? 'is-current' : '' }}">
                        <span aria-hidden="true">🇲🇾</span><span>Bahasa Melayu</span>
                        @if (app()->getLocale() === 'ms') <span class="ets-check" aria-hidden="true">✓</span> @endif
                    </a>
                </div>
            </div>
            <button type="button" class="ets-icon-button ets-menu-toggle" id="etsMenuToggle" aria-expanded="false" aria-controls="etsDrawer" aria-label="Open menu">
                <span class="ets-hamburger" aria-hidden="true"><i></i><i></i><i></i></span>
                <span data-i18n="Menu">Menu</span>
            </button>
        </div>
    </div>
</header>

<div class="ets-drawer-backdrop" id="etsDrawerBackdrop" hidden></div>
<aside class="ets-drawer" id="etsDrawer" aria-hidden="true" inert aria-label="Site menu">
    <div class="ets-drawer-head">
        <div>
            <strong data-i18n="Navigation">Navigation</strong>
            @auth
                <small>{{ $navUser->name }} · {{ strtoupper($navUser->role) }}</small>
            @else
                <small data-i18n="Welcome to e-TempahSurau">Welcome to e-TempahSurau</small>
            @endauth
        </div>
        <button type="button" class="ets-close-button" id="etsMenuClose" aria-label="Close menu">&times;</button>
    </div>

    <nav class="ets-drawer-links">
        @guest
            <a href="{{ route('login') }}"><span class="ets-menu-icon">↪</span><span data-i18n="Login">Login</span></a>
            <a href="{{ route('register') }}"><span class="ets-menu-icon">＋</span><span data-i18n="Register">Register</span></a>
        @else
            @if ($navUser->role === 'public')
                <a href="{{ route('booker.bookings.index') }}"><span class="ets-menu-icon">▣</span><span data-i18n="My Bookings">My Bookings</span></a>
            @elseif ($navUser->role === 'pic')
                <a class="ets-drawer-feature" href="{{ route('dashboard') }}"><span class="ets-menu-icon">⌂</span><span data-i18n="PIC Panel">PIC Panel</span></a>
                <a href="{{ route('pic.bookings') }}"><span class="ets-menu-icon">▣</span><span data-i18n="Bookings">Bookings</span></a>
                <a href="{{ route('pic.deposits') }}"><span class="ets-menu-icon">＄</span><span data-i18n="Deposits">Deposits</span></a>
                <a href="{{ route('staff.changes') }}"><span class="ets-menu-icon">↻</span><span data-i18n="Changes">Changes</span></a>
                <a href="{{ route('pic.facilities') }}"><span class="ets-menu-icon">▤</span><span data-i18n="Facilities & Equipment">Facilities &amp; Equipment</span></a>
                <a href="{{ route('pic.blocks') }}"><span class="ets-menu-icon">▦</span><span data-i18n="Blocked dates">Blocked dates</span></a>
                <a href="{{ route('staff.reports') }}"><span class="ets-menu-icon">▥</span><span data-i18n="Reports">Reports</span></a>
                <a href="{{ route('staff.users.index') }}"><span class="ets-menu-icon">♙</span><span data-i18n="Users">Users</span></a>
            @elseif ($navUser->role === 'chairman')
                <a class="ets-drawer-feature" href="{{ route('dashboard') }}"><span class="ets-menu-icon">⌂</span><span data-i18n="Chairman Dashboard">Chairman Dashboard</span></a>
                <a href="{{ route('chairman.bookings.index') }}"><span class="ets-menu-icon">✓</span><span data-i18n="Approvals">Approvals</span></a>
                <a href="{{ route('chairman.bookings.create', ['date' => today()->toDateString()]) }}"><span class="ets-menu-icon">＋</span><span data-i18n="New Booking">New Booking</span></a>
                <a href="{{ route('staff.changes') }}"><span class="ets-menu-icon">↻</span><span data-i18n="Changes">Changes</span></a>
                <a href="{{ route('staff.reports') }}"><span class="ets-menu-icon">▥</span><span data-i18n="Reports">Reports</span></a>
                <a href="{{ route('staff.users.index') }}"><span class="ets-menu-icon">♙</span><span data-i18n="Users">Users</span></a>
            @endif

            <div class="ets-drawer-divider"></div>
            <a href="{{ route('notifications.index') }}"><span class="ets-menu-icon">♧</span><span data-i18n="Notifications & Announcements">Notifications &amp; Announcements</span>
                @if ($navUser->unreadNotificationCount() > 0) <span class="ets-menu-badge">{{ $navUser->unreadNotificationCount() }}</span> @endif
            </a>
            <a href="{{ route('profile.show') }}"><span class="ets-menu-icon">◉</span><span data-i18n="Profile">Profile</span></a>
            <form method="POST" action="{{ route('logout') }}" class="ets-drawer-logout">@csrf
                <button type="submit"><span class="ets-menu-icon">↪</span><span data-i18n="Logout">Logout</span></button>
            </form>
        @endauth
    </nav>
    <div class="ets-drawer-foot">Madrasah Seksyen 11, Shah Alam</div>
</aside>
