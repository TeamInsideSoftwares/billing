<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\FinancialYear;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

abstract class Controller
{
    /**
     * Resolves the current account ID from the authenticated user context.
     * Strict multi-tenant scoping - no hardcoded fallbacks to other accounts.
     * 
     * @return string
     */
    protected function resolveAccountId(): string
    {
        if (!auth()->check()) {
            abort(401, 'Unauthorized');
        }

        $user = auth()->user();
        
        // 1. If the logged-in entity is itself an Account (primary key is accountid)
        if ($user instanceof \App\Models\Account) {
            return $user->accountid;
        }

        // 2. If it's a User model with an accountid field
        if (isset($user->accountid) && !empty($user->accountid)) {
            return $user->accountid;
        }

        // 3. Fallback to auth ID if it looks like an account ID
        $authId = auth()->id();
        if (is_string($authId) && Str::startsWith($authId, 'ACC')) {
            return $authId;
        }

        abort(403, 'Account context not found. Please contact support.');
    }

    /**
     * Resolve all financial years for the current account in display order.
     *
     * @return \Illuminate\Support\Collection<int, \App\Models\FinancialYear>
     */
    protected function resolveFinancialYears(string $accountid): Collection
    {
        return FinancialYear::query()
            ->where('accountid', $accountid)
            ->orderByDesc('default')
            ->orderByDesc('financial_year')
            ->get(['fy_id', 'financial_year', 'default']);
    }

    protected function resolveSelectedFinancialYearId(string $accountid): string
    {
        $financialYears = $this->resolveFinancialYears($accountid);
        $sessionFyId = trim((string) session('selected_financial_year_id', ''));

        if ($sessionFyId !== '' && $financialYears->contains('fy_id', $sessionFyId)) {
            return $sessionFyId;
        }

        $defaultFyId = (string) ($financialYears->firstWhere('default', true)?->fy_id ?? $financialYears->first()?->fy_id ?? '');

        if ($defaultFyId !== '') {
            session(['selected_financial_year_id' => $defaultFyId]);
        }

        return $defaultFyId;
    }

    protected function resolveSelectedFinancialYear(string $accountid): ?FinancialYear
    {
        $selectedFyId = $this->resolveSelectedFinancialYearId($accountid);
        if ($selectedFyId === '') {
            return null;
        }

        return $this->resolveFinancialYears($accountid)->firstWhere('fy_id', $selectedFyId);
    }

    /**
     * Resolve the active financial-year date window for forms.
     *
     * @return array{
     *     fy_id:string,
     *     financial_year:string,
     *     min_date:string,
     *     max_date:string,
     *     issue_max_date:string,
     *     due_max_date:string,
     *     default_date:string,
     *     default_issue_date:string,
     *     default_due_date:string,
     *     label:string
     * }
     */
    protected function resolveFinancialYearDateBounds(string $accountid): array
    {
        $account = Account::query()->find($accountid);
        $financialYear = $this->resolveSelectedFinancialYear($accountid);

        $fyStart = trim((string) ($account?->fy_startdate ?? '04-01'));
        if (!preg_match('/^(\d{2})-(\d{2})$/', $fyStart, $matches)) {
            $fyStart = '04-01';
            $matches = ['04-01', '04', '01'];
        }

        $startMonth = max(1, min(12, (int) $matches[1]));
        $startDay = max(1, min(31, (int) $matches[2]));
        $financialYearLabel = trim((string) ($financialYear?->financial_year ?? ''));

        $startYear = null;
        $endYear = null;

        if ($financialYearLabel !== '' && preg_match('/^(\d{4})\s*[-\/]\s*(\d{2,4})$/', $financialYearLabel, $fyMatches)) {
            $startYear = (int) $fyMatches[1];
            $endYearValue = (string) $fyMatches[2];
            $endYear = strlen($endYearValue) === 2
                ? (int) (substr((string) $startYear, 0, 2) . $endYearValue)
                : (int) $endYearValue;
        }

        if ($startYear === null || $endYear === null || $endYear <= $startYear) {
            $today = Carbon::today();
            $candidateStart = Carbon::create($today->year, $startMonth, 1)->day(
                min($startDay, Carbon::create($today->year, $startMonth, 1)->daysInMonth)
            );

            if ($today->lt($candidateStart)) {
                $startYear = $today->year - 1;
            } else {
                $startYear = $today->year;
            }

            $endYear = $startYear + 1;
        }

        $startOfFinancialYear = Carbon::create($startYear, $startMonth, 1)->day(
            min($startDay, Carbon::create($startYear, $startMonth, 1)->daysInMonth)
        )->startOfDay();
        $endOfFinancialYear = Carbon::create($endYear, $startMonth, 1)->day(
            min($startDay, Carbon::create($endYear, $startMonth, 1)->daysInMonth)
        )->subDay()->endOfDay();

        if ($endOfFinancialYear->lt($startOfFinancialYear)) {
            $endOfFinancialYear = (clone $startOfFinancialYear)->addYear()->subDay()->endOfDay();
        }

        $today = Carbon::today();
        $isFutureFinancialYear = $today->lt($startOfFinancialYear);
        $isCurrentFinancialYear = $today->betweenIncluded($startOfFinancialYear, $endOfFinancialYear);

        $issueMaxDate = $isCurrentFinancialYear
            ? $today->toDateString()
            : $endOfFinancialYear->toDateString();
        $dueMaxDate = $endOfFinancialYear->toDateString();

        $defaultIssueDate = '';
        $defaultDueDate = '';

        if ($isCurrentFinancialYear) {
            $defaultIssueDate = $today->toDateString();
            $defaultDueDate = Carbon::parse($defaultIssueDate)->addDays(7)->toDateString();

            if ($defaultDueDate > $dueMaxDate) {
                $defaultDueDate = $dueMaxDate;
            }
        } elseif (!$isFutureFinancialYear) {
            $defaultIssueDate = $endOfFinancialYear->toDateString();
            $defaultDueDate = $endOfFinancialYear->toDateString();
        }

        return [
            'fy_id' => (string) ($financialYear?->fy_id ?? ''),
            'financial_year' => $financialYearLabel,
            'min_date' => $startOfFinancialYear->toDateString(),
            'max_date' => $issueMaxDate,
            'issue_max_date' => $issueMaxDate,
            'due_max_date' => $dueMaxDate,
            'default_date' => $defaultIssueDate,
            'default_issue_date' => $defaultIssueDate,
            'default_due_date' => $defaultDueDate,
            'label' => $financialYearLabel !== '' ? $financialYearLabel : trim($startOfFinancialYear->format('Y') . '-' . $endOfFinancialYear->format('Y')),
        ];
    }

}
