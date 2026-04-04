<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Service;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

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

        $orders = $query->latest()->take(50)->get()->map(function ($order) {
            $businessName = $order->client->business_name ?? null;
            $contactName = $order->client->contact_name ?? null;

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
                'sales_person' => $order->salesPerson->name ?? '—',
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
        return view('orders.create', [
            'title' => 'Create Order',
            'clients' => Client::all(),
            'services' => Service::with('costings')->orderBy('name')->get(),
            'users' => User::where('accountid', auth()->user()->accountid ?? 'ACC0000001')->get(),
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
            'sales_person_id' => 'nullable|exists:users,id',
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
            $taxTotal += ($lineTotal * $taxRate) / 100;
        }
        $grandTotal = $subtotal + $taxTotal;
        $validated['subtotal'] = $subtotal;
        $validated['grand_total'] = $grandTotal;

        $order = Order::create($validated);

        foreach ($itemsData as $index => $itemData) {
            $service = Service::with('costings')->find($itemData['itemid'] ?? null);
            $taxRate = $this->resolveTaxRate($service, $itemData);
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
                'line_total' => $itemData['line_total'],
                'sort_order' => $index + 1,
            ]);
        }

        return redirect()->route('orders.index')->with('success', 'Order created successfully with items.');
    }

    public function ordersShow(Order $order): View
    {
        $order->load(['client', 'items.item']);
        return view('orders.show', ['title' => 'Order Details', 'order' => $order]);
    }

    public function ordersEdit(Order $order): View
    {
        $order->load(['items.item']);
        return view('orders.edit', [
            'title' => 'Edit Order',
            'order' => $order,
            'clients' => Client::all(),
            'services' => Service::with('costings')->orderBy('name')->get(),
            'users' => User::where('accountid', auth()->user()->accountid ?? 'ACC0000001')->get(),
            'items' => $order->items,
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
            'sales_person_id' => 'nullable|exists:users,id',
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
            $taxTotal += ($lineTotal * $taxRate) / 100;
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
            'grand_total' => $grandTotal,
        ]);

        $order->items()->delete();

        foreach ($itemsData as $index => $itemData) {
            $service = Service::with('costings')->find($itemData['itemid'] ?? null);
            $taxRate = $this->resolveTaxRate($service, $itemData);
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
        if ($service && $service->relationLoaded('costings')) {
            $costingTaxRate = (float) ($service->costings->first()?->tax_rate ?? 0);
            if ($costingTaxRate > 0) {
                return $costingTaxRate;
            }
        }

        return (float) ($itemData['tax_rate'] ?? 0);
    }
}
