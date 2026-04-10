<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\ProformaInvoice;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function dashboard(): View
    {
        $accountid = auth()->id();

        $stats = [
            ['label' => 'Total Clients', 'value' => Client::where('accountid', $accountid)->count(), 'change' => '', 'tone' => 'positive'],
            ['label' => 'Total Invoices', 'value' => Invoice::where('accountid', $accountid)->count() + ProformaInvoice::where('accountid', $accountid)->count(), 'change' => '', 'tone' => 'warning'],
            ['label' => 'Total Revenue', 'value' => 'Rs ' . number_format(Payment::where('accountid', $accountid)->sum('amount'), 0), 'change' => '', 'tone' => 'positive'],
            ['label' => 'Overdue Count', 'value' => Invoice::where('accountid', $accountid)->where('status', 'overdue')->count() + ProformaInvoice::where('accountid', $accountid)->where('status', 'overdue')->count(), 'change' => '', 'tone' => 'warning'],
        ];

        return view('dashboard', [
            'title' => 'Dashboard',
            'stats' => $stats,
            'upcomingInvoices' => [],
            'activities' => [
                'Welcome to your Billing Workspace.',
            ],
        ]);
    }
}
