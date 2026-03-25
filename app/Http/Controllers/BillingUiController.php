<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;

use App\Models\Client;
use App\Models\Estimate;
use App\Models\EstimateItem;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Account;

class BillingUiController extends Controller
{
    public function dashboard(): View
    {
        $accountId = auth()->id();
        
        $stats = [
            ['label' => 'Total Clients', 'value' => Client::where('account_id', $accountId)->count(), 'change' => '', 'tone' => 'positive'],
            ['label' => 'Total Invoices', 'value' => Invoice::where('account_id', $accountId)->count(), 'change' => '', 'tone' => 'warning'],
            ['label' => 'Total Revenue', 'value' => 'Rs ' . number_format(Payment::where('account_id', $accountId)->sum('amount'), 2), 'change' => '', 'tone' => 'positive'],
            ['label' => 'Overdue Count', 'value' => Invoice::where('account_id', $accountId)->where('status', 'overdue')->count(), 'change' => '', 'tone' => 'warning'],
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

    // Clients CRUD
    public function clients(): View
    {
        $query = Client::query();
        
        if (request('search')) {
            $query->where(function ($q) {
                $search = request('search');
                $q->where('business_name', 'like', '%' . $search . '%')
                  ->orWhere('contact_name', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%');
            });
        }
        
        $clients = $query->latest()->take(20)->get()->map(function ($client) {
            $outstanding = Invoice::where('client_id', $client->id)->where('status', '!=', 'paid')->sum('grand_total') - Payment::where('client_id', $client->id)->sum('amount');
            return [
                'id' => $client->id,
                'name' => $client->business_name ?? $client->contact_name,
                'contact' => $client->contact_name,
                'email' => $client->email,
                'status' => $client->status ?? 'Active',
                'balance' => 'Rs ' . number_format($outstanding, 2),
            ];
        });

        return view('clients.index', [
            'title' => 'Clients',
            'clients' => $clients,
        ]);
    }

    public function services(): View
    {
        $query = Service::query();
        if (request('search')) {
            $query->where('name', 'like', '%' . request('search') . '%');
        }
        $services = $query->latest()->take(20)->get()->map(function ($service) {
            return [
                'id' => $service->id,
                'name' => $service->name,
                'type' => ucfirst(str_replace('-', ' ', $service->billing_type ?? 'one time')),
                'price' => 'Rs ' . number_format($service->unit_price ?? 0, 2),
                'tax' => ($service->tax_rate ?? 18) . '%',
                'status' => $service->status ?? 'Active',
            ];
        });

        return view('services.index', [
            'title' => 'Services',
            'services' => $services,
        ]);
    }

    public function settings(): View
    {
        $query = Setting::query();
        if (request('search')) {
            $query->where('setting_key', 'like', '%' . request('search') . '%');
        }
        $settings = $query->latest()->take(20)->get()->map(function ($setting) {
            return [
                'id' => $setting->id,
                'key' => $setting->setting_key,
                'value' => $setting->setting_value,
                'status' => 'Active',
            ];
        });

        $account = null;
        if (auth()->check()) {
            $account = auth()->user()->account;
        } else {
            $account = Account::find('ACC0000001');
        }

        return view('settings.index', [
            'title' => 'Settings',
            'settings' => $settings,
            'account' => $account,
        ]);
    }

    public function agencyUpdate(Request $request)
    {
        $account = auth()->check() ? auth()->user()->account : Account::find('ACC0000001');

        if (! $account) {
            return redirect()->back()->with('error', 'Agency profile not found.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'required|email|max:150',
            'phone' => 'nullable|string|max:20',
            'legal_name' => 'nullable|string|max:150',
            'tax_number' => 'nullable|string|max:50',
            'website' => 'nullable|url|max:150',
            'currency_code' => 'required|string|size:3',
            'timezone' => 'required|string|max:100',
            'address_line_1' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
        ]);

        $account->update($validated);

        return redirect()->route('settings.index')->with('success', 'Agency profile updated successfully.');
    }

    public function invoices(): View
    {
        $invoices = Invoice::latest()->take(20)->get()->map(function ($invoice) {
            return [
                'id' => $invoice->id,
                'number' => $invoice->invoice_number ?? 'INV-' . str_pad($invoice->id, 4, '0', STR_PAD_LEFT),
                'client' => $invoice->client->business_name ?? 'Client',
                'issued' => $invoice->created_at->format('d M Y'),
                'due' => $invoice->due_date?->format('d M Y') ?? 'N/A',
                'amount' => 'Rs ' . number_format($invoice->grand_total ?? 0),
                'status' => $invoice->status ?? 'Draft',
            ];
        });

        return view('invoices.index', [
            'title' => 'Invoices',
            'invoices' => $invoices,
        ]);
    }

    public function payments(): View
    {
        $payments = Payment::latest()->take(20)->get()->map(function ($payment) {
            return [
                'id' => $payment->id,
                'ref' => $payment->reference ?? 'PAY-' . str_pad($payment->id, 3, '0', STR_PAD_LEFT),
                'client' => $payment->client->business_name ?? 'Client',
                'date' => $payment->paid_at?->format('d M Y') ?? $payment->created_at->format('d M Y'),
                'method' => $payment->method ?? 'Bank Transfer',
                'amount' => 'Rs ' . number_format($payment->amount ?? 0),
                'status' => $payment->status ?? 'Pending',
            ];
        });

        return view('payments.index', [
            'title' => 'Payments',
            'payments' => $payments,
        ]);
    }

    public function subscriptions(): View
    {
        $subscriptions = Subscription::latest()->take(20)->get()->map(function ($subscription) {
            return [
                'id' => $subscription->id,
                'client' => $subscription->client->business_name ?? 'Client',
                'service' => $subscription->service->name ?? 'Service',
                'next_bill' => $subscription->next_billing_date?->format('d M Y'),
                'amount' => 'Rs ' . number_format($subscription->price ?? 0),
                'status' => $subscription->status ?? 'Active',
            ];
        });

        return view('subscriptions.index', [
            'title' => 'Subscriptions',
            'subscriptions' => $subscriptions,
        ]);
    }

    public function estimates(): View
    {
        $estimates = Estimate::latest()->take(20)->get()->map(function ($estimate) {
            return [
                'id' => $estimate->id,
                'number' => $estimate->estimate_number ?? 'EST-' . str_pad($estimate->id, 4, '0', STR_PAD_LEFT),
                'client' => $estimate->client->business_name ?? 'Client',
                'amount' => 'Rs ' . number_format($estimate->total ?? 0),
                'expiry' => $estimate->expiry_date?->format('d M Y') ?? 'N/A',
                'status' => $estimate->status ?? 'Draft',
            ];
        });

        return view('estimates.index', [
            'title' => 'Estimates',
            'estimates' => $estimates,
        ]);
    }

    // Services CRUD
    public function servicesCreate(): View
    {
        return view('services.create', ['title' => 'New Service']);
    }

    public function servicesStore(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'billing_type' => 'required|in:one-time,recurring',
            'unit_price' => 'required|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'account_id' => 'nullable|size:10',
            'status' => 'in:active,inactive',
        ]);

        $userAccountId = auth()->check() ? (auth()->user()->account_id ?? 'ACC0000001') : 'ACC0000001';
        $validated['account_id'] = $validated['account_id'] ?? $userAccountId;

        Service::create($validated);

        return redirect()->route('services.index')->with('success', 'Service created successfully.');
    }

    public function servicesShow(Service $service): View
    {
        $service->load('subscriptions');
        return view('services.show', [
            'title' => 'Service Details',
            'service' => $service,
        ]);
    }

    public function servicesEdit(Service $service): View
    {
        return view('services.edit', ['title' => 'Edit Service', 'service' => $service]);
    }

    public function servicesUpdate(Request $request, Service $service)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'billing_type' => 'required|in:one-time,recurring',
            'unit_price' => 'required|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'status' => 'in:active,inactive',
        ]);

        $service->update($validated);

        return redirect()->route('services.index')->with('success', 'Service updated successfully.');
    }

    public function servicesDestroy(Service $service)
    {
        $service->delete();

        return redirect()->route('services.index')->with('success', 'Service deleted successfully.');
    }

    // Settings CRUD
    public function settingsCreate(): View
    {
        return view('settings.create', ['title' => 'New Setting']);
    }

    public function settingsStore(Request $request)
    {
        $validated = $request->validate([
            'key' => 'required|string|max:255|unique:settings,setting_key',
            'value' => 'required',
            'account_id' => 'nullable|size:10',
        ]);

        $userAccountId = auth()->check() ? (auth()->user()->account_id ?? 'ACC0000001') : 'ACC0000001';
        
        Setting::create([
            'setting_key' => $validated['key'],
            'setting_value' => $validated['value'],
            'account_id' => $validated['account_id'] ?? $userAccountId,
        ]);

        return redirect()->route('settings.index')->with('success', 'Setting created successfully.');
    }

    public function settingsShow(Setting $setting): View
    {
        return view('settings.show', [
            'title' => 'Setting Details',
            'setting' => $setting,
        ]);
    }

    public function settingsEdit(Setting $setting): View
    {
        return view('settings.edit', ['title' => 'Edit Setting', 'setting' => $setting]);
    }

    public function settingsUpdate(Request $request, Setting $setting)
    {
        $validated = $request->validate([
            'key' => 'required|string|max:255|unique:settings,setting_key,' . $setting->id,
            'value' => 'required',
        ]);

        $setting->update([
            'setting_key' => $validated['key'],
            'setting_value' => $validated['value'],
        ]);

        return redirect()->route('settings.index')->with('success', 'Setting updated successfully.');
    }

    public function settingsDestroy(Setting $setting)
    {
        $setting->delete();

        return redirect()->route('settings.index')->with('success', 'Setting deleted successfully.');
    }

    // Clients CRUD
    public function clientsCreate(): View
    {
        return view('clients.create', [
            'title' => 'New Client',
            'accounts' => Account::where('status', 'active')->get(),
        ]);
    }

    public function clientsStore(Request $request)
    {
        $userAccountId = auth()->check() ? auth()->user()->account_id ?? 'ACC0000001' : 'ACC0000001';

        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id|size:10',
            'business_name' => 'required|string|max:255',
            'contact_name' => 'nullable|string|max:255',
            'email' => 'required|email|unique:clients,email',
            'phone' => 'nullable|string|max:20',
            'billing_email' => 'nullable|email',
            'status' => 'in:active,review,inactive',
        ]);

        // Override with user context if not provided
        $validated['account_id'] = $validated['account_id'] ?? $userAccountId;

        Client::create($validated);

        return redirect()->route('clients.index')->with('success', 'Client created successfully.');
    }

    public function clientsShow(Client $client): View
    {
        $client->load(['invoices', 'payments', 'subscriptions']);
        $outstanding = ($client->invoices->sum('total') ?? 0) - ($client->payments->sum('amount') ?? 0);

        return view('clients.show', [
            'title' => 'Client Details',
            'client' => $client,
            'outstanding' => $outstanding,
        ]);
    }

    public function clientsEdit(Client $client): View
    {
        return view('clients.edit', [
            'title' => 'Edit Client', 
            'client' => $client,
            'accounts' => Account::where('status', 'active')->get(),
        ]);
    }

    public function clientsUpdate(Request $request, Client $client)
    {
        $validated = $request->validate([
            'business_name' => 'required|string|max:255',
            'contact_name' => 'nullable|string|max:255',
            'email' => 'required|email|unique:clients,email,' . $client->id,
            'phone' => 'nullable|string|max:20',
            'billing_email' => 'nullable|email',
            'status' => 'in:active,review,inactive',
        ]);

        $client->update($validated);

        return redirect()->route('clients.index')->with('success', 'Client updated successfully.');
    }

    public function clientsDestroy(Client $client)
    {
        $client->delete();

        return redirect()->route('clients.index')->with('success', 'Client deleted successfully.');
    }

    // Subscriptions CRUD
    public function subscriptionsCreate(): View
    {
        return view('subscriptions.create', [
            'title' => 'New Subscription',
            'clients' => Client::all(),
            'services' => Service::where('billing_type', 'recurring')->get(),
        ]);
    }

    public function subscriptionsStore(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'service_id' => 'required|exists:services,id',
            'start_date' => 'required|date',
            'next_billing_date' => 'required|date|after:start_date',
            'price' => 'required|numeric|min:0',
            'account_id' => 'nullable|size:10',
            'status' => 'required|in:active,cancelled,expired',
        ]);

        $userAccountId = auth()->check() ? (auth()->user()->account_id ?? 'ACC0000001') : 'ACC0000001';
        $validated['account_id'] = $validated['account_id'] ?? $userAccountId;

        Subscription::create($validated);

        return redirect()->route('subscriptions.index')->with('success', 'Subscription created successfully.');
    }

    // Estimates CRUD
    public function estimatesCreate(): View
    {
        return view('estimates.create', [
            'title' => 'New Estimate',
            'clients' => Client::all(),
        ]);
    }

    public function estimatesStore(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'estimate_number' => 'required|string|unique:estimates,estimate_number',
            'issue_date' => 'required|date',
            'expiry_date' => 'nullable|date|after_or_equal:issue_date',
            'account_id' => 'nullable|size:10',
            'status' => 'required|in:draft,sent,accepted,declined,expired',
        ]);

        $userAccountId = auth()->check() ? (auth()->user()->account_id ?? 'ACC0000001') : 'ACC0000001';
        $validated['account_id'] = $validated['account_id'] ?? $userAccountId;

        Estimate::create($validated);

        return redirect()->route('estimates.index')->with('success', 'Estimate created successfully.');
    }

    // Invoices CRUD
    public function invoicesCreate(): View
    {
        return view('invoices.create', [
            'title' => 'Create Invoice',
            'clients' => Client::all(),
            'services' => Service::all(),
        ]);
    }

    public function invoicesStore(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'invoice_number' => 'required|string|unique:invoices,invoice_number',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'notes' => 'nullable|string',
            'status' => 'required|in:draft,sent,paid,overdue,cancelled',
            'subtotal' => 'required|numeric|min:0',
            'tax_total' => 'required|numeric|min:0',
            'grand_total' => 'required|numeric|min:0',
            'items_data' => 'required|json',
            'account_id' => 'nullable|size:10',
        ]);

        $userAccountId = auth()->check() ? (auth()->user()->account_id ?? 'ACC0000001') : 'ACC0000001';
        $validated['account_id'] = $validated['account_id'] ?? $userAccountId;
        unset($validated['items_data']);

        $itemsData = json_decode($request->items_data, true);
        $subtotal = 0;
        $taxTotal = 0;
        foreach ($itemsData as $itemData) {
            $subtotal += $itemData['line_total'];
            $taxTotal += $itemData['line_total'] * ($itemData['tax_rate'] / 100);
        }
        $grandTotal = $subtotal + $taxTotal;
        $validated['subtotal'] = $subtotal;
        $validated['tax_total'] = $taxTotal;
        $validated['grand_total'] = $grandTotal;
        $validated['balance_due'] = $grandTotal;

        $invoice = Invoice::create($validated);

        foreach ($itemsData as $index => $itemData) {
            $service = Service::find($itemData['service_id']);
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'service_id' => $itemData['service_id'],
                'item_name' => $service?->name ?? 'Custom Service Item',
                'item_description' => null,
                'quantity' => $itemData['quantity'],
                'unit_price' => $itemData['unit_price'],
                'tax_rate' => $itemData['tax_rate'],
                'line_total' => $itemData['line_total'],
                'sort_order' => $index + 1,
            ]);
        }

        return redirect()->route('invoices.index')->with('success', 'Invoice created successfully with items.');
    }

    public function invoicesShow(Invoice $invoice): View
    {
        $invoice->load(['client', 'items.service', 'payments']);
        return view('invoices.show', ['title' => 'Invoice Details', 'invoice' => $invoice]);
    }

    public function invoicesEdit(Invoice $invoice): View
    {
        return view('invoices.edit', [
            'title' => 'Edit Invoice',
            'invoice' => $invoice,
            'clients' => Client::all()
        ]);
    }

    public function estimatesEdit(Estimate $estimate): View
    {
        return view('estimates.edit', [
            'title' => 'Edit Estimate',
            'estimate' => $estimate,
            'clients' => Client::all()
        ]);
    }

    public function subscriptionsEdit(Subscription $subscription): View
    {
        return view('subscriptions.edit', [
            'title' => 'Edit Subscription',
            'subscription' => $subscription,
            'clients' => Client::all(),
            'services' => Service::where('billing_type', 'recurring')->get()
        ]);
    }

    // Payments CRUD
    public function paymentsCreate(): View
    {
        return view('payments.create', [
            'title' => 'New Payment',
            'clients' => Client::all(),
            'invoices' => Invoice::where('status', '!=', 'paid')->get(),
        ]);
    }

    public function paymentsStore(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'invoice_id' => 'nullable|exists:invoices,id',
            'reference' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|in:cash,bank_transfer,card,upi',
            'paid_at' => 'required|date',
            'notes' => 'nullable|string',
            'account_id' => 'nullable|size:10',
            'status' => 'required|in:pending,confirmed,failed',
        ]);

        $userAccountId = auth()->check() ? (auth()->user()->account_id ?? 'ACC0000001') : 'ACC0000001';
        $validated['account_id'] = $validated['account_id'] ?? $userAccountId;

        Payment::create($validated);

        return redirect()->route('payments.index')->with('success', 'Payment recorded successfully.');
    }

    public function subscriptionsShow(Subscription $subscription): View
    {
        $subscription->load('client', 'service');
        return view('subscriptions.show', [
            'title' => 'Subscription Details',
            'subscription' => $subscription,
        ]);
    }

    public function subscriptionsUpdate(Request $request, Subscription $subscription)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'service_id' => 'required|exists:services,id',
            'start_date' => 'required|date',
            'next_billing_date' => 'required|date|after_or_equal:start_date',
            'price' => 'required|numeric|min:0',
            'status' => 'required|in:active,cancelled,expired',
        ]);

        $subscription->update($validated);

        return redirect()->route('subscriptions.index')->with('success', 'Subscription updated successfully.');
    }

    public function subscriptionsDestroy(Subscription $subscription)
    {
        $subscription->delete();

        return redirect()->route('subscriptions.index')->with('success', 'Subscription deleted successfully.');
    }

    // Complete Estimates CRUD
    public function estimatesShow(Estimate $estimate): View
    {
        $estimate->load('client');
        return view('estimates.show', [
            'title' => 'Estimate Details',
            'estimate' => $estimate,
        ]);
    }

    public function estimatesUpdate(Request $request, Estimate $estimate)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'estimate_number' => 'required|string|unique:estimates,estimate_number,' . $estimate->id,
            'issue_date' => 'required|date',
            'expiry_date' => 'nullable|date|after_or_equal:issue_date',
            'status' => 'required|in:draft,sent,accepted,declined,expired',
        ]);

        $estimate->update($validated);

        return redirect()->route('estimates.index')->with('success', 'Estimate updated successfully.');
    }

    public function estimatesDestroy(Estimate $estimate)
    {
        $estimate->delete();

        return redirect()->route('estimates.index')->with('success', 'Estimate deleted successfully.');
    }

    // Complete Invoices CRUD
    public function invoicesUpdate(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
'invoice_number' => 'required|string|unique:invoices,invoice_number,' . $invoice->id,
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'notes' => 'nullable|string',
            'status' => 'required|in:draft,sent,paid,overdue,cancelled',
        ]);

        $invoice->update($validated);

        return redirect()->route('invoices.index')->with('success', 'Invoice updated successfully.');
    }

    public function invoicesDestroy(Invoice $invoice)
    {
        $invoice->delete();

        return redirect()->route('invoices.index')->with('success', 'Invoice deleted successfully.');
    }
}

