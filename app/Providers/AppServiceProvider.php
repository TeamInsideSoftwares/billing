<?php

namespace App\Providers;

use App\Models\Account;
use App\Models\FinancialYear;
use App\Models\TermsCondition;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
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
        if (str_contains(config('app.url'), 'skoolready.com')) {
            URL::forceRootUrl(config('app.url'));
            if (str_starts_with(config('app.url'), 'https://')) {
                URL::forceScheme('https');
            }
        }

        // Share account business_name globally
        view()->composer('*', function ($view) {
            if (! Auth::check()) {
                return;
            }

            $viewData = $view->getData();
            $account = $viewData['account'] ?? null;

            if (! $account) {
                $user = Auth::user();

                if ($user instanceof Account) {
                    $account = $user;
                } elseif (method_exists($user, 'account')) {
                    $account = $user->account;
                }
            }

            if ($account) {
                if (! array_key_exists('account', $viewData)) {
                    $view->with('account', $account);
                }

                if (! array_key_exists('sharedFinancialYears', $view->getData())) {
                    $sharedFinancialYears = FinancialYear::query()
                        ->where('accountid', $account->accountid)
                        ->orderByDesc('default')
                        ->orderByDesc('financial_year')
                        ->get(['fy_id', 'financial_year', 'default']);

                    $selectedFinancialYearId = trim((string) session('selected_financial_year_id', ''));
                    if ($selectedFinancialYearId === '' || ! $sharedFinancialYears->contains('fy_id', $selectedFinancialYearId)) {
                        $selectedFinancialYearId = (string) (
                            $sharedFinancialYears->firstWhere('default', true)?->fy_id
                            ?? $sharedFinancialYears->first()?->fy_id
                            ?? ''
                        );
                        if ($selectedFinancialYearId !== '') {
                            session(['selected_financial_year_id' => $selectedFinancialYearId]);
                        }
                    }

                    $view->with([
                        'sharedFinancialYears' => $sharedFinancialYears,
                        'sharedSelectedFinancialYearId' => $selectedFinancialYearId,
                        'sharedSelectedFinancialYear' => $sharedFinancialYears->firstWhere('fy_id', $selectedFinancialYearId),
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
