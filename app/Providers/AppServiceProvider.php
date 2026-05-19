<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Auth;
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
        if (str_contains(config('app.url'), 'skoolready.com')) {
            URL::forceRootUrl(config('app.url'));
            if (str_starts_with(config('app.url'), 'https://')) {
                URL::forceScheme('https');
            }
        }

        // Share account business_name globally
        view()->composer('*', function ($view) {
            if (!Auth::check()) {
                return;
            }

            // If controller already provided an 'account' key, don't override it.
            if (array_key_exists('account', $view->getData())) {
                return;
            }

            $user = Auth::user();
            $account = null;

            if ($user instanceof \App\Models\Account) {
                $account = $user;
            } elseif (method_exists($user, 'account')) {
                $account = $user->account;
            }

            if ($account) {
                $view->with('account', $account);
            }
        });

        // Route model binding for TermsCondition
        \Illuminate\Support\Facades\Route::bind('term', function ($value) {
            return \App\Models\TermsCondition::where('tc_id', $value)->firstOrFail();
        });
    }
}
