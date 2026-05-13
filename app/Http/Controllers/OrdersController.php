<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Client;
use App\Models\ClientDocument;
use App\Models\Order;
use App\Models\Service;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class OrdersController extends Controller
{
    public function ordersFile(Order $order, string $type)
    {
        $userAccountId = $this->resolveAccountId();
        if ((string) $order->accountid !== $userAccountId) {
            abort(403);
        }

        if (!in_array($type, ['po', 'agreement'], true)) {
            abort(404);
        }

        $document = ClientDocument::query()
            ->where('accountid', $userAccountId)
            ->where('clientid', $order->clientid)
            ->where('type', $type)
            ->where('status', 'active')
            ->latest('document_date')
            ->latest('created_at')
            ->firstOrFail();
        $path = $document->file_path;
        if (!$path || !Storage::disk('public')->exists($path)) {
            abort(404);
        }

        return Storage::disk('public')->response($path);
    }

    public function selectClient(): View
    {
        $accountId = $this->resolveAccountId();

        return view('orders.select-client', [
            'title' => 'Manage Orders',
            'subtitle' => 'Choose a client to view their orders.',
            'clients' => Client::where('accountid', $accountId)->orderBy('business_name')->orderBy('contact_name')->get(),
        ]);
    }

    public function orders(): View
    {
        $accountId = $this->resolveAccountId();
        $clientId = trim((string) request('c', ''));
        $selectedItemId = trim((string) request('itemid', ''));
        $selectedClient = null;

        $query = Order::query()
            ->where('accountid', $accountId)
            ->with(['client', 'item', 'invoices']);

        if ($clientId !== '') {
            $query->where('clientid', $clientId);
            $selectedClient = Client::query()
                ->where('accountid', $accountId)
                ->find($clientId);
        }

        if ($selectedItemId !== '') {
            $query->where('itemid', $selectedItemId);
        }

        $records = $query->orderByDesc('created_at')->take(100)->get();
        $orders = $records->map(function (Order $order) {
            $linkedInvoice = $order->invoices->sortByDesc('created_at')->first();

            return [
                'record_id' => $order->orderid,
                'number' => $order->order_number,
                'client' => $order->client?->business_name ?? $order->client?->contact_name ?? 'Client',
                'clientid' => $order->clientid,
                'currency' => $order->client?->currency ?? 'INR',
                'order_date' => $order->created_at?->format('d M Y') ?? 'N/A',
                'delivery_date' => $order->delivery_date?->format('d M Y') ?? 'N/A',
                'amount' => null,
                'status' => (string) ($order->status ?? ''),
                'verified' => ($order->status ?? '') !== 'cancelled',
                'item_count' => 1,
                'items' => [[
                    'item_name' => $order->item_name ?: ($order->item?->name ?? 'Item'),
                    'item_description' => $order->item_description,
                    'quantity' => (float) ($order->quantity ?? 1),
                    'no_of_users' => $order->no_of_users,
                    'start_date' => $order->start_date?->format('Y-m-d'),
                    'end_date' => $order->end_date?->format('Y-m-d'),
                    'delivery_date' => $order->delivery_date?->format('Y-m-d'),
                ]],
                'has_pi' => $order->invoices->isNotEmpty(),
                'linked_invoice_id' => $linkedInvoice?->invoiceid,
                'linked_invoice_for' => $linkedInvoice?->invoice_for,
                'linked_invoice_has_ti' => !empty($linkedInvoice?->ti_number),
            ];
        });

        $groupedOrders = $clientId !== ''
            ? [($selectedClient?->business_name ?? $selectedClient?->contact_name ?? 'Client') => $orders]
            : $orders->groupBy('client')->sortKeys();

        $services = Service::query()
            ->where('accountid', $accountId)
            ->with('category')
            ->orderBy('name')
            ->get(['itemid', 'name', 'ps_catid']);

        return view('orders.index', [
            'title' => $clientId ? 'All Orders' : 'Manage Orders',
            'subtitle' => $clientId !== '' ? 'Showing orders for selected client.' : 'Showing orders across all clients.',
            'orders' => $orders,
            'groupedOrders' => $groupedOrders,
            'selectedClient' => $selectedClient,
            'clientId' => $clientId,
            'selectedItemId' => $selectedItemId,
            'services' => $services,
            'allClients' => Client::where('accountid', $accountId)->with('billingDetail')->orderBy('business_name')->orderBy('contact_name')->get(),
        ]);
    }

    public function ordersCreate(): View
    {
        $accountId = $this->resolveAccountId();
        $account = Account::where('accountid', $accountId)->first();
        $preSelectedClientId = request('c');
        $carryRecent = request()->boolean('carry');
        $documents = collect();
        $existingClientItemIds = [];
        $recentOrders = collect();

        if (!$carryRecent) {
            session()->forget('orders_create_recent_ids');
        }

        $recentOrderIds = collect(session('orders_create_recent_ids', []))
            ->map(fn ($id) => (string) $id)
            ->filter()
            ->unique()
            ->values();

        if ($preSelectedClientId) {
            $documents = ClientDocument::query()
                ->where('accountid', $accountId)
                ->where('clientid', $preSelectedClientId)
                ->where('type', 'po')
                ->where('status', 'active')
                ->orderByDesc('document_date')
                ->orderByDesc('created_at')
                ->get();

            $existingClientItemIds = Order::query()
                ->where('accountid', $accountId)
                ->where('clientid', $preSelectedClientId)
                ->whereIn('status', ['active', 'running'])
                ->whereNotNull('itemid')
                ->pluck('itemid')
                ->map(fn ($itemId) => (string) $itemId)
                ->unique()
                ->values()
                ->all();

            if ($recentOrderIds->isNotEmpty()) {
                $recentOrders = Order::query()
                    ->where('accountid', $accountId)
                    ->where('clientid', $preSelectedClientId)
                    ->whereIn('orderid', $recentOrderIds->all())
                    ->where('status', '!=', 'cancelled')
                    ->latest('created_at')
                    ->get();
            }
        }

        return view('orders.create', [
            'title' => 'Create Orders',
            'subtitle' => 'Each added item will be saved as its own order.',
            'clients' => Client::where('accountid', $accountId)->orderBy('business_name')->orderBy('contact_name')->get(),
            'services' => Service::where('accountid', $accountId)->with('costings', 'category')->orderBy('name')->get(),
            'preSelectedClientId' => $preSelectedClientId,
            'clientDocuments' => $documents->values(),
            'existingClientItemIds' => $existingClientItemIds,
            'recentOrders' => $recentOrders,
            'isEditMode' => false,
            'order' => null,
            'account' => $account,
        ]);
    }

    public function ordersStore(Request $request)
    {
        $validated = $request->validate([
            'clientid' => 'required|exists:clients,clientid',
            'client_docid' => [
                'nullable',
                Rule::exists('client_documents', 'client_docid')->where(fn ($query) => $query
                    ->where('accountid', $this->resolveAccountId())
                    ->where('type', 'po')
                    ->where('status', 'active')),
            ],
            'items_data' => 'required|json',
        ]);

        $itemsData = json_decode((string) $validated['items_data'], true);
        if (!is_array($itemsData) || $itemsData === []) {
            return back()->withErrors(['items_data' => 'Add at least one item.'])->withInput();
        }

        $accountId = $this->resolveAccountId();
        $createdOrders = [];

        foreach ($itemsData as $itemData) {
            $service = Service::where('accountid', $accountId)->with('costings')->find($itemData['itemid'] ?? null);
            $createdOrders[] = Order::create([
                'accountid' => $accountId,
                'clientid' => $validated['clientid'],
                'order_number' => $this->generateOrderNumber($accountId),
                'status' => 'active',
                'client_docid' => $validated['client_docid'] ?? null,
                'itemid' => $itemData['itemid'] ?? null,
                'item_name' => $service?->name ?? 'Custom Item',
                'item_description' => $itemData['item_description'] ?? null,
                'quantity' => $this->wholeQuantity($itemData['quantity'] ?? 1),
                'no_of_users' => $itemData['no_of_users'] ?? null,
                'start_date' => now()->toDateString(),
                'end_date' => $itemData['end_date'] ?? '2099-12-31',
                'delivery_date' => !empty($itemData['delivery_date']) ? $itemData['delivery_date'] : null,
            ]);
        }

        $redirectClientId = $createdOrders[0]->clientid ?? $validated['clientid'];
        $createdOrderIds = collect($createdOrders)
            ->pluck('orderid')
            ->map(fn ($id) => (string) $id)
            ->filter()
            ->values();

        $recentOrderIds = collect(session('orders_create_recent_ids', []))
            ->map(fn ($id) => (string) $id)
            ->merge($createdOrderIds)
            ->filter()
            ->unique()
            ->take(-50)
            ->values()
            ->all();

        session(['orders_create_recent_ids' => $recentOrderIds]);

        return redirect()
            ->route('orders.create', ['c' => $redirectClientId, 'carry' => 1])
            ->with('success', count($createdOrders) . ' order item(s) created successfully.');
    }

    public function getOrderJson(Request $request, $order)
    {
        $orderModel = Order::query()
            ->where('orderid', $order)
            ->where('accountid', $this->resolveAccountId())
            ->first();

        if (!$orderModel) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        return response()->json([
            'orderid' => $orderModel->orderid,
            'order' => [
                'orderid' => $orderModel->orderid,
                'order_number' => $orderModel->order_number,
                'clientid' => $orderModel->clientid,
                'order_date' => $orderModel->created_at?->format('Y-m-d'),
                'grand_total' => 0,
            ],
            'items' => [[
                'orderid' => $orderModel->orderid,
                'itemid' => $orderModel->itemid,
                'quantity' => $orderModel->quantity ?? 1,
                'item_name' => $orderModel->item_name ?? '',
                'item_description' => $orderModel->item_description ?? '',
                'no_of_users' => $orderModel->no_of_users,
                'start_date' => $orderModel->start_date?->format('Y-m-d'),
                'end_date' => $orderModel->end_date?->format('Y-m-d'),
                'delivery_date' => $orderModel->delivery_date?->format('Y-m-d'),
            ]],
        ]);
    }

    public function getOrderJsonByNumber(Request $request)
    {
        $lookup = $request->input('o');
        if (!$lookup) {
            return response()->json(['error' => 'Order ID required'], 400);
        }

        $order = Order::query()
            ->where('orderid', $lookup)
            ->orWhere('order_number', $lookup)
            ->first();

        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        return response()->json([
            'orderid' => $order->orderid,
            'order_number' => $order->order_number,
            'clientid' => $order->clientid,
            'grand_total' => '0',
            'items' => [[
                'orderid' => $order->orderid,
                'itemid' => $order->itemid,
                'quantity' => $order->quantity ?? 1,
                'item_name' => $order->item_name ?? 'Item',
                'item_description' => $order->item_description ?? '',
            ]],
        ]);
    }

    public function ordersEdit(Order $order): View
    {
        if ((string) $order->accountid !== $this->resolveAccountId()) {
            abort(404);
        }

        $accountId = $this->resolveAccountId();
        $account = Account::where('accountid', $accountId)->first();
        $documents = ClientDocument::query()
            ->where('accountid', $accountId)
            ->where('clientid', $order->clientid)
            ->where('type', 'po')
            ->where('status', 'active')
            ->orderByDesc('document_date')
            ->orderByDesc('created_at')
            ->get();

        return view('orders.create', [
            'title' => 'Edit Order',
            'subtitle' => 'Edit this item-based order.',
            'order' => $order,
            'clients' => Client::where('accountid', $accountId)->orderBy('business_name')->orderBy('contact_name')->get(),
            'services' => Service::where('accountid', $accountId)->with('costings', 'category')->orderBy('name')->get(),
            'preSelectedClientId' => $order->clientid,
            'clientDocuments' => $documents->values(),
            'isEditMode' => true,
            'account' => $account,
        ]);
    }

    public function ordersUpdate(Request $request, Order $order)
    {
        if ((string) $order->accountid !== $this->resolveAccountId()) {
            abort(404);
        }

        $validated = $request->validate([
            'clientid' => 'required|exists:clients,clientid',
            'client_docid' => [
                'nullable',
                Rule::exists('client_documents', 'client_docid')->where(fn ($query) => $query
                    ->where('accountid', $this->resolveAccountId())
                    ->where('type', 'po')
                    ->where('status', 'active')),
            ],
            'items_data' => 'required|json',
        ]);

        $itemsData = json_decode((string) $validated['items_data'], true);
        $itemData = is_array($itemsData) ? ($itemsData[0] ?? null) : null;
        if (!is_array($itemData)) {
            return back()->withErrors(['items_data' => 'One item is required for this order.'])->withInput();
        }

        $accountId = $this->resolveAccountId();
        $service = Service::where('accountid', $accountId)->with('costings')->find($itemData['itemid'] ?? null);

        $order->update([
            'clientid' => $validated['clientid'],
            'client_docid' => $validated['client_docid'] ?? null,
            'itemid' => $itemData['itemid'] ?? null,
            'item_name' => $service?->name ?? 'Custom Item',
            'item_description' => $itemData['item_description'] ?? null,
            'quantity' => $this->wholeQuantity($itemData['quantity'] ?? 1),
            'no_of_users' => $itemData['no_of_users'] ?? null,
            'start_date' => now()->toDateString(),
            'end_date' => $itemData['end_date'] ?? '2099-12-31',
            'delivery_date' => !empty($itemData['delivery_date']) ? $itemData['delivery_date'] : null,
        ]);

        if ($request->query('return_to') === 'create') {
            return redirect()->route('orders.create', ['c' => $order->clientid, 'carry' => 1])->with('success', 'Order updated successfully.');
        }

        return redirect()->route('orders.index', ['c' => $order->clientid])->with('success', 'Order updated successfully.');
    }

    public function ordersDestroy(Order $order)
    {
        if ((string) $order->accountid !== $this->resolveAccountId()) {
            abort(404);
        }

        $order->update([
            'status' => 'cancelled',
        ]);

        if (request()->query('return_to') === 'create') {
            $recentOrderIds = collect(session('orders_create_recent_ids', []))
                ->map(fn ($id) => (string) $id)
                ->reject(fn ($id) => $id === (string) $order->orderid)
                ->values()
                ->all();
            session(['orders_create_recent_ids' => $recentOrderIds]);

            return redirect()->route('orders.create', ['c' => $order->clientid, 'carry' => 1])->with('success', 'Order cancelled successfully.');
        }

        return redirect()->route('orders.index', ['c' => $order->clientid])->with('success', 'Order cancelled successfully.');
    }

    public function ordersRestore(Order $order)
    {
        if ((string) $order->accountid !== $this->resolveAccountId()) {
            abort(404);
        }

        $order->update([
            'status' => 'active',
        ]);

        return redirect()->route('orders.index', ['c' => $order->clientid])->with('success', 'Order restored successfully.');
    }

    protected function generateOrderNumber(string $accountId): string
    {
        $serialConfig = \App\Models\SerialConfiguration::where('accountid', $accountId)
            ->where('document_type', 'order')
            ->first();

        if ($serialConfig) {
            $candidate = $serialConfig->generateNextSerialNumber();
            return $this->ensureUniqueOrderNumber($candidate !== '' ? $candidate : 'ORD-0001', $accountId);
        }

        $count = Order::where('accountid', $accountId)->count();

        return $this->ensureUniqueOrderNumber('ORD-' . str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT), $accountId);
    }

    protected function ensureUniqueOrderNumber(string $candidate, string $accountId): string
    {
        $candidate = trim($candidate) ?: 'ORD-0001';
        $number = $candidate;
        $sequence = 2;

        while (Order::where('accountid', $accountId)->where('order_number', $number)->exists()) {
            if (preg_match('/^(.*?)(\d+)$/', $candidate, $matches)) {
                $number = $matches[1] . str_pad((string) ((int) $matches[2] + $sequence - 1), strlen($matches[2]), '0', STR_PAD_LEFT);
            } else {
                $number = $candidate . '-' . $sequence;
            }
            $sequence++;
        }

        return $number;
    }

    private function wholeQuantity(mixed $value): int
    {
        return max(1, (int) round((float) $value, 0));
    }
}
