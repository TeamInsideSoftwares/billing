<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\InvalidatesOrderCache;
use App\Models\Account;
use App\Models\Client;
use App\Models\ClientDocument;
use App\Models\InvoiceItem;
use App\Models\Order;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Service;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class OrdersController extends Controller
{
    use InvalidatesOrderCache;

    public function ordersFile(Order $order, string $type)
    {
        $userAccountId = $this->resolveAccountId();
        if ((string) $order->accountid !== $userAccountId) {
            abort(403);
        }

        if (! in_array($type, ['po', 'agreement'], true)) {
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
        if (! $path || ! Storage::disk('public')->exists($path)) {
            abort(404);
        }

        return Storage::disk('public')->response($path);
    }

    public function orders(): View
    {
        $accountId = $this->resolveAccountId();
        $clientFilter = trim((string) request('c', ''));
        $isAllClientsFilter = strtolower($clientFilter) === 'all';
        $hasClientFilter = request()->has('c') && $clientFilter !== '';
        $clientId = $isAllClientsFilter ? '' : $clientFilter;
        $selectedItemId = trim((string) request('itemid', ''));
        $selectedClient = null;

        $query = Order::query()
            ->where('accountid', $accountId)
            ->regular()
            ->with(['client', 'item', 'invoices', 'invoiceItems']);

        if ($clientId !== '') {
            $query->where('clientid', $clientId);
            $selectedClient = Client::query()
                ->where('accountid', $accountId)
                ->find($clientId);
        }

        if ($selectedItemId !== '') {
            $query->where('itemid', $selectedItemId);
        }

        $records = $query->orderByDesc('start_date')->orderByDesc('created_at')->take(100)->get();
        $orders = $records->map(function (Order $order) {
            $linkedInvoice = $order->invoices->sortByDesc('created_at')->first();
            $latestInvoiceItem = $order->invoiceItems->sortByDesc('created_at')->sortByDesc('invoice_itemid')->first();

            return [
                'record_id' => $order->orderid,
                'number' => $order->order_number,
                'client' => $order->client?->business_name ?? $order->client?->contact_name ?? 'Client',
                'clientid' => $order->clientid,
                'client_type' => strtolower((string) ($order->client?->type ?? 'regular')),
                'currency' => $order->client?->currency ?? 'INR',
                'order_date' => $order->created_at?->format('d M Y') ?? 'N/A',
                'delivery_date' => $order->delivery_date?->format('d M Y') ?? 'N/A',
                'amount' => null,
                'status' => (string) ($order->status ?? ''),
                'verified' => ($order->status ?? '') !== 'cancelled',
                'item_count' => 1,
                'itemid' => $order->itemid,
                'client_docid' => $order->client_docid,
                'items' => [[
                    'item_name' => $order->item_name ?: ($order->item?->name ?? 'Item'),
                    'item_description' => $order->item_description,
                    'quantity' => (float) ($order->quantity ?? 1),
                    'no_of_users' => $order->no_of_users,
                    'start_date' => $order->start_date?->format('Y-m-d'),
                    'end_date' => $order->end_date?->format('Y-m-d'),
                    'delivery_date' => $order->delivery_date?->format('Y-m-d'),
                    'frequency' => $latestInvoiceItem?->frequency ?? '',
                    'duration' => $latestInvoiceItem?->duration ?? 1,
                    'grace_period' => $order->grace_period ?? 0,
                ]],
                'has_pi' => $order->invoices->isNotEmpty(),
                'linked_invoice_id' => $linkedInvoice?->invoiceid,
                'linked_invoice_has_ti' => ! empty($linkedInvoice?->ti_number),
            ];
        });

        $groupedOrders = $orders->isEmpty()
            ? collect()
            : ($clientId !== ''
                ? [($selectedClient?->business_name ?? $selectedClient?->contact_name ?? 'Client') => $orders]
                : $orders->groupBy('client')->sortKeys());

        // Fetch trial orders for a specific regular client so they are visible and manageable
        $trialOrders = collect();
        if ($clientId !== '' && $selectedClient && strtolower((string) ($selectedClient->type ?? '')) !== 'trial') {
            $trialRecords = Order::query()
                ->where('accountid', $accountId)
                ->where('clientid', $clientId)
                ->trial()
                ->with(['item', 'invoices', 'invoiceItems'])
                ->orderByDesc('start_date')
                ->orderByDesc('created_at')
                ->get();

            $trialOrders = $trialRecords->map(function (Order $order) use ($selectedClient) {
                $latestInvoiceItem = $order->invoiceItems->sortByDesc('created_at')->sortByDesc('invoice_itemid')->first();

                return [
                    'record_id' => $order->orderid,
                    'number' => $order->order_number,
                    'client' => $selectedClient?->business_name ?? $selectedClient?->contact_name ?? 'Client',
                    'clientid' => $order->clientid,
                    'status' => (string) ($order->status ?? ''),
                    'itemid' => $order->itemid,
                    'client_docid' => $order->client_docid,
                    'items' => [[
                        'item_name' => $order->item_name ?: ($order->item?->name ?? 'Item'),
                        'item_description' => $order->item_description,
                        'quantity' => (float) ($order->quantity ?? 1),
                        'no_of_users' => $order->no_of_users,
                        'start_date' => $order->start_date?->format('Y-m-d'),
                        'end_date' => $order->end_date?->format('Y-m-d'),
                        'delivery_date' => $order->delivery_date?->format('Y-m-d'),
                        'frequency' => $latestInvoiceItem?->frequency ?? '',
                        'duration' => $latestInvoiceItem?->duration ?? 1,
                        'grace_period' => $order->grace_period ?? 0,
                    ]],
                ];
            });
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

        return view('orders.index', [
            'title' => $clientId ? 'All Orders' : 'Manage Orders',
            'orders' => $orders,
            'groupedOrders' => $groupedOrders,
            'trialOrders' => $trialOrders,
            'selectedClient' => $selectedClient,
            'clientId' => $clientId,
            'hasClientFilter' => $hasClientFilter,
            'showClientPicker' => ! $hasClientFilter && $selectedItemId === '',
            'selectedItemId' => $selectedItemId,
            'services' => $services,
            'clientDocuments' => $clientDocuments,
            'allClients' => Client::where('accountid', $accountId)->regular()->with(['billingDetail', 'primaryContact'])->orderBy('business_name')->get(),
        ]);
    }

    public function ordersCreate(): View
    {
        $accountId = $this->resolveAccountId();
        $account = Account::where('accountid', $accountId)->first();
        $preSelectedClientId = (string) request('c', session()->getOldInput('clientid', ''));
        $carryRecent = request()->boolean('carry');
        $documents = collect();
        $clientQuotations = collect();
        $existingClientItemIds = [];
        $existingClientItemMap = [];
        $recentOrders = collect();

        if (! $carryRecent) {
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

            $existingClientItemMap = Order::query()
                ->where('accountid', $accountId)
                ->where('clientid', $preSelectedClientId)
                ->whereNotNull('itemid')
                ->get()
                ->mapWithKeys(function ($order) {
                    return [(string) $order->itemid => (string) $order->orderid];
                })
                ->all();

            $existingClientItemIds = array_keys($existingClientItemMap);

            $clientQuotations = Quotation::query()
                ->where('accountid', $accountId)
                ->where('clientid', $preSelectedClientId)
                ->with(['items.item'])
                ->orderByDesc('issue_date')
                ->orderByDesc('created_at')
                ->get()
                ->map(function (Quotation $quotation): array {
                    return $this->buildQuotationOrderPayload($quotation);
                })
                ->values();

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
            'clients' => Client::where('accountid', $accountId)->regular()->active()->with('primaryContact')->orderBy('business_name')->get(),
            'services' => Service::where('accountid', $accountId)->with('costings', 'category')->orderBy('name')->get(),
            'preSelectedClientId' => $preSelectedClientId,
            'clientDocuments' => $documents->values(),
            'clientQuotations' => $clientQuotations,
            'existingClientItemIds' => $existingClientItemIds,
            'existingClientItemMap' => $existingClientItemMap,
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
        if (! is_array($itemsData) || $itemsData === []) {
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
                'start_date' => ! empty($itemData['start_date']) ? $itemData['start_date'] : now()->toDateString(),
                'end_date' => $itemData['end_date'] ?? '2099-12-31',
                'delivery_date' => ! empty($itemData['delivery_date']) ? $itemData['delivery_date'] : null,
                'grace_period' => $itemData['grace_period'] ?? 0,
            ]);
        }

        $this->invalidateOrderCache();

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

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => count($createdOrders).' order item(s) created successfully.',
                'orders' => $createdOrders,
            ]);
        }

        return redirect()
            ->route('orders.index', ['c' => $redirectClientId])
            ->with('success', count($createdOrders).' order item(s) created successfully.');
    }

    public function getOrderJson(Request $request, $order)
    {
        $orderModel = Order::query()
            ->where('orderid', $order)
            ->where('accountid', $this->resolveAccountId())
            ->first();

        if (! $orderModel) {
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

    public function ordersTimelineAjax(Order $order): JsonResponse
    {
        $userAccountId = $this->resolveAccountId();
        if ((string) $order->accountid !== $userAccountId) {
            abort(403);
        }

        $timeline = $order->timeline()
            ->with('creator')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($timeline);
    }

    public function getOrderJsonByNumber(Request $request)
    {
        $lookup = $request->input('o');
        if (! $lookup) {
            return response()->json(['error' => 'Order ID required'], 400);
        }

        $order = Order::query()
            ->where('orderid', $lookup)
            ->orWhere('order_number', $lookup)
            ->first();

        if (! $order) {
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
                'grace_period' => $order->grace_period ?? 0,
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
        $isItemLockedByInvoice = InvoiceItem::query()
            ->where('accountid', $accountId)
            ->where('clientid', $order->clientid)
            ->where('orderid', $order->orderid)
            ->exists();

        return view('orders.create', [
            'title' => 'Edit Order',
            'subtitle' => null,
            'order' => $order,
            'clients' => Client::where('accountid', $accountId)->regular()->active()->with('primaryContact')->orderBy('business_name')->get(),
            'services' => Service::where('accountid', $accountId)->with('costings', 'category')->orderBy('name')->get(),
            'preSelectedClientId' => $order->clientid,
            'clientDocuments' => $documents->values(),
            'isEditMode' => true,
            'account' => $account,
            'isItemLockedByInvoice' => $isItemLockedByInvoice,
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
        if (! is_array($itemData)) {
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
            'start_date' => ! empty($itemData['start_date']) ? $itemData['start_date'] : now()->toDateString(),
            'end_date' => $itemData['end_date'] ?? '2099-12-31',
            'delivery_date' => ! empty($itemData['delivery_date']) ? $itemData['delivery_date'] : null,
            'grace_period' => $itemData['grace_period'] ?? 0,
        ]);

        $this->invalidateOrderCache();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Order updated successfully.',
                'order' => $order->fresh(),
            ]);
        }

        if ($request->query('return_to') === 'create') {
            return redirect()->route('orders.create', [
                'c' => $order->clientid,
                'carry' => 1,
                'iframe' => $request->query('iframe'),
            ])->with('success', 'Order updated successfully.');
        }

        if ($request->query('return_to') === 'trials') {
            return redirect()->route('clients.trials')->with('success', 'Order updated successfully.');
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

        $this->invalidateOrderCache();

        if (request()->query('return_to') === 'create') {
            $recentOrderIds = collect(session('orders_create_recent_ids', []))
                ->map(fn ($id) => (string) $id)
                ->reject(fn ($id) => $id === (string) $order->orderid)
                ->values()
                ->all();
            session(['orders_create_recent_ids' => $recentOrderIds]);

            return redirect()->route('orders.create', [
                'c' => $order->clientid,
                'carry' => 1,
                'iframe' => request()->query('iframe'),
            ])->with('success', 'Order cancelled successfully.');
        }

        if (request()->query('return_to') === 'trials') {
            return redirect()->route('clients.trials')->with('success', 'Order cancelled successfully.');
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

        $this->invalidateOrderCache();

        if (request()->query('return_to') === 'trials') {
            return redirect()->route('clients.trials')->with('success', 'Order restored successfully.');
        }

        return redirect()->route('orders.index', ['c' => $order->clientid])->with('success', 'Order restored successfully.');
    }

    // have to commented later on
    public function ordersForceDelete(Order $order)
    {
        if ((string) $order->accountid !== $this->resolveAccountId()) {
            abort(404);
        }

        DB::transaction(function () use ($order) {
            $order->timeline()->delete();
            $order->invoiceItems()->delete();
            $order->delete();
        });

        $this->invalidateOrderCache();

        return redirect()->route('orders.index', ['c' => $order->clientid])->with('success', 'Order deleted permanently.');
    }

    public function convertOrderToRegular(Order $order)
    {
        if ((string) $order->accountid !== $this->resolveAccountId()) {
            abort(404);
        }

        if ($order->type !== 'trial') {
            return redirect()->route('orders.index', ['c' => $order->clientid])
                ->with('error', 'This order is not a trial order.');
        }

        $order->update(['type' => 'regular']);

        $this->invalidateOrderCache();

        return redirect()->route('orders.index', ['c' => $order->clientid])
            ->with('success', 'Order #'.$order->order_number.' converted to regular successfully.');
    }

    protected function generateOrderNumber(string $accountId): string
    {
        return Order::generateNextOrderNumberForAccount($accountId);
    }

    private function wholeQuantity(mixed $value): int
    {
        return max(1, (int) round((float) $value, 0));
    }

    private function buildQuotationOrderPayload(Quotation $quotation): array
    {
        $quotationTitle = trim((string) ($quotation->quo_title ?? ''));
        $quotationNumber = trim((string) ($quotation->quo_number ?? ''));
        $displayTitle = $quotationTitle !== '' ? $quotationTitle : ($quotationNumber !== '' ? $quotationNumber : ('Quotation '.$quotation->quotationid));

        return [
            'quotationid' => $quotation->quotationid,
            'quotation_number' => $quotationNumber,
            'quo_title' => $quotationTitle,
            'display_title' => $displayTitle,
            'items' => $quotation->items->map(function (QuotationItem $item): array {
                $resolvedName = trim((string) ($item->item_name ?? ''));

                if ($resolvedName === '') {
                    $resolvedName = trim((string) ($item->item?->name ?? ''));
                }

                if ($resolvedName === '') {
                    $resolvedName = 'Item';
                }

                return [
                    'itemid' => $item->itemid,
                    'item_name' => $resolvedName,
                    'item_description' => (string) ($item->item_description ?? ''),
                    'quantity' => (float) ($item->quantity ?? 1),
                    'no_of_users' => ! empty($item->no_of_users) ? (int) $item->no_of_users : null,
                    'frequency' => (string) ($item->frequency ?? ''),
                    'duration' => ! empty($item->duration) ? (int) $item->duration : null,
                    'start_date' => $item->start_date?->format('Y-m-d') ?? now()->toDateString(),
                    'end_date' => $item->end_date?->format('Y-m-d') ?? '2099-12-31',
                    'delivery_date' => null,
                ];
            })->values(),
        ];
    }
}
