<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountBillingDetail;
use App\Models\Client;
use App\Models\FinancialYear;
use App\Models\Quotation;
use App\Models\QuotationEmail;
use App\Models\QuotationItem;
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

class QuotationsController extends Controller
{
    use ConfiguresBrowsershot;

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
                'payment_status' => 'unpaid',
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
            'status' => 'nullable|in:draft,sent,accepted,declined,expired',
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

        $wasCreated = false;
        if ($draftQuotation) {
            $draftQuotation->update([
                'clientid' => $validated['clientid'],
                'quo_number' => $validated['quo_number'],
                'quo_title' => $validated['quo_title'],
                'status' => $validated['status'] ?? 'draft',
                'payment_status' => 'unpaid',
                'issue_date' => $validated['issue_date'],
                'due_date' => $validated['due_date'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'terms' => array_values(array_filter(array_map('trim', (array) $terms))),
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
                'status' => $validated['status'] ?? 'draft',
                'payment_status' => 'unpaid',
                'issue_date' => $validated['issue_date'],
                'due_date' => $validated['due_date'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'terms' => array_values(array_filter(array_map('trim', (array) $terms))),
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

        return response()->json(['ok' => true, 'count' => count($terms)]);
    }

    public function quotationsShow(Quotation $quotation): View
    {
        $quotation->load(['client', 'items']);

        return view('quotations.show', [
            'title' => $quotation->quo_number ?? 'Quotation',
            'subtitle' => 'Quotation Details',
            'quotation' => $quotation,
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

    public function quotationEmailCompose(Quotation $quotation): View
    {
        $quotation->load('client');
        $user = Auth::user();
        $accountid = $this->resolveAccountId();

        $draft = QuotationEmail::query()
            ->where('quotationid', $quotation->quotationid)
            ->where('accountid', $accountid)
            ->latest('created_at')
            ->first();

        return view('quotations.email-compose', [
            'title' => 'Create New Quotation',
            'subtitle' => null,
            'quotation' => $quotation,
            'composeEmail' => $draft,
            'fromEmail' => $user?->email ?? '',
            'toEmail' => old('to_email', $draft?->to_email ?? $quotation->client?->billing_email ?? $quotation->client?->email ?? ''),
            'ccEmail' => old('cc_email', $draft?->cc_email ?? ''),
            'phone' => old('phone_number', $draft?->phone_number ?? $quotation->client?->phone ?? ''),
            'subject' => old('subject', $draft?->subject ?? ('Quotation ' . ($quotation->quo_number ?? $quotation->quotationid))),
            'body' => old('body', $draft?->body ?? "Hello,\n\nPlease find attached quotation PDF " . ($quotation->quo_number ?? $quotation->quotationid) . ".\n\nRegards"),
        ]);
    }

    public function quotationEmailComposeStore(Request $request, Quotation $quotation)
    {
        $validated = $request->validate([
            'channel' => 'required|in:email,whatsapp,sms',
            'to_email' => 'nullable|string|max:255',
            'cc_email' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:50',
            'subject' => 'nullable|string|max:255',
            'body' => 'required|string',
            'action' => 'required|in:save,send',
        ]);

        $accountid = $this->resolveAccountId();
        $user = Auth::user();

        $email = QuotationEmail::create([
            'accountid' => $accountid,
            'quotationid' => $quotation->quotationid,
            'clientid' => $quotation->clientid,
            'from_email' => $user?->email,
            'to_email' => $validated['to_email'] ?? '',
            'cc_email' => $validated['cc_email'] ?? null,
            'phone_number' => $validated['phone_number'] ?? null,
            'subject' => $validated['subject'] ?? null,
            'body' => $validated['body'],
            'attachment_type' => 'quotation',
            'status' => $validated['action'] === 'send' ? 'sent' : 'draft',
            'channel' => $validated['channel'],
            'created_by' => (string) ($user?->userid ?? $user?->id ?? ''),
            'sent_at' => $validated['action'] === 'send' ? now() : null,
        ]);

        if ($validated['action'] === 'send') {
            if (($validated['channel'] ?? 'email') === 'email') {
                $toEmailValue = trim((string) ($validated['to_email'] ?? ''));
                if ($toEmailValue === '') {
                    return back()->withErrors(['to_email' => 'To Email is required for email channel.'])->withInput();
                }

                $quotationPdfUrl = route('quotations.pdf', ['quotation' => $quotation->quotationid, 'download' => 1]);
                $quotationNumber = trim((string) ($quotation->quo_number ?: $quotation->quotationid));
                $emailAttachmentItems = [[
                    'url' => $quotationPdfUrl,
                    'name' => 'Quotation - ' . ($quotationNumber !== '' ? $quotationNumber : $quotation->quotationid) . '.pdf',
                ]];

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
                    'message' => $this->sanitizeComposedMessageBody((string) ($validated['body'] ?? '')),
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
            }

            $quotation->update(['status' => 'sent']);
            return redirect()->route('quotations.show', $quotation->quotationid)
                ->with('success', 'Quotation marked as sent via ' . strtoupper($email->channel) . '.');
        }

        return redirect()->route('quotations.email-compose', $quotation->quotationid)
            ->with('success', 'Draft saved.');
    }

    public function quotationPdf(Request $request, Quotation $quotation): Response
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

        $pdfBinary = $this->getBrowsershot($html)->pdf();
        $filename = 'Quotation - ' . ($quotation->quo_number ?: $quotation->quotationid) . '.pdf';

        return response($pdfBinary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
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

    private function sanitizeComposedMessageBody(string $body): string
    {
        $text = trim($body);
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = str_replace(["\\r\\n", "\\n"], "\n", $text);
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;
        return trim($text);
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
