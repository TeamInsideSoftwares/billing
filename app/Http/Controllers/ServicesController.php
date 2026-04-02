<?php

namespace App\Http\Controllers;

use App\Models\ProductCategory;
use App\Models\Service;
use App\Models\ServiceAddon;
use App\Models\ServiceCosting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServicesController extends Controller
{
    public function services(): View
    {
        $query = Service::with(['category', 'costings', 'addons'])
            ->join('ps_categories', 'services.ps_catid', '=', 'ps_categories.ps_catid', 'left')
            ->select('services.*', 'ps_categories.name as cat_name')
            ->orderBy('cat_name', 'asc')
            ->orderBy('services.sequence', 'asc')
            ->orderBy('services.name', 'asc');

        $searchTerm = request('search', '');
        if ($searchTerm) {
            $query->where('services.name', 'like', '%' . $searchTerm . '%');
        }
        $resultCount = $query->count();
        $services = $query->take(20)->get()->map(function ($service) {
            $costings = $service->costings->map(function (ServiceCosting $costing) {
                return [
                    'currency_code' => $costing->currency_code,
                    'cost_price' => $costing->cost_price,
                    'selling_price' => $costing->selling_price,
                    'sac_code' => $costing->sac_code,
                    'tax_rate' => $costing->tax_rate,
                    'tax_included' => $costing->tax_included,
                ];
            });

            $addons = $service->addons->map(function ($addon) {
                return [
                    'name' => $addon->name,
                    'costings' => $addon->costings->map(function ($ac) {
                        return [
                            'currency_code' => $ac->currency_code,
                            'selling_price' => $ac->selling_price,
                        ];
                    }),
                ];
            });

            return [
                'record_id' => $service->serviceid,
                'name' => $service->name,
                'type' => $service->type ?? 'service',
                'sequence' => (int) ($service->sequence ?? 0),
                'category_name' => $service->category->name ?? 'No Category',
                'costings' => $costings,
                'addon_count' => $service->addons->count(),
                'addons' => $addons,
                'status' => $service->is_active ? 'Active' : 'Inactive',
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
            'title' => 'Services',
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
        $currencies = DB::table('currency')
            ->orderBy('iso')
            ->get(['iso', 'name']);
        $accountCurrency = auth()->check()
            ? (auth()->user()->account->currency_code ?? 'INR')
            : 'INR';
        return view('services.create', [
            'title' => 'New Service',
            'categories' => $categories,
            'defaultCurrency' => $accountCurrency,
            'currencies' => $currencies,
            'nextServiceSequence' => (Service::max('sequence') ?? 0) + 1,
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
            'accountid' => 'nullable|size:6',
            'costings' => 'required|array|min:1',
            'costings.*.currency_code' => 'required|string|size:3|exists:currency,iso|distinct',
            'costings.*.cost_price' => 'required|numeric|min:0',
            'costings.*.selling_price' => 'required|numeric|min:0',
            'costings.*.sac_code' => 'nullable|string|max:20',
            'costings.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'costings.*.tax_included' => 'required|in:yes,no',
            'addons' => 'nullable|array',
            'addons.*.name' => 'required|string|max:150',
            'addons.*.description' => 'nullable|string',
            'addons.*.status' => 'required|in:active,inactive',
            'addons.*.sequence' => 'nullable|integer|min:0',
            'addons.*.costings' => 'required|array|min:1',
            'addons.*.costings.*.currency_code' => 'required|string|size:3|exists:currency,iso',
            'addons.*.costings.*.cost_price' => 'required|numeric|min:0',
            'addons.*.costings.*.selling_price' => 'required|numeric|min:0',
            'addons.*.costings.*.sac_code' => 'nullable|string|max:20',
            'addons.*.costings.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'addons.*.costings.*.tax_included' => 'required|in:yes,no',
        ]);

        $userAccountId = auth()->check() ? (auth()->user()->accountid ?? 'ACC0000001') : 'ACC0000001';
        $validated['accountid'] = $validated['accountid'] ?? $userAccountId;
        $validated['is_active'] = true;

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

            $service = Service::create([
                'type' => $validated['type'],
                'sync' => $validated['sync'],
                'name' => $validated['name'],
                'ps_catid' => $validated['ps_catid'] ?? null,
                'description' => $validated['description'] ?? null,
                'accountid' => $validated['accountid'],
                'is_active' => $validated['is_active'],
                'sequence' => $validated['sequence'] ?? ((Service::where('accountid', $validated['accountid'])->where('ps_catid', $validated['ps_catid'] ?? null)->max('sequence') ?? 0) + 1),
            ]);

            $costings->each(function ($costing) use ($service, $validated) {
                $service->costings()->create([
                    'accountid' => $validated['accountid'],
                    'currency_code' => $costing['currency_code'],
                    'cost_price' => $costing['cost_price'],
                    'selling_price' => $costing['selling_price'],
                    'sac_code' => $costing['sac_code'],
                    'tax_rate' => $costing['tax_rate'],
                    'tax_included' => $costing['tax_included'],
                ]);
            });

            $addons = collect($validated['addons'] ?? [])
                ->filter(fn (array $addon) => ! empty(trim((string) ($addon['name'] ?? ''))))
                ->values();

            $addons->each(function (array $addon, int $index) use ($service, $validated) {
                $addonModel = $service->addons()->create([
                    'accountid' => $validated['accountid'],
                    'name' => trim($addon['name']),
                    'description' => $addon['description'] ?? null,
                    'sequence' => $addon['sequence'] ?? ($index + 1),
                    'is_active' => true,
                ]);

                collect($addon['costings'] ?? [])->each(function (array $costing) use ($addonModel, $validated) {
                    $addonModel->costings()->create([
                        'accountid' => $validated['accountid'],
                        'currency_code' => strtoupper($costing['currency_code']),
                        'cost_price' => $costing['cost_price'],
                        'selling_price' => $costing['selling_price'],
                        'sac_code' => $costing['sac_code'] ?? null,
                        'tax_rate' => $costing['tax_rate'] ?? 0,
                        'tax_included' => $costing['tax_included'],
                    ]);
                });
            });
        });

        return redirect()->route('services.index')->with('success', 'Service created successfully.');
    }

    public function servicesShow(Service $service): View
    {
        $service->load(['subscriptions', 'category', 'costings', 'addons.costings']);
        return view('services.show', [
            'title' => 'Service Details',
            'service' => $service,
        ]);
    }

    public function servicesEdit(Service $service): View
    {
        $categories = ProductCategory::where('status', 'active')->orderBy('sequence')->orderBy('name')->get();
        $currencies = DB::table('currency')
            ->orderBy('iso')
            ->get(['iso', 'name']);
        $service->load(['costings', 'addons.costings']);
        $accountCurrency = auth()->check()
            ? (auth()->user()->account->currency_code ?? 'INR')
            : 'INR';
        return view('services.edit', [
            'title' => 'Edit Service',
            'service' => $service,
            'categories' => $categories,
            'defaultCurrency' => $accountCurrency,
            'currencies' => $currencies,
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
            'addons.*.name' => 'required|string|max:150',
            'addons.*.description' => 'nullable|string',
            'addons.*.status' => 'required|in:active,inactive',
            'addons.*.sequence' => 'nullable|integer|min:0',
            'addons.*.costings' => 'required|array|min:1',
            'addons.*.costings.*.currency_code' => 'required|string|size:3|exists:currency,iso',
            'addons.*.costings.*.cost_price' => 'required|numeric|min:0',
            'addons.*.costings.*.selling_price' => 'required|numeric|min:0',
            'addons.*.costings.*.sac_code' => 'nullable|string|max:20',
            'addons.*.costings.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'addons.*.costings.*.tax_included' => 'required|in:yes,no',
        ]);

        $validated['is_active'] = true;

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
                'is_active' => $validated['is_active'],
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

            $service->addons()->delete();

            $addons = collect($validated['addons'] ?? [])
                ->filter(fn (array $addon) => ! empty(trim((string) ($addon['name'] ?? ''))))
                ->values();

            $addons->each(function (array $addon, int $index) use ($service) {
                $addonModel = $service->addons()->create([
                    'accountid' => $service->accountid,
                    'name' => trim($addon['name']),
                    'description' => $addon['description'] ?? null,
                    'sequence' => $addon['sequence'] ?? ($index + 1),
                    'is_active' => ($addon['status'] ?? 'active') === 'active',
                ]);

                collect($addon['costings'] ?? [])->each(function (array $costing) use ($addonModel, $service) {
                    $addonModel->costings()->create([
                        'accountid' => $service->accountid,
                        'currency_code' => strtoupper($costing['currency_code']),
                        'cost_price' => $costing['cost_price'],
                        'selling_price' => $costing['selling_price'],
                        'sac_code' => $costing['sac_code'] ?? null,
                        'tax_rate' => $costing['tax_rate'] ?? 0,
                        'tax_included' => $costing['tax_included'],
                    ]);
                });
            });
        });

        return redirect()->route('services.index')->with('success', 'Service updated successfully.');
    }

    public function servicesDestroy(Service $service)
    {
        $service->delete();

        return redirect()->route('services.index')->with('success', 'Service deleted successfully.');
    }

    public function servicesReorder(Request $request)
    {
        $validated = $request->validate([
            'order' => 'required|array|min:1',
            'order.*' => 'required|string|exists:services,serviceid',
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated['order'] as $index => $serviceId) {
                Service::where('serviceid', $serviceId)->update([
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
                'serviceid' => 'nullable|string|exists:services,serviceid',
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
            $service = DB::transaction(function () use ($validated, $userAccountId) {
                $serviceData = [
                    'type' => $validated['type'],
                    'sync' => $validated['sync'],
                    'name' => $validated['name'],
                    'ps_catid' => $validated['ps_catid'] ?? null,
                    'description' => $validated['description'] ?? null,
                    'accountid' => $userAccountId,
                    'is_active' => true,
                ];

                if (!empty($validated['serviceid'])) {
                    $service = Service::where('serviceid', $validated['serviceid'])->firstOrFail();
                    $service->update($serviceData);
                } else {
                    $serviceData['sequence'] = (Service::where('accountid', $userAccountId)->max('sequence') ?? 0) + 1;
                    $service = Service::create($serviceData);
                }

                $service->costings()->delete();
                foreach ($validated['costings'] as $costing) {
                    $service->costings()->create([
                        'accountid' => $userAccountId,
                        'currency_code' => strtoupper($costing['currency_code']),
                        'cost_price' => $costing['cost_price'],
                        'selling_price' => $costing['selling_price'],
                        'sac_code' => $costing['sac_code'] ?? null,
                        'tax_rate' => $costing['tax_rate'] ?? 0,
                        'tax_included' => $costing['tax_included'],
                    ]);
                }

                return $service;
            });

            return response()->json([
                'success' => true,
                'message' => 'Service saved successfully.',
                'serviceid' => $service->serviceid,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save service: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function addonsSaveAjax(Request $request)
    {
        try {
            $validated = $request->validate([
                'serviceid' => 'required|string|exists:services,serviceid',
                'addonid' => 'nullable|string|exists:service_addons,addonid',
                'name' => 'required|string|max:150',
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
            $addon = DB::transaction(function () use ($validated, $userAccountId) {
                $addonData = [
                    'accountid' => $userAccountId,
                    'serviceid' => $validated['serviceid'],
                    'name' => trim($validated['name']),
                    'description' => $validated['description'] ?? null,
                    'is_active' => true,
                ];

                if (!empty($validated['addonid'])) {
                    $addon = ServiceAddon::where('addonid', $validated['addonid'])->firstOrFail();
                    $addon->update($addonData);
                } else {
                    $addonData['sequence'] = (ServiceAddon::where('serviceid', $validated['serviceid'])->max('sequence') ?? 0) + 1;
                    $addon = ServiceAddon::create($addonData);
                }

                $addon->costings()->delete();
                foreach ($validated['costings'] as $costing) {
                    $addon->costings()->create([
                        'accountid' => $userAccountId,
                        'currency_code' => strtoupper($costing['currency_code']),
                        'cost_price' => $costing['cost_price'],
                        'selling_price' => $costing['selling_price'],
                        'sac_code' => $costing['sac_code'] ?? null,
                        'tax_rate' => $costing['tax_rate'] ?? 0,
                        'tax_included' => $costing['tax_included'],
                    ]);
                }

                return $addon;
            });

            return response()->json([
                'success' => true,
                'message' => 'Add-on item saved successfully.',
                'addonid' => $addon->addonid,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save add-on: ' . $e->getMessage(),
            ], 500);
        }
    }
}

