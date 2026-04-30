<?php

namespace App\Http\Controllers;

use App\Models\ProductCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductCategoriesController extends Controller
{
    public function productCategories(): View
    {
        $query = ProductCategory::query()->orderBy('sequence')->orderBy('name');
        $searchTerm = request('search', '');
        if ($searchTerm) {
            $query->where('name', 'like', '%' . $searchTerm . '%');
        }
        $resultCount = $query->count();
        $productCategories = $query->take(20)->get()->map(function ($pc) {
            return [
                'record_id' => $pc->ps_catid,
                'name' => $pc->name,
                'sequence' => (int) ($pc->sequence ?? 0),
                'description' => Str::limit($pc->description ?? '', 50),
                'status' => ucfirst($pc->status ?? 'Active'),
            ];
        });

        return view('product-categories.index', [
            'title' => 'Product Categories',
            'productCategories' => $productCategories,
            'searchTerm' => $searchTerm,
            'resultCount' => $resultCount,
        ]);
    }

    public function productCategoriesCreate(): View
    {
        return view('product-categories.form', ['title' => 'New Product Category']);
    }

    public function productCategoriesStore(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'sequence' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'status' => 'in:active,inactive',
        ]);

        $userAccountId = auth()->check() ? (auth()->user()->accountid ?? 'ACC0000001') : 'ACC0000001';
        $validated['accountid'] = $userAccountId;
        $validated['sequence'] = $validated['sequence'] ?? ((ProductCategory::max('sequence') ?? 0) + 1);

        ProductCategory::create($validated);

        return redirect()->back()->with('success', 'Product category created successfully.')->with('open_cat_modal', true);
    }

    public function productCategoriesShow(ProductCategory $productCategory): View
    {
        return view('product-categories.show', [
            'title' => 'Product Category Details',
            'productCategory' => $productCategory,
        ]);
    }

    public function productCategoriesEdit(ProductCategory $productCategory): View
    {
        return view('product-categories.form', ['title' => 'Edit Product Category', 'productCategory' => $productCategory]);
    }

    public function productCategoriesUpdate(Request $request, $id)
    {
        $category = ProductCategory::where('ps_catid', $id)->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'sequence' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'status' => 'in:active,inactive',
        ]);

        $validated['sequence'] = $validated['sequence'] ?? ($category->sequence ?? 0);
        $category->update($validated);

        return redirect()->back()->with('success', 'Product category updated successfully.')->with('open_cat_modal', true);
    }

    public function productCategoriesDestroy(ProductCategory $productCategory)
    {
        $productCategory->delete();

        return redirect()->back()->with('success', 'Product category deleted successfully.')->with('open_cat_modal', true);
    }
}
