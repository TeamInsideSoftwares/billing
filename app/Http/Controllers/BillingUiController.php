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
        $accountid = auth()->id();
        
        $stats = [
            ['label' => 'Total Clients', 'value' => Client::where('accountid', $accountid)->count(), 'change' => '', 'tone' => 'positive'],
            ['label' => 'Total Invoices', 'value' => Invoice::where('accountid', $accountid)->count(), 'change' => '', 'tone' => 'warning'],
            ['label' => 'Total Revenue', 'value' => 'Rs ' . number_format(Payment::where('accountid', $accountid)->sum('amount'), 2), 'change' => '', 'tone' => 'positive'],
            ['label' => 'Overdue Count', 'value' => Invoice::where('accountid', $accountid)->where('status', 'overdue')->count(), 'change' => '', 'tone' => 'warning'],
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
        $searchTerm = request('search', '');
        
        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('business_name', 'like', '%' . $searchTerm . '%')
                  ->orWhere('contact_name', 'like', '%' . $searchTerm . '%')
                  ->orWhere('email', 'like', '%' . $searchTerm . '%');
            });
        }
        $resultCount = $query->count();
        
        $clients = $query->latest()->take(20)->get()->map(function ($client) {
            $outstanding = Invoice::where('clientid', $client->clientid)->where('status', '!=', 'paid')->sum('grand_total') - Payment::where('clientid', $client->clientid)->sum('amount');
            return [
'record_id' => $client->clientid,
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
            'searchTerm' => $searchTerm,
            'resultCount' => $resultCount,
        ]);
    }

    public function services(): View
    {
        $query = Service::query();
        $searchTerm = request('search', '');
        if ($searchTerm) {
            $query->where('name', 'like', '%' . $searchTerm . '%');
        }
        $resultCount = $query->count();
        $services = $query->latest()->take(20)->get()->map(function ($service) {
            return [
'record_id' => $service->serviceid,
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
            'searchTerm' => $searchTerm,
            'resultCount' => $resultCount,
        ]);
    }

    public function settings(): View
    {
        $query = Setting::query();
        $searchTerm = request('search', '');
        if ($searchTerm) {
            $query->where('setting_key', 'like', '%' . $searchTerm . '%');
        }
        $resultCount = $query->count();
        $settings = $query->latest()->take(20)->get()->map(function ($setting) {
            return [
'record_id' => $setting->settingid,
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
            'searchTerm' => $searchTerm,
            'resultCount' => $resultCount,
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
        $query = Invoice::with('client');
        $searchTerm = request('search', '');
        
        if ($searchTerm) {
            $query->where('invoice_number', 'like', '%' . $searchTerm . '%')
                  ->orWhereHas('client', function ($q) use ($searchTerm) {
                      $q->where('business_name', 'like', '%' . $searchTerm . '%')
                        ->orWhere('contact_name', 'like', '%' . $searchTerm . '%');
                  });
        }
        $resultCount = $query->count();
        
        $invoices = $query->latest()->take(20)->get()->map(function ($invoice) {
            return [
'record_id' => $invoice->invoiceid,
                'number' => $invoice->invoice_number ?? 'INV-' . str_pad($invoice->invoiceid, 4, '0', STR_PAD_LEFT),
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
            'searchTerm' => $searchTerm,
            'resultCount' => $resultCount,
        ]);
    }

    public function payments(): View
    {
        $query = Payment::with('client');
        $searchTerm = request('search', '');
        
        if ($searchTerm) {
            $query->where('reference', 'like', '%' . $searchTerm . '%')
                  ->orWhereHas('client', function ($q) use ($searchTerm) {
                      $q->where('business_name', 'like', '%' . $searchTerm . '%')
                        ->orWhere('contact_name', 'like', '%' . $searchTerm . '%');
                  });
        }
        $resultCount = $query->count();
        
        $payments = $query->latest()->take(20)->get()->map(function ($payment) {
            return [
                'record_id' => $payment->paymentid,
                'number' => $payment->payment_number,
                'client' => $payment->client->business_name ?? 'Client',
                'date' => $payment->payment_date?->format('d M Y'),
                'method' => $payment->payment_method ?? 'Bank Transfer',
                'amount' => 'Rs ' . number_format($payment->amount ?? 0),
                'status' => $payment->status ?? 'Completed',
            ];
        });

        return view('payments.index', [
            'title' => 'Payments',
            'payments' => $payments,
            'searchTerm' => $searchTerm,
            'resultCount' => $resultCount,
        ]);
    }

    public function subscriptions(): View
    {
        $query = Subscription::with(['client', 'service']);
        $searchTerm = request('search', '');
        
        if ($searchTerm) {
            $query->whereHas('client', function ($q) use ($searchTerm) {
                      $q->where('business_name', 'like', '%' . $searchTerm . '%')
                        ->orWhere('contact_name', 'like', '%' . $searchTerm . '%');
                  })
                  ->orWhereHas('service', function ($q) use ($searchTerm) {
                      $q->where('name', 'like', '%' . $searchTerm . '%');
                  });
        }
        $resultCount = $query->count();
        
        $subscriptions = $query->latest()->take(20)->get()->map(function ($subscription) {
            return [
'record_id' => $subscription->subscriptionid,
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
            'searchTerm' => $searchTerm,
            'resultCount' => $resultCount,
        ]);
    }

    public function estimates(): View
    {
        $query = Estimate::with('client');
        $searchTerm = request('search', '');
        
        if ($searchTerm) {
            $query->where('estimate_number', 'like', '%' . $searchTerm . '%')
                  ->orWhereHas('client', function ($q) use ($searchTerm) {
                      $q->where('business_name', 'like', '%' . $searchTerm . '%')
                        ->orWhere('contact_name', 'like', '%' . $searchTerm . '%');
                  });
        }
        $resultCount = $query->count();
        
        $estimates = $query->latest()->take(20)->get()->map(function ($estimate) {
            return [
'record_id' => $estimate->estimateid,
                'number' => $estimate->estimate_number ?? 'EST-' . str_pad($estimate->estimateid, 4, '0', STR_PAD_LEFT),
                'client' => $estimate->client->business_name ?? 'Client',
                'amount' => 'Rs ' . number_format($estimate->total ?? 0),
                'expiry' => $estimate->expiry_date?->format('d M Y') ?? 'N/A',
                'status' => $estimate->status ?? 'Draft',
            ];
        });

        return view('estimates.index', [
            'title' => 'Estimates',
            'estimates' => $estimates,
            'searchTerm' => $searchTerm,
            'resultCount' => $resultCount,
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
            'accountid' => 'nullable|size:6',
            'status' => 'in:active,inactive',
        ]);

        $userAccountId = auth()->check() ? (auth()->user()->accountid ?? 'ACC0000001') : 'ACC0000001';
        $validated['accountid'] = $validated['accountid'] ?? $userAccountId;

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
            'accountid' => 'nullable|size:10',
        ]);

        $userAccountId = auth()->check() ? (auth()->user()->accountid ?? 'ACC0000001') : 'ACC0000001';
        
        Setting::create([
            'setting_key' => $validated['key'],
            'setting_value' => $validated['value'],
            'accountid' => $validated['accountid'] ?? $userAccountId,
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
'key' => 'required|string|max:255|unique:settings,setting_key,' . $setting->getKey() . ',settingid',
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
        $userAccountId = auth()->check() ? auth()->user()->accountid ?? 'ACC0000001' : 'ACC0000001';

        $validated = $request->validate([
            'accountid' => 'required|exists:accounts,accountid|size:10',
            'business_name' => 'required|string|max:255',
            'contact_name' => 'nullable|string|max:255',
            'email' => 'required|email|unique:clients,email',
            'phone' => 'nullable|string|max:20',
            'billing_email' => 'nullable|email',
            'status' => 'in:active,review,inactive',
        ]);

        // Override with user context if not provided
        $validated['accountid'] = $validated['accountid'] ?? $userAccountId;

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
'email' => 'required|email|unique:clients,email,' . $client->getKey() . ',clientid',
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
            'clientid' => 'required|exists:clients,clientid',
            'serviceid' => 'required|exists:services,serviceid',
            'start_date' => 'required|date',
            'next_billing_date' => 'required|date|after:start_date',
            'price' => 'required|numeric|min:0',
            'accountid' => 'nullable|size:10',
            'status' => 'required|in:active,cancelled,expired',
        ]);

        $userAccountId = auth()->check() ? (auth()->user()->accountid ?? 'ACC0000001') : 'ACC0000001';
        $validated['accountid'] = $validated['accountid'] ?? $userAccountId;

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
            'clientid' => 'required|exists:clients,clientid',
            'estimate_number' => 'required|string|unique:estimates,estimate_number',
            'issue_date' => 'required|date',
            'expiry_date' => 'nullable|date|after_or_equal:issue_date',
            'accountid' => 'nullable|size:10',
            'status' => 'required|in:draft,sent,accepted,declined,expired',
        ]);

        $userAccountId = auth()->check() ? (auth()->user()->accountid ?? 'ACC0000001') : 'ACC0000001';
        $validated['accountid'] = $validated['accountid'] ?? $userAccountId;

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
            'clientid' => 'required|exists:clients,clientid',
            'invoice_number' => 'required|string|unique:invoices,invoice_number',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'notes' => 'nullable|string',
            'status' => 'required|in:draft,sent,paid,overdue,cancelled',
            'subtotal' => 'required|numeric|min:0',
            'tax_total' => 'required|numeric|min:0',
            'grand_total' => 'required|numeric|min:0',
            'items_data' => 'required|json',
            'accountid' => 'nullable|size:10',
        ]);

        $userAccountId = auth()->check() ? (auth()->user()->accountid ?? 'ACC0000001') : 'ACC0000001';
        $validated['accountid'] = $validated['accountid'] ?? $userAccountId;
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
            $service = Service::find($itemData['serviceid']);
            InvoiceItem::create([
                'invoiceid' => $invoice->invoiceid,
                'serviceid' => $itemData['serviceid'],
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
        $invoice->load(['items.service']);
        return view('invoices.edit', [
            'title' => 'Edit Invoice',
            'invoice' => $invoice,
            'clients' => Client::all(),
            'services' => Service::all(),
            'items' => $invoice->items
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
            'clientid' => 'required|exists:clients,clientid',
            'invoiceid' => 'nullable|exists:invoices,invoiceid',
            'reference' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|string',
            'paid_at' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $userAccountId = auth()->check() ? (auth()->user()->accountid ?? 'ACC0000001') : 'ACC0000001';
        
        $paymentData = [
            'accountid' => $userAccountId,
            'clientid' => $validated['clientid'],
            'invoiceid' => $validated['invoiceid'],
            'payment_number' => 'PAY-' . strtoupper(bin2hex(random_bytes(3))),
            'payment_date' => $validated['paid_at'],
            'amount' => $validated['amount'],
            'payment_method' => $validated['method'],
            'reference_number' => $validated['reference'],
            'notes' => $validated['notes'],
            'status' => 'completed',
            'received_by' => (auth()->user() instanceof \App\Models\User) ? auth()->id() : null,
        ];

        $payment = Payment::create($paymentData);

        // Update Invoice balance if applicable
        if ($payment->invoiceid) {
            $invoice = Invoice::find($payment->invoiceid);
            if ($invoice) {
                $invoice->balance_due -= $payment->amount;
                $invoice->amount_paid += $payment->amount;
                if ($invoice->balance_due <= 0) {
                    $invoice->status = 'paid';
                    $invoice->balance_due = 0;
                    $invoice->paid_at = now();
                }
                $invoice->save();
            }
        }

        return redirect()->route('payments.index')->with('success', 'Payment recorded successfully.');
    }

    public function paymentsShow(Payment $payment): View
    {
        $payment->load(['client', 'invoice']);
        return view('payments.show', [
            'title' => 'Payment Details',
            'payment' => $payment,
        ]);
    }

    public function paymentsEdit(Payment $payment): View
    {
        return view('payments.edit', [
            'title' => 'Edit Payment',
            'payment' => $payment,
            'clients' => Client::all(),
            'invoices' => Invoice::all(),
        ]);
    }

    public function paymentsUpdate(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'clientid' => 'required|exists:clients,clientid',
            'invoiceid' => 'nullable|exists:invoices,invoiceid',
            'reference' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|string',
            'paid_at' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $payment->update([
            'clientid' => $validated['clientid'],
            'invoiceid' => $validated['invoiceid'],
            'payment_date' => $validated['paid_at'],
            'amount' => $validated['amount'],
            'payment_method' => $validated['method'],
            'reference_number' => $validated['reference'],
            'notes' => $validated['notes'],
        ]);

        return redirect()->route('payments.index')->with('success', 'Payment updated successfully.');
    }

    public function paymentsDestroy(Payment $payment)
    {
        // If it was linked to an invoice, we should ideally revert the balance_due.
        if ($payment->invoiceid) {
            $invoice = Invoice::find($payment->invoiceid);
            if ($invoice) {
                $invoice->balance_due += $payment->amount;
                if ($invoice->balance_due > 0 && $invoice->status == 'paid') {
                    $invoice->status = 'sent'; // Or whatever was previous
                }
                $invoice->save();
            }
        }

        $payment->delete();

        return redirect()->route('payments.index')->with('success', 'Payment deleted successfully.');
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
            'clientid' => 'required|exists:clients,clientid',
            'serviceid' => 'required|exists:services,serviceid',
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
            'clientid' => 'required|exists:clients,clientid',
'estimate_number' => 'required|string|unique:estimates,estimate_number,' . $estimate->getKey() . ',estimateid',
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
            'clientid' => 'required|exists:clients,clientid',
            'invoice_number' => 'required|string|unique:invoices,invoice_number,' . $invoice->invoiceid . ',invoiceid',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'notes' => 'nullable|string',
            'status' => 'required|in:draft,sent,paid,overdue,cancelled',
            'items_data' => 'required|json',
        ]);

        $itemsData = json_decode($request->items_data, true);
        $subtotal = 0;
        $taxTotal = 0;
        foreach ($itemsData as $itemData) {
            $subtotal += $itemData['line_total'];
            $taxTotal += $itemData['line_total'] * ($itemData['tax_rate'] / 100);
        }
        $grandTotal = $subtotal + $taxTotal;

        $invoice->update([
            'clientid' => $validated['clientid'],
            'invoice_number' => $validated['invoice_number'],
            'issue_date' => $validated['issue_date'],
            'due_date' => $validated['due_date'],
            'notes' => $validated['notes'],
            'status' => $validated['status'],
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'grand_total' => $grandTotal,
            'balance_due' => $grandTotal, // Simple logic: reset balance due on update. In a real app, you might want to subtract payments already made.
        ]);

        // Delete old items and recreate
        $invoice->items()->delete();

        foreach ($itemsData as $index => $itemData) {
            $service = Service::find($itemData['serviceid']);
            InvoiceItem::create([
                'invoiceid' => $invoice->invoiceid,
                'serviceid' => $itemData['serviceid'],
                'item_name' => $service?->name ?? 'Custom Service Item',
                'item_description' => null,
                'quantity' => $itemData['quantity'],
                'unit_price' => $itemData['unit_price'],
                'tax_rate' => $itemData['tax_rate'],
                'line_total' => $itemData['line_total'],
                'sort_order' => $index + 1,
            ]);
        }

        return redirect()->route('invoices.index')->with('success', 'Invoice updated successfully.');
    }

    public function invoicesDestroy(Invoice $invoice)
    {
        $invoice->delete();

        return redirect()->route('invoices.index')->with('success', 'Invoice deleted successfully.');
    }
}

