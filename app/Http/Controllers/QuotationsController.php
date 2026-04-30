<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Client;
use App\Models\Quotation;
use App\Models\Tax;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class QuotationsController extends Controller
{
    public function quotations(): View
    {
        $query = Quotation::with('client');
        $searchTerm = request('search', '');

        if ($searchTerm) {
            $query->where('quotation_number', 'like', '%' . $searchTerm . '%')
                ->orWhereHas('client', function ($q) use ($searchTerm) {
                    $q->where('business_name', 'like', '%' . $searchTerm . '%')
                        ->orWhere('contact_name', 'like', '%' . $searchTerm . '%');
                });
        }
        $resultCount = $query->count();

        $quotations = $query->latest()->take(20)->get()->map(function ($quotation) {
            return [
                'record_id' => $quotation->quotationid,
                'number' => $quotation->quotation_number ?? 'QUO-' . str_pad($quotation->quotationid, 4, '0', STR_PAD_LEFT),
                'client' => $quotation->client->business_name ?? 'Client',
                'amount' => 'Rs ' . number_format($quotation->total ?? 0),
                'expiry' => $quotation->expiry_date?->format('d M Y') ?? 'N/A',
                'status' => $quotation->status ?? 'Draft',
            ];
        });

        return view('quotations.index', [
            'title' => 'All Quotations',
            'subtitle' => $searchTerm ? 'Search results for "' . $searchTerm . '"' : null,
            'quotations' => $quotations,
            'searchTerm' => $searchTerm,
            'resultCount' => $resultCount,
        ]);
    }

    public function quotationsCreate(): View
    {
        $accountid = auth()->check() ? (auth()->user()->accountid ?? 'ACC0000001') : 'ACC0000001';
        $account = Account::find($accountid);

        return view('quotations.form', [
            'title' => 'Create New Quotation',
            'clients' => Client::all(),
            'taxes' => ($account && $account->allow_multi_taxation) ? Tax::where('accountid', $accountid)->where('is_active', true)->orderByRaw('COALESCE(sequence, 999999), created_at DESC')->get() : collect(),
            'account' => $account,
        ]);
    }

    public function quotationsStore(Request $request)
    {
        $validated = $request->validate([
            'clientid' => 'required|exists:clients,clientid',
            'quotation_number' => 'required|string|unique:quotations,quotation_number',
            'issue_date' => 'required|date',
            'expiry_date' => 'nullable|date|after_or_equal:issue_date',
            'accountid' => 'nullable|size:10',
            'status' => 'required|in:draft,sent,accepted,declined,expired',
        ]);

        $userAccountId = auth()->check() ? (auth()->user()->accountid ?? 'ACC0000001') : 'ACC0000001';
        $validated['accountid'] = $validated['accountid'] ?? $userAccountId;

        Quotation::create($validated);

        return redirect()->route('quotations.index')->with('success', 'Quotation created successfully.');
    }

    public function quotationsShow(Quotation $quotation): View
    {
        $quotation->load('client');
        return view('quotations.show', [
            'title' => $quotation->quotation_number ?? 'Quotation',
            'subtitle' => 'Quotation Details',
            'quotation' => $quotation,
        ]);
    }

    public function quotationsEdit(Quotation $quotation): View
    {
        $accountid = auth()->check() ? (auth()->user()->accountid ?? 'ACC0000001') : 'ACC0000001';
        $account = Account::find($accountid);

        return view('quotations.form', [
            'title' => 'Edit ' . ($quotation->quotation_number ?? 'Quotation'),
            'quotation' => $quotation,
            'clients' => Client::all(),
            'taxes' => ($account && $account->allow_multi_taxation) ? Tax::where('accountid', $accountid)->where('is_active', true)->orderByRaw('COALESCE(sequence, 999999), created_at DESC')->get() : collect(),
            'account' => $account,
        ]);
    }

    public function quotationsUpdate(Request $request, Quotation $quotation)
    {
        $validated = $request->validate([
            'clientid' => 'required|exists:clients,clientid',
            'quotation_number' => 'required|string|unique:quotations,quotation_number,' . $quotation->getKey() . ',quotationid',
            'issue_date' => 'required|date',
            'expiry_date' => 'nullable|date|after_or_equal:issue_date',
            'status' => 'required|in:draft,sent,accepted,declined,expired',
        ]);

        $quotation->update($validated);

        return redirect()->route('quotations.index')->with('success', 'Quotation updated successfully.');
    }

    public function quotationsDestroy(Quotation $quotation)
    {
        $quotation->delete();

        return redirect()->route('quotations.index')->with('success', 'Quotation deleted successfully.');
    }
}
