<?php

namespace App\Http\Controllers;

use App\Models\ProductCategory;
use App\Models\Service;
use App\Models\ServiceCosting;
use App\Models\Tax;
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

        $visibleItemIds = $records->pluck('itemid')->all();

        // Add-on semantics in this module: current item can be linked "under" other parent items.
        // For index display, we show the inverse relation: parents list their child add-ons.
        $addonsByParent = [];
        Service::query()
            ->select(['itemid', 'name', 'addons'])
            ->get()
            ->each(function (Service $candidate) use (&$addonsByParent, $visibleItemIds) {
                foreach (collect($candidate->addons ?? [])->filter()->all() as $parentId) {
                    if (! in_array($parentId, $visibleItemIds, true)) {
                        continue;
                    }

                    $addonsByParent[$parentId] = $addonsByParent[$parentId] ?? [];
                    $addonsByParent[$parentId][$candidate->itemid] = [
                        'itemid' => $candidate->itemid,
                        'name' => $candidate->name,
                    ];
                }
            });

        $services = $records->map(function (Service $item) use ($addonsByParent) {
            $costings = $item->costings->map(function (ServiceCosting $costing) {
                return [
                    'currency_code' => $costing->currency_code,
                    'cost_price' => $costing->cost_price,
                    'selling_price' => $costing->selling_price,
                    'sac_code' => $costing->sac_code,
                    'tax_rate' => $costing->tax_rate,
                ];
            });

            $addons = collect($addonsByParent[$item->itemid] ?? [])
                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values();

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
        $accountid = auth()->check() ? auth()->id() : 'ACC0000001';
        $account = \App\Models\Account::find($accountid);
        
        // Only load taxes if multi-taxation is enabled
        $taxes = ($account && $account->allow_multi_taxation) 
            ? Tax::where('accountid', $accountid)->orderByRaw('COALESCE(sequence, 999999), created_at DESC')->get() 
            : collect();

        // Available addons: all items except items being created in current request (via old input)
        $availableAddonItems = Service::orderBy('sequence')
            ->orderBy('name')
            ->get(['itemid', 'name', 'type']);

        return view('services.create', [
            'title' => 'New Item',
            'categories' => $categories,
            'defaultCurrency' => $accountCurrency,
            'currencies' => $currencies,
            'nextServiceSequence' => (Service::max('sequence') ?? 0) + 1,
            'availableAddonItems' => $availableAddonItems,
            'taxes' => $taxes,
            'account' => $account,
        ]);
    }

    public function servicesStore(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:product,service',
            'sync' => 'required|in:yes,no',
            'user_wise' => 'nullable|boolean',
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
            'costings.*.taxid' => 'nullable|string|exists:account_taxes,taxid',
            'addons' => 'nullable|array',
            'addons.*' => 'required|string|distinct|exists:items,itemid',
        ]);

        $userAccountId = auth()->check() ? (auth()->user()->accountid ?? 'ACC0000001') : 'ACC0000001';
        $validated['accountid'] = $validated['accountid'] ?? $userAccountId;
        $validated['user_wise'] = $request->boolean('user_wise');

        DB::transaction(function () use ($validated) {
            $account = \App\Models\Account::find($validated['accountid']);
            $isMultiTax = $account && $account->allow_multi_taxation;
            $fixedTaxRate = $account ? ($account->fixed_tax_rate ?? 0) : 0;

            $costings = collect($validated['costings'])->map(function (array $costing) use ($isMultiTax, $fixedTaxRate) {
                $taxid = $costing['taxid'] ?? null;
                $taxRate = 0;

                if ($isMultiTax) {
                    // Multi-taxation mode: look up tax rate from taxid
                    if ($taxid) {
                        $tax = \App\Models\Tax::find($taxid);
                        $taxRate = $tax ? $tax->rate : 0;
                    }
                } else {
                    // Fixed tax mode: use fixed_tax_rate from accounts table
                    $taxRate = $fixedTaxRate;
                }

                return [
                    'currency_code' => strtoupper($costing['currency_code']),
                    'cost_price' => $costing['cost_price'],
                    'selling_price' => $costing['selling_price'],
                    'sac_code' => $costing['sac_code'] ?? null,
                    'taxid' => $taxid,
                    'tax_rate' => $taxRate,
                ];
            });

            $item = Service::create([
                'type' => $validated['type'],
                'sync' => $validated['sync'],
                'user_wise' => $validated['user_wise'],
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
                    'taxid' => $costing['taxid'],
                    'tax_rate' => $costing['tax_rate'],
                ]);
            });
        });

        return redirect()->route('services.index')->with('success', 'Item created successfully.');
    }

    public function servicesShow(Service $service): View
    {
        $service->load(['subscriptions', 'category', 'costings']);

        $addonItems = Service::query()
            ->select(['itemid', 'name', 'type', 'addons'])
            ->get()
            ->filter(function (Service $candidate) use ($service) {
                return in_array($service->itemid, collect($candidate->addons ?? [])->filter()->all(), true);
            })
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

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
        $accountid = $service->accountid;
        $account = \App\Models\Account::find($accountid);
        
        // Only load taxes if multi-taxation is enabled
        $taxes = ($account && $account->allow_multi_taxation) 
            ? Tax::where('accountid', $accountid)->orderByRaw('COALESCE(sequence, 999999), created_at DESC')->get() 
            : collect();

        return view('services.edit', [
            'title' => 'Edit Item',
            'service' => $service,
            'categories' => $categories,
            'defaultCurrency' => $accountCurrency,
            'currencies' => $currencies,
            'availableAddonItems' => Service::where('itemid', '!=', $service->itemid)->orderBy('sequence')->orderBy('name')->get(['itemid', 'name', 'type']),
            'taxes' => $taxes,
            'account' => $account,
        ]);
    }

    public function servicesUpdate(Request $request, Service $service)
    {
        $validated = $request->validate([
            'type' => 'required|in:product,service',
            'sync' => 'required|in:yes,no',
            'user_wise' => 'nullable|boolean',
            'name' => 'required|string|max:255',
            'ps_catid' => 'nullable|exists:ps_categories,ps_catid',
            'description' => 'nullable|string',
            'sequence' => 'nullable|integer|min:0',
            'costings' => 'required|array|min:1',
            'costings.*.currency_code' => 'required|string|size:3|exists:currency,iso|distinct',
            'costings.*.cost_price' => 'required|numeric|min:0',
            'costings.*.selling_price' => 'required|numeric|min:0',
            'costings.*.sac_code' => 'nullable|string|max:20',
            'costings.*.taxid' => 'nullable|string|exists:account_taxes,taxid',
            'addons' => 'nullable|array',
            'addons.*' => 'required|string|distinct|exists:items,itemid',
        ]);

        $validated['user_wise'] = $request->boolean('user_wise');

        DB::transaction(function () use ($validated, $service) {
            $account = \App\Models\Account::find($service->accountid);
            $isMultiTax = $account && $account->allow_multi_taxation;
            $fixedTaxRate = $account ? ($account->fixed_tax_rate ?? 0) : 0;

            $costings = collect($validated['costings'])->map(function (array $costing) use ($isMultiTax, $fixedTaxRate) {
                $taxid = $costing['taxid'] ?? null;
                $taxRate = 0;

                if ($isMultiTax) {
                    // Multi-taxation mode: look up tax rate from taxid
                    if ($taxid) {
                        $tax = Tax::find($taxid);
                        $taxRate = $tax ? $tax->rate : 0;
                    }
                } else {
                    // Fixed tax mode: use fixed_tax_rate from accounts table
                    $taxRate = $fixedTaxRate;
                }

                return [
                    'currency_code' => strtoupper($costing['currency_code']),
                    'cost_price' => $costing['cost_price'],
                    'selling_price' => $costing['selling_price'],
                    'sac_code' => $costing['sac_code'] ?? null,
                    'taxid' => $taxid,
                    'tax_rate' => $taxRate,
                ];
            });

            $service->update([
                'type' => $validated['type'],
                'sync' => $validated['sync'],
                'user_wise' => $validated['user_wise'],
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
                    'taxid' => $costing['taxid'],
                    'tax_rate' => $costing['tax_rate'],
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
                'user_wise' => 'nullable|boolean',
                'name' => 'required|string|max:255',
                'ps_catid' => 'nullable|exists:ps_categories,ps_catid',
                'description' => 'nullable|string',
                'costings' => 'required|array|min:1',
                'costings.*.currency_code' => 'required|string|size:3|exists:currency,iso',
                'costings.*.cost_price' => 'required|numeric|min:0',
                'costings.*.selling_price' => 'required|numeric|min:0',
                'costings.*.sac_code' => 'nullable|string|max:20',
                'costings.*.taxid' => 'nullable|string|exists:account_taxes,taxid',
                'addons' => 'nullable|array',
                'addons.*' => 'required|string|distinct|exists:items,itemid',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . implode(', ', $e->validator->errors()->all()),
            ], 422);
        }

        $userAccountId = auth()->check() ? (auth()->user()->accountid ?? 'ACC0000001') : 'ACC0000001';

        try {
            $validated['user_wise'] = $request->boolean('user_wise');
            $item = DB::transaction(function () use ($validated, $userAccountId) {
                $itemData = [
                    'type' => $validated['type'],
                    'sync' => $validated['sync'],
                    'user_wise' => $validated['user_wise'],
                    'name' => $validated['name'],
                    'ps_catid' => $validated['ps_catid'] ?? null,
                    'description' => $validated['description'] ?? null,
                    'addons' => $validated['addons'] ?? [],
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
                $account = \App\Models\Account::find($userAccountId);
                $isMultiTax = $account && $account->allow_multi_taxation;
                $fixedTaxRate = $account ? ($account->fixed_tax_rate ?? 0) : 0;

                foreach ($validated['costings'] as $costing) {
                    $taxid = $costing['taxid'] ?? null;
                    $taxRate = 0;

                    if ($isMultiTax) {
                        // Multi-taxation mode: look up tax rate from taxid
                        if ($taxid) {
                            $tax = Tax::find($taxid);
                            $taxRate = $tax ? $tax->rate : 0;
                        }
                    } else {
                        // Fixed tax mode: use fixed_tax_rate from accounts table
                        $taxRate = $fixedTaxRate;
                    }

                    $item->costings()->create([
                        'accountid' => $userAccountId,
                        'currency_code' => strtoupper($costing['currency_code']),
                        'cost_price' => $costing['cost_price'],
                        'selling_price' => $costing['selling_price'],
                        'sac_code' => $costing['sac_code'] ?? null,
                        'taxid' => $taxid,
                        'tax_rate' => $taxRate,
                    ]);
                }

                return $item;
            });

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
