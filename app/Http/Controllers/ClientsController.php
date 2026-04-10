<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Client;
use App\Models\ClientBillingDetail;
use App\Models\Group;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\ProformaInvoice;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ClientsController extends Controller
{
    public function clients(): View
    {
        $query = Client::query();
        $searchTerm = request('search', '');
        $accountId = auth()->check() ? (auth()->user()->accountid ?? 'ACC0000001') : 'ACC0000001';

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
            $account = Account::find($client->accountid);
            $cur = $account?->currency_code ?? 'INR';
            return [
                'record_id' => $client->clientid,
                'name' => $client->business_name ?? $client->contact_name,
                'contact' => $client->contact_name,
                'email' => $client->email,
                'phone' => $client->phone,
                'city' => $client->city,
                'currency' => $cur,
                'status' => $client->status ?? 'Active',
                'balance' => $cur . ' ' . number_format($outstanding, 0),
                'created_at' => $client->created_at,
                'invoice_count' => Invoice::where('clientid', $client->clientid)->count() + ProformaInvoice::where('clientid', $client->clientid)->count(),
            ];
        });

        $groups = Group::where('accountid', $accountId)->orderBy('group_name')->get();

        return view('clients.index', [
            'title' => 'Clients',
            'clients' => $clients,
            'groups' => $groups,
            'searchTerm' => $searchTerm,
            'resultCount' => $resultCount,
        ]);
    }

    public function clientsCreate(): View
    {
        $accountId = auth()->check() ? (auth()->user()->accountid ?? 'ACC0000001') : 'ACC0000001';
        $billingProfiles = ClientBillingDetail::query()
            ->where('accountid', $accountId)
            ->orderBy('business_name')
            ->get();
        $currencies = DB::table('currency')
            ->orderBy('iso')
            ->get(['iso', 'name']);

        return view('clients.create', [
            'title' => 'New Client',
            'accounts' => Account::where('status', 'active')->get(),
            'groups' => Group::all(),
            'billingProfiles' => $billingProfiles,
            'currencies' => $currencies,
        ]);
    }

    public function clientsStore(Request $request)
    {
        $userAccountId = auth()->check() ? auth()->user()->accountid ?? 'ACC0000001' : 'ACC0000001';

        $validated = $request->validate([
            'accountid' => 'required|exists:accounts,accountid|size:10',
            'business_name' => 'required|string|max:255',
            'groupid' => 'nullable|exists:groups,groupid',
            'contact_name' => 'nullable|string|max:255',
            'email' => 'required|email|unique:clients,email',
            'phone' => 'nullable|string|max:20',
            'whatsapp_number' => 'nullable|string|max:20',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'status' => 'in:active,review,inactive',
            'currency' => 'required|string|size:3|exists:currency,iso',
            'country' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'address_line_1' => 'nullable|string|max:150',
            'existing_bd_id' => 'nullable|string|size:6|exists:client_billing_details,bd_id',
            'billing_business_name' => 'required|string|max:150',
            'billing_gstin' => 'nullable|string|max:20',
            'billing_email' => 'nullable|email',
            'billing_city' => 'nullable|string|max:100',
            'billing_state' => 'nullable|string|max:100',
            'billing_country' => 'nullable|string|max:100',
            'billing_postal_code' => 'nullable|string|max:20',
            'billing_address_line_1' => 'nullable|string|max:150',
            'billing_phone' => 'nullable|string|max:20',
        ]);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos', 'public');
            $baseUrl = rtrim(config('app.url'), '/');
            $validated['logo_path'] = $baseUrl . '/public/storage/' . $path;
        }

        $validated['accountid'] = $validated['accountid'] ?? $userAccountId;

        $selectedBillingDetail = null;

        if (!empty($validated['existing_bd_id'])) {
            $selectedBillingDetail = ClientBillingDetail::query()
                ->where('bd_id', $validated['existing_bd_id'] ?? '')
                ->where('accountid', $validated['accountid'])
                ->first();

            if (!$selectedBillingDetail) {
                return back()->withInput()->withErrors([
                    'existing_bd_id' => 'Selected billing details are invalid for this account.',
                ]);
            }

            $selectedBillingDetail->update([
                'business_name' => $validated['billing_business_name'],
                'gstin' => $validated['billing_gstin'] ?? null,
                'billing_email' => $validated['billing_email'] ?? null,
                'city' => $validated['billing_city'] ?? null,
                'state' => $validated['billing_state'] ?? null,
                'country' => $validated['billing_country'] ?? 'India',
                'postal_code' => $validated['billing_postal_code'] ?? null,
                'phone' => $validated['billing_phone'] ?? null,
                'address_line_1' => $validated['billing_address_line_1'] ?? null,
            ]);
        } else {
            $selectedBillingDetail = ClientBillingDetail::create([
                'bd_id' => Group::generateUniqueAlphaId(new Group()),
                'accountid' => $validated['accountid'],
                'business_name' => $validated['billing_business_name'],
                'gstin' => $validated['billing_gstin'] ?? null,
                'billing_email' => $validated['billing_email'] ?? null,
                'city' => $validated['billing_city'] ?? null,
                'state' => $validated['billing_state'] ?? null,
                'country' => $validated['billing_country'] ?? 'India',
                'postal_code' => $validated['billing_postal_code'] ?? null,
                'phone' => $validated['billing_phone'] ?? null,
                'address_line_1' => $validated['billing_address_line_1'] ?? null,
            ]);
        }

        $validated['bd_id'] = $selectedBillingDetail->bd_id;
        $clientData = collect($validated)->except([
            'existing_bd_id',
            'billing_business_name',
            'billing_gstin',
            'billing_email',
            'billing_city',
            'billing_state',
            'billing_country',
            'billing_postal_code',
            'billing_address_line_1',
        ])->all();
        Client::create($clientData);

        return redirect()->route('clients.index')->with('success', 'Client created successfully.');
    }

    public function clientsShow(Client $client): View
    {
        $client->load(['invoices', 'proformaInvoices', 'payments', 'subscriptions']);
        $outstanding = ($client->invoices->sum('grand_total') ?? 0) - ($client->payments->sum('amount') ?? 0);
        $allInvoices = $client->proformaInvoices
            ->concat($client->invoices)
            ->sortByDesc('created_at')
            ->values();

        return view('clients.show', [
            'title' => 'Client Details',
            'client' => $client,
            'outstanding' => $outstanding,
            'allInvoices' => $allInvoices,
        ]);
    }

    public function clientsEdit(Client $client): View
    {
        $accountId = $client->accountid ?: (auth()->check() ? (auth()->user()->accountid ?? 'ACC0000001') : 'ACC0000001');
        $billingProfiles = ClientBillingDetail::query()
            ->where('accountid', $accountId)
            ->orderBy('business_name')
            ->get();
        $currencies = DB::table('currency')
            ->orderBy('iso')
            ->get(['iso', 'name']);

        return view('clients.edit', [
            'title' => 'Edit Client',
            'client' => $client,
            'accounts' => Account::where('status', 'active')->get(),
            'groups' => Group::all(),
            'billingProfiles' => $billingProfiles,
            'currencies' => $currencies,
        ]);
    }

    public function clientsUpdate(Request $request, Client $client)
    {
        $validated = $request->validate([
            'business_name' => 'required|string|max:255',
            'groupid' => 'nullable|exists:groups,groupid',
            'contact_name' => 'nullable|string|max:255',
            'email' => 'required|email|unique:clients,email,' . $client->getKey() . ',clientid',
            'phone' => 'nullable|string|max:20',
            'whatsapp_number' => 'nullable|string|max:20',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'status' => 'in:active,review,inactive',
            'currency' => 'required|string|size:3|exists:currency,iso',
            'country' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'address_line_1' => 'nullable|string|max:150',
            'existing_bd_id' => 'nullable|string|size:6|exists:client_billing_details,bd_id',
            'billing_business_name' => 'required|string|max:150',
            'billing_gstin' => 'nullable|string|max:20',
            'billing_email' => 'nullable|email',
            'billing_city' => 'nullable|string|max:100',
            'billing_state' => 'nullable|string|max:100',
            'billing_country' => 'nullable|string|max:100',
            'billing_postal_code' => 'nullable|string|max:20',
            'billing_address_line_1' => 'nullable|string|max:150',
            'billing_phone' => 'nullable|string|max:20',
        ]);

        if ($request->hasFile('logo')) {
            if ($client->logo_path) {
                $storageBase = rtrim(config('app.url'), '/') . '/public/storage/';
                $oldPath = str_replace($storageBase, '', $client->logo_path);
                Storage::disk('public')->delete($oldPath);
            }
            $path = $request->file('logo')->store('logos', 'public');
            $baseUrl = rtrim(config('app.url'), '/');
            $validated['logo_path'] = $baseUrl . '/public/storage/' . $path;
        }

        $selectedBdId = $client->bd_id;

        if (!empty($validated['existing_bd_id'])) {
            $existingBillingDetail = ClientBillingDetail::query()
                ->where('bd_id', $validated['existing_bd_id'] ?? '')
                ->where('accountid', $client->accountid)
                ->first();

            if (!$existingBillingDetail) {
                return back()->withInput()->withErrors([
                    'existing_bd_id' => 'Selected billing details are invalid for this account.',
                ]);
            }

            $existingBillingDetail->update([
                'business_name' => $validated['billing_business_name'],
                'gstin' => $validated['billing_gstin'] ?? null,
                'billing_email' => $validated['billing_email'] ?? null,
                'city' => $validated['billing_city'] ?? null,
                'state' => $validated['billing_state'] ?? null,
                'country' => $validated['billing_country'] ?? 'India',
                'postal_code' => $validated['billing_postal_code'] ?? null,
                'phone' => $validated['billing_phone'] ?? null,
                'address_line_1' => $validated['billing_address_line_1'] ?? null,
            ]);
            $selectedBdId = $existingBillingDetail->bd_id;
        } else {
            $billingData = [
                'accountid' => $client->accountid,
                'business_name' => $validated['billing_business_name'],
                'gstin' => $validated['billing_gstin'] ?? null,
                'billing_email' => $validated['billing_email'] ?? null,
                'city' => $validated['billing_city'] ?? null,
                'state' => $validated['billing_state'] ?? null,
                'country' => $validated['billing_country'] ?? 'India',
                'postal_code' => $validated['billing_postal_code'] ?? null,
                'address_line_1' => $validated['billing_address_line_1'] ?? null,
            ];

            $currentUsageCount = Client::where('bd_id', $client->bd_id)->count();
            if ($client->billingDetail && $currentUsageCount <= 1) {
                $client->billingDetail->update($billingData);
                $selectedBdId = $client->billingDetail->bd_id;
            } else {
                $newBillingDetail = ClientBillingDetail::create(array_merge($billingData, [
                    'bd_id' => Group::generateUniqueAlphaId(new Group()),
                ]));
                $selectedBdId = $newBillingDetail->bd_id;
            }
        }

        $validated['bd_id'] = $selectedBdId;
        $clientData = collect($validated)->except([
            'existing_bd_id',
            'billing_business_name',
            'billing_gstin',
            'billing_email',
            'billing_city',
            'billing_state',
            'billing_country',
            'billing_postal_code',
            'billing_address_line_1',
        ])->all();
        $client->update($clientData);

        return redirect()->route('clients.index')->with('success', 'Client updated successfully.');
    }

    public function clientsDestroy(Client $client)
    {
        $client->delete();

        return redirect()->route('clients.index')->with('success', 'Client deleted successfully.');
    }
}
