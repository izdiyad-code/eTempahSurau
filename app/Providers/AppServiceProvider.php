<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Simple Previous / Next pager that matches the site design.
        Paginator::defaultView('partials.pager');
        Paginator::defaultSimpleView('partials.pager');
    }
}
