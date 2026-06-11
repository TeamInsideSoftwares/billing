<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function dashboard(): View
    {
        $accountid = $this->resolveAccountId();

        $payments = Payment::query()
            ->where('accountid', $accountid)
            ->get(['paymentid', 'clientid', 'received_amount', 'payment_date', 'created_at']);

        $totalRevenue = (float) $payments->sum(function ($payment) {
            return max(0, (float) ($payment->received_amount ?? 0));
        });

        $today = now()->startOfDay();

        $renewalItems = Order::query()
            ->where('accountid', $accountid)
            ->whereNotNull('end_date')
            ->where(function ($query) {
                $query->whereNotIn('status', ['cancelled', 'suspended'])
                    ->orWhereNull('status');
            })
            ->with([
                'client',
                'item:itemid,name',
            ])
            ->orderBy('end_date')
            ->get([
                'orderid',
                'order_number',
                'clientid',
                'itemid',
                'item_name',
                'status',
                'end_date',
            ])
            ->map(function (Order $order) use ($today) {
                $endDate = $order->end_date?->copy()->startOfDay();
                $daysLeft = $endDate ? $today->diffInDays($endDate, false) : null;

                return [
                    'orderid' => (string) $order->orderid,
                    'order_number' => (string) ($order->order_number ?? '-'),
                    'clientid' => (string) $order->clientid,
                    'client_name' => (string) (
                        $order->client?->business_name
                        ?: $order->client?->contact_name
                        ?: 'Client'
                    ),
                    'item_name' => (string) ($order->item_name ?: $order->item?->name ?: 'Item'),
                    'end_date' => $endDate,
                    'end_date_display' => $endDate?->format('d M Y') ?? '-',
                    'days_left' => $daysLeft,
                ];
            })
            ->filter(fn (array $item): bool => $item['end_date'] !== null)
            ->values();

        $renewalsDue30Days = $renewalItems
            ->filter(fn (array $item): bool => ($item['days_left'] ?? 0) > 0 && ($item['days_left'] ?? 0) <= 30)
            ->values();
        $renewalsDueThisWeek = $renewalItems
            ->filter(fn (array $item): bool => ($item['days_left'] ?? 0) > 0 && ($item['days_left'] ?? 0) <= 7)
            ->values();
        $expiredRenewals = $renewalItems
            ->filter(fn (array $item): bool => ($item['days_left'] ?? 1) <= 0)
            ->values();
        $renewalsNeedAttention = $renewalItems
            ->filter(fn (array $item): bool => ($item['days_left'] ?? 0) > 0 && ($item['days_left'] ?? 0) <= 30)
            ->sortBy('days_left')
            ->take(5)
            ->values();

        $renewalClientPriorities = $renewalItems
            ->filter(fn (array $item): bool => ($item['days_left'] ?? PHP_INT_MAX) <= 30)
            ->groupBy('clientid')
            ->map(function ($items) {
                $rows = collect($items)->sortBy('days_left')->values();
                $expiredCount = $rows->filter(fn (array $row): bool => ($row['days_left'] ?? 1) <= 0)->count();
                $dueThisWeekCount = $rows->filter(fn (array $row): bool => ($row['days_left'] ?? 0) > 0 && ($row['days_left'] ?? 0) <= 7)->count();
                $dueThisMonthCount = $rows->filter(fn (array $row): bool => ($row['days_left'] ?? 0) > 0 && ($row['days_left'] ?? 0) <= 30)->count();

                return [
                    'clientid' => (string) ($rows->first()['clientid'] ?? ''),
                    'client_name' => (string) ($rows->first()['client_name'] ?? 'Client'),
                    'expired_count' => $expiredCount,
                    'due_this_week_count' => $dueThisWeekCount,
                    'due_this_month_count' => $dueThisMonthCount,
                    'nearest_days_left' => $rows->first()['days_left'] ?? null,
                    'attention_score' => ($expiredCount * 3) + ($dueThisWeekCount * 2) + $dueThisMonthCount,
                ];
            })
            ->sortByDesc('attention_score')
            ->take(6)
            ->values();

        $stats = [
            [
                'label' => 'Total Clients',
                'value' => Client::where('accountid', $accountid)->count(),
                'change' => '',
                'icon' => 'fa-users',
                'tone' => 'brand',
                'url' => route('clients.index'),
            ],
            [
                'label' => 'Renewals Due (30 days)',
                'value' => $renewalsDue30Days->count(),
                'change' => '',
                'icon' => 'fa-clock',
                'tone' => 'accent',
                'url' => route('invoices.expiry-list', ['tab' => 'upcoming']),
            ],
            [
                'label' => 'Expired Renewal Items',
                'value' => $expiredRenewals->count(),
                'change' => '',
                'icon' => 'fa-exclamation-circle',
                'tone' => 'danger',
                'url' => route('invoices.expiry-list', ['tab' => 'expired']),
            ],
        ];

        $recentRevenue = Payment::where('accountid', $accountid)
            ->with('client')
            ->latest('payment_date')
            ->latest('created_at')
            ->take(5)
            ->get()
            ->map(function ($payment) {
                $net = (float) ($payment->received_amount ?? 0);

                return [
                    'title' => 'Payment from '.($payment->client->business_name ?? $payment->client->contact_name ?? 'Unknown Client'),
                    'amount' => number_format(abs($net), 0),
                    'date' => optional($payment->payment_date ?? $payment->created_at)?->format('d M, Y'),
                    'status' => 'success',
                ];
            });

        $attentionCount = $renewalsDue30Days->count();

        $totalOutstanding = (float) Invoice::query()
            ->where('accountid', $accountid)
            ->where('status', '!=', 'cancelled')
            ->get()
            ->sum(function ($invoice) {
                return max(0.0, (float) (($invoice->grand_total ?? 0) - ($invoice->amount_paid ?? 0)));
            });

        $outstandingInvoices = Invoice::query()
            ->where('accountid', $accountid)
            ->whereNotIn('status', ['cancelled', 'draft'])
            ->with(['client', 'paymentDetails.payment', 'invoiceItems'])
            ->get()
            ->filter(function ($invoice) {
                return $invoice->balance_due > 0;
            })
            ->sortByDesc('created_at')
            ->take(5)
            ->map(function ($invoice) {
                return [
                    'invoiceid' => $invoice->invoiceid,
                    'invoice_number' => $invoice->invoice_number,
                    'client_name' => $invoice->client->business_name ?? $invoice->client->contact_name ?? 'Client',
                    'balance_due' => number_format($invoice->balance_due, 0),
                    'date' => optional($invoice->issue_date ?? $invoice->created_at)->format('d M, Y'),
                ];
            })
            ->values();

        return view('dashboard', [
            'title' => 'Dashboard',
            'subtitle' => $attentionCount > 0
                ? sprintf('%d renewal item(s) need attention in the next 30 days.', $attentionCount)
                : 'No renewal items due in the next 30 days.',
            'stats' => $stats,
            'totalRevenue' => $totalRevenue,
            'totalInvoices' => Invoice::where('accountid', $accountid)->count(),
            'totalOutstanding' => $totalOutstanding,
            'renewalsNeedAttention' => $renewalsNeedAttention,
            'renewalClientPriorities' => $renewalClientPriorities,
            'expiredRenewals' => $expiredRenewals->take(5)->values(),
            'recentRevenue' => $recentRevenue,
            'outstandingInvoices' => $outstandingInvoices,
        ]);
    }
}
