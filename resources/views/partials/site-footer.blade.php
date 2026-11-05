<footer class="ets-footer">
    <div class="ets-footer-inner">
        <div class="ets-footer-brand">
            <strong>e-TempahSurau</strong>
            <span data-i18n="Surau Booking Management System">Surau Booking Management System</span>
        </div>
        <nav class="ets-bottom-nav" aria-label="Main navigation">
            <a href="{{ route('home') }}" class="ets-bottom-link">
                <span class="ets-bottom-icon" aria-hidden="true">⌂</span><span data-i18n="Home">Home</span>
            </a>
            <a href="{{ route('home') }}#calendar-section" class="ets-bottom-link">
                <span class="ets-bottom-icon" aria-hidden="true">▦</span><span data-i18n="Calendar">Calendar</span>
            </a>
            <a href="{{ route('home') }}#facilities" class="ets-bottom-link">
                <span class="ets-bottom-icon" aria-hidden="true">▤</span><span data-i18n="Facilities">Facilities</span>
            </a>
            <a href="{{ route('home') }}#about" class="ets-bottom-link">
                <span class="ets-bottom-icon" aria-hidden="true">ⓘ</span><span data-i18n="About Surau">About Surau</span>
            </a>
            @auth
                @if (auth()->user()->role === 'pic')
                    <a href="{{ route('dashboard') }}" class="ets-bottom-link ets-bottom-role">
                        <span class="ets-bottom-icon" aria-hidden="true">▣</span><span data-i18n="PIC Panel">PIC Panel</span>
                    </a>
                @elseif (auth()->user()->role === 'chairman')
                    <a href="{{ route('dashboard') }}" class="ets-bottom-link ets-bottom-role">
                        <span class="ets-bottom-icon" aria-hidden="true">▣</span><span data-i18n="Chairman Dashboard">Chairman Dashboard</span>
                    </a>
                @endif
            @endauth
        </nav>
        <div class="ets-footer-meta">
            <span>Madrasah Seksyen 11, Shah Alam</span>
            <span>© {{ date('Y') }} e-TempahSurau</span>
        </div>
    </div>
</footer>
