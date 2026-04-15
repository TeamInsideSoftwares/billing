<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\FinancialYear;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Service;
use App\Models\Tax;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class OrdersController extends Controller
{
    public function selectClient(): View
    {
        $clients = Client::orderBy('business_name')
            ->orderBy('contact_name')
            ->get();

        return view('orders.select-client', [
            'title' => 'Select Client - Orders',
            'clients' => $clients,
        ]);
    }

    public function orders(): View
    {
        $clientId = request('c');
        $selectedClient = null;
        
        $query = Order::with(['client', 'items.item']);
        
        // Filter by client if client_id is provided
        if ($clientId) {
            $query->where('clientid', $clientId);
            $selectedClient = Client::find($clientId);
        }
        
        $searchTerm = request('search', '');

        if ($searchTerm) {
            $query->where('order_number', 'like', '%' . $searchTerm . '%')
                ->orWhereHas('client', function ($q) use ($searchTerm) {
                    $q->where('business_name', 'like', '%' . $searchTerm . '%')
                        ->orWhere('contact_name', 'like', '%' . $searchTerm . '%');
                });
        }
        $resultCount = $query->count();

        $records = $query->latest()->take(50)->get();
        $salesPersonLookup = $this->getSalesPeopleLookup(
            $records->pluck('sales_person_id')->filter()->map(fn ($id) => (string) $id)->unique()->values()
        );

        $orders = $records->map(function ($order) use ($salesPersonLookup) {
            $businessName = $order->client->business_name ?? null;
            $contactName = $order->client->contact_name ?? null;
            $salesPersonId = (string) ($order->sales_person_id ?? '');

            return [
                'record_id' => $order->orderid,
                'number' => $order->order_number ?? 'ORD-' . str_pad($order->orderid, 4, '0', STR_PAD_LEFT),
                'order_title' => $order->order_title,
                'client' => $order->client->business_name ?? $order->client->contact_name ?? 'Client',
                'client_business_name' => $businessName,
                'client_contact_name' => $contactName,
                'client_email' => $order->client->email,
                'client_phone' => $order->client->phone,
                'client_city' => $order->client->city,
                'clientid' => $order->clientid,
                'order_date' => $order->order_date?->format('d M Y') ?? 'N/A',
                'delivery_date' => $order->delivery_date?->format('d M Y') ?? 'N/A',
                'amount' => number_format($order->grand_total ?? 0, 2),
                'discount' => $order->discount_total ?? 0,
                'item_count' => $order->items->count(),
                'items' => $order->items->map(function ($item) {
                    return [
                        'item_name' => $item->item_name ?: ($item->item->name ?? 'Item'),
                        'quantity' => (float) ($item->quantity ?? 1),
                        'unit_price' => (float) ($item->unit_price ?? 0),
                        'tax_rate' => (float) ($item->tax_rate ?? 0),
                        'line_total' => (float) ($item->line_total ?? 0),
                        'discount_percent' => (float) ($item->discount_percent ?? 0),
                        'discount_amount' => (float) ($item->discount_amount ?? 0),
                        'frequency' => (string) ($item->frequency ?? ''),
                        'duration' => (string) ($item->duration ?? ''),
                        'no_of_users' => $item->no_of_users,
                        'start_date' => $item->start_date?->format('Y-m-d'),
                        'end_date' => $item->end_date?->format('Y-m-d'),
                        'delivery_date' => $item->delivery_date?->format('Y-m-d'),
                    ];
                })->values()->all(),
                'sales_person' => $salesPersonLookup[$salesPersonId] ?? ($order->salesPerson->name ?? '-'),
                'is_verified' => ($order->is_verified ?? 'no') === 'yes' ? 'Verified' : 'Unverified',
                'verified' => ($order->is_verified ?? 'no') === 'yes',
            ];
        });

        // Group by client only if no specific client is selected
        if ($clientId) {
            $groupedOrders = ['All Orders' => $orders->sortByDesc('order_date')];
        } else {
            $groupedOrders = $orders->groupBy('client')->sortBy(fn($g, $k) => strtolower($k));
        }

        return view('orders.index', [
            'title' => 'Orders',
            'orders' => $orders,
            'groupedOrders' => $groupedOrders,
            'searchTerm' => $searchTerm,
            'resultCount' => $resultCount,
            'selectedClient' => $selectedClient,
            'clientId' => $clientId,
            'allClients' => Client::orderBy('business_name')->orderBy('contact_name')->get(),
        ]);
    }

    public function ordersCreate(): View
    {
        $accountid = auth()->check() ? (auth()->user()->accountid ?? 'ACC0000001') : 'ACC0000001';
        $account = \App\Models\Account::find($accountid);
        $preSelectedClientId = request('c');
        
        // Get default financial year
        $defaultFy = FinancialYear::where('accountid', $accountid)
            ->where('default', true)
            ->first();
        
        // Generate next order number
        $nextOrderNumber = $this->generateOrderNumber($accountid, $defaultFy?->fy_id);

        return view('orders.create', [
            'title' => 'Create Order',
            'clients' => Client::all(),
            'services' => Service::with('costings')->orderBy('name')->get(),
            'users' => $this->getSalesPeopleForForm($accountid),
            'taxes' => ($account && $account->allow_multi_taxation) ? Tax::where('accountid', $accountid)->where('is_active', true)->orderByRaw('COALESCE(sequence, 999999), created_at DESC')->get() : collect(),
            'account' => $account,
            'fixedTaxRate' => ($account && !$account->allow_multi_taxation) ? ($account->fixed_tax_rate ?? 0) : 0,
            'preSelectedClientId' => $preSelectedClientId,
            'nextOrderNumber' => $nextOrderNumber,
        ]);
    }

    public function ordersStore(Request $request)
    {
        // Check if this is an AJAX request without items_data
        if (!$request->has('items_data')) {
            return $this->saveOrderAjax($request);
        }

        // Check if we're updating an existing order (orderid is present)
        $existingOrderId = $request->input('orderid');
        $existingOrder = $existingOrderId ? Order::find($existingOrderId) : null;

        if ($existingOrder) {
            // Update existing order instead of creating new one
            $validated = $request->validate([
                'clientid' => 'required|exists:clients,clientid',
                'order_number' => 'required|string|unique:orders,order_number,' . $existingOrder->orderid . ',orderid',
                'order_title' => 'required|string|max:255',
                'order_date' => 'required|date',
                'delivery_date' => 'nullable|date|after_or_equal:order_date',
                'notes' => 'nullable|string',
                'sales_person_id' => 'nullable|string|max:50',
                'items_data' => 'required|json',
            ]);

            $itemsData = json_decode($request->items_data, true) ?: [];
            $subtotal = 0;
            $discountTotal = 0;
            $taxTotal = 0;
            foreach ($itemsData as $itemData) {
                $lineTotal = (float) ($itemData['line_total'] ?? 0);
                $discountAmount = (float) ($itemData['discount_amount'] ?? 0);
                $subtotal += $lineTotal;
                $discountTotal += $discountAmount;

                $service = Service::with('costings')->find($itemData['itemid'] ?? null);
                $taxRate = $this->resolveTaxRate($service, $itemData);
                $taxableAmount = $lineTotal - $discountAmount;
                $taxAmount = ($taxableAmount * $taxRate) / 100;
                $taxTotal += $taxAmount;
            }
            $grandTotal = $subtotal - $discountTotal + $taxTotal;

            $existingOrder->update([
                'clientid' => $validated['clientid'],
                'order_number' => $validated['order_number'],
                'order_title' => $validated['order_title'] ?? null,
                'order_date' => $validated['order_date'],
                'delivery_date' => $validated['delivery_date'] ?? null,
                'notes' => $validated['notes'],
                'sales_person_id' => $validated['sales_person_id'] ?? null,
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'tax_total' => $taxTotal,
                'grand_total' => $grandTotal,
            ]);

            // Delete existing items and recreate
            $existingOrder->items()->delete();

            foreach ($itemsData as $index => $itemData) {
                $service = Service::with('costings')->find($itemData['itemid'] ?? null);
                $taxRate = $this->resolveTaxRate($service, $itemData);

                OrderItem::create([
                    'orderid' => $existingOrder->orderid,
                    'itemid' => $itemData['itemid'],
                    'item_name' => $service?->name ?? 'Custom Item',
                    'item_description' => null,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'tax_rate' => $taxRate,
                    'discount_percent' => $itemData['discount_percent'] ?? 0,
                    'discount_amount' => $itemData['discount_amount'] ?? 0,
                    'duration' => $itemData['duration'] ?? null,
                    'frequency' => $itemData['frequency'] ?? null,
                    'no_of_users' => $itemData['no_of_users'] ?? null,
                    'start_date' => $itemData['start_date'] ?? null,
                    'end_date' => $itemData['end_date'] ?? null,
                    'delivery_date' => $itemData['delivery_date'] ?? null,
                    'line_total' => $itemData['line_total'],
                    'sort_order' => $index + 1,
                ]);
            }

            return redirect()->route('orders.index', ['c' => $existingOrder->clientid])->with('success', 'Order updated successfully.');
        }

        // Create new order
        $validated = $request->validate([
            'clientid' => 'required|exists:clients,clientid',
            'order_number' => 'required|string|unique:orders,order_number',
            'order_title' => 'required|string|max:255',
            'order_date' => 'required|date',
            'delivery_date' => 'nullable|date|after_or_equal:order_date',
            'notes' => 'nullable|string',
            'sales_person_id' => 'nullable|string|max:50',
            'subtotal' => 'nullable|numeric|min:0',
            'grand_total' => 'nullable|numeric|min:0',
            'items_data' => 'required|json',
            'accountid' => 'nullable|size:10',
        ]);

        $userAccountId = auth()->check() ? (auth()->user()->accountid ?? 'ACC0000001') : 'ACC0000001';
        $validated['accountid'] = $validated['accountid'] ?? $userAccountId;
        $validated['status'] = 'unverified'; // Always set to unverified
        unset($validated['items_data']);

        $itemsData = json_decode($request->items_data, true) ?: [];
        $subtotal = 0;
        $discountTotal = 0;
        $taxTotal = 0;
        foreach ($itemsData as $itemData) {
            $lineTotal = (float) ($itemData['line_total'] ?? 0);
            $discountAmount = (float) ($itemData['discount_amount'] ?? 0);
            $subtotal += $lineTotal;
            $discountTotal += $discountAmount;

            $service = Service::with('costings')->find($itemData['itemid'] ?? null);
            $taxRate = $this->resolveTaxRate($service, $itemData);
            $taxableAmount = $lineTotal - $discountAmount;
            $taxAmount = ($taxableAmount * $taxRate) / 100;
            $taxTotal += $taxAmount;
        }
        $grandTotal = $subtotal - $discountTotal + $taxTotal;
        $validated['subtotal'] = $subtotal;
        $validated['discount_total'] = $discountTotal;
        $validated['tax_total'] = $taxTotal;
        $validated['grand_total'] = $grandTotal;

        $order = Order::create($validated);

        foreach ($itemsData as $index => $itemData) {
            $service = Service::with('costings')->find($itemData['itemid'] ?? null);
            $taxRate = $this->resolveTaxRate($service, $itemData);

            \Log::info('Order Item Create', [
                'item' => $itemData['itemid'],
                'delivery_date' => $itemData['delivery_date'] ?? 'NULL',
            ]);

            OrderItem::create([
                'orderid' => $order->orderid,
                'itemid' => $itemData['itemid'],
                'item_name' => $service?->name ?? 'Custom Item',
                'item_description' => null,
                'quantity' => $itemData['quantity'],
                'unit_price' => $itemData['unit_price'],
                'tax_rate' => $taxRate,
                'discount_percent' => $itemData['discount_percent'] ?? 0,
                'discount_amount' => $itemData['discount_amount'] ?? 0,
                'duration' => $itemData['duration'] ?? null,
                'frequency' => $itemData['frequency'] ?? null,
                'no_of_users' => $itemData['no_of_users'] ?? null,
                'start_date' => $itemData['start_date'] ?? null,
                'end_date' => $itemData['end_date'] ?? null,
                'delivery_date' => $itemData['delivery_date'] ?? null,
                'line_total' => $itemData['line_total'],
                'sort_order' => $index + 1,
            ]);
        }

        // Return JSON response for AJAX requests
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'orderid' => $order->orderid,
                'message' => 'Order created successfully',
            ]);
        }

        return redirect()->route('orders.index', ['c' => $order->clientid])->with('success', 'Order created successfully with items.');
    }

    public function ordersShow(Order $order): View
    {
        $order->load(['client', 'items.item']);
        $salesPersonName = $this->getSalesPeopleLookup(collect([(string) ($order->sales_person_id ?? '')]))[(string) ($order->sales_person_id ?? '')]
            ?? ($order->salesPerson->name ?? '-');

        return view('orders.show', [
            'title' => 'Order Details',
            'order' => $order,
            'salesPersonName' => $salesPersonName,
        ]);
    }

    /**
     * Get order details with items as JSON (for AJAX)
     */
    public function getOrderJson(Request $request, $order)
    {
        $orderModel = Order::where('orderid', $order)->with(['items.service'])->first();
        
        if (!$orderModel) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        return response()->json([
            'orderid' => $orderModel->orderid,
            'order' => [
                'orderid' => $orderModel->orderid,
                'order_number' => $orderModel->order_number,
                'clientid' => $orderModel->clientid,
                'order_title' => $orderModel->order_title,
                'order_date' => $orderModel->order_date ? $orderModel->order_date->format('Y-m-d') : null,
                'delivery_date' => $orderModel->delivery_date ? $orderModel->delivery_date->format('Y-m-d') : null,
                'sales_person_id' => $orderModel->sales_person_id,
                'notes' => $orderModel->notes,
                'grand_total' => $orderModel->grand_total,
            ],
            'items' => $orderModel->items->map(function ($item) {
                return [
                    'orderid' => $item->orderid,
                    'orderitemid' => $item->orderitemid,
                    'itemid' => $item->itemid,
                    'quantity' => $item->quantity ?? 1,
                    'unit_price' => $item->unit_price ?? 0,
                    'tax_rate' => $item->tax_rate ?? 0,
                    'line_total' => $item->line_total ?? 0,
                    'item_name' => $item->item_name ?? '',
                    'frequency' => $item->frequency,
                    'duration' => $item->duration,
                    'no_of_users' => $item->no_of_users,
                    'start_date' => $item->start_date ? $item->start_date->format('Y-m-d') : null,
                    'end_date' => $item->end_date ? $item->end_date->format('Y-m-d') : null,
                    'delivery_date' => $item->delivery_date ? $item->delivery_date->format('Y-m-d') : null,
                ];
            }),
        ]);
    }

    public function getOrderJsonByNumber(Request $request)
    {
        $orderId = $request->input('o');
        
        if (!$orderId) {
            return response()->json(['error' => 'Order ID required'], 400);
        }

        try {
            $order = Order::where('orderid', $orderId)
                ->orWhere('order_number', $orderId)
                ->with('items')
                ->first();

            if (!$order) {
                return response()->json(['error' => 'Order not found'], 404);
            }

            $items = $order->items->map(function ($item) {
                return [
                    'orderid' => $item->orderid,
                    'itemid' => $item->itemid,
                    'quantity' => $item->quantity ?? 1,
                    'unit_price' => number_format($item->unit_price ?? 0, 2),
                    'tax_rate' => number_format($item->tax_rate ?? 0, 2),
                    'line_total' => number_format($item->line_total ?? 0, 2),
                    'item_name' => $item->item_name ?? 'Item',
                    'service' => null,
                ];
            });

            return response()->json([
                'orderid' => $order->orderid,
                'order_number' => $order->order_number,
                'clientid' => $order->clientid,
                'grand_total' => number_format($order->grand_total ?? 0, 2),
                'items' => $items,
            ]);
        } catch (\Exception $e) {
            \Log::error('Order JSON error: ' . $e->getMessage());
            return response()->json(['error' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    public function ordersEdit(Order $order): View
    {
        $order->load(['items.item']);
        $accountid = auth()->check() ? (auth()->user()->accountid ?? 'ACC0000001') : 'ACC0000001';
        $account = \App\Models\Account::find($accountid);

        return view('orders.edit', [
            'title' => 'Edit Order',
            'order' => $order,
            'clients' => Client::all(),
            'services' => Service::with('costings')->orderBy('name')->get(),
            'users' => $this->getSalesPeopleForForm($accountid),
            'items' => $order->items,
            'taxes' => ($account && $account->allow_multi_taxation) ? Tax::where('accountid', $accountid)->where('is_active', true)->orderByRaw('COALESCE(sequence, 999999), created_at DESC')->get() : collect(),
            'account' => $account,
            'clientId' => request('c'),
        ]);
    }

    public function ordersUpdate(Request $request, Order $order)
    {
        $validated = $request->validate([
            'clientid' => 'required|exists:clients,clientid',
            'order_number' => 'required|string|unique:orders,order_number,' . $order->orderid . ',orderid',
            'order_title' => 'required|string|max:255',
            'order_date' => 'required|date',
            'delivery_date' => 'nullable|date|after_or_equal:order_date',
            'notes' => 'nullable|string',
            'sales_person_id' => 'nullable|string|max:50',
            'items_data' => 'required|json',
            'po_number' => 'nullable|string|max:50',
            'po_date' => 'nullable|date',
            'po_file' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
            'agreement_ref' => 'nullable|string|max:50',
            'agreement_date' => 'nullable|date',
            'agreement_file' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
        ]);

        $itemsData = json_decode($request->items_data, true) ?: [];
        $subtotal = 0;
        $discountTotal = 0;
        $taxTotal = 0;
        foreach ($itemsData as $itemData) {
            $lineTotal = (float) ($itemData['line_total'] ?? 0);
            $discountAmount = (float) ($itemData['discount_amount'] ?? 0);
            $subtotal += $lineTotal;
            $discountTotal += $discountAmount;

            $service = Service::with('costings')->find($itemData['itemid'] ?? null);
            $taxRate = $this->resolveTaxRate($service, $itemData);
            $taxableAmount = $lineTotal - $discountAmount;
            $taxAmount = ($taxableAmount * $taxRate) / 100;
            $taxTotal += $taxAmount;
        }
        $grandTotal = $subtotal - $discountTotal + $taxTotal;

        // Handle PO file upload
        if ($request->hasFile('po_file')) {
            // Delete old file if exists
            if ($order->po_file && \Storage::disk('public')->exists($order->po_file)) {
                \Storage::disk('public')->delete($order->po_file);
            }
            $validated['po_file'] = $request->file('po_file')->store('orders/po', 'public');
        }

        // Handle Agreement file upload
        if ($request->hasFile('agreement_file')) {
            // Delete old file if exists
            if ($order->agreement_file && \Storage::disk('public')->exists($order->agreement_file)) {
                \Storage::disk('public')->delete($order->agreement_file);
            }
            $validated['agreement_file'] = $request->file('agreement_file')->store('orders/agreements', 'public');
        }

        $order->update([
            'clientid' => $validated['clientid'],
            'order_number' => $validated['order_number'],
            'order_title' => $validated['order_title'] ?? null,
            'order_date' => $validated['order_date'],
            'delivery_date' => $validated['delivery_date'] ?? null,
            'notes' => $validated['notes'],
            'sales_person_id' => $validated['sales_person_id'] ?? null,
            'subtotal' => $subtotal,
            'discount_total' => $discountTotal,
            'tax_total' => $taxTotal,
            'grand_total' => $grandTotal,
            'po_number' => $validated['po_number'] ?? null,
            'po_date' => $validated['po_date'] ?? null,
            'po_file' => $validated['po_file'] ?? $order->po_file,
            'agreement_ref' => $validated['agreement_ref'] ?? null,
            'agreement_date' => $validated['agreement_date'] ?? null,
            'agreement_file' => $validated['agreement_file'] ?? $order->agreement_file,
        ]);

        $order->items()->delete();

        foreach ($itemsData as $index => $itemData) {
            $service = Service::with('costings')->find($itemData['itemid'] ?? null);
            $taxRate = $this->resolveTaxRate($service, $itemData);
            
            // Log for debugging
            \Log::info('Order Item Save', [
                'item' => $itemData['itemid'],
                'delivery_date' => $itemData['delivery_date'] ?? 'NULL',
                'start_date' => $itemData['start_date'] ?? 'NULL',
                'end_date' => $itemData['end_date'] ?? 'NULL',
            ]);
            
            OrderItem::create([
                'orderid' => $order->orderid,
                'itemid' => $itemData['itemid'],
                'item_name' => $service?->name ?? 'Custom Item',
                'item_description' => null,
                'quantity' => $itemData['quantity'],
                'unit_price' => $itemData['unit_price'],
                'tax_rate' => $taxRate,
                'tax_included' => $itemData['tax_included'] ?? 'no',
                'discount_percent' => $itemData['discount_percent'] ?? 0,
                'discount_amount' => $itemData['discount_amount'] ?? 0,
                'duration' => $itemData['duration'] ?? null,
                'frequency' => $itemData['frequency'] ?? null,
                'no_of_users' => $itemData['no_of_users'] ?? null,
                'start_date' => $itemData['start_date'] ?? null,
                'end_date' => $itemData['end_date'] ?? null,
                'delivery_date' => $itemData['delivery_date'] ?? null,
                'line_total' => $itemData['line_total'],
                'sort_order' => $index + 1,
            ]);
        }

        return redirect()->route('orders.index', ['c' => $order->clientid])->with('success', 'Order updated successfully.');
    }

    public function ordersDestroy(Order $order)
    {
        $order->delete();

        return redirect()->route('orders.index', ['c' => $order->clientid])->with('success', 'Order deleted successfully.');
    }

    private function resolveTaxRate(?Service $service, array $itemData): float
    {
        $accountid = auth()->check() ? (auth()->user()->accountid ?? 'ACC0000001') : 'ACC0000001';
        $account = \App\Models\Account::find($accountid);

        if ($account && !$account->allow_multi_taxation) {
            return (float) ($account->fixed_tax_rate ?? 0);
        }

        if (array_key_exists('tax_rate', $itemData) && $itemData['tax_rate'] !== null && $itemData['tax_rate'] !== '') {
            return (float) $itemData['tax_rate'];
        }

        if ($service && $service->relationLoaded('costings')) {
            $costingTaxRate = (float) ($service->costings->first()?->tax_rate ?? 0);
            if ($costingTaxRate > 0) {
                return $costingTaxRate;
            }
        }

        return (float) ($itemData['tax_rate'] ?? 0);
    }

    private function getSalesPeopleForForm(string $accountId): Collection
    {
        $salesPeople = $this->getSalesPeople();

        if ($salesPeople->isNotEmpty()) {
            return $salesPeople;
        }

        // Fallback so forms remain usable if external DB is unreachable/misconfigured.
        return User::where('accountid', $accountId)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(function ($user) {
                return (object) [
                    'id' => (string) $user->id,
                    'name' => (string) $user->name,
                ];
            });
    }

    private function getSalesPeopleLookup(Collection $ids): array
    {
        if ($ids->isEmpty()) {
            return [];
        }

        $external = $this->getSalesPeople($ids);
        if ($external->isNotEmpty()) {
            return $external->pluck('name', 'id')->toArray();
        }

        return User::whereIn('id', $ids->all())
            ->get(['id', 'name'])
            ->mapWithKeys(fn ($user) => [(string) $user->id => (string) $user->name])
            ->all();
    }

    private function getSalesPeople(?Collection $onlyIds = null): Collection
    {
        $connection = (string) config('database.sales_people.connection', 'admin_mysql');
        $table = (string) config('database.sales_people.table', 'adminlogin');
        $idColumn = (string) config('database.sales_people.id_column', 'id');
        $nameColumn = (string) config('database.sales_people.name_column', 'name');

        try {
            $query = DB::connection($connection)
                ->table($table)
                ->select([
                    DB::raw("`{$idColumn}` as id"),
                    DB::raw("`{$nameColumn}` as name"),
                ]);

            if ($onlyIds && $onlyIds->isNotEmpty()) {
                $query->whereIn($idColumn, $onlyIds->values()->all());
            }

            return $query
                ->orderBy($nameColumn)
                ->get()
                ->map(function ($row) {
                    return (object) [
                        'id' => (string) ($row->id ?? ''),
                        'name' => (string) ($row->name ?? ''),
                    ];
                })
                ->filter(fn ($row) => $row->id !== '' && $row->name !== '')
                ->values();
        } catch (Throwable) {
            return collect();
        }
    }

    /**
     * AJAX endpoint to save order and return order ID
     */
    public function saveOrderAjax(Request $request)
    {
        try {
            // Check if we're updating an existing order
            $existingOrderId = $request->input('orderid');
            $existingOrder = $existingOrderId ? Order::find($existingOrderId) : null;

            if ($existingOrder) {
                // Update existing order
                $validated = $request->validate([
                    'clientid' => 'required|string|exists:clients,clientid',
                    'order_number' => 'nullable|string|max:50|unique:orders,order_number,' . $existingOrder->orderid . ',orderid',
                    'order_title' => 'required|string|max:255',
                    'order_date' => 'required|date',
                    'delivery_date' => 'nullable|date',
                    'sales_person_id' => 'nullable|string|max:50',
                    'notes' => 'nullable|string',
                    'po_number' => 'nullable|string|max:50',
                    'po_date' => 'nullable|date',
                    'po_file' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
                    'agreement_ref' => 'nullable|string|max:50',
                    'agreement_date' => 'nullable|date',
                    'agreement_file' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
                ]);

                // Handle PO file upload
                if ($request->hasFile('po_file')) {
                    // Delete old file if exists
                    if ($existingOrder->po_file && \Storage::disk('public')->exists($existingOrder->po_file)) {
                        \Storage::disk('public')->delete($existingOrder->po_file);
                    }
                    $validated['po_file'] = $request->file('po_file')->store('orders/po', 'public');
                }

                // Handle Agreement file upload
                if ($request->hasFile('agreement_file')) {
                    // Delete old file if exists
                    if ($existingOrder->agreement_file && \Storage::disk('public')->exists($existingOrder->agreement_file)) {
                        \Storage::disk('public')->delete($existingOrder->agreement_file);
                    }
                    $validated['agreement_file'] = $request->file('agreement_file')->store('orders/agreements', 'public');
                }

                $existingOrder->update([
                    'clientid' => $validated['clientid'],
                    'order_number' => $validated['order_number'] ?? $existingOrder->order_number,
                    'order_title' => $validated['order_title'] ?? null,
                    'order_date' => $validated['order_date'],
                    'delivery_date' => $validated['delivery_date'] ?? null,
                    'sales_person_id' => $validated['sales_person_id'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                    'po_number' => $validated['po_number'] ?? null,
                    'po_date' => $validated['po_date'] ?? null,
                    'po_file' => $validated['po_file'] ?? $existingOrder->po_file,
                    'agreement_ref' => $validated['agreement_ref'] ?? null,
                    'agreement_date' => $validated['agreement_date'] ?? null,
                    'agreement_file' => $validated['agreement_file'] ?? $existingOrder->agreement_file,
                ]);

                return response()->json([
                    'success' => true,
                    'orderid' => $existingOrder->orderid,
                    'order_number' => $existingOrder->order_number,
                    'message' => 'Order updated successfully',
                ]);
            }

            // Create new order
            $validated = $request->validate([
                'clientid' => 'required|string|exists:clients,clientid',
                'order_number' => 'nullable|string|max:50',
                'order_title' => 'required|string|max:255',
                'order_date' => 'required|date',
                'delivery_date' => 'nullable|date',
                'sales_person_id' => 'nullable|string|max:50',
                'notes' => 'nullable|string',
                'accountid' => 'nullable|size:10',
                'po_number' => 'nullable|string|max:50',
                'po_date' => 'nullable|date',
                'po_file' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
                'agreement_ref' => 'nullable|string|max:50',
                'agreement_date' => 'nullable|date',
                'agreement_file' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
            ]);

            $userAccountId = auth()->check() ? (auth()->user()->accountid ?? 'ACC0000001') : 'ACC0000001';
            $validated['accountid'] = $validated['accountid'] ?? $userAccountId;
            $validated['status'] = 'unverified'; // Default status
            
            // Get default financial year
            $defaultFy = FinancialYear::where('accountid', $validated['accountid'])
                ->where('default', true)
                ->first();
            $validated['fy_id'] = $defaultFy?->fy_id;
            
            // Generate order number if not provided
            if (empty($validated['order_number'])) {
                $validated['order_number'] = $this->generateOrderNumber($validated['accountid'], $validated['fy_id']);
            }

            // Handle PO file upload
            if ($request->hasFile('po_file')) {
                $validated['po_file'] = $request->file('po_file')->store('orders/po', 'public');
            }

            // Handle Agreement file upload
            if ($request->hasFile('agreement_file')) {
                $validated['agreement_file'] = $request->file('agreement_file')->store('orders/agreements', 'public');
            }

            $order = Order::create($validated);

            return response()->json([
                'success' => true,
                'orderid' => $order->orderid,
                'order_number' => $order->order_number,
                'message' => 'Order saved successfully',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . implode(', ', $e->validator->errors()->all()),
                'errors' => $e->validator->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Order save error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to save order: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate order number using SerialConfiguration
     */
    protected function generateOrderNumber(string $accountid, ?string $fyId = null): string
    {
        // First, check for SerialConfiguration (from Financial Year tab settings)
        $serialConfig = \App\Models\SerialConfiguration::where('accountid', $accountid)
            ->where('document_type', 'order')
            ->first();

        if ($serialConfig) {
            // Use the SerialConfiguration from Financial Year tab
            $candidate = $serialConfig->generateNextSerialNumber();
            return $this->ensureUniqueOrderNumber($candidate !== '' ? $candidate : 'ORD-0001', $accountid);
        }

        // Fallback: simple auto-increment if no configuration exists
        $count = Order::where('accountid', $accountid)->count();
        $candidate = 'ORD-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
        return $this->ensureUniqueOrderNumber($candidate, $accountid);
    }

    /**
     * Ensure order number is unique
     */
    protected function ensureUniqueOrderNumber(string $candidate, string $accountid): string
    {
        $candidate = trim($candidate);

        if ($candidate === '') {
            $candidate = 'ORD-0001';
        }

        $number = $candidate;
        $sequence = 2;

        while (Order::where('accountid', $accountid)->where('order_number', $number)->exists()) {
            if (preg_match('/^(.*?)(\d+)$/', $candidate, $matches)) {
                $prefix = $matches[1];
                $digits = $matches[2];
                $number = $prefix . str_pad((string) ((int) $digits + $sequence - 1), strlen($digits), '0', STR_PAD_LEFT);
            } else {
                $number = $candidate . '-' . $sequence;
            }

            $sequence++;
        }

        return $number;
    }

    /**
     * AJAX endpoint to add item to order
     */
    public function addOrderItemAjax(Request $request, $orderId)
    {
        try {
            $order = Order::where('orderid', $orderId)->firstOrFail();

            $validated = $request->validate([
                'itemid' => 'required|string|exists:items,itemid',
                'quantity' => 'required|numeric|min:0',
                'unit_price' => 'required|numeric|min:0',
                'frequency' => 'nullable|string',
                'duration' => 'nullable|string',
                'no_of_users' => 'nullable|integer|min:1',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date',
                'delivery_date' => 'nullable|date',
                'line_total' => 'required|numeric|min:0',
                'discount_percent' => 'nullable|numeric|min:0|max:100',
                'discount_amount' => 'nullable|numeric|min:0',
                'tax_rate' => 'required|numeric|min:0',
            ]);

            $service = Service::find($validated['itemid']);
            $discountAmount = $validated['discount_amount'] ?? 0;
            $taxableAmount = $validated['line_total'] - $discountAmount;
            $taxAmount = ($taxableAmount * $validated['tax_rate']) / 100;

            $orderItem = OrderItem::create([
                'orderid' => $order->orderid,
                'itemid' => $validated['itemid'],
                'item_name' => $service?->name ?? 'Custom Item',
                'item_description' => null,
                'quantity' => $validated['quantity'],
                'unit_price' => $validated['unit_price'],
                'tax_rate' => $validated['tax_rate'],
                'discount_percent' => $validated['discount_percent'] ?? 0,
                'discount_amount' => $discountAmount,
                'duration' => $validated['duration'] ?? null,
                'frequency' => $validated['frequency'] ?? null,
                'no_of_users' => $validated['no_of_users'] ?? null,
                'start_date' => $validated['start_date'] ?? null,
                'end_date' => $validated['end_date'] ?? null,
                'delivery_date' => $validated['delivery_date'] ?? null,
                'line_total' => $validated['line_total'],
                'sort_order' => $order->items()->count() + 1,
            ]);

            // Update order totals
            $this->updateOrderTotals($order);

            return response()->json([
                'success' => true,
                'order_item_id' => $orderItem->orderitemid,
                'message' => 'Item added successfully',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . implode(', ', $e->validator->errors()->all()),
                'errors' => $e->validator->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Order item add error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'o' => $orderId,
                'request' => $request->all(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to add item: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * AJAX endpoint to delete order item
     */
    public function deleteOrderItemAjax(Request $request, $orderId, $orderItemId)
    {
        try {
            $order = Order::where('orderid', $orderId)->firstOrFail();
            $orderItem = OrderItem::where('orderid', $order->orderid)
                ->where('orderitemid', $orderItemId)
                ->firstOrFail();

            $orderItem->delete();

            // Update order totals
            $this->updateOrderTotals($order);

            return response()->json([
                'success' => true,
                'message' => 'Item removed successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * AJAX endpoint to update order item
     */
    public function updateOrderItemAjax(Request $request, $orderId, $orderItemId)
    {
        try {
            $order = Order::where('orderid', $orderId)->firstOrFail();
            $orderItem = OrderItem::where('orderid', $order->orderid)
                ->where('orderitemid', $orderItemId)
                ->firstOrFail();

            $validated = $request->validate([
                'itemid' => 'required|string|exists:items,itemid',
                'quantity' => 'required|numeric|min:0',
                'unit_price' => 'required|numeric|min:0',
                'frequency' => 'nullable|string',
                'duration' => 'nullable|string',
                'no_of_users' => 'nullable|integer|min:1',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date',
                'delivery_date' => 'nullable|date',
                'line_total' => 'required|numeric|min:0',
                'discount_percent' => 'nullable|numeric|min:0|max:100',
                'discount_amount' => 'nullable|numeric|min:0',
                'tax_rate' => 'required|numeric|min:0',
            ]);

            $service = Service::find($validated['itemid']);
            $discountAmount = $validated['discount_amount'] ?? 0;
            $taxableAmount = $validated['line_total'] - $discountAmount;
            $taxAmount = ($taxableAmount * $validated['tax_rate']) / 100;

            $orderItem->update([
                'itemid' => $validated['itemid'],
                'item_name' => $service?->name ?? 'Custom Item',
                'quantity' => $validated['quantity'],
                'unit_price' => $validated['unit_price'],
                'tax_rate' => $validated['tax_rate'],
                'discount_percent' => $validated['discount_percent'] ?? 0,
                'discount_amount' => $discountAmount,
                'duration' => $validated['duration'] ?? null,
                'frequency' => $validated['frequency'] ?? null,
                'no_of_users' => $validated['no_of_users'] ?? null,
                'start_date' => $validated['start_date'] ?? null,
                'end_date' => $validated['end_date'] ?? null,
                'delivery_date' => $validated['delivery_date'] ?? null,
                'line_total' => $validated['line_total'],
            ]);

            // Update order totals
            $this->updateOrderTotals($order);

            return response()->json([
                'success' => true,
                'message' => 'Item updated successfully',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . implode(', ', $e->validator->errors()->all()),
                'errors' => $e->validator->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Order item update error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'o' => $orderId,
                'order_item_id' => $orderItemId,
                'request' => $request->all(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update item: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update order totals (subtotal, tax_total, grand_total)
     */
    private function updateOrderTotals(Order $order): void
    {
        $items = $order->items;
        $subtotal = 0;
        $discountTotal = 0;
        $taxTotal = 0;

        foreach ($items as $item) {
            $lineTotal = (float) ($item->line_total ?? 0);
            $taxRate = (float) ($item->tax_rate ?? 0);
            $discountAmount = (float) ($item->discount_amount ?? 0);
            $subtotal += $lineTotal;
            $discountTotal += $discountAmount;
            $taxableAmount = $lineTotal - $discountAmount;
            $taxTotal += ($taxableAmount * $taxRate) / 100;
        }

        $order->update([
            'subtotal' => round($subtotal, 2),
            'discount_total' => round($discountTotal, 2),
            'tax_total' => round($taxTotal, 2),
            'grand_total' => round($subtotal - $discountTotal + $taxTotal, 2),
        ]);
    }
}
