<?php

namespace App\Http\Controllers;

use App\Models\ProductCategory;
use App\Models\Service;
use App\Models\ServiceCosting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServicesController extends Controller
{
    public function services(): View
    {
        $query = Service::with(['category', 'costings'])
            ->join('ps_categories', 'items.ps_catid', '=', 'ps_categories.ps_catid', 'left')
            ->select('items.*', 'ps_categories.name as cat_name')
            ->orderBy('cat_name', 'asc')
            ->orderBy('items.sequence', 'asc')
            ->orderBy('items.name', 'asc');

        $searchTerm = request('search', '');
        if ($searchTerm) {
            $query->where('items.name', 'like', '%' . $searchTerm . '%');
        }

        $resultCount = $query->count();
        $records = $query->take(20)->get();

        $addonIds = $records
            ->flatMap(fn (Service $item) => collect($item->addons ?? []))
            ->filter(fn ($id) => is_string($id) && $id !== '')
            ->unique()
            ->values();

        $addonLookup = $addonIds->isEmpty()
            ? collect()
            : Service::whereIn('itemid', $addonIds)->pluck('name', 'itemid');

        $services = $records->map(function (Service $item) use ($addonLookup) {
            $costings = $item->costings->map(function (ServiceCosting $costing) {
                return [
                    'currency_code' => $costing->currency_code,
                    'cost_price' => $costing->cost_price,
                    'selling_price' => $costing->selling_price,
                    'sac_code' => $costing->sac_code,
                    'tax_rate' => $costing->tax_rate,
                    'tax_included' => $costing->tax_included,
                ];
            });

            $addons = collect($item->addons ?? [])->map(function (string $addonId) use ($addonLookup) {
                return [
                    'itemid' => $addonId,
                    'name' => $addonLookup[$addonId] ?? $addonId,
                ];
            })->values();

            return [
                'record_id' => $item->itemid,
                'name' => $item->name,
                'type' => $item->type ?? 'service',
                'sequence' => (int) ($item->sequence ?? 0),
                'category_name' => $item->category->name ?? 'No Category',
                'costings' => $costings,
                'addons' => $addons,
                'status' => $item->is_active ? 'Active' : 'Inactive',
            ];
        });

        $catQuery = ProductCategory::query()->orderBy('sequence')->orderBy('name');
        $catSearch = request('cat_search', '');
        if ($catSearch) {
            $catQuery->where('name', 'like', '%' . $catSearch . '%');
        }
        $catResultCount = $catQuery->count();
        $productCategories = $catQuery->take(20)->get()->map(function ($pc) {
            return [
                'record_id' => $pc->ps_catid,
                'name' => $pc->name,
                'sequence' => (int) ($pc->sequence ?? 0),
                'description' => $pc->description ?? '',
                'status' => strtolower($pc->status ?? 'active'),
            ];
        });

        return view('services.index', [
            'title' => 'Items',
            'services' => $services,
            'searchTerm' => $searchTerm,
            'resultCount' => $resultCount,
            'productCategories' => $productCategories,
            'catSearch' => $catSearch,
            'catResultCount' => $catResultCount,
        ]);
    }

    public function servicesCreate(): View
    {
        $categories = ProductCategory::where('status', 'active')->orderBy('sequence')->orderBy('name')->get();
        $currencies = DB::table('currency')->orderBy('iso')->get(['iso', 'name']);
        $accountCurrency = auth()->check() ? (auth()->user()->account->currency_code ?? 'INR') : 'INR';

        // Get items created in current session to exclude from addon dropdown
        $sessionSavedItemIds = session()->get('session_saved_item_ids', []);
        
        // Available addons exclude items already saved in this session
        $availableAddonItems = Service::whereNotIn('itemid', $sessionSavedItemIds)
            ->orderBy('sequence')
            ->orderBy('name')
            ->get(['itemid', 'name', 'type']);

        return view('services.create', [
            'title' => 'New Item',
            'categories' => $categories,
            'defaultCurrency' => $accountCurrency,
            'currencies' => $currencies,
            'nextServiceSequence' => (Service::max('sequence') ?? 0) + 1,
            'availableAddonItems' => $availableAddonItems,
        ]);
    }

    public function servicesStore(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:product,service',
            'sync' => 'required|in:yes,no',
            'name' => 'required|string|max:255',
            'ps_catid' => 'nullable|exists:ps_categories,ps_catid',
            'description' => 'nullable|string',
            'sequence' => 'nullable|integer|min:0',
            'accountid' => 'nullable|size:10',
            'costings' => 'required|array|min:1',
            'costings.*.currency_code' => 'required|string|size:3|exists:currency,iso|distinct',
            'costings.*.cost_price' => 'required|numeric|min:0',
            'costings.*.selling_price' => 'required|numeric|min:0',
            'costings.*.sac_code' => 'nullable|string|max:20',
            'costings.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'costings.*.tax_included' => 'required|in:yes,no',
            'addons' => 'nullable|array',
            'addons.*' => 'required|string|distinct|exists:items,itemid',
        ]);

        $userAccountId = auth()->check() ? (auth()->user()->accountid ?? 'ACC0000001') : 'ACC0000001';
        $validated['accountid'] = $validated['accountid'] ?? $userAccountId;

        DB::transaction(function () use ($validated) {
            $costings = collect($validated['costings'])->map(function (array $costing) {
                return [
                    'currency_code' => strtoupper($costing['currency_code']),
                    'cost_price' => $costing['cost_price'],
                    'selling_price' => $costing['selling_price'],
                    'sac_code' => $costing['sac_code'] ?? null,
                    'tax_rate' => $costing['tax_rate'] ?? 0,
                    'tax_included' => $costing['tax_included'],
                ];
            });

            $item = Service::create([
                'type' => $validated['type'],
                'sync' => $validated['sync'],
                'name' => $validated['name'],
                'ps_catid' => $validated['ps_catid'] ?? null,
                'description' => $validated['description'] ?? null,
                'addons' => array_values($validated['addons'] ?? []),
                'accountid' => $validated['accountid'],
                'is_active' => true,
                'sequence' => $validated['sequence'] ?? ((Service::where('accountid', $validated['accountid'])->where('ps_catid', $validated['ps_catid'] ?? null)->max('sequence') ?? 0) + 1),
            ]);

            $costings->each(function ($costing) use ($item, $validated) {
                $item->costings()->create([
                    'accountid' => $validated['accountid'],
                    'currency_code' => $costing['currency_code'],
                    'cost_price' => $costing['cost_price'],
                    'selling_price' => $costing['selling_price'],
                    'sac_code' => $costing['sac_code'],
                    'tax_rate' => $costing['tax_rate'],
                    'tax_included' => $costing['tax_included'],
                ]);
            });
        });

        return redirect()->route('services.index')->with('success', 'Item created successfully.');
    }

    public function servicesShow(Service $service): View
    {
        $service->load(['subscriptions', 'category', 'costings']);

        $addonItems = collect($service->addons ?? [])->isEmpty()
            ? collect()
            : Service::whereIn('itemid', $service->addons ?? [])->orderBy('name')->get(['itemid', 'name', 'type']);

        return view('services.show', [
            'title' => 'Item Details',
            'service' => $service,
            'addonItems' => $addonItems,
        ]);
    }

    public function servicesEdit(Service $service): View
    {
        $categories = ProductCategory::where('status', 'active')->orderBy('sequence')->orderBy('name')->get();
        $currencies = DB::table('currency')->orderBy('iso')->get(['iso', 'name']);
        $service->load(['costings']);
        $accountCurrency = auth()->check() ? (auth()->user()->account->currency_code ?? 'INR') : 'INR';

        return view('services.edit', [
            'title' => 'Edit Item',
            'service' => $service,
            'categories' => $categories,
            'defaultCurrency' => $accountCurrency,
            'currencies' => $currencies,
            'availableAddonItems' => Service::where('itemid', '!=', $service->itemid)->orderBy('sequence')->orderBy('name')->get(['itemid', 'name', 'type']),
        ]);
    }

    public function servicesUpdate(Request $request, Service $service)
    {
        $validated = $request->validate([
            'type' => 'required|in:product,service',
            'sync' => 'required|in:yes,no',
            'name' => 'required|string|max:255',
            'ps_catid' => 'nullable|exists:ps_categories,ps_catid',
            'description' => 'nullable|string',
            'sequence' => 'nullable|integer|min:0',
            'costings' => 'required|array|min:1',
            'costings.*.currency_code' => 'required|string|size:3|exists:currency,iso|distinct',
            'costings.*.cost_price' => 'required|numeric|min:0',
            'costings.*.selling_price' => 'required|numeric|min:0',
            'costings.*.sac_code' => 'nullable|string|max:20',
            'costings.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'costings.*.tax_included' => 'required|in:yes,no',
            'addons' => 'nullable|array',
            'addons.*' => 'required|string|distinct|exists:items,itemid',
        ]);

        DB::transaction(function () use ($validated, $service) {
            $costings = collect($validated['costings'])->map(function (array $costing) {
                return [
                    'currency_code' => strtoupper($costing['currency_code']),
                    'cost_price' => $costing['cost_price'],
                    'selling_price' => $costing['selling_price'],
                    'sac_code' => $costing['sac_code'] ?? null,
                    'tax_rate' => $costing['tax_rate'] ?? 0,
                    'tax_included' => $costing['tax_included'],
                ];
            });

            $service->update([
                'type' => $validated['type'],
                'sync' => $validated['sync'],
                'name' => $validated['name'],
                'ps_catid' => $validated['ps_catid'] ?? null,
                'description' => $validated['description'] ?? null,
                'addons' => array_values($validated['addons'] ?? []),
                'is_active' => true,
                'sequence' => $validated['sequence'] ?? ($service->sequence ?? 0),
            ]);

            $service->costings()->delete();

            $costings->each(function ($costing) use ($service) {
                $service->costings()->create([
                    'accountid' => $service->accountid,
                    'currency_code' => $costing['currency_code'],
                    'cost_price' => $costing['cost_price'],
                    'selling_price' => $costing['selling_price'],
                    'sac_code' => $costing['sac_code'],
                    'tax_rate' => $costing['tax_rate'],
                    'tax_included' => $costing['tax_included'],
                ]);
            });
        });

        return redirect()->route('services.index')->with('success', 'Item updated successfully.');
    }

    public function servicesDestroy(Service $service)
    {
        $service->delete();

        return redirect()->route('services.index')->with('success', 'Item deleted successfully.');
    }

    public function servicesReorder(Request $request)
    {
        $validated = $request->validate([
            'order' => 'required|array|min:1',
            'order.*' => 'required|string|exists:items,itemid',
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated['order'] as $index => $itemId) {
                Service::where('itemid', $itemId)->update([
                    'sequence' => $index + 1,
                ]);
            }
        });

        return response()->json(['success' => true]);
    }

    public function servicesSaveAjax(Request $request)
    {
        try {
            $validated = $request->validate([
                'itemid' => 'nullable|string|exists:items,itemid',
                'type' => 'required|in:product,service',
                'sync' => 'required|in:yes,no',
                'name' => 'required|string|max:255',
                'ps_catid' => 'nullable|exists:ps_categories,ps_catid',
                'description' => 'nullable|string',
                'costings' => 'required|array|min:1',
                'costings.*.currency_code' => 'required|string|size:3|exists:currency,iso',
                'costings.*.cost_price' => 'required|numeric|min:0',
                'costings.*.selling_price' => 'required|numeric|min:0',
                'costings.*.sac_code' => 'nullable|string|max:20',
                'costings.*.tax_rate' => 'nullable|numeric|min:0|max:100',
                'costings.*.tax_included' => 'required|in:yes,no',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . implode(', ', $e->validator->errors()->all()),
            ], 422);
        }

        $userAccountId = auth()->check() ? (auth()->user()->accountid ?? 'ACC0000001') : 'ACC0000001';

        try {
            $item = DB::transaction(function () use ($validated, $userAccountId) {
                $itemData = [
                    'type' => $validated['type'],
                    'sync' => $validated['sync'],
                    'name' => $validated['name'],
                    'ps_catid' => $validated['ps_catid'] ?? null,
                    'description' => $validated['description'] ?? null,
                    'accountid' => $userAccountId,
                    'is_active' => true,
                ];

                if (! empty($validated['itemid'])) {
                    $item = Service::where('itemid', $validated['itemid'])->firstOrFail();
                    $item->update($itemData);
                } else {
                    $itemData['sequence'] = (Service::where('accountid', $userAccountId)->max('sequence') ?? 0) + 1;
                    $item = Service::create($itemData);
                }

                $item->costings()->delete();
                foreach ($validated['costings'] as $costing) {
                    $item->costings()->create([
                        'accountid' => $userAccountId,
                        'currency_code' => strtoupper($costing['currency_code']),
                        'cost_price' => $costing['cost_price'],
                        'selling_price' => $costing['selling_price'],
                        'sac_code' => $costing['sac_code'] ?? null,
                        'tax_rate' => $costing['tax_rate'] ?? 0,
                        'tax_included' => $costing['tax_included'],
                    ]);
                }

                return $item;
            });

            // Track saved item IDs in session to exclude from addon dropdown
            $sessionSavedItemIds = session()->get('session_saved_item_ids', []);
            $sessionSavedItemIds[] = $item->itemid;
            session()->put('session_saved_item_ids', $sessionSavedItemIds);

            return response()->json([
                'success' => true,
                'message' => 'Item saved successfully.',
                'itemid' => $item->itemid,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save item: ' . $e->getMessage(),
            ], 500);
        }
    }
}
