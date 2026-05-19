<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Client;
use App\Models\ClientBillingDetail;
use App\Models\ClientDocument;
use App\Models\Group;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ClientsController extends Controller
{
    public function clients(): View
    {
        $accountId = $this->resolveAccountId();
        $query = Client::query()->where('accountid', $accountId)->with(['invoices.items', 'payments']);
        $searchTerm = trim((string) request('search', ''));
        $selectedState = trim((string) request('state', ''));
        $selectedCity = trim((string) request('city', ''));

        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('business_name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('contact_name', 'like', '%' . $searchTerm . '%');
            });
        }

        if ($selectedState !== '') {
            $query->where('state', $selectedState);
        }

        if ($selectedCity !== '') {
            $query->where('city', $selectedCity);
        }
        $resultCount = $query->count();

        $clients = $query->latest()->take(20)->get()->map(function ($client) {
            $invoiceTotal = $client->invoices
                ->where('status', '!=', 'cancelled')
                ->where('payment_status', '!=', 'paid')
                ->sum(fn ($invoice) => (float) ($invoice->grand_total ?? 0));
            $paidTotal = (float) $client->payments->sum(function ($payment) {
                return (float) ($payment->received_amount ?? 0);
            });
            $outstanding = $invoiceTotal - $paidTotal;
            $account = Account::find($client->accountid);
            $cur = $account?->currency_code ?? 'INR';
            return [
                'record_id' => $client->clientid,
                'name' => $client->business_name ?? $client->contact_name,
                'contact' => $client->contact_name,
                'email' => $client->email,
                'phone' => $client->phone,
                'state' => $client->state,
                'city' => $client->city,
                'currency' => $cur,
                'status' => $client->status ?? 'Active',
                'balance' => $cur . ' ' . number_format($outstanding, 0),
                'created_at' => $client->created_at,
                'invoice_count' => $client->invoices->count(),
            ];
        });

        $groups = Group::where('accountid', $accountId)->orderBy('group_name')->get();
        $stateOptions = Client::query()
            ->where('accountid', $accountId)
            ->whereNotNull('state')
            ->where('state', '!=', '')
            ->select('state')
            ->distinct()
            ->orderBy('state')
            ->pluck('state');
        $cityOptions = Client::query()
            ->where('accountid', $accountId)
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->when($selectedState !== '', fn ($cityQuery) => $cityQuery->where('state', $selectedState))
            ->select('city')
            ->distinct()
            ->orderBy('city')
            ->pluck('city');

        return view('clients.index', [
            'title' => 'All Clients',
            'subtitle' => $searchTerm ? 'Found ' . $resultCount . ' result(s) for "' . $searchTerm . '"' : null,
            'clients' => $clients,
            'groups' => $groups,
            'searchTerm' => $searchTerm,
            'resultCount' => $resultCount,
            'selectedState' => $selectedState,
            'selectedCity' => $selectedCity,
            'stateOptions' => $stateOptions,
            'cityOptions' => $cityOptions,
        ]);
    }

    public function clientsCreate(): View
    {
        $accountId = $this->resolveAccountId();
        $billingProfiles = ClientBillingDetail::query()
            ->where('accountid', $accountId)
            ->orderBy('business_name')
            ->get();
        $currencies = DB::table('currency')
            ->orderBy('iso')
            ->get(['iso', 'name']);

        return view('clients.form', [
            'title' => 'Create New Client',
            'accounts' => Account::where('status', 'active')->get(),
            'groups' => Group::where('accountid', $accountId)->get(),
            'billingProfiles' => $billingProfiles,
            'currencies' => $currencies,
        ]);
    }

    public function clientsStore(Request $request)
    {
        $userAccountId = $this->resolveAccountId();

        $validated = $request->validate([
            'accountid' => 'required|string|exists:accounts,accountid|max:10',
            'business_name' => 'required|string',
            'groupid' => 'nullable|exists:groups,groupid',
            'contact_name' => 'nullable|string',
            'email' => 'required|string|max:150',
            'phone' => 'nullable|string|max:50',
            'whatsapp_number' => 'nullable|string|max:50',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'status' => 'in:active,review,inactive',
            'currency' => 'required|string|size:3|exists:currency,iso',
            'country' => 'nullable|string',
            'state' => 'required|string',
            'city' => 'nullable|string',
            'postal_code' => 'nullable|string|max:20',
            'address_line_1' => 'nullable|string',
            'existing_bd_id' => 'nullable|string|size:6|exists:client_billing_details,bd_id',
            'billing_business_name' => 'required|string',
            'billing_gstin' => 'nullable|string|size:15',
            'billing_email' => 'nullable|string|max:150',
            'billing_city' => 'nullable|string',
            'billing_state' => 'required|string',
            'billing_country' => 'nullable|string',
            'billing_postal_code' => 'nullable|string',
            'billing_address_line_1' => 'nullable|string',
            'billing_phone' => 'nullable|string',
        ]);

        $validated['email'] = $this->normalizeClientEmails((string) ($validated['email'] ?? ''));

        // Normalize billing_email if multiple addresses provided
        if (!empty($validated['billing_email'])) {
            $validated['billing_email'] = $this->normalizeClientEmails((string) $validated['billing_email']);
            if (strlen($validated['billing_email']) > 150) {
                throw ValidationException::withMessages(['billing_email' => 'Combined billing emails exceed 150 characters.']);
            }
        } else {
            $validated['billing_email'] = null;
        }

        $validated['billing_phone'] = isset($validated['billing_phone'])
            ? trim((string) $validated['billing_phone'])
            : null;
        if ($validated['billing_phone'] === '') {
            $validated['billing_phone'] = null;
        }

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos', 'public');
            $baseUrl = rtrim(config('app.url'), '/');
            $validated['logo_path'] = $baseUrl . '/public/storage/' . $path;
        }

        $validated['accountid'] = $validated['accountid'] ?? $userAccountId;

        $selectedBillingDetail = null;

        if (!empty($validated['existing_bd_id'])) {
            $selectedBillingDetail = ClientBillingDetail::query()
                ->where('bd_id', $validated['existing_bd_id'] ?? '')
                ->where('accountid', $validated['accountid'])
                ->first();

            if (!$selectedBillingDetail) {
                return back()->withInput()->withErrors([
                    'existing_bd_id' => 'Selected billing details are invalid for this account.',
                ]);
            }

            $selectedBillingDetail->update([
                'business_name' => $validated['billing_business_name'],
                'gstin' => $validated['billing_gstin'] ?? null,
                'billing_email' => $validated['billing_email'] ?? null,
                'city' => $validated['billing_city'] ?? null,
                'state' => $validated['billing_state'] ?? null,
                'country' => $validated['billing_country'] ?? 'India',
                'postal_code' => $validated['billing_postal_code'] ?? null,
                'billing_phone' => $validated['billing_phone'] ?? null,
                'address_line_1' => $validated['billing_address_line_1'] ?? null,
            ]);
        } else {
            $selectedBillingDetail = ClientBillingDetail::create([
                'bd_id' => Group::generateUniqueAlphaId(new Group()),
                'accountid' => $validated['accountid'],
                'business_name' => $validated['billing_business_name'],
                'gstin' => $validated['billing_gstin'] ?? null,
                'billing_email' => $validated['billing_email'] ?? null,
                'city' => $validated['billing_city'] ?? null,
                'state' => $validated['billing_state'] ?? null,
                'country' => $validated['billing_country'] ?? 'India',
                'postal_code' => $validated['billing_postal_code'] ?? null,
                'billing_phone' => $validated['billing_phone'] ?? null,
                'address_line_1' => $validated['billing_address_line_1'] ?? null,
            ]);
        }

        $validated['bd_id'] = $selectedBillingDetail->bd_id;
        $clientData = collect($validated)->except([
            'existing_bd_id',
            'billing_business_name',
            'billing_gstin',
            'billing_email',
            'billing_city',
            'billing_state',
            'billing_country',
            'billing_postal_code',
            'billing_address_line_1',
            'billing_phone',
        ])->all();
        Client::create($clientData);

        return redirect()->route('clients.index')->with('success', 'Client created successfully.');
    }

    public function clientsShow(Client $client): View
    {
        $client->load(['invoices', 'payments', 'billingDetail', 'documents']);
        $paidTotal = (float) $client->payments->sum(function ($payment) {
            return (float) ($payment->received_amount ?? 0);
        });
        $outstanding = ($client->invoices->sum('grand_total') ?? 0) - $paidTotal;
        $allInvoices = $client->invoices->sortByDesc('created_at')->values();

        return view('clients.show', [
            'title' => $client->business_name ?? $client->contact_name ?? 'Client',
            'subtitle' => 'Client Details',
            'client' => $client,
            'outstanding' => $outstanding,
            'allInvoices' => $allInvoices,
        ]);
    }

    public function clientsDocumentsCreate(Request $request, Client $client): View
    {
        $accountId = $this->resolveAccountId();
        if ((string) $client->accountid !== $accountId) {
            abort(404);
        }

        $focusType = strtolower((string) $request->query('type', 'po'));
        if (!in_array($focusType, ['po', 'agreement'], true)) {
            $focusType = 'po';
        }

        $client->load(['documents' => function ($query) {
            $query->latest('document_date')
                ->latest('created_at');
        }]);

        $editDocument = null;
        $editId = (string) $request->query('edit', '');
        if ($editId !== '') {
            $candidate = $client->documents->firstWhere('client_docid', $editId);
            if (
                $candidate &&
                (string) $candidate->accountid === $accountId &&
                ($candidate->status ?? 'active') !== 'cancelled'
            ) {
                $editDocument = $candidate;
                $focusType = $candidate->type;
            }
        }

        return view('clients.documents-form', [
            'title' => 'PO & Agreements for ' . ($client->business_name ?? $client->contact_name ?? 'Client'),
            'client' => $client,
            'focusType' => $focusType,
            'editDocument' => $editDocument,
            'poDocuments' => $client->documents->where('type', 'po')->values(),
            'agreementDocuments' => $client->documents->where('type', 'agreement')->values(),
        ]);
    }

    public function clientsDocumentsStore(Request $request, Client $client)
    {
        $accountId = $this->resolveAccountId();
        if ((string) $client->accountid !== $accountId) {
            abort(404);
        }

        $validated = $request->validate([
            'type' => 'required|in:po,agreement',
            'title' => 'nullable|string|max:150',
            'document_number' => 'nullable|string|max:100',
            'document_date' => 'nullable|date',
            'file' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
        ]);

        $filePath = null;
        if ($request->hasFile('file')) {
            $folder = $validated['type'] === 'po' ? 'client-documents/po' : 'client-documents/agreements';
            $filePath = $request->file('file')->store($folder, 'public');
        }

        ClientDocument::create([
            'accountid' => $accountId,
            'clientid' => $client->clientid,
            'type' => $validated['type'],
            'status' => 'active',
            'title' => $validated['title'] ?? null,
            'document_number' => $validated['document_number'] ?? null,
            'document_date' => $validated['document_date'] ?? null,
            'file_path' => $filePath,
        ]);

        return redirect()
            ->route('clients.documents.create', ['client' => $client->clientid, 'type' => $validated['type']])
            ->with('success', ucfirst($validated['type']) . ' saved successfully.');
    }

    public function clientsDocumentsUpdate(Request $request, Client $client, ClientDocument $document)
    {
        $accountId = $this->resolveAccountId();
        if (
            (string) $client->accountid !== $accountId ||
            (string) $document->accountid !== $accountId ||
            (string) $document->clientid !== (string) $client->clientid
        ) {
            abort(404);
        }

        if (($document->status ?? 'active') === 'cancelled') {
            return redirect()
                ->route('clients.documents.create', ['client' => $client->clientid, 'type' => $document->type])
                ->with('error', ucfirst($document->type) . ' is cancelled. Restore it before editing.');
        }

        $validated = $request->validate([
            'type' => 'required|in:po,agreement',
            'title' => 'nullable|string|max:150',
            'document_number' => 'nullable|string|max:100',
            'document_date' => 'nullable|date',
            'file' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
        ]);

        $filePath = $document->file_path;
        if ($request->hasFile('file')) {
            if ($filePath) {
                Storage::disk('public')->delete($filePath);
            }

            $folder = $validated['type'] === 'po' ? 'client-documents/po' : 'client-documents/agreements';
            $filePath = $request->file('file')->store($folder, 'public');
        }

        $document->update([
            'type' => $validated['type'],
            'title' => $validated['title'] ?? null,
            'document_number' => $validated['document_number'] ?? null,
            'document_date' => $validated['document_date'] ?? null,
            'file_path' => $filePath,
            'status' => 'active',
        ]);

        return redirect()
            ->route('clients.documents.create', ['client' => $client->clientid, 'type' => $validated['type']])
            ->with('success', ucfirst($validated['type']) . ' updated successfully.');
    }

    public function clientsDocumentsCancel(Client $client, ClientDocument $document)
    {
        $accountId = $this->resolveAccountId();
        if (
            (string) $client->accountid !== $accountId ||
            (string) $document->accountid !== $accountId ||
            (string) $document->clientid !== (string) $client->clientid
        ) {
            abort(404);
        }

        $document->update(['status' => 'cancelled']);

        return redirect()
            ->route('clients.documents.create', ['client' => $client->clientid, 'type' => $document->type])
            ->with('success', ucfirst($document->type) . ' cancelled successfully.');
    }

    public function clientsDocumentsRestore(Client $client, ClientDocument $document)
    {
        $accountId = $this->resolveAccountId();
        if (
            (string) $client->accountid !== $accountId ||
            (string) $document->accountid !== $accountId ||
            (string) $document->clientid !== (string) $client->clientid
        ) {
            abort(404);
        }

        $document->update(['status' => 'active']);

        return redirect()
            ->route('clients.documents.create', ['client' => $client->clientid, 'type' => $document->type])
            ->with('success', ucfirst($document->type) . ' restored successfully.');
    }

    public function clientsDocumentsFile(Client $client, ClientDocument $document)
    {
        $accountId = $this->resolveAccountId();
        if (
            (string) $client->accountid !== $accountId ||
            (string) $document->accountid !== $accountId ||
            (string) $document->clientid !== (string) $client->clientid
        ) {
            abort(404);
        }

        if (!$document->file_path || !Storage::disk('public')->exists($document->file_path)) {
            abort(404);
        }

        return Storage::disk('public')->response($document->file_path);
    }

    public function clientsEdit(Client $client): View
    {
        $accountId = $client->accountid ?: $this->resolveAccountId();
        $billingProfiles = ClientBillingDetail::query()
            ->where('accountid', $accountId)
            ->orderBy('business_name')
            ->get();
        $currencies = DB::table('currency')
            ->orderBy('iso')
            ->get(['iso', 'name']);

        return view('clients.form', [
            'title' => 'Edit ' . ($client->business_name ?? $client->contact_name ?? 'Client'),
            'client' => $client,
            'accounts' => Account::where('status', 'active')->get(),
            'groups' => Group::where('accountid', $accountId)->get(),
            'billingProfiles' => $billingProfiles,
            'currencies' => $currencies,
        ]);
    }

    public function clientsUpdate(Request $request, Client $client)
    {
        $validated = $request->validate([
            'business_name' => 'required|string',
            'groupid' => 'nullable|exists:groups,groupid',
            'contact_name' => 'nullable|string',
            'email' => 'required|string|max:150',
            'phone' => 'nullable|string|max:50',
            'whatsapp_number' => 'nullable|string|max:50',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'status' => 'in:active,review,inactive',
            'currency' => 'required|string|size:3|exists:currency,iso',
            'country' => 'nullable|string',
            'state' => 'required|string',
            'city' => 'nullable|string',
            'postal_code' => 'nullable|string|max:20',
            'address_line_1' => 'nullable|string',
            'existing_bd_id' => 'nullable|string|size:6|exists:client_billing_details,bd_id',
            'billing_business_name' => 'required|string',
            'billing_gstin' => 'nullable|string|size:15',
            'billing_email' => 'nullable|string|max:150',
            'billing_city' => 'nullable|string',
            'billing_state' => 'required|string',
            'billing_country' => 'nullable|string',
            'billing_postal_code' => 'nullable|string',
            'billing_address_line_1' => 'nullable|string',
            'billing_phone' => 'nullable|string',
        ]);

        $validated['email'] = $this->normalizeClientEmails((string) ($validated['email'] ?? ''));

        // Normalize billing_email if multiple addresses provided
        if (!empty($validated['billing_email'])) {
            $validated['billing_email'] = $this->normalizeClientEmails((string) $validated['billing_email']);
            if (strlen($validated['billing_email']) > 150) {
                throw ValidationException::withMessages(['billing_email' => 'Combined billing emails exceed 150 characters.']);
            }
        } else {
            $validated['billing_email'] = null;
        }

        $validated['billing_phone'] = isset($validated['billing_phone'])
            ? trim((string) $validated['billing_phone'])
            : null;
        if ($validated['billing_phone'] === '') {
            $validated['billing_phone'] = null;
        }

        if ($request->hasFile('logo')) {
            if ($client->logo_path) {
                $storageBase = rtrim(config('app.url'), '/') . '/public/storage/';
                $oldPath = str_replace($storageBase, '', $client->logo_path);
                Storage::disk('public')->delete($oldPath);
            }
            $path = $request->file('logo')->store('logos', 'public');
            $baseUrl = rtrim(config('app.url'), '/');
            $validated['logo_path'] = $baseUrl . '/public/storage/' . $path;
        }

        $selectedBdId = $client->bd_id;

        if (!empty($validated['existing_bd_id'])) {
            $existingBillingDetail = ClientBillingDetail::query()
                ->where('bd_id', $validated['existing_bd_id'] ?? '')
                ->where('accountid', $client->accountid)
                ->first();

            if (!$existingBillingDetail) {
                return back()->withInput()->withErrors([
                    'existing_bd_id' => 'Selected billing details are invalid for this account.',
                ]);
            }

            $existingBillingDetail->update([
                'business_name' => $validated['billing_business_name'],
                'gstin' => $validated['billing_gstin'] ?? null,
                'billing_email' => $validated['billing_email'] ?? null,
                'city' => $validated['billing_city'] ?? null,
                'state' => $validated['billing_state'] ?? null,
                'country' => $validated['billing_country'] ?? 'India',
                'postal_code' => $validated['billing_postal_code'] ?? null,
                'billing_phone' => $validated['billing_phone'] ?? null,
                'address_line_1' => $validated['billing_address_line_1'] ?? null,
            ]);
            $selectedBdId = $existingBillingDetail->bd_id;
        } else {
            $billingData = [
                'accountid' => $client->accountid,
                'business_name' => $validated['billing_business_name'],
                'gstin' => $validated['billing_gstin'] ?? null,
                'billing_email' => $validated['billing_email'] ?? null,
                'city' => $validated['billing_city'] ?? null,
                'state' => $validated['billing_state'] ?? null,
                'country' => $validated['billing_country'] ?? 'India',
                'postal_code' => $validated['billing_postal_code'] ?? null,
                'billing_phone' => $validated['billing_phone'] ?? null,
                'address_line_1' => $validated['billing_address_line_1'] ?? null,
            ];

            $currentUsageCount = Client::where('bd_id', $client->bd_id)->count();
            if ($client->billingDetail && $currentUsageCount <= 1) {
                $client->billingDetail->update($billingData);
                $selectedBdId = $client->billingDetail->bd_id;
            } else {
                $newBillingDetail = ClientBillingDetail::create(array_merge($billingData, [
                    'bd_id' => Group::generateUniqueAlphaId(new Group()),
                ]));
                $selectedBdId = $newBillingDetail->bd_id;
            }
        }

        $validated['bd_id'] = $selectedBdId;
        $clientData = collect($validated)->except([
            'existing_bd_id',
            'billing_business_name',
            'billing_gstin',
            'billing_email',
            'billing_city',
            'billing_state',
            'billing_country',
            'billing_postal_code',
            'billing_address_line_1',
            'billing_phone',
        ])->all();
        $client->update($clientData);

        return redirect()->route('clients.index')->with('success', 'Client updated successfully.');
    }

    public function clientsDestroy(Client $client)
    {
        $client->delete();

        return redirect()->route('clients.index')->with('success', 'Client deleted successfully.');
    }

    private function normalizeClientEmails(string $rawEmails): string
    {
        $emails = collect(explode(',', $rawEmails))
            ->map(fn ($email) => trim($email))
            ->filter()
            ->unique()
            ->values();

        if ($emails->isEmpty()) {
            throw ValidationException::withMessages([
                'email' => 'At least one email is required.',
            ]);
        }

        foreach ($emails as $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw ValidationException::withMessages([
                    'email' => 'Invalid email address: ' . $email,
                ]);
            }
        }

        return $emails->implode(', ');
    }
}
