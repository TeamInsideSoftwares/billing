<?php

namespace App\Http\Controllers;

use App\Models\ClientCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientCategoriesController extends Controller
{
    private function categoriesJsonResponse(string $accountId, string $message): JsonResponse
    {
        $categories = ClientCategory::where('accountid', $accountId)->orderBy('sequence')->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'message' => $message,
            'categories' => $categories->map(function ($c) {
                return [
                    'categoryid' => $c->categoryid,
                    'name' => $c->name,
                    'sequence' => $c->sequence,
                ];
            }),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
        ]);

        $userAccountId = $this->resolveAccountId();
        $validated['accountid'] = $userAccountId;

        $maxSeq = ClientCategory::where('accountid', $userAccountId)->max('sequence');
        $validated['sequence'] = $maxSeq ? $maxSeq + 1 : 1;

        ClientCategory::create($validated);

        if ($request->ajax() || $request->wantsJson()) {
            return $this->categoriesJsonResponse($userAccountId, 'Category created successfully.');
        }

        return redirect()->back()->with('success', 'Category created successfully.')->with('open_category_modal', true);
    }

    public function update(Request $request, $id)
    {
        $userAccountId = $this->resolveAccountId();
        $category = ClientCategory::where('categoryid', $id)->where('accountid', $userAccountId)->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string',
        ]);

        $category->update($validated);

        if ($request->ajax() || $request->wantsJson()) {
            return $this->categoriesJsonResponse($userAccountId, 'Category updated successfully.');
        }

        return redirect()->back()->with('success', 'Category updated successfully.')->with('open_category_modal', true);
    }

    public function destroy($id)
    {
        $userAccountId = $this->resolveAccountId();
        $category = ClientCategory::where('categoryid', $id)->where('accountid', $userAccountId)->firstOrFail();

        $category->delete();

        if (request()->ajax() || request()->wantsJson()) {
            return $this->categoriesJsonResponse($userAccountId, 'Category deleted successfully.');
        }

        return redirect()->back()->with('success', 'Category deleted successfully.')->with('open_category_modal', true);
    }

    public function updateSequence(Request $request, $id)
    {
        $userAccountId = $this->resolveAccountId();
        $category = ClientCategory::where('categoryid', $id)->where('accountid', $userAccountId)->firstOrFail();

        $validated = $request->validate([
            'sequence' => 'required|integer|min:1',
        ]);

        $newSequence = $validated['sequence'];
        $oldSequence = $category->sequence;

        if ($newSequence != $oldSequence) {
            $targetCategory = ClientCategory::where('accountid', $userAccountId)
                ->where('sequence', $newSequence)
                ->first();

            if ($targetCategory) {
                $targetCategory->update(['sequence' => 9999]);
                $category->update(['sequence' => $newSequence]);
                $targetCategory->update(['sequence' => $oldSequence]);
            } else {
                $category->update(['sequence' => $newSequence]);
            }
        }

        if ($request->ajax() || $request->wantsJson()) {
            return $this->categoriesJsonResponse($userAccountId, 'Sequence updated successfully.');
        }

        return redirect()->back()->with('success', 'Sequence updated successfully.')->with('open_category_modal', true);
    }
}
