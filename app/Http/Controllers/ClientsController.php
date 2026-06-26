<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountBillingDetail;
use App\Models\Client;
use App\Models\ClientBillingDetail;
use App\Models\ClientCategory;
use App\Models\ClientDocument;
use App\Models\CommunicationLog;
use App\Models\Group;
use App\Models\Ledger;
use App\Models\Order;
use App\Models\Service;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class ClientsController extends Controller
{
    public function clientsStoreApi(Request $request): JsonResponse
    {
        $this->assertInternalApiKey($request);

        $validated = $request->validate([
            'accountid' => 'required|string|max:10',
            'type' => 'required',
            'itemid' => 'required|string|max:10',
            'business_name' => 'nullable|string|max:150',
            'contact_name' => 'nullable|string|max:150',
            'primary_email' => 'required|email|max:150',
            'phone' => 'nullable|string|max:50',
            'whatsapp_number' => 'nullable|string|max:50',
            'logo_path' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:150',
            'address_line_1' => 'nullable|string|max:150',
            'address_line_2' => 'nullable|string|max:150',
            'groupid' => 'nullable|exists:groups,groupid',
            'categoryid' => 'nullable|string|exists:client_categories,categoryid',
            'domain' => 'nullable|string|max:150',
        ]);

        $validated['primary_email'] = strtolower(trim((string) ($validated['primary_email'] ?? '')));

        $existingClient = Client::query()
            ->whereRaw('LOWER(primary_email) = ?', [$validated['primary_email']])
            ->first();
        if ($existingClient && (string) $existingClient->accountid === (string) $validated['accountid']) {
            $existingActiveOrder = Order::query()
                ->where('clientid', $existingClient->clientid)
                ->where('itemid', $validated['itemid'])
                ->where('status', 'active')
                ->first();

            if ($existingActiveOrder) {
                Log::warning('Client API skipped: client already has an active order for this product.', [
                    'accountid' => $validated['accountid'] ?? null,
                    'clientid' => $existingClient->clientid,
                    'primary_email' => $validated['primary_email'] ?? null,
                    'itemid' => $validated['itemid'] ?? null,
                    'orderid' => $existingActiveOrder->orderid,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'You have already purchased this product.',
                ], 409);
            }

            $service = Service::query()
                ->where('accountid', $validated['accountid'])
                ->where('itemid', $validated['itemid'])
                ->first();
            if (! $service) {
                Log::warning('Client API blocked: invalid itemid for account.', [
                    'accountid' => $validated['accountid'] ?? null,
                    'primary_email' => $validated['primary_email'] ?? null,
                    'itemid' => $validated['itemid'] ?? null,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid itemid for this account.',
                ], 422);
            }

            try {
                $order = DB::transaction(function () use ($existingClient, $validated, $service) {
                    $startDate = Carbon::today();
                    $frequency = trim((string) env('TRIAL_API_ORDER_FREQUENCY', 'month'));
                    $duration = (int) env('TRIAL_API_ORDER_DURATION', 1);
                    $endDate = $this->calculateOrderEndDate($startDate, $frequency, $duration);

                    return Order::create([
                        'accountid' => $validated['accountid'],
                        'clientid' => $existingClient->clientid,
                        'order_number' => Order::generateNextOrderNumberForAccount($validated['accountid']),
                        'status' => 'active',
                        'client_docid' => null,
                        'itemid' => $service->itemid,
                        'item_name' => $service->name ?? 'Item',
                        'item_description' => $service->description ?? null,
                        'quantity' => 1,
                        'no_of_users' => $service->user_wise ? 2 : null,
                        'start_date' => $startDate->toDateString(),
                        'end_date' => $endDate->toDateString(),
                        'delivery_date' => null,
                        'type' => $validated['type'] === 'trial' ? 'trial' : 'regular',
                    ]);
                });
            } catch (Throwable $e) {
                Log::error('Order creation failed for existing client.', [
                    'accountid' => $validated['accountid'] ?? null,
                    'clientid' => $existingClient->clientid,
                    'itemid' => $validated['itemid'] ?? null,
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Order creation failed. Please check logs.',
                ], 500);
            }

            Log::info('New order created for existing client (no trial conversion).', [
                'clientid' => $existingClient->clientid,
                'orderid' => $order->orderid,
                'itemid' => $validated['itemid'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Order created for existing client.',
                'data' => [
                    'clientid' => $existingClient->clientid,
                    'accountid' => $existingClient->accountid,
                    'type' => $existingClient->type,
                    'business_name' => $existingClient->business_name,
                    'contact_name' => $existingClient->contact_name,
                    'primary_email' => $existingClient->primary_email,
                    'orderid' => $order->orderid,
                    'order_number' => $order->order_number,
                    'itemid' => $order->itemid,
                    'item_name' => $order->item_name,
                    'start_date' => optional($order->start_date)->format('Y-m-d'),
                    'end_date' => optional($order->end_date)->format('Y-m-d'),
                ],
            ], 201);
        }

        if ($existingClient) {
            Log::warning('Client API skipped: primary email already exists.', [
                'accountid' => $validated['accountid'] ?? null,
                'type' => $validated['type'] ?? null,
                'primary_email' => $validated['primary_email'] ?? null,
                'itemid' => $validated['itemid'] ?? null,
                'existing_accountid' => $existingClient->accountid,
            ]);

            return response()->json([
                'success' => false,
                'warning' => true,
                'message' => 'Primary email already exists.',
            ], 409);
        }

        $service = Service::query()
            ->where('accountid', $validated['accountid'])
            ->where('itemid', $validated['itemid'])
            ->first();
        if (! $service) {
            Log::warning('Client API blocked: invalid itemid for account.', [
                'accountid' => $validated['accountid'] ?? null,
                'primary_email' => $validated['primary_email'] ?? null,
                'itemid' => $validated['itemid'] ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid itemid for this account.',
            ], 422);
        }

        $businessName = trim((string) ($validated['business_name'] ?? ''));
        if ($businessName === '') {
            $businessName = $this->deriveBusinessNameFromEmail($validated['primary_email']);
        }
        $addressLine1 = $validated['address_line_1'] ?? $validated['address'] ?? null;

        $startDate = Carbon::today();
        $frequency = trim((string) env('TRIAL_API_ORDER_FREQUENCY', 'month'));
        $duration = (int) env('TRIAL_API_ORDER_DURATION', 1);
        $endDate = $this->calculateOrderEndDate($startDate, $frequency, $duration);

        $client = null;
        $order = null;
        $temporaryPassword = \Illuminate\Support\Str::random(6);
        $welcomeEmailSent = false;
        try {
            DB::transaction(function () use (&$client, &$order, $validated, $service, $businessName, $startDate, $endDate, $addressLine1) {
                $client = Client::create([
                    'accountid' => $validated['accountid'],
                    'type' => $validated['type'],
                    'business_name' => $businessName,
                    'primary_email' => $validated['primary_email'],
                    'email' => null,
                    'phone' => $validated['phone'] ?? null,
                    'whatsapp_number' => $validated['whatsapp_number'] ?? null,
                    'logo_path' => $validated['logo_path'] ?? null,
                    'currency' => 'INR',
                    'status' => 'active',
                    'country' => $validated['country'] ?? 'India',
                    'state' => $validated['state'] ?? null,
                    'city' => $validated['city'] ?? null,
                    'postal_code' => $validated['postal_code'] ?? null,
                    'address_line_1' => $addressLine1,
                    'address_line_2' => $validated['address_line_2'] ?? null,
                    'groupid' => $validated['groupid'] ?? null,
                    'categoryid' => $validated['categoryid'] ?? null,
                ]);

                $client->contacts()->create([
                    'accountid' => $client->accountid,
                    'name' => $validated['contact_name'] ?: 'N/A',
                    'phone' => $validated['phone'] ?? null,
                    'email' => $validated['primary_email'] ?? null,
                    'designation' => null,
                    'is_primary' => true,
                ]);

                $order = Order::create([
                    'accountid' => $validated['accountid'],
                    'clientid' => $client->clientid,
                    'order_number' => Order::generateNextOrderNumberForAccount($validated['accountid']),
                    'status' => 'active',
                    'client_docid' => null,
                    'itemid' => $service->itemid,
                    'item_name' => $service->name ?? 'Item',
                    'item_description' => $service->description ?? null,
                    'quantity' => 1,
                    'no_of_users' => $service->user_wise ? 2 : null,
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                    'delivery_date' => null,
                    'type' => $validated['type'] === 'trial' ? 'trial' : 'regular',
                ]);
            });

            $this->syncWithSuperadmin($client, $validated['domain'] ?? null, $temporaryPassword);

            if (strtolower((string) $client->type) === 'trial') {
                $welcomeEmailSent = $this->sendTrialWelcomeEmail(
                    $client,
                    $order,
                    $temporaryPassword
                );
            }
        } catch (Throwable $e) {
            Log::error('Client API failed: client/order not inserted (transaction rolled back).', [
                'accountid' => $validated['accountid'] ?? null,
                'type' => $validated['type'] ?? null,
                'primary_email' => $validated['primary_email'] ?? null,
                'itemid' => $validated['itemid'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Insert failed. Please check logs.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Client and order created successfully.',
            'data' => [
                'clientid' => $client->clientid,
                'accountid' => $client->accountid,
                'type' => $client->type,
                'business_name' => $client->business_name,
                'contact_name' => $client->contact_name,
                'primary_email' => $client->primary_email,
                'orderid' => $order->orderid,
                'order_number' => $order->order_number,
                'itemid' => $order->itemid,
                'item_name' => $order->item_name,
                'start_date' => optional($order->start_date)->format('Y-m-d'),
                'end_date' => optional($order->end_date)->format('Y-m-d'),
                'welcome_email_sent' => $welcomeEmailSent,
            ],
        ], 201);
    }

    public function clients(): View
    {
        $accountId = $this->resolveAccountId();
        $query = Client::query()->where('accountid', $accountId)->regular()->with(['invoices.invoiceItems', 'payments', 'primaryContact']);
        $searchTerm = trim((string) request('search', ''));
        $selectedState = trim((string) request('state', ''));
        $selectedCity = trim((string) request('city', ''));

        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('business_name', 'like', '%'.$searchTerm.'%')
                    ->orWhereHas('contacts', function ($qContact) use ($searchTerm) {
                        $qContact->where('name', 'like', '%'.$searchTerm.'%');
                    });
            });
        }

        if ($selectedState !== '') {
            $query->where('state', $selectedState);
        }

        if ($selectedCity !== '') {
            $query->where('city', $selectedCity);
        }

        $selectedGroup = trim((string) request('groupid', ''));
        if ($selectedGroup !== '') {
            $query->where('groupid', $selectedGroup);
        }

        $resultCount = $query->count();

        $clients = $query->orderBy('business_name')->paginate(20);

        $clients->getCollection()->transform(function ($client) {
            $invoiceTotal = $client->invoices
                ->where('status', '!=', 'cancelled')
                ->where('payment_status', '!=', 'paid')
                ->sum(fn ($invoice) => (float) ($invoice->grand_total ?? 0));
            $paidTotal = (float) $client->payments->sum(function ($payment) {
                return (float) ($payment->received_amount ?? 0);
            });
            $outstanding = $invoiceTotal - $paidTotal;
            $account = Account::find($client->accountid);
            $cur = $account?->currency_code ?? 'INR';

            return [
                'record_id' => $client->clientid,
                'name' => $client->business_name ?? $client->contact_name,
                'contact' => $client->contact_name,
                'email' => $client->primary_email ?? $client->email,
                'phone' => $client->phone,
                'state' => $client->state,
                'city' => $client->city,
                'currency' => $client->currency,
                'status' => $client->status ?? 'Active',
                'balance' => $client->currency.' '.number_format($outstanding, 0),
                'created_at' => $client->created_at,
            ];
        });

        $groups = Group::where('accountid', $accountId)->orderBy('group_name')->get();
        $stateOptions = Client::query()
            ->where('accountid', $accountId)
            ->regular()
            ->whereNotNull('state')
            ->where('state', '!=', '')
            ->select('state')
            ->distinct()
            ->orderBy('state')
            ->pluck('state');
        $cityOptions = Client::query()
            ->where('accountid', $accountId)
            ->regular()
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->when($selectedState !== '', fn ($cityQuery) => $cityQuery->where('state', $selectedState))
            ->select('city')
            ->distinct()
            ->orderBy('city')
            ->pluck('city');

        $categories = ClientCategory::where('accountid', $accountId)->orderBy('sequence')->orderBy('name')->get();

        return view('clients.index', [
            'title' => 'All Clients',
            'subtitle' => $searchTerm ? 'Found '.$resultCount.' result(s) for "'.$searchTerm.'"' : null,
            'clients' => $clients,
            'groups' => $groups,
            'categories' => $categories,
            'searchTerm' => $searchTerm,
            'resultCount' => $resultCount,
            'selectedState' => $selectedState,
            'selectedCity' => $selectedCity,
            'selectedGroup' => $selectedGroup,
            'stateOptions' => $stateOptions,
            'cityOptions' => $cityOptions,
        ]);
    }

    public function trialClients(): View
    {
        $accountId = $this->resolveAccountId();
        $searchTerm = trim((string) request('search', ''));
        $selectedClient = trim((string) request('client', ''));
        $selectedItem = trim((string) request('item', ''));

        $query = Client::query()
            ->where('accountid', $accountId)
            ->trial()
            ->with(['invoices.invoiceItems', 'payments', 'orders', 'primaryContact']);

        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('business_name', 'like', '%'.$searchTerm.'%')
                    ->orWhereHas('contacts', function ($qContact) use ($searchTerm) {
                        $qContact->where('name', 'like', '%'.$searchTerm.'%');
                    });
            });
        }

        if ($selectedClient !== '') {
            $query->where('clientid', $selectedClient);
        }

        if ($selectedItem !== '') {
            $query->whereHas('orders', function ($q) use ($selectedItem) {
                $q->where('item_name', $selectedItem);
            });
        }

        $resultCount = $query->count();

        $clients = $query->orderBy('business_name')->take(50)->get()->map(function ($client) use ($selectedItem) {
            $invoiceTotal = $client->invoices
                ->where('status', '!=', 'cancelled')
                ->where('payment_status', '!=', 'paid')
                ->sum(fn ($invoice) => (float) ($invoice->grand_total ?? 0));
            $paidTotal = (float) $client->payments->sum(function ($payment) {
                return (float) ($payment->received_amount ?? 0);
            });
            $outstanding = $invoiceTotal - $paidTotal;
            $account = Account::find($client->accountid);
            // $cur = $account?->currency_code ?? 'INR';

            $itemName = $selectedItem !== ''
                ? $client->orders->where('item_name', $selectedItem)->first()?->item_name
                : $client->orders->first()?->item_name;

            $itemEndDate = $selectedItem !== ''
                ? $client->orders->where('item_name', $selectedItem)->first()?->end_date
                : $client->orders->first()?->end_date;

            $itemOrderId = $selectedItem !== ''
                ? $client->orders->where('item_name', $selectedItem)->first()?->orderid
                : $client->orders->first()?->orderid;

            return [
                'record_id' => $client->clientid,
                'name' => $client->business_name ?? $client->contact_name,
                'contact' => $client->contact_name,
                'email' => $client->primary_email ?? $client->email,
                'phone' => $client->phone,
                'currency' => $client->currency,
                'balance' => $client->currency.' '.number_format($outstanding, 0),
                'status' => $client->status ?? 'active',
                'created_at' => $client->created_at,
                'item_name' => $itemName,
                'item_end_date' => $itemEndDate,
                'item_order_id' => $itemOrderId,
                'orders_data' => $client->orders->map(fn ($order) => [
                    'record_id' => $order->orderid,
                    'number' => $order->order_number,
                    'clientid' => $client->clientid,
                    'status' => $order->status ?? '',
                    'items' => [[
                        'item_name' => $order->item_name,
                        'item_description' => $order->item_description,
                        'quantity' => (float) ($order->quantity ?? 1),
                        'no_of_users' => $order->no_of_users,
                        'start_date' => $order->start_date?->format('Y-m-d'),
                        'end_date' => $order->end_date?->format('Y-m-d'),
                        'delivery_date' => $order->delivery_date?->format('Y-m-d'),
                    ]],
                ])->values(),
            ];
        });

        // Distinct item names from orders of trial clients in this account
        $trialClientIds = Client::query()
            ->where('accountid', $accountId)
            ->trial()
            ->pluck('clientid');

        $itemOptions = Order::query()
            ->whereIn('clientid', $trialClientIds)
            ->whereNotNull('item_name')
            ->where('item_name', '!=', '')
            ->select('item_name')
            ->distinct()
            ->orderBy('item_name')
            ->pluck('item_name');

        // All trial clients for the client filter dropdown
        $clientOptions = Client::query()
            ->where('accountid', $accountId)
            ->trial()
            ->with('primaryContact')
            ->orderBy('business_name')
            ->get();

        return view('clients.trials', [
            'title' => 'Trial Clients',
            'subtitle' => $searchTerm ? 'Found '.$resultCount.' result(s) for "'.$searchTerm.'"' : null,
            'clients' => $clients,
            'searchTerm' => $searchTerm,
            'resultCount' => $resultCount,
            'selectedClient' => $selectedClient,
            'selectedItem' => $selectedItem,
            'itemOptions' => $itemOptions,
            'clientOptions' => $clientOptions,
        ]);
    }

    public function clientsCreate(): View
    {
        $accountId = $this->resolveAccountId();
        $billingProfiles = ClientBillingDetail::query()
            ->where('accountid', $accountId)
            ->with('clients:clientid,bd_id,business_name')
            ->orderBy('business_name')
            ->get();
        $currencies = DB::table('currency')
            ->orderBy('iso')
            ->get(['iso', 'name']);

        return view('clients.form', [
            'title' => 'Add Client',
            'accounts' => Account::where('status', 'active')->get(),
            'groups' => Group::where('accountid', $accountId)->get(),
            'categories' => ClientCategory::where('accountid', $accountId)->orderBy('sequence')->orderBy('name')->get(),
            'billingProfiles' => $billingProfiles,
            'currencies' => $currencies,
        ]);
    }

    public function clientsStore(Request $request)
    {
        $userAccountId = $this->resolveAccountId();

        $validated = $request->validate([
            'accountid' => 'required|string|exists:accounts,accountid|max:10',
            'business_name' => 'required|string',
            'groupid' => 'nullable|exists:groups,groupid',
            'categoryid' => 'nullable|exists:client_categories,categoryid',
            'contacts_json' => 'nullable|json',
            'primary_email' => 'required|email|max:150',
            'email' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:50',
            'whatsapp_number' => 'nullable|string|max:50',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'status' => 'in:active,review,inactive',
            'currency' => 'required|string|size:3|exists:currency,iso',
            'country' => 'nullable|string',
            'state' => 'nullable|string',
            'city' => 'nullable|string',
            'postal_code' => 'nullable|string|max:20',
            'address_line_1' => 'nullable|string',
            'existing_bd_id' => 'nullable|string|size:6|exists:client_billing_details,bd_id',
            'billing_business_name' => 'required|string',
            'billing_gstin' => 'nullable|string|size:15',
            'billing_email' => 'nullable|string|max:150',
            'billing_city' => 'nullable|string',
            'billing_state' => 'required|string',
            'billing_country' => 'nullable|string',
            'billing_postal_code' => 'nullable|string',
            'billing_address_line_1' => 'nullable|string',
            'billing_phone' => 'nullable|string',
        ]);

        $validated['primary_email'] = strtolower(trim((string) ($validated['primary_email'] ?? '')));
        $validated['email'] = $this->normalizeClientEmails((string) ($validated['email'] ?? ''), false, 'email');
        $validated['email'] = $this->removeEmailFromList($validated['email'], $validated['primary_email']);
        if ($validated['email'] !== null && strlen($validated['email']) > 500) {
            throw ValidationException::withMessages(['email' => 'Secondary emails exceed 500 characters.']);
        }

        // Normalize billing_email if multiple addresses provided
        if (! empty($validated['billing_email'])) {
            $validated['billing_email'] = $this->normalizeClientEmails((string) $validated['billing_email']);
            if (strlen($validated['billing_email']) > 150) {
                throw ValidationException::withMessages(['billing_email' => 'Combined billing emails exceed 150 characters.']);
            }
        } else {
            $validated['billing_email'] = null;
        }

        $validated['billing_phone'] = isset($validated['billing_phone'])
            ? trim((string) $validated['billing_phone'])
            : null;
        if ($validated['billing_phone'] === '') {
            $validated['billing_phone'] = null;
        }

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos', 'public');
            $baseUrl = rtrim(config('app.url'), '/');
            $validated['logo_path'] = $baseUrl.'/public/storage/'.$path;
        }

        $validated['accountid'] = $validated['accountid'] ?? $userAccountId;

        $validated['type'] = 'regular';
        $selectedBillingDetail = null;

        if (! empty($validated['existing_bd_id'])) {
            $selectedBillingDetail = ClientBillingDetail::query()
                ->where('bd_id', $validated['existing_bd_id'] ?? '')
                ->where('accountid', $validated['accountid'])
                ->first();

            if (! $selectedBillingDetail) {
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
                'billing_phone' => $validated['billing_phone'] ?? null,
                'address_line_1' => $validated['billing_address_line_1'] ?? null,
            ]);
        } else {
            $selectedBillingDetail = ClientBillingDetail::create([
                'bd_id' => Group::generateUniqueAlphaId(new Group),
                'accountid' => $validated['accountid'],
                'business_name' => $validated['billing_business_name'],
                'gstin' => $validated['billing_gstin'] ?? null,
                'billing_email' => $validated['billing_email'] ?? null,
                'city' => $validated['billing_city'] ?? null,
                'state' => $validated['billing_state'] ?? null,
                'country' => $validated['billing_country'] ?? 'India',
                'postal_code' => $validated['billing_postal_code'] ?? null,
                'billing_phone' => $validated['billing_phone'] ?? null,
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
            'billing_phone',
            'contacts_json',
        ])->all();

        $contacts = [];
        if ($request->filled('contacts_json')) {
            $rawContacts = json_decode($request->input('contacts_json'), true) ?: [];
            $hasPrimary = false;
            foreach ($rawContacts as $item) {
                $name = trim((string) ($item['name'] ?? ''));
                if ($name === '') {
                    continue;
                }
                $isPrimary = filter_var($item['is_primary'] ?? false, FILTER_VALIDATE_BOOLEAN);
                if ($isPrimary) {
                    $hasPrimary = true;
                }
                $contacts[] = [
                    'name' => $name,
                    'phone' => trim((string) ($item['phone'] ?? '')) ?: null,
                    'email' => trim((string) ($item['email'] ?? '')) ?: null,
                    'designation' => trim((string) ($item['designation'] ?? '')) ?: null,
                    'is_primary' => $isPrimary,
                ];
            }
            if (! empty($contacts) && ! $hasPrimary) {
                $contacts[0]['is_primary'] = true;
            }
        }

        if (empty($contacts)) {
            throw ValidationException::withMessages([
                'contacts_json' => ['At least one contact must be added.'],
            ]);
        }

        $client = null;
        DB::transaction(function () use ($clientData, $contacts, &$client) {
            $client = Client::create($clientData);

            foreach ($contacts as $contact) {
                $client->contacts()->create([
                    'accountid' => $client->accountid,
                    'name' => $contact['name'],
                    'phone' => $contact['phone'],
                    'email' => $contact['email'],
                    'designation' => $contact['designation'],
                    'is_primary' => $contact['is_primary'],
                ]);
            }
        });

        $this->syncWithSuperadmin($client);

        return redirect()->route('clients.index')->with('success', 'Client created successfully.');
    }

    public function clientDashboard(Request $request, ?Client $client = null): View
    {
        $accountId = $this->resolveAccountId();

        // Fetch all regular clients for selection dropdown
        $clients = Client::where('accountid', $accountId)->regular()->with('primaryContact')->orderBy('business_name')->get();

        if ($client) {
            if ((string) $client->accountid !== $accountId) {
                abort(404);
            }

            $client->load(['invoices', 'payments', 'billingDetail', 'documents', 'orders', 'quotations', 'contacts', 'primaryContact']);

            $invoicedTotal = (float) $client->invoices->where('status', '!=', 'cancelled')->sum('grand_total');
            $paidTotal = (float) $client->payments->sum('received_amount');
            $outstanding = $invoicedTotal - $paidTotal;

            $invoices = $client->invoices->sortByDesc('created_at')->values();
            $payments = $client->payments->sortByDesc('payment_date')->values();
            $orders = $client->orders->sortByDesc('start_date')->values();
            $quotations = $client->quotations->sortByDesc('issue_date')->values();
            $documents = $client->documents->sortByDesc('created_at')->values();

            $ledger = Ledger::where('clientid', $client->clientid)->orderBy('date', 'desc')->get();
            $communicationLogs = CommunicationLog::where('clientid', $client->clientid)->orderBy('created_at', 'desc')->get();

            $activeOrdersCount = $client->orders->where('status', 'active')->count();
        }

        $services = Service::query()
            ->where('accountid', $accountId)
            ->with('category')
            ->orderBy('name')
            ->get(['itemid', 'name', 'ps_catid', 'user_wise']);

        $clientDocuments = ClientDocument::query()
            ->where('accountid', $accountId)
            ->where('type', 'po')
            ->where('status', 'active')
            ->orderByDesc('document_date')
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('clientid');

        return view('clients.dashboard', [
            'title' => $client ? ($client->business_name ?? $client->contact_name).' - Dashboard' : 'Client Dashboard',
            'subtitle' => null,
            'clients' => $clients,
            'client' => $client,
            'outstanding' => $outstanding ?? 0,
            'invoicedTotal' => $invoicedTotal ?? 0,
            'paidTotal' => $paidTotal ?? 0,
            'activeOrdersCount' => $activeOrdersCount ?? 0,
            'invoices' => $invoices ?? collect(),
            'payments' => $payments ?? collect(),
            'orders' => $orders ?? collect(),
            'quotations' => $quotations ?? collect(),
            'documents' => $documents ?? collect(),
            'ledger' => $ledger ?? collect(),
            'communicationLogs' => $communicationLogs ?? collect(),
            'services' => $services,
            'clientDocuments' => $clientDocuments,
        ]);
    }

    public function clientsEdit(Client $client): View
    {
        $accountId = $client->accountid ?: $this->resolveAccountId();
        $billingProfiles = ClientBillingDetail::query()
            ->where('accountid', $accountId)
            ->with('clients:clientid,bd_id,business_name')
            ->orderBy('business_name')
            ->get();
        $currencies = DB::table('currency')
            ->orderBy('iso')
            ->get(['iso', 'name']);

        $client->load('contacts');

        return view('clients.form', [
            'title' => 'Edit '.($client->business_name ?? $client->contact_name ?? 'Client'),
            'client' => $client,
            'accounts' => Account::where('status', 'active')->get(),
            'groups' => Group::where('accountid', $accountId)->get(),
            'categories' => ClientCategory::where('accountid', $accountId)->orderBy('sequence')->orderBy('name')->get(),
            'billingProfiles' => $billingProfiles,
            'currencies' => $currencies,
        ]);
    }

    public function clientsUpdate(Request $request, Client $client)
    {
        $validated = $request->validate([
            'business_name' => 'required|string',
            'groupid' => 'nullable|exists:groups,groupid',
            'categoryid' => 'nullable|exists:client_categories,categoryid',
            'contacts_json' => 'nullable|json',
            'primary_email' => 'required|email|max:150',
            'email' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:50',
            'whatsapp_number' => 'nullable|string|max:50',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'status' => 'in:active,review,inactive',
            'currency' => 'required|string|size:3|exists:currency,iso',
            'country' => 'nullable|string',
            'state' => 'nullable|string',
            'city' => 'nullable|string',
            'postal_code' => 'nullable|string|max:20',
            'address_line_1' => 'nullable|string',
            'existing_bd_id' => 'nullable|string|size:6|exists:client_billing_details,bd_id',
            'billing_business_name' => 'required|string',
            'billing_gstin' => 'nullable|string|size:15',
            'billing_email' => 'nullable|string|max:150',
            'billing_city' => 'nullable|string',
            'billing_state' => 'required|string',
            'billing_country' => 'nullable|string',
            'billing_postal_code' => 'nullable|string',
            'billing_address_line_1' => 'nullable|string',
            'billing_phone' => 'nullable|string',
        ]);

        $validated['primary_email'] = strtolower(trim((string) ($validated['primary_email'] ?? '')));
        $validated['email'] = $this->normalizeClientEmails((string) ($validated['email'] ?? ''), false, 'email');
        $validated['email'] = $this->removeEmailFromList($validated['email'], $validated['primary_email']);
        if ($validated['email'] !== null && strlen($validated['email']) > 500) {
            throw ValidationException::withMessages(['email' => 'Secondary emails exceed 500 characters.']);
        }

        // Normalize billing_email if multiple addresses provided
        if (! empty($validated['billing_email'])) {
            $validated['billing_email'] = $this->normalizeClientEmails((string) $validated['billing_email']);
            if (strlen($validated['billing_email']) > 150) {
                throw ValidationException::withMessages(['billing_email' => 'Combined billing emails exceed 150 characters.']);
            }
        } else {
            $validated['billing_email'] = null;
        }

        $validated['billing_phone'] = isset($validated['billing_phone'])
            ? trim((string) $validated['billing_phone'])
            : null;
        if ($validated['billing_phone'] === '') {
            $validated['billing_phone'] = null;
        }

        if ($request->hasFile('logo')) {
            if ($client->logo_path) {
                $storageBase = rtrim(config('app.url'), '/').'/public/storage/';
                $oldPath = str_replace($storageBase, '', $client->logo_path);
                Storage::disk('public')->delete($oldPath);
            }
            $path = $request->file('logo')->store('logos', 'public');
            $baseUrl = rtrim(config('app.url'), '/');
            $validated['logo_path'] = $baseUrl.'/public/storage/'.$path;
        }

        $selectedBdId = $client->bd_id;

        if (! empty($validated['existing_bd_id'])) {
            $existingBillingDetail = ClientBillingDetail::query()
                ->where('bd_id', $validated['existing_bd_id'] ?? '')
                ->where('accountid', $client->accountid)
                ->first();

            if (! $existingBillingDetail) {
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
                'billing_phone' => $validated['billing_phone'] ?? null,
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
                'billing_phone' => $validated['billing_phone'] ?? null,
                'address_line_1' => $validated['billing_address_line_1'] ?? null,
            ];

            $currentUsageCount = Client::where('bd_id', $client->bd_id)->count();
            if ($client->billingDetail && $currentUsageCount <= 1) {
                $client->billingDetail->update($billingData);
                $selectedBdId = $client->billingDetail->bd_id;
            } else {
                $newBillingDetail = ClientBillingDetail::create(array_merge($billingData, [
                    'bd_id' => Group::generateUniqueAlphaId(new Group),
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
            'billing_phone',
            'contacts_json',
        ])->all();

        $contacts = [];
        if ($request->filled('contacts_json')) {
            $rawContacts = json_decode($request->input('contacts_json'), true) ?: [];
            $hasPrimary = false;
            foreach ($rawContacts as $item) {
                $name = trim((string) ($item['name'] ?? ''));
                if ($name === '') {
                    continue;
                }
                $isPrimary = filter_var($item['is_primary'] ?? false, FILTER_VALIDATE_BOOLEAN);
                if ($isPrimary) {
                    $hasPrimary = true;
                }
                $contacts[] = [
                    'name' => $name,
                    'phone' => trim((string) ($item['phone'] ?? '')) ?: null,
                    'email' => trim((string) ($item['email'] ?? '')) ?: null,
                    'designation' => trim((string) ($item['designation'] ?? '')) ?: null,
                    'is_primary' => $isPrimary,
                ];
            }
            if (! empty($contacts) && ! $hasPrimary) {
                $contacts[0]['is_primary'] = true;
            }
        }

        if (empty($contacts)) {
            throw ValidationException::withMessages([
                'contacts_json' => ['At least one contact must be added.'],
            ]);
        }

        DB::transaction(function () use ($client, $clientData, $contacts) {
            $client->update($clientData);

            $client->contacts()->delete();
            foreach ($contacts as $contact) {
                $client->contacts()->create([
                    'accountid' => $client->accountid,
                    'name' => $contact['name'],
                    'phone' => $contact['phone'],
                    'email' => $contact['email'],
                    'designation' => $contact['designation'],
                    'is_primary' => $contact['is_primary'],
                ]);
            }
        });

        $this->syncWithSuperadmin($client);

        return redirect()->route('clients.index')->with('success', 'Client updated successfully.');
    }

    public function clientsDestroy(Client $client)
    {
        $client->delete();

        return redirect()->route('clients.index')->with('success', 'Client deleted successfully.');
    }

    public function convertTrialToRegular(Client $client)
    {
        if ((string) $client->accountid !== $this->resolveAccountId()) {
            abort(404);
        }

        DB::transaction(function () use ($client) {
            $client->update(['type' => 'regular']);
            $client->orders()->where('type', 'trial')->update(['type' => 'regular']);
        });

        $this->syncWithSuperadmin($client);

        return redirect()->route('clients.edit', $client)->with('success', 'Client converted to regular successfully.');
    }

    public function toggleClientStatus(Request $request, Client $client): JsonResponse
    {
        if ((string) $client->accountid !== $this->resolveAccountId()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.',
            ], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:active,inactive',
        ]);

        $client->update([
            'status' => $validated['status'],
        ]);

        $this->syncWithSuperadmin($client);

        return response()->json([
            'success' => true,
            'message' => 'Client status updated successfully.',
            'status' => $client->status,
        ]);
    }

    private function normalizeClientEmails(string $rawEmails, bool $required = true, string $field = 'email'): ?string
    {
        $emails = collect(explode(',', $rawEmails))
            ->map(fn ($email) => trim($email))
            ->filter()
            ->unique()
            ->values();

        if ($emails->isEmpty()) {
            if (! $required) {
                return null;
            }

            throw ValidationException::withMessages([
                $field => 'At least one email is required.',
            ]);
        }

        foreach ($emails as $email) {
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw ValidationException::withMessages([
                    $field => 'Invalid email address: '.$email,
                ]);
            }
        }

        return $emails->implode(', ');
    }

    private function removeEmailFromList(?string $emails, string $emailToRemove): ?string
    {
        if ($emails === null) {
            return null;
        }

        $normalizedPrimary = strtolower(trim($emailToRemove));

        $filtered = collect(explode(',', $emails))
            ->map(fn ($email) => trim($email))
            ->filter()
            ->reject(fn ($email) => strtolower($email) === $normalizedPrimary)
            ->unique()
            ->values();

        return $filtered->isEmpty() ? null : $filtered->implode(', ');
    }

    private function deriveBusinessNameFromEmail(string $email): string
    {
        $local = trim((string) strtok($email, '@'));
        if ($local === '') {
            return 'Client';
        }

        return ucfirst(str_replace(['.', '_', '-'], ' ', $local));
    }

    private function sendTrialWelcomeEmail(Client $client, Order $order, string $temporaryPassword): bool
    {
        $email = trim((string) ($client->primary_email ?? $client->email ?? ''));
        if ($email === '') {
            return false;
        }

        if ($temporaryPassword === '') {
            Log::warning('Trial welcome email skipped: temporary password missing.', [
                'accountid' => $client->accountid,
                'clientid' => $client->clientid,
                'orderid' => $order->orderid,
                'email' => $email,
            ]);

            return false;
        }

        try {
            $accountBillingDetail = AccountBillingDetail::where('accountid', $client->accountid)->first();
            $senderEmail = $accountBillingDetail?->billing_from_email ?? '';
            $senderName = $accountBillingDetail?->billing_name ?: $senderEmail;

            if ($senderEmail !== '') {
                try {
                    $adminMessage = view('emails.admin-trial-notification', [
                        'businessName' => (string) ($client->business_name ?: 'N/A'),
                        'contactName' => (string) ($client->contact_name ?: 'N/A'),
                        'email' => $email,
                        'phone' => $client->phone,
                        'trialItemName' => $order->item_name ?? 'N/A',
                        'trialDays' => $this->trialEmailDays(),
                    ])->render();

                    $this->sendViaCampio('email', [
                        'account_id' => $client->accountid,
                        'campaign_name' => '',
                        'schedule_at' => now()->toIso8601String(),
                        'subject' => 'New Trial Activation: '.($client->business_name ?: 'N/A'),
                        'message' => $adminMessage,
                        'sender_id' => $senderName,
                        'records' => [[
                            'id' => $client->clientid,
                            'name' => 'Admin',
                            'email' => $senderEmail,
                        ]],
                        'source_url' => config('app.url'),
                        'notes' => 'Admin trial notification',
                    ]);
                } catch (Throwable $e) {
                    Log::warning('Admin trial notification email failed.', [
                        'accountid' => $client->accountid,
                        'clientid' => $client->clientid,
                        'email' => $senderEmail,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $clientMessage = view('emails.trial-welcome', [
                'name' => (string) ($client->contact_name ?: $client->business_name ?: 'there'),
                'email' => $email,
                'temporaryPassword' => $temporaryPassword,
                'trialDays' => $this->trialEmailDays(),
                'loginUrl' => (string) env('TRIAL_LOGIN_URL', 'http://alpha.skoolready.com/login'),
            ])->render();

            $this->sendViaCampio('email', [
                'account_id' => $client->accountid,
                'campaign_name' => '',
                'schedule_at' => now()->toIso8601String(),
                'subject' => 'Welcome to SkoolReady - Your 30 Day Trial is Active',
                'message' => $clientMessage,
                'sender_id' => $senderName,
                'records' => [[
                    'id' => $client->clientid,
                    'name' => (string) ($client->contact_name ?: $client->business_name ?: 'there'),
                    'email' => $email,
                ]],
                'source_url' => config('app.url'),
                'notes' => 'Trial welcome email',
            ]);

            return true;
        } catch (Throwable $e) {
            Log::warning('Trial welcome email failed.', [
                'accountid' => $client->accountid,
                'clientid' => $client->clientid,
                'orderid' => $order->orderid,
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function trialEmailDays(): int
    {
        $frequency = strtolower(trim((string) env('TRIAL_API_ORDER_FREQUENCY', 'month')));
        $duration = max(1, (int) env('TRIAL_API_ORDER_DURATION', 1));

        return match ($frequency) {
            'day', 'daily', 'days' => $duration,
            'week', 'weekly', 'weeks' => $duration * 7,
            'year', 'yearly', 'years', 'annual' => $duration * 365,
            default => $duration * 30,
        };
    }

    private function calculateOrderEndDate(Carbon $startDate, string $frequency, int $duration): Carbon
    {
        $normalizedFrequency = strtolower(trim($frequency));
        $safeDuration = max(1, $duration);
        $endDate = $startDate->copy();

        return match ($normalizedFrequency) {
            'day', 'daily', 'days' => $endDate->addDays($safeDuration),
            'week', 'weekly', 'weeks' => $endDate->addWeeks($safeDuration),
            'year', 'yearly', 'years', 'annual' => $endDate->addYears($safeDuration),
            default => $endDate->addMonths($safeDuration),
        };
    }

    private function assertInternalApiKey(Request $request): void
    {
        $expectedApiKey = trim((string) env('INTERNAL_CLIENT_API_KEY', ''));
        if ($expectedApiKey === '') {
            return;
        }

        $provided = (string) $request->header('X-API-KEY', '');
        if (! hash_equals($expectedApiKey, $provided)) {
            abort(401, 'Invalid API key.');
        }
    }

    /**
     * Save or update basic client information via AJAX.
     */
    public function clientsSaveInfoAjax(Request $request): JsonResponse
    {
        $userAccountId = $this->resolveAccountId();

        $validated = $request->validate([
            'clientid' => 'nullable|string|exists:clients,clientid',
            'business_name' => 'required|string|max:150',
            'groupid' => 'nullable|exists:groups,groupid',
            'categoryid' => 'nullable|exists:client_categories,categoryid',
            'primary_email' => 'required|email|max:150',
            'email' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:50',
            'whatsapp_number' => 'nullable|string|max:50',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'type' => 'nullable|in:regular,trial',
            'currency' => 'required|string|size:3|exists:currency,iso',
        ]);

        $validated['primary_email'] = strtolower(trim((string) ($validated['primary_email'] ?? '')));
        if (! empty($validated['email'])) {
            $validated['email'] = $this->normalizeClientEmails((string) ($validated['email'] ?? ''), false, 'email');
            $validated['email'] = $this->removeEmailFromList($validated['email'], $validated['primary_email']);
            if ($validated['email'] !== null && strlen($validated['email']) > 500) {
                throw ValidationException::withMessages(['email' => 'Secondary emails exceed 500 characters.']);
            }
        }

        $client = null;
        if (! empty($validated['clientid'])) {
            $client = Client::where('clientid', $validated['clientid'])->where('accountid', $userAccountId)->firstOrFail();
        }

        if ($request->hasFile('logo')) {
            if ($client && $client->logo_path) {
                $storageBase = rtrim(config('app.url'), '/').'/public/storage/';
                $oldPath = str_replace($storageBase, '', $client->logo_path);
                Storage::disk('public')->delete($oldPath);
            }
            $path = $request->file('logo')->store('logos', 'public');
            $baseUrl = rtrim(config('app.url'), '/');
            $logoPath = $baseUrl.'/public/storage/'.$path;
        } else {
            $logoPath = $client ? $client->logo_path : null;
        }

        $clientData = [
            'accountid' => $userAccountId,
            'business_name' => $validated['business_name'],
            'groupid' => $validated['groupid'] ?? null,
            'categoryid' => $validated['categoryid'] ?? null,
            'primary_email' => $validated['primary_email'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'whatsapp_number' => $validated['whatsapp_number'] ?? null,
            'type' => $validated['type'] ?? 'regular',
            'currency' => $validated['currency'],
            'logo_path' => $logoPath,
            'status' => $client ? $client->status : 'active',
        ];

        if ($client) {
            $client->update($clientData);
            $message = 'Client information updated successfully.';
        } else {
            $client = Client::create($clientData);
            $message = 'Client information saved successfully.';
        }

        $this->syncWithSuperadmin($client);

        return response()->json([
            'success' => true,
            'clientid' => $client->clientid,
            'logo_path' => $client->logo_path,
            'message' => $message,
            'client' => $client,
        ]);
    }

    private function syncWithSuperadmin(Client $client, ?string $domain = null, ?string $password = null): void
    {
        $account = Account::find($client->accountid);
        if ($account && $account->allow_sync) {
            $apiUrl = config('services.superadmin_api.url');
            $apiKey = config('services.superadmin_api.key');

            $contactName = $client->contact_name ?: ($client->business_name ?: 'Client User');

            $payload = [
                'accountid' => $client->clientid,
                'groupid' => $client->groupid ?: $client->clientid,
                'account_type' => $client->category ? $client->category->name : 'Client',
                'account_business_name' => $client->business_name ?: '',
                'account_contact_person_name' => $contactName,
                'account_domain' => $domain,
                'account_email' => $client->primary_email ?: ($client->email ?: ''),
                'account_mobile' => $client->phone ?: '',
                'account_address' => trim($client->address_line_1.' '.$client->address_line_2) ?: null,
                'account_city' => $client->city ?: 'N/A',
                'account_state' => $client->state ?: 'N/A',
                'status' => $client->status ?: 'active',
                'user_name' => $contactName,
                'user_email' => $client->primary_email ?: ($client->email ?: ''),
                'user_password' => $password ?: '123456',
                'user_mobile' => $client->phone ?: '',
            ];

            Log::info('Initiating Superadmin Sync', [
                'clientid' => $client->clientid,
                'apiUrl' => $apiUrl,
                'payload' => $payload,
            ]);

            try {
                $response = Http::acceptJson()
                    ->timeout(10)
                    ->connectTimeout(5)
                    ->withHeaders([
                        'X-API-KEY' => $apiKey,
                    ])->post($apiUrl, $payload);

                if ($response->successful()) {
                    Log::info('Superadmin Sync Succeeded', [
                        'status' => $response->status(),
                        'body' => $response->json() ?? $response->body(),
                    ]);
                } else {
                    Log::error('Superadmin Sync Failed with Status '.$response->status(), [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                }
            } catch (Throwable $e) {
                Log::error('Superadmin Sync Failed with Exception', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }
    }

    private function sendViaCampio(string $channel, array $payload): array
    {
        $baseUrl = rtrim((string) env('CAMPIO_BASE_URL', 'http://alpha.skoolready.com/campio'), '/');
        if ($baseUrl === '') {
            return ['ok' => false, 'message' => 'CAMPIO_BASE_URL is not configured.'];
        }

        $endpoint = $baseUrl.'/api/campaigns/schedule/'.$channel;
        $token = trim((string) env('CAMPIO_AUTH_TOKEN', ''));
        $apiKey = trim((string) env('CAMPIO_API_KEY', ''));

        $request = Http::acceptJson()->asJson()->timeout(30);
        if ($token !== '') {
            $request = $request->withToken($token);
        }
        if ($apiKey !== '') {
            $request = $request->withHeaders(['X-API-KEY' => $apiKey]);
        }

        try {
            $response = $request->post($endpoint, $payload);
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => 'Campio request failed: '.$e->getMessage()];
        }

        $json = $response->json();
        if (! $response->successful()) {
            Log::error('Campio trial welcome email dispatch failed', [
                'channel' => $channel,
                'status' => $response->status(),
                'response' => $json,
            ]);

            return [
                'ok' => false,
                'message' => is_array($json)
                    ? ((string) ($json['message'] ?? 'Campio API returned an error.'))
                    : ('Campio API returned HTTP '.$response->status().'.'),
            ];
        }

        return [
            'ok' => true,
            'campaign_id' => (string) (is_array($json) ? ($json['campaign_id'] ?? '') : ''),
        ];
    }
}
