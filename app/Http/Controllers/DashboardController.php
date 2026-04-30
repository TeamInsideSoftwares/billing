<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function dashboard(): View
    {
        $accountid = auth()->id();

        $stats = [
            [
                'label' => 'Total Clients',
                'value' => Client::where('accountid', $accountid)->count(),
                'change' => '+12%',
                'icon' => 'fa-users',
                'tone' => 'brand'
            ],
            [
                'label' => 'Total Revenue',
                'value' => 'Rs ' . number_format(Payment::where('accountid', $accountid)->sum('amount'), 0),
                'change' => '+8%',
                'icon' => 'fa-wallet',
                'tone' => 'success'
            ],
            [
                'label' => 'Total Invoices',
                'value' => Invoice::where('accountid', $accountid)->count(),
                'change' => '+5%',
                'icon' => 'fa-file-invoice-dollar',
                'tone' => 'brand'
            ],
            [
                'label' => 'Overdue Invoices',
                'value' => Invoice::where('accountid', $accountid)->where('status', 'overdue')->count(),
                'change' => '-2%',
                'icon' => 'fa-exclamation-triangle',
                'tone' => 'danger'
            ],
        ];

        // Mocking monthly payments data for the chart
        $monthlyPayments = [
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            'data' => [12000, 19000, 15000, 25000, 22000, 30000, 28000, 35000, 32000, 40000, 38000, 45000]
        ];

        $recentRevenue = Payment::where('accountid', $accountid)
            ->with('client')
            ->latest()
            ->take(5)
            ->get()
            ->map(function($payment) {
                return [
                    'title' => 'Payment from ' . ($payment->client->name ?? 'Unknown Client'),
                    'amount' => 'Rs ' . number_format($payment->amount, 0),
                    'date' => $payment->created_at->format('d M, Y'),
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
