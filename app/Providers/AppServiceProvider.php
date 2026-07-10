<?php

namespace App\Providers;

use App\Models\Account;
use App\Models\FinancialYear;
use App\Models\TermsCondition;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;

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
        Gate::define('viewPulse', function ($user) {
            return true;
        });

        if (str_contains(config('app.url'), 'skoolready.com')) {
            URL::forceRootUrl(config('app.url'));
            if (str_starts_with(config('app.url'), 'https://')) {
                URL::forceScheme('https');
            }
        }

        // Share account business_name globally
        view()->composer('*', function ($view) {
            static $memoizedAccount = null;
            static $memoizedFinancialYears = null;
            static $memoizedSelectedYearId = null;

            if (! Auth::check()) {
                return;
            }

            $viewData = $view->getData();
            
            if ($memoizedAccount === null) {
                $account = $viewData['account'] ?? null;
                if (! $account) {
                    $user = Auth::user();
                    if ($user instanceof Account) {
                        $account = $user;
                    } elseif (method_exists($user, 'account')) {
                        $account = $user->account;
                    }
                }
                $memoizedAccount = $account;
            }

            if ($memoizedAccount) {
                if (! array_key_exists('account', $viewData)) {
                    $view->with('account', $memoizedAccount);
                }

                if (! array_key_exists('sharedFinancialYears', $view->getData())) {
                    if ($memoizedFinancialYears === null) {
                        $memoizedFinancialYears = FinancialYear::query()
                            ->where('accountid', $memoizedAccount->accountid)
                            ->orderByDesc('default')
                            ->orderByDesc('financial_year')
                            ->get(['fy_id', 'financial_year', 'default']);
                        
                        $selectedYearId = trim((string) session('selected_financial_year_id', ''));
                        if ($selectedYearId === '' || ! $memoizedFinancialYears->contains('fy_id', $selectedYearId)) {
                            $selectedYearId = (string) (
                                $memoizedFinancialYears->firstWhere('default', true)?->fy_id
                                ?? $memoizedFinancialYears->first()?->fy_id
                                ?? ''
                            );
                            if ($selectedYearId !== '') {
                                session(['selected_financial_year_id' => $selectedYearId]);
                            }
                        }
                        $memoizedSelectedYearId = $selectedYearId;
                    }

                    $view->with([
                        'sharedFinancialYears' => $memoizedFinancialYears,
                        'sharedSelectedFinancialYearId' => $memoizedSelectedYearId,
                        'sharedSelectedFinancialYear' => $memoizedFinancialYears->firstWhere('fy_id', $memoizedSelectedYearId),
                    ]);
                }
            }
        });

        // Route model binding for TermsCondition
        Route::bind('term', function ($value) {
            return TermsCondition::where('tc_id', $value)->firstOrFail();
        });
    }
}
