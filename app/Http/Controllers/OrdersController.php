<?php

namespace App\Http\Controllers;

use App\Models\Client;
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
    public function orders(): View
    {
        $query = Order::with('client');
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
                'order_date' => $order->order_date?->format('d M Y') ?? 'N/A',
                'delivery_date' => $order->delivery_date?->format('d M Y') ?? 'N/A',
                'amount' => number_format($order->grand_total ?? 0),
                'item_count' => $order->items()->count(),
                'sales_person' => $salesPersonLookup[$salesPersonId] ?? ($order->salesPerson->name ?? '-'),
                'status' => ucfirst($order->status ?? 'Draft'),
            ];
        });

        $groupedOrders = $orders->groupBy('client')->sortBy(fn($g, $k) => strtolower($k));

        return view('orders.index', [
            'title' => 'Orders',
            'orders' => $orders,
            'groupedOrders' => $groupedOrders,
            'searchTerm' => $searchTerm,
            'resultCount' => $resultCount,
        ]);
    }

    public function ordersCreate(): View
    {
        $accountid = auth()->check() ? (auth()->user()->accountid ?? 'ACC0000001') : 'ACC0000001';
        $account = \App\Models\Account::find($accountid);

        return view('orders.create', [
            'title' => 'Create Order',
            'clients' => Client::all(),
            'services' => Service::with('costings')->orderBy('name')->get(),
            'users' => $this->getSalesPeopleForForm($accountid),
            'taxes' => ($account && $account->allow_multi_taxation) ? Tax::where('accountid', $accountid)->where('is_active', true)->orderByRaw('COALESCE(sequence, 999999), created_at DESC')->get() : collect(),
            'account' => $account,
            'fixedTaxRate' => ($account && !$account->allow_multi_taxation) ? ($account->fixed_tax_rate ?? 0) : 0,
        ]);
    }

    public function ordersStore(Request $request)
    {
        $validated = $request->validate([
            'clientid' => 'required|exists:clients,clientid',
            'order_number' => 'required|string|unique:orders,order_number',
            'order_title' => 'nullable|string|max:255',
            'order_date' => 'required|date',
            'delivery_date' => 'nullable|date|after_or_equal:order_date',
            'notes' => 'nullable|string',
            'status' => 'required|in:draft,confirmed,processing,shipped,delivered,cancelled',
            'sales_person_id' => 'nullable|string|max:50',
            'subtotal' => 'nullable|numeric|min:0',
            'grand_total' => 'nullable|numeric|min:0',
            'items_data' => 'required|json',
            'accountid' => 'nullable|size:10',
        ]);

        $userAccountId = auth()->check() ? (auth()->user()->accountid ?? 'ACC0000001') : 'ACC0000001';
        $validated['accountid'] = $validated['accountid'] ?? $userAccountId;
        unset($validated['items_data']);

        $itemsData = json_decode($request->items_data, true) ?: [];
        $subtotal = 0;
        $taxTotal = 0;
        foreach ($itemsData as $itemData) {
            $lineTotal = (float) ($itemData['line_total'] ?? 0);
            $subtotal += $lineTotal;

            $service = Service::with('costings')->find($itemData['itemid'] ?? null);
            $taxRate = $this->resolveTaxRate($service, $itemData);
            $taxAmount = ($lineTotal * $taxRate) / 100;
            $taxTotal += $taxAmount;
        }
        $grandTotal = $subtotal + $taxTotal;
        $validated['subtotal'] = $subtotal;
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

        return redirect()->route('orders.index')->with('success', 'Order created successfully with items.');
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
    public function getOrderJson(Request $request, Order $order)
    {
        $order->load(['items.service']);
        
        return response()->json([
            'orderid' => $order->orderid,
            'order_number' => $order->order_number,
            'clientid' => $order->clientid,
            'grand_total' => $order->grand_total,
            'items' => $order->items->map(function ($item) {
                return [
                    'orderid' => $item->orderid,
                    'serviceid' => $item->itemid,
                    'itemid' => $item->itemid,
                    'quantity' => $item->quantity ?? 1,
                    'unit_price' => $item->unit_price ?? 0,
                    'tax_rate' => $item->tax_rate ?? 0,
                    'line_total' => $item->line_total ?? 0,
                    'item_name' => $item->item_name ?? '',
                    'service' => $item->service ? [
                        'itemid' => $item->service->itemid,
                        'name' => $item->service->name,
                    ] : null,
                ];
            }),
        ]);
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
        ]);
    }

    public function ordersUpdate(Request $request, Order $order)
    {
        $validated = $request->validate([
            'clientid' => 'required|exists:clients,clientid',
            'order_number' => 'required|string|unique:orders,order_number,' . $order->orderid . ',orderid',
            'order_title' => 'nullable|string|max:255',
            'order_date' => 'required|date',
            'delivery_date' => 'nullable|date|after_or_equal:order_date',
            'notes' => 'nullable|string',
            'status' => 'required|in:draft,confirmed,processing,shipped,delivered,cancelled',
            'sales_person_id' => 'nullable|string|max:50',
            'items_data' => 'required|json',
        ]);

        $itemsData = json_decode($request->items_data, true) ?: [];
        $subtotal = 0;
        $taxTotal = 0;
        foreach ($itemsData as $itemData) {
            $lineTotal = (float) ($itemData['line_total'] ?? 0);
            $subtotal += $lineTotal;

            $service = Service::with('costings')->find($itemData['itemid'] ?? null);
            $taxRate = $this->resolveTaxRate($service, $itemData);
            $taxAmount = ($lineTotal * $taxRate) / 100;
            $taxTotal += $taxAmount;
        }
        $grandTotal = $subtotal + $taxTotal;

        $order->update([
            'clientid' => $validated['clientid'],
            'order_number' => $validated['order_number'],
            'order_title' => $validated['order_title'] ?? null,
            'order_date' => $validated['order_date'],
            'delivery_date' => $validated['delivery_date'] ?? null,
            'notes' => $validated['notes'],
            'status' => $validated['status'],
            'sales_person_id' => $validated['sales_person_id'] ?? null,
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'grand_total' => $grandTotal,
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

        return redirect()->route('orders.index')->with('success', 'Order updated successfully.');
    }

    public function ordersDestroy(Order $order)
    {
        $order->delete();

        return redirect()->route('orders.index')->with('success', 'Order deleted successfully.');
    }

    private function resolveTaxRate(?Service $service, array $itemData): float
    {
        $accountid = auth()->check() ? (auth()->user()->accountid ?? 'ACC0000001') : 'ACC0000001';
        $account = \App\Models\Account::find($accountid);

        // If multi-taxation is disabled, use the fixed tax rate
        if ($account && !$account->allow_multi_taxation) {
            return (float) ($account->fixed_tax_rate ?? 0);
        }

        // Otherwise, try to get tax from service costing
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
}
