@if ($paginator->hasPages())
    <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
        @if ($paginator->onFirstPage())
            <span class="btn btn-secondary btn-sm" style="opacity:.5;">&laquo; {{ __('Previous') }}</span>
        @else
            <a class="btn btn-secondary btn-sm" href="{{ $paginator->previousPageUrl() }}">&laquo; {{ __('Previous') }}</a>
        @endif

        <span class="hint">{{ __('Page') }} {{ $paginator->currentPage() }}</span>

        @if ($paginator->hasMorePages())
            <a class="btn btn-secondary btn-sm" href="{{ $paginator->nextPageUrl() }}">{{ __('Next') }} &raquo;</a>
        @else
            <span class="btn btn-secondary btn-sm" style="opacity:.5;">{{ __('Next') }} &raquo;</span>
        @endif
    </div>
@endif
