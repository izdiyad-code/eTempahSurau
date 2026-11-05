<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the language chosen with the EN / BM switch (all roles).
 * Priority: session choice, then the user's saved language, then English.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('locale');

        if (!$locale && $request->user()) {
            $locale = $request->user()->preferred_language;
        }

        App::setLocale(in_array($locale, ['en', 'ms'], true) ? $locale : 'en');

        return $next($request);
    }
}
