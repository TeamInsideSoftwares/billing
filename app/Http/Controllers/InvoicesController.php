<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Service;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class InvoicesController extends Controller
{
    public function invoices(): View
    {
        $query = Invoice::with('client');
        $searchTerm = request('search', '');

        if ($searchTerm) {
            $query->where('invoice_number', 'like', '%' . $searchTerm . '%')
                ->orWhereHas('client', function ($q) use ($searchTerm) {
                    $q->where('business_name', 'like', '%' . $searchTerm . '%')
                        ->orWhere('contact_name', 'like', '%' . $searchTerm . '%');
                });
        }
        $resultCount = $query->count();

        $invoices = $query->latest()->take(20)->get()->map(function ($invoice) {
            return [
                'record_id' => $invoice->invoiceid,
                'number' => $invoice->invoice_number ?? 'INV-' . str_pad($invoice->invoiceid, 4, '0', STR_PAD_LEFT),
                'client' => $invoice->client->business_name ?? 'Client',
                'issued' => $invoice->created_at->format('d M Y'),
                'due' => $invoice->due_date?->format('d M Y') ?? 'N/A',
                'amount' => 'Rs ' . number_format($invoice->grand_total ?? 0),
                'status' => $invoice->status ?? 'Draft',
            ];
        });

        return view('invoices.index', [
            'title' => 'Invoices',
            'invoices' => $invoices,
            'searchTerm' => $searchTerm,
            'resultCount' => $resultCount,
        ]);
    }

    public function invoicesCreate(): View
    {
        return view('invoices.create', [
            'title' => 'Create Invoice',
            'clients' => Client::all(),
            'services' => Service::orderBy('sequence')->orderBy('name')->get(),
        ]);
    }

    public function invoicesStore(Request $request)
    {
        $validated = $request->validate([
            'clientid' => 'required|exists:clients,clientid',
            'invoice_number' => 'required|string|unique:invoices,invoice_number',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'notes' => 'nullable|string',
            'status' => 'required|in:draft,sent,paid,overdue,cancelled',
            'subtotal' => 'required|numeric|min:0',
            'tax_total' => 'required|numeric|min:0',
            'grand_total' => 'required|numeric|min:0',
            'items_data' => 'required|json',
            'accountid' => 'nullable|size:10',
        ]);

        $userAccountId = auth()->check() ? (auth()->user()->accountid ?? 'ACC0000001') : 'ACC0000001';
        $validated['accountid'] = $validated['accountid'] ?? $userAccountId;
        unset($validated['items_data']);

        $itemsData = json_decode($request->items_data, true);
        $subtotal = 0;
        $taxTotal = 0;
        foreach ($itemsData as $itemData) {
            $subtotal += $itemData['line_total'];
            $taxTotal += $itemData['line_total'] * ($itemData['tax_rate'] / 100);
        }
        $grandTotal = $subtotal + $taxTotal;
        $validated['subtotal'] = $subtotal;
        $validated['tax_total'] = $taxTotal;
        $validated['grand_total'] = $grandTotal;
        $validated['balance_due'] = $grandTotal;

        $invoice = Invoice::create($validated);

        foreach ($itemsData as $index => $itemData) {
            $service = Service::find($itemData['itemid']);
            InvoiceItem::create([
                'invoiceid' => $invoice->invoiceid,
                'itemid' => $itemData['itemid'],
                'item_name' => $service?->name ?? 'Custom Item',
                'item_description' => null,
                'quantity' => $itemData['quantity'],
                'unit_price' => $itemData['unit_price'],
                'tax_rate' => $itemData['tax_rate'],
                'line_total' => $itemData['line_total'],
                'sort_order' => $index + 1,
            ]);
        }

        return redirect()->route('invoices.index')->with('success', 'Invoice created successfully with items.');
    }

    public function invoicesShow(Invoice $invoice): View
    {
        $invoice->load(['client', 'items.service', 'payments']);
        return view('invoices.show', ['title' => 'Invoice Details', 'invoice' => $invoice]);
    }

    public function invoicesEdit(Invoice $invoice): View
    {
        $invoice->load(['items.service']);
        return view('invoices.edit', [
            'title' => 'Edit Invoice',
            'invoice' => $invoice,
            'clients' => Client::all(),
            'services' => Service::orderBy('sequence')->orderBy('name')->get(),
            'items' => $invoice->items,
        ]);
    }

    public function invoicesUpdate(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'clientid' => 'required|exists:clients,clientid',
            'invoice_number' => 'required|string|unique:invoices,invoice_number,' . $invoice->invoiceid . ',invoiceid',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'notes' => 'nullable|string',
            'status' => 'required|in:draft,sent,paid,overdue,cancelled',
            'items_data' => 'required|json',
        ]);

        $itemsData = json_decode($request->items_data, true);
        $subtotal = 0;
        $taxTotal = 0;
        foreach ($itemsData as $itemData) {
            $subtotal += $itemData['line_total'];
            $taxTotal += $itemData['line_total'] * ($itemData['tax_rate'] / 100);
        }
        $grandTotal = $subtotal + $taxTotal;

        $invoice->update([
            'clientid' => $validated['clientid'],
            'invoice_number' => $validated['invoice_number'],
            'issue_date' => $validated['issue_date'],
            'due_date' => $validated['due_date'],
            'notes' => $validated['notes'],
            'status' => $validated['status'],
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'grand_total' => $grandTotal,
            'balance_due' => $grandTotal,
        ]);

        $invoice->items()->delete();

        foreach ($itemsData as $index => $itemData) {
            $service = Service::find($itemData['itemid']);
            InvoiceItem::create([
                'invoiceid' => $invoice->invoiceid,
                'itemid' => $itemData['itemid'],
                'item_name' => $service?->name ?? 'Custom Item',
                'item_description' => null,
                'quantity' => $itemData['quantity'],
                'unit_price' => $itemData['unit_price'],
                'tax_rate' => $itemData['tax_rate'],
                'line_total' => $itemData['line_total'],
                'sort_order' => $index + 1,
            ]);
        }

        return redirect()->route('invoices.index')->with('success', 'Invoice updated successfully.');
    }

    public function invoicesDestroy(Invoice $invoice)
    {
        $invoice->delete();

        return redirect()->route('invoices.index')->with('success', 'Invoice deleted successfully.');
    }
}

