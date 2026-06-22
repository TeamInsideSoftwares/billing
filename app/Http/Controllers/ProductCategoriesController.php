<?php

namespace App\Http\Controllers;

use App\Models\ProductCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductCategoriesController extends Controller
{
    private function categoriesJsonResponse(string $accountId, string $message): JsonResponse
    {
        $categories = ProductCategory::where('accountid', $accountId)->orderBy('sequence')->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'message' => $message,
            'categories' => $categories->map(function ($c) {
                return [
                    'record_id' => $c->ps_catid,
                    'name' => $c->name,
                    'description' => $c->description ?? '',
                    'status' => strtolower($c->status ?? 'active'),
                ];
            }),
        ]);
    }

    public function productCategoriesStore(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'sequence' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'status' => 'in:active,inactive',
        ]);

        $userAccountId = $this->resolveAccountId();
        $validated['accountid'] = $userAccountId;
        $validated['sequence'] = $validated['sequence'] ?? ((ProductCategory::max('sequence') ?? 0) + 1);

        $category = ProductCategory::create($validated);

        if ($request->expectsJson() || $request->ajax()) {
            return $this->categoriesJsonResponse($userAccountId, 'Product category created successfully.');
        }

        return redirect()->back()->with('success', 'Product category created successfully.')->with('open_cat_modal', true);
    }

    public function productCategoriesUpdate(Request $request, $id)
    {
        $userAccountId = $this->resolveAccountId();
        $category = ProductCategory::where('ps_catid', $id)->where('accountid', $userAccountId)->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'sequence' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'status' => 'in:active,inactive',
        ]);

        $validated['sequence'] = $validated['sequence'] ?? ($category->sequence ?? 0);
        $category->update($validated);

        if ($request->ajax() || $request->wantsJson()) {
            return $this->categoriesJsonResponse($userAccountId, 'Product category updated successfully.');
        }

        return redirect()->back()->with('success', 'Product category updated successfully.')->with('open_cat_modal', true);
    }

    public function productCategoriesDestroy(ProductCategory $productCategory)
    {
        if ($productCategory->accountid !== $this->resolveAccountId()) {
            abort(403);
        }
        $productCategory->delete();

        if (request()->ajax() || request()->wantsJson()) {
            return $this->categoriesJsonResponse($this->resolveAccountId(), 'Product category deleted successfully.');
        }

        return redirect()->back()->with('success', 'Product category deleted successfully.')->with('open_cat_modal', true);
    }
}
