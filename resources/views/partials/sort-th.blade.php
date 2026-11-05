@php
    $isCurrent = request('sort') === $key;
    $nextDir = ($isCurrent && request('dir') === 'asc') ? 'desc' : 'asc';
@endphp
<th><a href="{{ request()->fullUrlWithQuery(['sort' => $key, 'dir' => $nextDir, 'page' => null]) }}"
       style="color:inherit;text-decoration:none;white-space:nowrap;">
    {{ $label }}@if ($isCurrent) {{ request('dir') === 'asc' ? '▲' : '▼' }}@else <span style="opacity:.35">↕</span>@endif
</a></th>
