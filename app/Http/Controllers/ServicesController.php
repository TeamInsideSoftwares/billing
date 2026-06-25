<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\ProductCategory;
use App\Models\Service;
use App\Models\ServiceCosting;
use App\Models\Tax;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ServicesController extends Controller
{
    private function resolveNextItemSequence(string $accountId, ?string $categoryId = null, ?string $excludeItemId = null): int
    {
        $query = Service::query()->where('accountid', $accountId);

        if ($categoryId) {
            $query->where('ps_catid', $categoryId);
        } else {
            $query->whereNull('ps_catid');
        }

        if ($excludeItemId) {
            $query->where('itemid', '!=', $excludeItemId);
        }

        return ((int) ($query->max('sequence') ?? 0)) + 1;
    }

    public function services(): View
    {
        $userAccountId = $this->resolveAccountId();
        $query = Service::with(['category', 'costings'])
            ->where('items.accountid', $userAccountId)
            ->join('ps_categories', 'items.ps_catid', '=', 'ps_categories.ps_catid', 'left')
            ->select('items.*', 'ps_categories.name as cat_name')
            ->orderBy('cat_name', 'asc')
            ->orderBy('items.sequence', 'asc')
            ->orderBy('items.name', 'asc');

        $searchTerm = request('search', '');
        if ($searchTerm) {
            $query->where('items.name', 'like', '%'.$searchTerm.'%');
        }

        $resultCount = $query->count();
        $records = $query->take(20)->get();

        $visibleItemIds = $records->pluck('itemid')->all();

        // Add-on semantics in this module: current item can be linked "under" other parent items.
        // For index display, we show the inverse relation: parents list their child add-ons.
        $addonsByParent = [];
        Service::query()
            ->where('accountid', $userAccountId)
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
                'grace_period' => (int) ($item->grace_period ?? 0),
                'costings' => $costings,
                'addons' => $addons,
                'status' => $item->is_active ? 'Active' : 'Inactive',
            ];
        });

        $catQuery = ProductCategory::query()
            ->where('accountid', $userAccountId)
            ->orderBy('sequence')
            ->orderBy('name');
        $catSearch = request('cat_search', '');
        if ($catSearch) {
            $catQuery->where('name', 'like', '%'.$catSearch.'%');
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
            'title' => 'All Items',
            'subtitle' => $searchTerm ? 'Found '.$resultCount.' result(s) for "'.$searchTerm.'"' : null,
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
        $accountid = $this->resolveAccountId();
        $categories = ProductCategory::where('accountid', $accountid)->where('status', 'active')->orderBy('sequence')->orderBy('name')->get();
        $currencies = DB::table('currency')->orderBy('iso')->get(['iso', 'name']);
        $accountCurrency = auth()->check() ? (auth()->user()->account->currency_code ?? 'INR') : 'INR';
        $account = Account::find($accountid);

        // Only load taxes if multi-taxation is enabled
        $taxes = ($account && $account->allow_multi_taxation)
            ? Tax::where('accountid', $accountid)->orderByRaw('COALESCE(sequence, 999999), created_at DESC')->get()
            : collect();

        // Available addons: all items except items being created in current request (via old input)
        $availableAddonItems = Service::where('accountid', $accountid)
            ->orderBy('sequence')
            ->orderBy('name')
            ->get(['itemid', 'name', 'type']);

        return view('services.form', [
            'title' => 'Create Item',
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
        $account = Account::find($this->resolveAccountId());
        $allowSync = (bool) ($account?->allow_sync ?? false);

        $validated = $request->validate([
            'type' => 'required|in:product,service',
            'sync' => 'nullable|in:yes,no',
            'user_wise' => 'nullable|boolean',
            'name' => 'required|string|max:255',
            'ps_catid' => 'nullable|exists:ps_categories,ps_catid',
            'description' => 'nullable|string',
            'grace_period' => 'nullable|integer|min:0',
            'sequence' => 'nullable|integer|min:0',
            'accountid' => 'nullable|string|max:10',
            'costings' => 'required|array|min:1',
            'costings.*.currency_code' => 'required|string|size:3|exists:currency,iso|distinct',
            'costings.*.cost_price' => 'required|numeric|min:0',
            'costings.*.selling_price' => 'required|numeric|min:0',
            'costings.*.sac_code' => 'nullable|string|max:20',
            'costings.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'addons' => 'nullable|array',
            'addons.*' => 'required|string|distinct|exists:items,itemid',
        ]);

        $userAccountId = $this->resolveAccountId();
        $validated['accountid'] = $validated['accountid'] ?? $userAccountId;
        $validated['user_wise'] = $request->boolean('user_wise');
        $validated['sync'] = $allowSync && ($validated['sync'] ?? 'no') === 'yes' ? 'yes' : 'no';

        DB::transaction(function () use ($validated) {
            $account = Account::find($validated['accountid']);
            $isMultiTax = $account && $account->allow_multi_taxation;
            $fixedTaxRate = $account ? ($account->fixed_tax_rate ?? 0) : 0;

            $costings = collect($validated['costings'])->map(function (array $costing) use ($isMultiTax, $fixedTaxRate) {
                $taxRate = $isMultiTax ? (float) ($costing['tax_rate'] ?? 0) : (float) $fixedTaxRate;

                return [
                    'currency_code' => strtoupper($costing['currency_code']),
                    'cost_price' => $costing['cost_price'],
                    'selling_price' => $costing['selling_price'],
                    'sac_code' => $costing['sac_code'] ?? null,
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
                'grace_period' => (int) ($validated['grace_period'] ?? 0),
                'addons' => array_values($validated['addons'] ?? []),
                'accountid' => $validated['accountid'],
                'is_active' => true,
                'sequence' => $validated['sequence'] ?? $this->resolveNextItemSequence($validated['accountid'], $validated['ps_catid'] ?? null),
            ]);

            $costings->each(function ($costing) use ($item, $validated) {
                $item->costings()->create([
                    'accountid' => $validated['accountid'],
                    'currency_code' => $costing['currency_code'],
                    'cost_price' => $costing['cost_price'],
                    'selling_price' => $costing['selling_price'],
                    'sac_code' => $costing['sac_code'],
                    'tax_rate' => $costing['tax_rate'],
                ]);
            });

            $this->syncWithSuperadmin($item);
        });

        return redirect()->route('services.index')->with('success', 'Item created successfully.');
    }

    public function servicesEdit(Service $service): View
    {
        $accountid = $service->accountid;
        $categories = ProductCategory::where('accountid', $accountid)->where('status', 'active')->orderBy('sequence')->orderBy('name')->get();
        $currencies = DB::table('currency')->orderBy('iso')->get(['iso', 'name']);
        $service->load(['costings']);
        $accountCurrency = auth()->check() ? (auth()->user()->account->currency_code ?? 'INR') : 'INR';
        $accountid = $service->accountid;
        $account = Account::find($accountid);

        // Only load taxes if multi-taxation is enabled
        $taxes = ($account && $account->allow_multi_taxation)
            ? Tax::where('accountid', $accountid)->orderByRaw('COALESCE(sequence, 999999), created_at DESC')->get()
            : collect();

        return view('services.form', [
            'title' => 'Edit '.($service->name ?? 'Item'),
            'service' => $service,
            'categories' => $categories,
            'defaultCurrency' => $accountCurrency,
            'currencies' => $currencies,
            'availableAddonItems' => Service::where('accountid', $accountid)->where('itemid', '!=', $service->itemid)->orderBy('sequence')->orderBy('name')->get(['itemid', 'name', 'type']),
            'taxes' => $taxes,
            'account' => $account,
        ]);
    }

    public function servicesUpdate(Request $request, Service $service)
    {
        $account = Account::find($service->accountid);
        $allowSync = (bool) ($account?->allow_sync ?? false);

        $validated = $request->validate([
            'type' => 'required|in:product,service',
            'sync' => 'nullable|in:yes,no',
            'user_wise' => 'nullable|boolean',
            'name' => 'required|string|max:255',
            'ps_catid' => 'nullable|exists:ps_categories,ps_catid',
            'description' => 'nullable|string',
            'grace_period' => 'nullable|integer|min:0',
            'sequence' => 'nullable|integer|min:0',
            'costings' => 'required|array|min:1',
            'costings.*.currency_code' => 'required|string|size:3|exists:currency,iso|distinct',
            'costings.*.cost_price' => 'required|numeric|min:0',
            'costings.*.selling_price' => 'required|numeric|min:0',
            'costings.*.sac_code' => 'nullable|string|max:20',
            'costings.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'addons' => 'nullable|array',
            'addons.*' => 'required|string|distinct|exists:items,itemid',
        ]);

        $validated['user_wise'] = $request->boolean('user_wise');
        $validated['sync'] = $allowSync && ($validated['sync'] ?? 'no') === 'yes' ? 'yes' : 'no';

        DB::transaction(function () use ($validated, $service) {
            $account = Account::find($service->accountid);
            $isMultiTax = $account && $account->allow_multi_taxation;
            $fixedTaxRate = $account ? ($account->fixed_tax_rate ?? 0) : 0;

            $costings = collect($validated['costings'])->map(function (array $costing) use ($isMultiTax, $fixedTaxRate) {
                $taxRate = $isMultiTax ? (float) ($costing['tax_rate'] ?? 0) : (float) $fixedTaxRate;

                return [
                    'currency_code' => strtoupper($costing['currency_code']),
                    'cost_price' => $costing['cost_price'],
                    'selling_price' => $costing['selling_price'],
                    'sac_code' => $costing['sac_code'] ?? null,
                    'tax_rate' => $taxRate,
                ];
            });

            $nextSequence = $validated['sequence'] ?? ($service->sequence ?? 0);
            $targetCategoryId = $validated['ps_catid'] ?? null;
            $categoryChanged = (string) ($service->ps_catid ?? '') !== (string) ($targetCategoryId ?? '');

            if (! isset($validated['sequence']) && $categoryChanged) {
                $nextSequence = $this->resolveNextItemSequence($service->accountid, $targetCategoryId, $service->itemid);
            }

            $service->update([
                'type' => $validated['type'],
                'sync' => $validated['sync'],
                'user_wise' => $validated['user_wise'],
                'name' => $validated['name'],
                'ps_catid' => $targetCategoryId,
                'description' => $validated['description'] ?? null,
                'grace_period' => (int) ($validated['grace_period'] ?? 0),
                'addons' => array_values($validated['addons'] ?? []),
                'is_active' => true,
                'sequence' => $nextSequence,
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
                ]);
            });

            $this->syncWithSuperadmin($service);
        });

        return redirect()->route('services.index')->with('success', 'Item updated successfully.');
    }

    public function servicesDestroy(Service $service)
    {
        DB::transaction(function () use ($service) {
            $service->costings()->delete();
            $service->delete();

            DB::table('isplayhr_account_auth.products')->where('productid', $service->itemid)->delete();
        });

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
        $account = Account::find($this->resolveAccountId());
        $allowSync = (bool) ($account?->allow_sync ?? false);

        try {
            $validated = $request->validate([
                'itemid' => 'nullable|string|exists:items,itemid',
                'type' => 'required|in:product,service',
                'sync' => 'nullable|in:yes,no',
                'user_wise' => 'nullable|boolean',
                'name' => 'required|string|max:255',
                'ps_catid' => 'nullable|exists:ps_categories,ps_catid',
                'description' => 'nullable|string',
                'grace_period' => 'nullable|integer|min:0',
                'costings' => 'required|array|min:1',
                'costings.*.currency_code' => 'required|string|size:3|exists:currency,iso',
                'costings.*.cost_price' => 'required|numeric|min:0',
                'costings.*.selling_price' => 'required|numeric|min:0',
                'costings.*.sac_code' => 'nullable|string|max:20',
                'costings.*.tax_rate' => 'nullable|numeric|min:0|max:100',
                'addons' => 'nullable|array',
                'addons.*' => 'required|string|distinct|exists:items,itemid',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: '.implode(', ', $e->validator->errors()->all()),
            ], 422);
        }

        $userAccountId = $this->resolveAccountId();

        try {
            $validated['user_wise'] = $request->boolean('user_wise');
            $validated['sync'] = $allowSync && ($validated['sync'] ?? 'no') === 'yes' ? 'yes' : 'no';
            $item = DB::transaction(function () use ($validated, $userAccountId) {
                $itemData = [
                    'type' => $validated['type'],
                    'sync' => $validated['sync'],
                    'user_wise' => $validated['user_wise'],
                    'name' => $validated['name'],
                    'ps_catid' => $validated['ps_catid'] ?? null,
                    'description' => $validated['description'] ?? null,
                    'grace_period' => (int) ($validated['grace_period'] ?? 0),
                    'addons' => $validated['addons'] ?? [],
                    'accountid' => $userAccountId,
                    'is_active' => true,
                ];

                if (! empty($validated['itemid'])) {
                    $item = Service::where('itemid', $validated['itemid'])->firstOrFail();
                    $item->update($itemData);
                } else {
                    $itemData['sequence'] = $this->resolveNextItemSequence($userAccountId, $validated['ps_catid'] ?? null);
                    $item = Service::create($itemData);
                }

                $item->costings()->delete();
                $account = Account::find($userAccountId);
                $isMultiTax = $account && $account->allow_multi_taxation;
                $fixedTaxRate = $account ? ($account->fixed_tax_rate ?? 0) : 0;

                foreach ($validated['costings'] as $costing) {
                    $taxRate = $isMultiTax ? (float) ($costing['tax_rate'] ?? 0) : (float) $fixedTaxRate;

                    $item->costings()->create([
                        'accountid' => $userAccountId,
                        'currency_code' => strtoupper($costing['currency_code']),
                        'cost_price' => $costing['cost_price'],
                        'selling_price' => $costing['selling_price'],
                        'sac_code' => $costing['sac_code'] ?? null,
                        'tax_rate' => $taxRate,
                    ]);
                }

                $this->syncWithSuperadmin($item);

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
                'message' => 'Failed to save item: '.$e->getMessage(),
            ], 500);
        }
    }

    private function syncWithSuperadmin(Service $item): void
    {
        if ($item->sync === 'yes') {
            $apiUrl = str_replace('/accounts', '/products', config('services.superadmin_api.url'));
            $apiKey = config('services.superadmin_api.key');

            $payload = [
                'productid' => $item->itemid,
                'name' => $item->name,
            ];

            try {
                Http::acceptJson()
                    ->timeout(10)
                    ->connectTimeout(5)
                    ->withHeaders([
                        'X-API-KEY' => $apiKey,
                    ])->post($apiUrl, $payload);
            } catch (\Throwable $e) {
                Log::error('Superadmin Product Sync Failed', [
                    'message' => $e->getMessage(),
                    'productid' => $item->itemid,
                ]);
            }
        } else {
            DB::table('isplayhr_account_auth.products')->where('productid', $item->itemid)->delete();
        }
    }
}
