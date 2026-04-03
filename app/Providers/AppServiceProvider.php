<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (str_contains(config('app.url'), 'alpha.insidesoftwares.com')) {
            URL::forceRootUrl(config('app.url'));
            if (str_starts_with(config('app.url'), 'https://')) {
                URL::forceScheme('https');
            }
        }

        // Route model binding for TermsCondition
        \Illuminate\Support\Facades\Route::bind('term', function ($value) {
            return \App\Models\TermsCondition::where('tc_id', $value)->firstOrFail();
        });
    }
}
