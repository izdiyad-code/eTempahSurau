{{-- Same links + Menu button for the top bar and the footer --}}
<nav class="site-links" aria-label="{{ __('Quick links') }}">
    <a href="{{ route('home') }}"><x-icon name="home" /> {{ __('Home') }}</a>
    <a href="{{ route('home') }}#calendar-section"><x-icon name="calendar" /> {{ __('Calendar') }}</a>
    <a href="{{ route('home') }}#facilities"><x-icon name="building" /> {{ __('Facilities') }}</a>
    <a href="{{ route('home') }}#about"><x-icon name="info" /> {{ __('About the Surau') }}</a>
    <label for="menuToggle" class="site-menu" role="button" tabindex="0" aria-label="{{ __('Menu') }}">
        <x-icon name="menu" /> {{ __('Menu') }}
    </label>
</nav>
