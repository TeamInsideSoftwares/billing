<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountBillingDetail;
use App\Models\Client;
use App\Models\FinancialYear;
use App\Models\Payment;
use App\Models\Quotation;
use App\Models\CommunicationLog;
use App\Models\QuotationItem;
use App\Models\MessageTemplate;
use App\Models\SerialConfiguration;
use App\Models\Service;
use App\Models\Tax;
use App\Models\TermsCondition;
use App\Traits\ConfiguresBrowsershot;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class QuotationsController extends Controller
{
    use ConfiguresBrowsershot;

    private function renderQuotationMessageTemplate(string $value, Quotation $quotation, ?string $companyName = null): string
    {
        return strtr($value, $this->buildQuotationMessageTemplateReplacements($quotation, $companyName));
    }

    private function buildQuotationMessageTemplateReplacements(Quotation $quotation, ?string $companyName = null): array
    {
        $quotation->loadMissing(['client', 'items']);
        $clientBusinessName = trim((string) ($quotation->client?->business_name ?? ''));
        $clientContactPerson = trim((string) ($quotation->client?->contact_name ?? ''));
        $clientName = trim((string) ($clientBusinessName !== '' ? $clientBusinessName : $clientContactPerson));
        $currency = (string) ($quotation->client?->currency ?? 'INR');
        $dueDate = $quotation->due_date?->format('d M Y') ?? '';
        $quotationNumber = (string) ($quotation->quo_number ?? $quotation->quotationid ?? '');
        $quotationTitle = (string) ($quotation->quo_title ?? '');
        $quotationLink = route('quotations.pdf', ['quotation' => $quotation->quotationid]);

        $totalAmount = (float) $quotation->items->sum(function ($item) {
            $lineSub = (float) ($item->amount ?? 0);
            $discountPercent = max(0, min(100, (float) ($item->discount_percent ?? 0)));
            $discounted = max(0, $lineSub - floor($lineSub * ($discountPercent / 100)));
            $taxAmount = ceil($discounted * ((float) ($item->tax_rate ?? 0) / 100));
            return max(0, $discounted + $taxAmount);
        });

        $primaryItem = $quotation->items->sortBy('sequence')->first();
        $itemName = trim((string) ($primaryItem?->item_name ?? ''));
        $itemStartDate = $primaryItem?->start_date?->format('d M Y') ?? '';
        $itemEndDate = $primaryItem?->end_date?->format('d M Y') ?? '';
        $daysLeft = $primaryItem?->end_date ? now()->startOfDay()->diffInDays($primaryItem->end_date->startOfDay(), false) : null;

        $accountid = $this->resolveAccountId();
        $accountBillingDetail = AccountBillingDetail::query()->where('accountid', $accountid)->first();
        $billingName = trim((string) ($accountBillingDetail?->billing_name ?? $companyName ?? ''));

        $latestPayment = Payment::query()
            ->where('clientid', $quotation->clientid)
            ->orderByDesc('payment_date')
            ->orderByDesc('created_at')
            ->first();

        return [
            '{{client_business_name}}' => $clientBusinessName,
            '{{client_contact_person}}' => $clientContactPerson,
            '{{client_name}}' => $clientName,
            '{{business_name}}' => $billingName,
            '{{company_name}}' => $billingName,
            '{{account_name}}' => $billingName,
            '{{quotation_number}}' => $quotationNumber,
            '{{quotation_title}}' => $quotationTitle,
            '{{quotation_link}}' => $quotationLink,
            '{{total_amount}}' => $currency . ' ' . number_format($totalAmount, 2),
            '{{due_date}}' => $dueDate,
            '{{item_name}}' => $itemName,
            '{{item_start_date}}' => $itemStartDate,
            '{{item_end_date}}' => $itemEndDate,
            '{{days_left}}' => (string) max(0, (int) ($daysLeft ?? 0)),
            '{{payment_amount}}' => !empty($latestPayment?->received_amount) ? ($currency . ' ' . number_format((float) $latestPayment->received_amount, 2)) : '',
            '{{payment_date}}' => $latestPayment?->payment_date?->format('d M Y') ?? '',
            '{{payment_reference}}' => trim((string) ($latestPayment?->reference_number ?? '')),
        ];
    }

    private function extractCampioQuotationTemplateVariables(string $templateBody, Quotation $quotation, ?string $companyName = null): array
    {
        $body = trim($templateBody);
        if ($body === '') {
            return [];
        }

        preg_match_all('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', $body, $matches);
        $tokens = $matches[1] ?? [];
        if (empty($tokens)) {
            return [];
        }

        $replace = $this->buildQuotationMessageTemplateReplacements($quotation, $companyName);
        $variables = [];
        foreach ($tokens as $token) {
            $placeholder = '{{' . $token . '}}';
            $resolved = array_key_exists($placeholder, $replace)
                ? (string) $replace[$placeholder]
                : (string) $token;
            $variables[] = $this->sanitizeForCampioText($resolved);
        }

        return $variables;
    }

    public function quotations(): View
    {
        $userAccountId = $this->resolveAccountId();
        $selectedClientId = (string) request('c', '');
        $query = Quotation::query()
            ->where('accountid', $userAccountId)
            ->with(['client', 'items']);

        if ($selectedClientId !== '') {
            $query->where('clientid', $selectedClientId);
        }

        $searchTerm = request('search', '');
        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('quo_number', 'like', '%' . $searchTerm . '%')
                    ->orWhere('quo_title', 'like', '%' . $searchTerm . '%')
                    ->orWhereHas('client', function ($cq) use ($searchTerm) {
                        $cq->where('business_name', 'like', '%' . $searchTerm . '%')
                            ->orWhere('contact_name', 'like', '%' . $searchTerm . '%');
                    });
            });
        }

        $resultCount = $query->count();

        $quotations = $query->latest()->take(20)->get()->map(function (Quotation $quotation) {
            return [
                'record_id' => $quotation->quotationid,
                'client_id' => $quotation->clientid,
                'number' => $quotation->quo_number ?: ('QUO-' . $quotation->quotationid),
                'title' => $quotation->quo_title ?: ($quotation->quo_number ?: ('QUO-' . $quotation->quotationid)),
                'client' => $quotation->client->business_name ?? $quotation->client->contact_name ?? 'Client',
                'amount' => 'Rs ' . number_format($quotation->grand_total ?? 0),
                'due' => $quotation->due_date?->format('d M Y') ?? 'N/A',
                'status' => $quotation->status ?? 'draft',
            ];
        });

        return view('quotations.index', [
            'title' => 'All Quotations',
            'subtitle' => $searchTerm ? 'Search results for "' . $searchTerm . '"' : null,
            'quotations' => $quotations,
            'searchTerm' => $searchTerm,
            'resultCount' => $resultCount,
            'clients' => Client::query()
                ->where('accountid', $userAccountId)
                ->orderBy('business_name')
                ->orderBy('contact_name')
                ->get(),
            'selectedClientId' => $selectedClientId,
        ]);
    }

    public function quotationsCreate(): View
    {
        $accountid = $this->resolveAccountId();
        $account = Account::find($accountid);
        $currentStep = max(1, min(4, (int) request('step', 1)));

        $clientId = (string) request('c', request('clientid', ''));
        $selectedClient = $clientId !== ''
            ? Client::query()->where('accountid', $accountid)->where('clientid', $clientId)->first()
            : null;
        $draftId = (string) request('d', '');
        $draftQuotation = $draftId !== ''
            ? Quotation::query()
                ->where('accountid', $accountid)
                ->where('quotationid', $draftId)
                ->with('items')
                ->first()
            : null;

        return view('quotations.create', [
            'title' => 'Create New Quotation',
            'currentStep' => $currentStep,
            'clients' => Client::where('accountid', $accountid)->orderBy('business_name')->get(),
            'services' => Service::where('accountid', $accountid)->with(['category', 'costings'])->orderBy('sequence')->orderBy('name')->get(),
            'taxes' => ($account && $account->allow_multi_taxation)
                ? Tax::where('accountid', $accountid)->where('is_active', true)->orderByRaw('COALESCE(sequence, 999999), created_at DESC')->get()
                : collect(),
            'account' => $account,
            'selectedClient' => $selectedClient,
            'selectedClientId' => $clientId,
            'nextQuotationNumber' => $this->generateQuotationNumber(),
            'draftQuotation' => $draftQuotation,
            'quotationTerms' => TermsCondition::query()
                ->where('accountid', $accountid)
                ->where('type', 'quotation')
                ->where('is_active', true)
                ->orderByRaw('COALESCE(sequence, 999999), created_at DESC')
                ->get(),
        ]);
    }

    public function saveStep2Draft(Request $request)
    {
        $validated = $request->validate([
            'quotationid' => 'nullable|string|exists:quotations,quotationid',
            'clientid' => 'required|exists:clients,clientid',
            'quo_number' => 'required|string|max:30',
            'quo_title' => 'required|string|max:255',
            'issue_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:issue_date',
            'notes' => 'nullable|string',
            'items_data' => 'required|string',
        ]);

        $accountid = $this->resolveAccountId();
        $user = Auth::user();
        $items = json_decode((string) $validated['items_data'], true);
        if (!is_array($items) || empty($items)) {
            return response()->json(['ok' => false, 'message' => 'Please add at least one item.'], 422);
        }

        $quotation = null;
        if (!empty($validated['quotationid'])) {
            $quotation = Quotation::query()
                ->where('accountid', $accountid)
                ->where('quotationid', $validated['quotationid'])
                ->first();
        }

        $wasCreated = false;
        if (!$quotation) {
            $candidateNumber = trim((string) $validated['quo_number']);
            $uniqueNumber = $this->ensureUniqueQuotationNumber($candidateNumber !== '' ? $candidateNumber : $this->generateQuotationNumber(), $accountid);
            $wasCreated = true;
            $quotation = Quotation::create([
                'accountid' => $accountid,
                'fy_id' => FinancialYear::query()->where('accountid', $accountid)->where('default', true)->value('fy_id'),
                'clientid' => $validated['clientid'],
                'quo_number' => $uniqueNumber,
                'quo_title' => $validated['quo_title'],
                'status' => 'draft',
                'issue_date' => $validated['issue_date'],
                'due_date' => $validated['due_date'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'terms' => [],
                'created_by' => (string) ($user?->userid ?? $user?->id ?? ''),
            ]);
        } else {
            $quotation->update([
                'clientid' => $validated['clientid'],
                'quo_title' => $validated['quo_title'],
                'issue_date' => $validated['issue_date'],
                'due_date' => $validated['due_date'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);
            QuotationItem::query()->where('quotationid', $quotation->quotationid)->delete();
        }

        foreach (array_values($items) as $index => $item) {
            $quantity = max(1, (float) ($item['quantity'] ?? 1));
            $unitPrice = max(0, (float) ($item['unit_price'] ?? 0));
            $discountPercent = max(0, min(100, (float) ($item['discount_percent'] ?? 0)));
            $taxRate = max(0, (float) ($item['tax_rate'] ?? 0));
            $lineSubtotal = $quantity * $unitPrice;
            $discountedUnitPrice = round(max(0, $unitPrice * ((100 - $discountPercent) / 100)), 2);
            $discountAmount = $lineSubtotal * ($discountPercent / 100);
            $taxable = max(0, $lineSubtotal - $discountAmount);

            QuotationItem::create([
                'quotationid' => $quotation->quotationid,
                'accountid' => $accountid,
                'clientid' => $quotation->clientid,
                'itemid' => (string) ($item['itemid'] ?? ''),
                'item_name' => (string) ($item['item_name'] ?? 'Item'),
                'item_description' => (string) ($item['item_description'] ?? ''),
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'tax_rate' => $taxRate,
                'discount_percent' => $discountPercent,
                'discount_amount' => $discountedUnitPrice,
                'duration' => !empty($item['duration']) ? (int) $item['duration'] : null,
                'frequency' => (string) ($item['frequency'] ?? ''),
                'no_of_users' => !empty($item['no_of_users']) ? (int) $item['no_of_users'] : null,
                'start_date' => !empty($item['start_date']) ? $item['start_date'] : null,
                'end_date' => !empty($item['end_date']) ? $item['end_date'] : null,
                'status' => 'active',
                'amount' => round($lineSubtotal, 2),
                'sequence' => $index + 1,
            ]);
        }

        return response()->json([
            'ok' => true,
            'was_created' => $wasCreated,
            'quotationid' => $quotation->quotationid,
            'quo_number' => $quotation->quo_number,
            'redirect_url' => route('quotations.create', ['step' => 3, 'c' => $quotation->clientid, 'd' => $quotation->quotationid]),
        ]);
    }

    public function quotationsStore(Request $request)
    {
        $draftId = (string) $request->input('quotationid', '');
        $validated = $request->validate([
            'quotationid' => 'nullable|string|exists:quotations,quotationid',
            'clientid' => 'required|exists:clients,clientid',
            'quo_number' => 'required|string|max:30',
            'quo_title' => 'required|string|max:255',
            'issue_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:issue_date',
            'status' => 'nullable|in:draft,active,cancelled',
            'notes' => 'nullable|string',
            'terms' => 'nullable',
            'items_data' => 'required|string',
        ]);

        $accountid = $this->resolveAccountId();
        $user = Auth::user();
        $draftQuotation = null;
        if ($draftId !== '') {
            $draftQuotation = Quotation::query()
                ->where('accountid', $accountid)
                ->where('quotationid', $draftId)
                ->first();
        }

        $request->validate([
            'quo_number' => 'required|string|max:30|unique:quotations,quo_number,' . ($draftQuotation?->quotationid ?? 'NULL') . ',quotationid',
        ]);

        $items = json_decode((string) $validated['items_data'], true);
        if (!is_array($items) || empty($items)) {
            return back()->withErrors(['items_data' => 'Please add at least one item.'])->withInput();
        }

        $terms = $request->input('terms', []);
        if (is_string($terms)) {
            $decodedTerms = json_decode($terms, true);
            $terms = is_array($decodedTerms) ? $decodedTerms : [];
        }
        $terms = array_values(array_filter(array_map('trim', (array) $terms)));
        if (empty($terms)) {
            $terms = TermsCondition::query()
                ->where('accountid', $accountid)
                ->where('type', 'quotation')
                ->where('is_active', true)
                ->where('is_default', true)
                ->orderByRaw('COALESCE(sequence, 999999), created_at ASC')
                ->pluck('content')
                ->map(fn($term) => trim((string) $term))
                ->filter()
                ->values()
                ->all();
        }

        $wasCreated = false;
        if ($draftQuotation) {
            $draftQuotation->update([
                'clientid' => $validated['clientid'],
                'quo_number' => $validated['quo_number'],
                'quo_title' => $validated['quo_title'],
                'status' => 'active',
                'issue_date' => $validated['issue_date'],
                'due_date' => $validated['due_date'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'terms' => $terms,
            ]);
            QuotationItem::query()->where('quotationid', $draftQuotation->quotationid)->delete();
            $quotation = $draftQuotation;
        } else {
            $wasCreated = true;
            $quotation = Quotation::create([
                'accountid' => $accountid,
                'fy_id' => FinancialYear::query()->where('accountid', $accountid)->where('default', true)->value('fy_id'),
                'clientid' => $validated['clientid'],
                'quo_number' => $validated['quo_number'],
                'quo_title' => $validated['quo_title'],
                'status' => 'active',
                'issue_date' => $validated['issue_date'],
                'due_date' => $validated['due_date'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'terms' => $terms,
                'created_by' => (string) ($user?->userid ?? $user?->id ?? ''),
            ]);
        }

        foreach (array_values($items) as $index => $item) {
            $quantity = max(1, (float) ($item['quantity'] ?? 1));
            $unitPrice = max(0, (float) ($item['unit_price'] ?? 0));
            $discountPercent = max(0, min(100, (float) ($item['discount_percent'] ?? 0)));
            $taxRate = max(0, (float) ($item['tax_rate'] ?? 0));

            $lineSubtotal = $quantity * $unitPrice;
            $discountedUnitPrice = round(max(0, $unitPrice * ((100 - $discountPercent) / 100)), 2);
            $discountAmount = $lineSubtotal * ($discountPercent / 100);
            $taxable = max(0, $lineSubtotal - $discountAmount);
            $taxAmount = ceil($taxable * ($taxRate / 100));
            $lineTotal = $taxable + $taxAmount;

            QuotationItem::create([
                'quotationid' => $quotation->quotationid,
                'accountid' => $accountid,
                'clientid' => $quotation->clientid,
                'itemid' => (string) ($item['itemid'] ?? ''),
                'item_name' => (string) ($item['item_name'] ?? 'Item'),
                'item_description' => (string) ($item['item_description'] ?? ''),
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'tax_rate' => $taxRate,
                'discount_percent' => $discountPercent,
                'discount_amount' => $discountedUnitPrice,
                'duration' => !empty($item['duration']) ? (int) $item['duration'] : null,
                'frequency' => (string) ($item['frequency'] ?? ''),
                'no_of_users' => !empty($item['no_of_users']) ? (int) $item['no_of_users'] : null,
                'start_date' => !empty($item['start_date']) ? $item['start_date'] : null,
                'end_date' => !empty($item['end_date']) ? $item['end_date'] : null,
                'status' => 'active',
                'amount' => round($lineSubtotal, 2),
                'sequence' => $index + 1,
            ]);
        }
        $this->persistQuotationPdfVersion($quotation);

        $redirect = redirect()->route('quotations.email-compose', $quotation->quotationid);
        if ($wasCreated) {
            return $redirect->with('success', 'Quotation created. Compose message and send it now.');
        }
        return $redirect;
    }

    public function applyTerms(Request $request, Quotation $quotation)
    {
        $accountid = $this->resolveAccountId();
        abort_unless((string) $quotation->accountid === (string) $accountid, 403);

        $request->validate([
            'terms' => 'nullable|array',
            'terms.*' => 'string',
        ]);

        $terms = array_values(array_filter(array_map('trim', (array) $request->input('terms', []))));
        if (empty($terms)) {
            $terms = TermsCondition::query()
                ->where('accountid', $accountid)
                ->where('type', 'quotation')
                ->where('is_active', true)
                ->where('default', true)
                ->orderByRaw('COALESCE(sequence, 999999), created_at DESC')
                ->pluck('content')
                ->map(fn ($content) => trim((string) $content))
                ->filter()
                ->values()
                ->all();
        }

        $quotation->update(['terms' => $terms]);
        $this->persistQuotationPdfVersion($quotation);

        return response()->json(['ok' => true, 'count' => count($terms)]);
    }

    public function quotationsShow(Quotation $quotation): View
    {
        $quotation->load(['client', 'items']);

        return view('quotations.show', [
            'title' => $quotation->quo_number ?? 'Quotation',
            'subtitle' => 'Quotation Details',
            'quotation' => $quotation,
            'pdfVersions' => $this->listStoredQuotationPdfVersions($quotation),
        ]);
    }

    public function quotationsEdit(Quotation $quotation): View
    {
        $accountid = $this->resolveAccountId();

        return view('quotations.form', [
            'title' => 'Edit ' . ($quotation->quo_number ?? 'Quotation'),
            'quotation' => $quotation,
            'clients' => Client::where('accountid', $accountid)->get(),
        ]);
    }

    public function quotationsUpdate(Request $request, Quotation $quotation)
    {
        $validated = $request->validate([
            'clientid' => 'required|exists:clients,clientid',
            'quo_number' => 'required|string|unique:quotations,quo_number,' . $quotation->getKey() . ',quotationid',
            'issue_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:issue_date',
            'status' => 'required|in:draft,active,cancelled',
        ]);

        $quotation->update($validated);
        $this->persistQuotationPdfVersion($quotation);

        return redirect()->route('quotations.index')->with('success', 'Quotation updated successfully.');
    }

    public function quotationsDestroy(Quotation $quotation)
    {
        $quotation->update(['status' => 'cancelled']);

        return redirect()->route('quotations.index')->with('success', 'Quotation cancelled successfully.');
    }

    public function quotationEmailCompose(Quotation $quotation): View
    {
        $quotation->load('client.billingDetail');
        $user = Auth::user();
        $accountid = $this->resolveAccountId();
        $accountBillingDetail = AccountBillingDetail::query()->where('accountid', $accountid)->first();

        $latestDraft = CommunicationLog::query()
            ->where('quotationid', $quotation->quotationid)
            ->where('accountid', $accountid)
            ->latest('created_at')
            ->first();
        $billingAddressLines = array_values(array_filter([
            trim((string) ($accountBillingDetail?->address ?? '')),
            trim((string) (collect([
                $accountBillingDetail?->city ?? '',
                $accountBillingDetail?->postal_code ?? '',
                $accountBillingDetail?->state ?? '',
            ])->filter()->implode(', '))),
            trim((string) ($accountBillingDetail?->country ?? '')),
        ]));
        $signatureName = trim((string) ($accountBillingDetail?->billing_name ?? ''));
        $signatureLines = array_values(array_filter(array_merge([$signatureName], $billingAddressLines)));
        $defaultBody = "Hello,\n\nPlease find attached quotation PDF " . ($quotation->quo_number ?? $quotation->quotationid) . '.';
        if (!empty($signatureLines)) {
            $defaultBody .= "\n\nRegards,\n" . implode("\n", $signatureLines);
        } else {
            $defaultBody .= "\n\nRegards";
        }
        $templateRows = MessageTemplate::query()
            ->where('accountid', $accountid)
            ->where('template_type', 'quotation')
            ->where('is_active', true)
            ->whereIn('channel', ['email', 'whatsapp', 'sms'])
            ->orderBy('channel')
            ->orderBy('created_at')
            ->get();

        $templateCatalog = [];
        foreach ($templateRows as $row) {
            $channel = (string) $row->channel;
            if (!in_array($channel, ['email', 'whatsapp', 'sms'], true)) {
                continue;
            }
            $templateCatalog[$channel] = $templateCatalog[$channel] ?? [];
            $templateCatalog[$channel][] = [
                'templateid' => (string) $row->templateid,
                'name' => (string) ($row->name ?? ''),
                'subject' => $this->renderQuotationMessageTemplate((string) ($row->subject ?? ''), $quotation, $user?->name),
                'body' => $this->renderQuotationMessageTemplate((string) ($row->body ?? ''), $quotation, $user?->name),
            ];
        }
        $availableChannels = collect(['email', 'whatsapp', 'sms'])
            ->filter(fn($channel) => !empty($templateCatalog[$channel] ?? []))
            ->values()
            ->all();

        $requestedChannel = trim((string) request('channel', ''));
        if ($requestedChannel !== '' && in_array($requestedChannel, ['email', 'whatsapp', 'sms'], true)) {
            $prefillChannel = $requestedChannel;
        } elseif (!empty($latestDraft?->channel) && in_array((string) $latestDraft->channel, ['email', 'whatsapp', 'sms'], true)) {
            $prefillChannel = (string) $latestDraft->channel;
        } elseif (!empty($availableChannels)) {
            $prefillChannel = (string) ($availableChannels[0] ?? 'email');
        } else {
            $prefillChannel = 'email';
            $availableChannels = ['email'];
        }

        $draftForChannel = CommunicationLog::query()
            ->where('quotationid', $quotation->quotationid)
            ->where('accountid', $accountid)
            ->where('channel', $prefillChannel)
            ->latest('created_at')
            ->first();
        $draft = $draftForChannel;

        $channelTemplates = $templateCatalog[$prefillChannel] ?? [];
        $defaultTemplate = $channelTemplates[0] ?? null;
        $preferTemplateForMessagingChannel = in_array($prefillChannel, ['whatsapp', 'sms'], true)
            && ((string) ($draft?->status ?? '')) !== 'sent';
        $basePrefillSubject = $preferTemplateForMessagingChannel
            ? ($defaultTemplate['subject'] ?? ($draft?->subject ?? ('Quotation ' . ($quotation->quo_number ?? $quotation->quotationid))))
            : ($draft?->subject ?? ($defaultTemplate['subject'] ?? ('Quotation ' . ($quotation->quo_number ?? $quotation->quotationid))));
        $basePrefillBody = $preferTemplateForMessagingChannel
            ? ($defaultTemplate['body'] ?? ($draft?->body ?? $defaultBody))
            : ($draft?->body ?? ($defaultTemplate['body'] ?? $defaultBody));

        $oldChannel = trim((string) old('channel', ''));
        $reuseOldForSameChannel = $oldChannel !== '' && $oldChannel === $prefillChannel;
        $prefillSubject = $reuseOldForSameChannel ? old('subject', $basePrefillSubject) : $basePrefillSubject;
        $prefillBody = $reuseOldForSameChannel ? old('body', $basePrefillBody) : $basePrefillBody;
        if (in_array($prefillChannel, ['whatsapp', 'sms'], true)) {
            $prefillBody = $this->normalizeChannelBodyForStorage((string) $prefillBody, $prefillChannel);
        }
        $prefillTemplateId = old(
            'selected_templateid',
            trim((string) ($draft?->selected_templateid ?? '')) !== ''
                ? (string) $draft->selected_templateid
                : ($defaultTemplate['templateid'] ?? '')
        );

        return view('quotations.email-compose', [
            'title' => 'Create New Quotation',
            'subtitle' => null,
            'quotation' => $quotation,
            'composeEmail' => $draft,
            'fromEmail' => $user?->email ?? '',
            'toEmail' => old('to_email', $draft?->to_email ?? $quotation->client?->billing_email ?? $quotation->client?->primary_email ?? $quotation->client?->email ?? ''),
            'ccEmail' => old('cc_email', $draft?->cc_email ?? ''),
            'phone' => old('phone_number', trim((string) (
                $draft?->phone_number
                ?? $quotation->client?->billingDetail?->billing_phone
                ?? $quotation->client?->whatsapp_number
                ?? $quotation->client?->phone
                ?? ''
            ))),
            'subject' => $prefillSubject,
            'body' => $prefillBody,
            'prefillChannel' => $prefillChannel,
            'templateCatalog' => $templateCatalog,
            'availableChannels' => $availableChannels,
            'prefillTemplateId' => $prefillTemplateId,
            'customAttachmentUrl' => !empty($draft?->custom_attachment_path)
                ? $this->buildPublicAttachmentUrl((string) $draft->custom_attachment_path)
                : null,
            'customAttachmentName' => !empty($draft?->custom_attachment_path)
                ? basename((string) parse_url($this->buildPublicAttachmentUrl((string) $draft->custom_attachment_path), PHP_URL_PATH) ?: 'Attachment')
                : null,
        ]);
    }

    public function quotationEmailComposeStore(Request $request, Quotation $quotation)
    {
        $validated = $request->validate([
            'channel' => 'required|in:email,whatsapp,sms',
            'selected_templateid' => 'nullable|string|max:20',
            'to_email' => 'nullable|string|max:255',
            'cc_email' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:50',
            'subject' => 'nullable|string|max:255',
            'body' => 'required|string',
            'action' => 'required|in:save,send',
            'custom_attachment' => 'nullable|file|max:10240',
            'existing_custom_attachment_path' => 'nullable|string|max:2048',
        ]);

        $accountid = $this->resolveAccountId();
        $user = Auth::user();

        $hasNewCustomFile = $request->hasFile('custom_attachment') && $request->file('custom_attachment')->isValid();
        $customAttachmentPath = null;
        if ($hasNewCustomFile) {
            $storedPath = $request->file('custom_attachment')->store('quotation-email-attachments', 'public');
            $customAttachmentPath = $this->buildPublicAttachmentUrl($storedPath);
        }
        $finalCustomAttachmentPath = $customAttachmentPath ?: trim((string) ($validated['existing_custom_attachment_path'] ?? ''));
        if ($finalCustomAttachmentPath === '') {
            $finalCustomAttachmentPath = null;
        }
        $channel = (string) ($validated['channel'] ?? 'email');
        $isEmailChannel = ($channel === 'email');
        $normalizedBody = $this->normalizeChannelBodyForStorage((string) ($validated['body'] ?? ''), $channel);

        $email = CommunicationLog::create([
            'accountid' => $accountid,
            'quotationid' => $quotation->quotationid,
            'clientid' => $quotation->clientid,
            'from_email' => $user?->email,
            'to_email' => $validated['to_email'] ?? '',
            'cc_email' => $validated['cc_email'] ?? null,
            'phone_number' => $validated['phone_number'] ?? null,
            'subject' => $validated['subject'] ?? null,
            'body' => $normalizedBody,
            'attachment_type' => 'quotation',
            'attachment_path' => $isEmailChannel ? $this->resolveCampioQuotationPdfUrl($quotation) : null,
            'custom_attachment_path' => $isEmailChannel ? $finalCustomAttachmentPath : null,
            'status' => $validated['action'] === 'send' ? 'sent' : 'draft',
            'channel' => $channel,
            'created_by' => (string) ($user?->userid ?? $user?->id ?? ''),
        ]);

        if ($validated['action'] === 'send') {
            if ($channel === 'email') {
                $toEmailValue = trim((string) ($validated['to_email'] ?? ''));
                if ($toEmailValue === '') {
                    return back()->withErrors(['to_email' => 'To Email is required for email channel.'])->withInput();
                }

                $quotationPdfUrl = $this->resolveCampioQuotationPdfUrl($quotation);
                $quotationName = trim((string) ($quotation->quo_title ?: $quotation->quo_number ?: $quotation->quotationid));
                $emailAttachmentItems = [[
                    'url' => $quotationPdfUrl,
                    'name' => 'Quotation - ' . ($quotationName !== '' ? $quotationName : $quotation->quotationid) . '.pdf',
                ]];
                if (!empty($finalCustomAttachmentPath)) {
                    $emailAttachmentItems[] = [
                        'url' => $finalCustomAttachmentPath,
                        'name' => basename((string) parse_url($finalCustomAttachmentPath, PHP_URL_PATH) ?: 'Attachment'),
                    ];
                }

                $emails = collect(preg_split('/[\s,;]+/', $toEmailValue))
                    ->map(fn($mail) => trim((string) $mail))
                    ->filter()
                    ->values()
                    ->all();
                $ccEmails = collect(preg_split('/[\s,;]+/', trim((string) ($validated['cc_email'] ?? ''))))
                    ->map(fn($mail) => trim((string) $mail))
                    ->filter()
                    ->values()
                    ->all();

                $recipientRecords = [];
                foreach ($emails as $mail) {
                    $recipientRecords[] = $this->buildCampioQuotationRecipientRecord($quotation, 'email', $mail, (string) ($validated['phone_number'] ?? ''));
                }
                foreach ($ccEmails as $mail) {
                    $recipientRecords[] = $this->buildCampioQuotationRecipientRecord($quotation, 'email', $mail, (string) ($validated['phone_number'] ?? ''));
                }
                if (empty($recipientRecords)) {
                    return back()->withErrors(['to_email' => 'Please provide at least one valid recipient email.'])->withInput();
                }

                $payload = [
                    'account_id' => $accountid,
                    'campaign_name' => '',
                    'schedule_at' => now()->toIso8601String(),
                    'subject' => (string) ($validated['subject'] ?? ('Quotation ' . ($quotation->quo_number ?? $quotation->quotationid))),
                    'message' => $this->sanitizeComposedMessageBody(
                        $this->renderQuotationMessageTemplate((string) ($validated['body'] ?? ''), $quotation, $user?->name)
                    ),
                    'sender_id' => (string) ($user?->email ?? ''),
                    'records' => $recipientRecords,
                    'source_url' => url()->current(),
                    'notes' => 'Quotation communication: EMAIL',
                ];

                $emailAttachments = $this->buildCampioAttachments($emailAttachmentItems);
                if (!empty($emailAttachments)) {
                    $payload['attachments'] = $emailAttachments;
                }

                $campioResult = $this->sendViaCampio('email', $payload);
                if (!$campioResult['ok']) {
                    return back()->withErrors(['general' => $campioResult['message']])->withInput();
                }
            } else {
                $phone = trim((string) (
                    $validated['phone_number']
                    ?? $quotation->client?->billingDetail?->billing_phone
                    ?? $quotation->client?->whatsapp_number
                    ?? $quotation->client?->phone
                    ?? ''
                ));
                if ($phone === '') {
                    return back()->withErrors(['phone_number' => 'Phone number is required for this channel.'])->withInput();
                }

                $selectedTemplateId = trim((string) ($validated['selected_templateid'] ?? ''));
                $channelTemplateQuery = MessageTemplate::query()
                    ->where('accountid', $accountid)
                    ->where('template_type', 'quotation')
                    ->where('channel', $channel)
                    ->where('is_active', true);
                if ($selectedTemplateId !== '') {
                    $channelTemplateQuery->where('templateid', $selectedTemplateId);
                }
                $channelTemplateConfig = $channelTemplateQuery->first();
                if ($selectedTemplateId !== '' && !$channelTemplateConfig) {
                    return back()->withErrors([
                        'selected_templateid' => 'Selected template is invalid for this channel.',
                    ])->withInput();
                }

                $renderedBody = $this->renderQuotationMessageTemplate($normalizedBody, $quotation, $user?->name);
                $plainBodyHtml = str_replace(['<br>', '<br/>', '<br />'], "\n", $renderedBody);
                $plainBodyHtml = preg_replace('/<\/(p|div|li|h[1-6])>/i', "\n", (string) $plainBodyHtml) ?? (string) $plainBodyHtml;
                $plainBodyHtml = preg_replace('/<(ul|ol)[^>]*>/i', "\n", (string) $plainBodyHtml) ?? (string) $plainBodyHtml;
                $plainBody = trim(strip_tags((string) $plainBodyHtml));
                $plainBody = $this->sanitizeForCampioText($plainBody);
                $plainBody = str_replace("\n", "\r\n", $plainBody);

                $payload = [
                    'account_id' => $accountid,
                    'campaign_name' => '',
                    'schedule_at' => now()->toIso8601String(),
                    'message' => $plainBody,
                    'records' => [
                        $this->buildCampioQuotationRecipientRecord($quotation, $channel, '', $phone),
                    ],
                    'source_url' => url()->current(),
                    'notes' => 'Quotation communication: ' . strtoupper($channel),
                ];

                if (!empty($channelTemplateConfig?->template_id)) {
                    $payload['template_id'] = (string) $channelTemplateConfig->template_id;
                    $payload['meta_template_id'] = (string) ($channelTemplateConfig->meta_template_id ?: $channelTemplateConfig->template_id);
                }
                if (!empty($channelTemplateConfig?->sender_id)) {
                    $payload['sender_id'] = (string) $channelTemplateConfig->sender_id;
                }
                if (!empty($channelTemplateConfig?->template_id)) {
                    $templateVariables = $this->extractCampioQuotationTemplateVariables((string) ($channelTemplateConfig->body ?? ''), $quotation, $user?->name);
                    if (!empty($templateVariables)) {
                        $payload['variables'] = $templateVariables;
                        $payload['dynamic_context'] = [
                            'fields' => collect($templateVariables)->values()->map(
                                fn($value, $index) => [
                                    'key' => 'Body_' . ((int) $index + 1),
                                    'type' => 'custom',
                                    'value' => (string) $value,
                                ]
                            )->all(),
                        ];
                    }
                }

                $campioResult = $this->sendViaCampio($channel, $payload);
                if (!$campioResult['ok']) {
                    return back()->withErrors(['general' => $campioResult['message']])->withInput();
                }
            }

            $quotation->update(['status' => 'active']);
            return redirect()->route('quotations.email-compose', $quotation->quotationid)
                ->with('success', 'Quotation sent successfully via ' . strtoupper($email->channel) . '.');
        }

        return redirect()->route('quotations.email-compose', $quotation->quotationid)
            ->with('success', 'Draft saved.');
    }

    public function quotationPdf(Request $request, Quotation $quotation): Response
    {
        $pdfAttachment = $this->buildQuotationPdfAttachment($quotation);

        return response($pdfAttachment['binary'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $pdfAttachment['filename'] . '"',
        ]);
    }

    public function quotationPdfVersions(Quotation $quotation)
    {
        return response()->json([
            'ok' => true,
            'quotationid' => $quotation->quotationid,
            'versions' => $this->listStoredQuotationPdfVersions($quotation),
        ]);
    }

    private function buildQuotationPdfAttachment(Quotation $quotation): array
    {
        $quotation->load(['client.billingDetail', 'items', 'account']);
        $accountid = $this->resolveAccountId();
        $account = $quotation->account ?? Account::query()->find($accountid);
        $accountBillingDetail = AccountBillingDetail::query()->where('accountid', $accountid)->first();

        $normalizeTaxState = fn($v) => preg_replace('/[^A-Z0-9]/', '', strtoupper(trim((string) $v)));
        $clientState = $normalizeTaxState($quotation->client->state ?? '');
        $accountState = $normalizeTaxState($account->state ?? '');
        $sameStateGst = $clientState !== '' && $accountState !== '' && $clientState === $accountState;

        $signatureUrl = null;
        $sigPath = optional($accountBillingDetail)->signature_upload;
        if (!empty($sigPath)) {
            if (str_starts_with($sigPath, 'http://') || str_starts_with($sigPath, 'https://')) {
                $signatureUrl = $sigPath;
            } else {
                $relative = str_starts_with($sigPath, 'storage/') ? $sigPath : 'storage/' . ltrim($sigPath, '/');
                $signatureUrl = public_path($relative);
            }
        }

        $terms = array_values(array_filter(array_map('trim', (array) ($quotation->terms ?? []))));
        if (empty($terms)) {
            $terms = TermsCondition::query()
                ->where('accountid', $accountid)
                ->where('type', 'quotation')
                ->where('is_active', true)
                ->where('is_default', true)
                ->orderByRaw('COALESCE(sequence, 999999), created_at ASC')
                ->pluck('content')
                ->map(fn($term) => trim((string) $term))
                ->filter()
                ->values()
                ->all();
        }
        $html = view('quotations.pdf', [
            'quotation' => $quotation,
            'quotationTerms' => $terms,
            'account' => $account,
            'accountBillingDetail' => $accountBillingDetail,
            'sameStateGst' => $sameStateGst,
            'signatureUrl' => $signatureUrl,
        ])->render();

        return [
            'filename' => 'Quotation - ' . ($quotation->quo_number ?: $quotation->quotationid) . '.pdf',
            'binary' => $this->getBrowsershot($html)->pdf(),
        ];
    }

    private function resolveCampioQuotationPdfUrl(Quotation $quotation): string
    {
        $existing = collect($this->listStoredQuotationPdfVersions($quotation))
            ->sortByDesc(fn($row) => (int) ($row['version'] ?? 0))
            ->first();

        if (!empty($existing['path'])) {
            return $this->buildFriendlyCampioQuotationPdfUrl($quotation, (string) $existing['path']);
        }

        $saved = $this->persistQuotationPdfVersion($quotation);
        if (!empty($saved['path'])) {
            return $this->buildFriendlyCampioQuotationPdfUrl($quotation, (string) $saved['path']);
        }

        return route('quotations.pdf', ['quotation' => $quotation->quotationid, 'download' => 1]);
    }

    private function buildFriendlyCampioQuotationPdfUrl(Quotation $quotation, string $sourcePath): string
    {
        $disk = Storage::disk('public');
        $number = trim((string) ($quotation->quo_number ?: $quotation->quotationid));
        $friendlyBase = 'Quotation - ' . ($number !== '' ? $number : $quotation->quotationid);
        $friendlyBase = preg_replace('/[\\\\\\/:*?"<>|]+/', '-', $friendlyBase) ?: 'Quotation';
        $friendlyBase = preg_replace('/\s+/', '-', $friendlyBase) ?: $friendlyBase;
        $targetPath = 'clients/' . $quotation->clientid . '/quotations-share/' . $friendlyBase . '.pdf';

        if (!$disk->exists($targetPath) && $disk->exists($sourcePath)) {
            $disk->put($targetPath, (string) $disk->get($sourcePath));
        }

        return asset('storage/' . $targetPath);
    }

    private function persistQuotationPdfVersion(Quotation $quotation): ?array
    {
        $quotation->loadMissing(['client', 'items']);
        $disk = Storage::disk('public');
        $directory = 'clients/' . $quotation->clientid . '/quotations-pdf';
        $baseName = $quotation->quotationid;
        $fingerprintPath = $directory . '/' . $baseName . '__latest.hash';
        $currentFingerprint = $this->buildQuotationRevisionFingerprint($quotation);
        $lastFingerprint = $disk->exists($fingerprintPath) ? trim((string) $disk->get($fingerprintPath)) : '';
        if ($lastFingerprint !== '' && hash_equals($lastFingerprint, $currentFingerprint)) {
            return null;
        }

        $pdfAttachment = $this->buildQuotationPdfAttachment($quotation);
        $existing = collect($disk->files($directory))
            ->map(function (string $path) use ($baseName) {
                $name = pathinfo($path, PATHINFO_FILENAME);
                if (preg_match('/^' . preg_quote($baseName, '/') . '__v(\d+)$/', $name, $m)) {
                    return (int) $m[1];
                }
                return null;
            })
            ->filter(fn($v) => $v !== null)
            ->values();

        $nextVersion = ((int) ($existing->max() ?? 0)) + 1;
        $relativePath = $directory . '/' . $baseName . '__v' . $nextVersion . '.pdf';
        $disk->put($relativePath, $pdfAttachment['binary']);
        $disk->put($fingerprintPath, $currentFingerprint);

        return [
            'version' => $nextVersion,
            'filename' => basename($relativePath),
            'path' => $relativePath,
            'url' => asset('storage/' . $relativePath),
            'saved_at' => now()->toDateTimeString(),
        ];
    }

    private function listStoredQuotationPdfVersions(Quotation $quotation): array
    {
        $disk = Storage::disk('public');
        $directory = 'clients/' . $quotation->clientid . '/quotations-pdf';
        if (!$disk->exists($directory)) {
            return [];
        }

        return collect($disk->files($directory))
            ->map(function (string $path) use ($quotation, $disk) {
                $name = pathinfo($path, PATHINFO_FILENAME);
                if (!preg_match('/^' . preg_quote($quotation->quotationid, '/') . '__v(\d+)$/', $name, $m)) {
                    return null;
                }

                return [
                    'version' => (int) $m[1],
                    'filename' => basename($path),
                    'path' => $path,
                    'url' => asset('storage/' . $path),
                    'saved_at' => optional($disk->lastModified($path) ? \Carbon\Carbon::createFromTimestamp($disk->lastModified($path)) : null)?->toDateTimeString(),
                ];
            })
            ->filter()
            ->sortByDesc('version')
            ->values()
            ->all();
    }

    private function buildQuotationRevisionFingerprint(Quotation $quotation): string
    {
        $items = $quotation->items
            ->map(fn($item) => [
                'itemid' => (string) ($item->itemid ?? ''),
                'item_name' => (string) ($item->item_name ?? ''),
                'item_description' => trim((string) ($item->item_description ?? '')),
                'quantity' => (float) ($item->quantity ?? 0),
                'unit_price' => (float) ($item->unit_price ?? 0),
                'discount_percent' => (float) ($item->discount_percent ?? 0),
                'tax_rate' => (float) ($item->tax_rate ?? 0),
                'sequence' => (int) ($item->sequence ?? 0),
            ])
            ->sortBy('sequence')
            ->values()
            ->all();

        $payload = [
            'quo_title' => trim((string) ($quotation->quo_title ?? '')),
            'issue_date' => optional($quotation->issue_date)->format('Y-m-d') ?? '',
            'due_date' => optional($quotation->due_date)->format('Y-m-d') ?? '',
            'terms' => array_values(array_filter(array_map('trim', (array) ($quotation->terms ?? [])))),
            'items' => $items,
        ];

        return hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function buildCampioQuotationRecipientRecord(Quotation $quotation, string $channel, string $toEmail, string $phone): array
    {
        $clientName = trim((string) (
            $quotation->client?->business_name
            ?: $quotation->client?->contact_name
            ?: 'Customer'
        ));

        $record = [
            'id' => (string) ($quotation->clientid ?? $quotation->quotationid),
            'name' => $clientName,
            'leadid' => (string) ($quotation->clientid ?? ''),
            'student_customer_name' => $clientName,
            'quotation_number' => (string) ($quotation->quo_number ?? ''),
            'quotation_title' => (string) ($quotation->quo_title ?? ''),
            'due_date' => optional($quotation->due_date)->format('Y-m-d') ?? '',
        ];

        if ($channel === 'email') {
            $record['email'] = $toEmail;
        } else {
            $phoneDigits = preg_replace('/[^0-9]/', '', (string) $phone);
            $localPhone = $phoneDigits;
            if (strlen($phoneDigits) === 12 && str_starts_with($phoneDigits, '91')) {
                $localPhone = substr($phoneDigits, 2);
            }
            $record['mobile'] = $localPhone !== '' ? $localPhone : $phone;
        }

        return $record;
    }

    private function sendViaCampio(string $channel, array $payload): array
    {
        $baseUrl = rtrim((string) env('CAMPIO_BASE_URL', 'http://alpha.skoolready.com/campio'), '/');
        if ($baseUrl === '') {
            return ['ok' => false, 'message' => 'CAMPIO_BASE_URL is not configured.'];
        }

        $endpoint = $baseUrl . '/api/campaigns/schedule/' . $channel;
        $token = trim((string) env('CAMPIO_AUTH_TOKEN', ''));
        $apiKey = trim((string) env('CAMPIO_API_KEY', ''));

        $request = Http::acceptJson()->asJson()->timeout(30);
        if ($token !== '') {
            $request = $request->withToken($token);
        }
        if ($apiKey !== '') {
            $request = $request->withHeaders(['X-API-KEY' => $apiKey]);
        }

        try {
            $response = $request->post($endpoint, $payload);
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Campio request failed: ' . $e->getMessage()];
        }

        $json = $response->json();
        if (!$response->successful()) {
            $message = is_array($json)
                ? ((string) ($json['message'] ?? 'Campio API returned an error.'))
                : ('Campio API returned HTTP ' . $response->status() . '.');
            return ['ok' => false, 'message' => $message];
        }

        return [
            'ok' => true,
            'campaign_id' => (string) (is_array($json) ? ($json['campaign_id'] ?? '') : ''),
            'raw' => $json,
        ];
    }

    private function buildCampioAttachments(array $attachmentsInput): array
    {
        $attachments = [];
        foreach ($attachmentsInput as $index => $item) {
            $url = is_array($item) ? ($item['url'] ?? '') : $item;
            $name = is_array($item) ? ($item['name'] ?? '') : '';

            $cleanUrl = trim((string) $url);
            if ($cleanUrl === '') {
                continue;
            }

            $fileName = trim((string) $name);
            if ($fileName === '') {
                $path = (string) parse_url($cleanUrl, PHP_URL_PATH);
                $fileName = basename($path ?: ('attachment-' . ($index + 1) . '.pdf'));
            }
            $normalizedUrl = $this->normalizeUrlForPayload($cleanUrl);
            if ($normalizedUrl === '') {
                continue;
            }

            $attachments[] = [
                'file_url' => $normalizedUrl,
                'file_name' => $fileName,
            ];
        }

        return $attachments;
    }

    private function normalizeUrlForPayload(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        $parts = parse_url($url);
        if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
            return '';
        }

        $path = (string) ($parts['path'] ?? '');
        $segments = explode('/', $path);
        $encodedSegments = array_map(fn($s) => rawurlencode($s), $segments);
        $encodedPath = implode('/', $encodedSegments);

        $rebuilt = $parts['scheme'] . '://' . $parts['host']
            . (isset($parts['port']) ? ':' . $parts['port'] : '')
            . $encodedPath
            . (isset($parts['query']) ? '?' . $parts['query'] : '')
            . (isset($parts['fragment']) ? '#' . $parts['fragment'] : '');

        return filter_var($rebuilt, FILTER_VALIDATE_URL) ? $rebuilt : '';
    }

    private function sanitizeForCampioText(string $text): string
    {
        $value = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = str_replace(["\r\n", "\r"], "\n", $value);
        $value = str_replace(["\\r\\n", "\\n"], "\n", $value);
        $value = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $value) ?? $value;
        $value = preg_replace('/[^\P{C}\n\t]+/u', '', $value) ?? $value;
        $value = preg_replace("/\n{3,}/", "\n\n", $value) ?? $value;
        return trim($value);
    }

    private function sanitizeComposedMessageBody(string $body): string
    {
        $text = trim($body);
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = str_replace(["\\r\\n", "\\n"], "\n", $text);
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;
        return trim($text);
    }

    private function normalizeChannelBodyForStorage(string $body, string $channel): string
    {
        if ($channel === 'email') {
            return $body;
        }

        $decodedBody = html_entity_decode($body, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $plainBody = str_replace(['<br>', '<br/>', '<br />'], "\n", $decodedBody);
        $plainBody = preg_replace('/<\/(p|div|li|h[1-6])>/i', "\n", (string) $plainBody) ?? (string) $plainBody;
        $plainBody = preg_replace('/<(ul|ol)[^>]*>/i', "\n", (string) $plainBody) ?? (string) $plainBody;
        $plainBody = trim(strip_tags((string) $plainBody));
        return $this->sanitizeForCampioText($plainBody);
    }

    private function buildPublicAttachmentUrl(string $pathOrUrl): string
    {
        $value = trim($pathOrUrl);
        if ($value === '') {
            return '';
        }

        if (preg_match('/^https?:\/\//i', $value)) {
            $value = str_replace('/storage/app/public/', '/storage/', $value);
            return $value;
        }

        $relative = ltrim($value, '/');
        if (str_starts_with($relative, 'storage/app/public/')) {
            $relative = substr($relative, strlen('storage/app/public/'));
        } elseif (str_starts_with($relative, 'app/public/')) {
            $relative = substr($relative, strlen('app/public/'));
        } elseif (str_starts_with($relative, 'public/')) {
            $relative = substr($relative, strlen('public/'));
        } elseif (str_starts_with($relative, 'storage/')) {
            $relative = substr($relative, strlen('storage/'));
        }

        return asset('storage/' . ltrim($relative, '/'));
    }

    private function generateQuotationNumber(): string
    {
        $accountid = $this->resolveAccountId();

        $serialConfig = SerialConfiguration::query()
            ->where('accountid', $accountid)
            ->where('document_type', 'quotation')
            ->first();

        if ($serialConfig) {
            $candidate = $serialConfig->generateNextSerialNumber();
            return $this->ensureUniqueQuotationNumber($candidate !== '' ? $candidate : 'QUO-0001', $accountid);
        }

        $count = Quotation::query()->where('accountid', $accountid)->count();
        return $this->ensureUniqueQuotationNumber('QUO-' . str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT), $accountid);
    }

    private function ensureUniqueQuotationNumber(string $candidate, string $accountid): string
    {
        $candidate = trim($candidate) ?: 'QUO-0001';
        $number = $candidate;
        $sequence = 2;

        while (Quotation::query()->where('accountid', $accountid)->where('quo_number', $number)->exists()) {
            if (preg_match('/^(.*?)(\d+)$/', $candidate, $matches)) {
                $number = $matches[1] . str_pad((string) ((int) $matches[2] + $sequence - 1), strlen($matches[2]), '0', STR_PAD_LEFT);
            } else {
                $number = $candidate . '-' . $sequence;
            }
            $sequence++;
        }

        return $number;
    }
}
