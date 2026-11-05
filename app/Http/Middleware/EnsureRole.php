<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Role-based access control (NFR2).
 * Usage: ->middleware('role:pic') or ->middleware('role:pic,chairman')
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user || !in_array($user->role, $roles, true)) {
            abort(403, __('You are not allowed to access this page.'));
        }

        return $next($request);
    }
}
