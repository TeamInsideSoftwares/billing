<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function dashboard(): View
    {
        $accountid = $this->resolveAccountId();

        $payments = Payment::query()
            ->where('accountid', $accountid)
            ->get(['paymentid', 'clientid', 'received_amount', 'tds_amount', 'payment_date', 'created_at']);

        $totalRevenue = (float) $payments->sum(function ($payment) {
            return max(0, ((float) ($payment->received_amount ?? 0)) + ((float) ($payment->tds_amount ?? 0)));
        });

        $now = now();
        $labels = [];
        $monthlyRevenueData = [];
        $monthlyTxnData = [];
        for ($i = 11; $i >= 0; $i--) {
            $monthDate = $now->copy()->startOfMonth()->subMonths($i);
            $labels[] = $monthDate->format('M');

            $monthPayments = $payments->filter(function ($payment) use ($monthDate) {
                $date = $payment->payment_date ?? $payment->created_at;
                if (!$date) {
                    return false;
                }
                $d = $date instanceof Carbon ? $date : Carbon::parse($date);
                return $d->year === $monthDate->year && $d->month === $monthDate->month;
            });

            $monthRevenue = (float) $monthPayments->sum(function ($payment) {
                return max(0, ((float) ($payment->received_amount ?? 0)) + ((float) ($payment->tds_amount ?? 0)));
            });
            $monthlyRevenueData[] = round($monthRevenue, 2);
            $monthlyTxnData[] = $monthPayments->count();
        }

        $stats = [
            [
                'label' => 'Total Clients',
                'value' => Client::where('accountid', $accountid)->count(),
                'change' => '',
                'icon' => 'fa-users',
                'tone' => 'brand'
            ],
            [
                'label' => 'Total Revenue',
                'value' => 'Rs ' . number_format($totalRevenue, 0),
                'change' => '',
                'icon' => 'fa-wallet',
                'tone' => 'success'
            ],
            [
                'label' => 'Total Invoices',
                'value' => Invoice::where('accountid', $accountid)->count(),
                'change' => '',
                'icon' => 'fa-file-invoice-dollar',
                'tone' => 'brand'
            ],
            [
                'label' => 'Overdue Invoices',
                'value' => Invoice::where('accountid', $accountid)->where('status', 'overdue')->count(),
                'change' => '',
                'icon' => 'fa-exclamation-triangle',
                'tone' => 'danger'
            ],
        ];

        $monthlyPayments = [
            'labels' => $labels,
            'data' => $monthlyRevenueData,
            'transactions' => $monthlyTxnData,
        ];

        $recentRevenue = Payment::where('accountid', $accountid)
            ->with('client')
            ->latest('payment_date')
            ->latest('created_at')
            ->take(5)
            ->get()
            ->map(function($payment) {
                $net = ((float) ($payment->received_amount ?? 0)) + ((float) ($payment->tds_amount ?? 0));
                return [
                    'title' => 'Payment from ' . ($payment->client->business_name ?? $payment->client->contact_name ?? 'Unknown Client'),
                    'amount' => 'Rs ' . number_format(abs($net), 0),
                    'date' => optional($payment->payment_date ?? $payment->created_at)?->format('d M, Y'),
                    'status' => 'success'
                ];
            });

        // Mocking recent expenses since no model exists yet
        $recentExpenses = collect([
            ['title' => 'Server Hosting', 'amount' => 'Rs 2,500', 'date' => '28 Apr, 2024', 'status' => 'danger'],
            ['title' => 'Office Supplies', 'amount' => 'Rs 1,200', 'date' => '25 Apr, 2024', 'status' => 'danger'],
            ['title' => 'Marketing Ads', 'amount' => 'Rs 5,000', 'date' => '22 Apr, 2024', 'status' => 'danger'],
            ['title' => 'Software Subscription', 'amount' => 'Rs 800', 'date' => '20 Apr, 2024', 'status' => 'danger'],
            ['title' => 'Internet Bill', 'amount' => 'Rs 1,500', 'date' => '15 Apr, 2024', 'status' => 'danger'],
        ]);

        return view('dashboard', [
            'title' => 'Dashboard',
            'subtitle' => 'Welcome back to your billing control center.',
            'stats' => $stats,
            'monthlyPayments' => $monthlyPayments,
            'recentRevenue' => $recentRevenue,
            'recentExpenses' => $recentExpenses,
        ]);
    }

}
