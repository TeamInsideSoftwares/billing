<?php

namespace App\Http\Controllers;

use App\Models\AccountBillingDetail;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\FinancialYear;
use App\Models\InvoiceEmail;
use App\Models\MessageTemplate;
use App\Models\Setting;
use App\Models\Service;
use App\Models\Tax;
use App\Models\TermsCondition;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

use App\Traits\ConfiguresBrowsershot;

class InvoicesController extends Controller
{
    use ConfiguresBrowsershot;

    private function mapInvoiceTemplateType(Invoice $invoice): string
    {
        return !empty(trim((string) $invoice->ti_number)) ? 'ti' : 'pi';
    }

    private function renderMessageTemplate(string $value, Invoice $invoice, ?string $companyName = null): string
    {
        $clientBusinessName = trim((string) ($invoice->client->business_name ?? ''));
        $clientContactPerson = trim((string) ($invoice->client->contact_name ?? ''));
        $clientName = trim((string) ($clientBusinessName !== '' ? $clientBusinessName : $clientContactPerson));
        $currency = (string) ($invoice->client->currency ?? 'INR');
        $totalAmount = (float) ($invoice->grand_total ?? $invoice->items->sum('line_total') ?? 0);
        $dueDate = $invoice->due_date?->format('d M Y') ?? '';

        $piLink = route('invoices.pdf', ['invoice' => $invoice->invoiceid, 'type' => 'pi']);
        $tiLink = route('invoices.pdf', ['invoice' => $invoice->invoiceid, 'type' => 'tax_invoice']);

        // Get YOUR billing name from Account Billing Details
        $accountid = $this->resolveAccountId();
        $accountBillingDetail = AccountBillingDetail::where('accountid', $accountid)->first();
        $billingName = $accountBillingDetail->billing_name ?? ($companyName ?? '');

        $replace = [
            // Client info - clear unambiguous tags
            '{{client_business_name}}' => $clientBusinessName,
            '{{client_contact_person}}' => $clientContactPerson,
            '{{client_name}}' => $clientName, // Legacy - use client_business_name instead
            // YOUR business info - from Billing Details tab (or Account as fallback)
            '{{business_name}}' => $billingName,
            // Invoice info
            '{{invoice_number}}' => (string) ($invoice->invoice_number ?? ''),
            '{{invoice_title}}' => (string) ($invoice->invoice_title ?? ''),
            '{{pi_number}}' => (string) ($invoice->pi_number ?? ''),
            '{{ti_number}}' => (string) ($invoice->ti_number ?? ''),
            '{{pi_link}}' => $piLink,
            '{{ti_link}}' => $tiLink,
            '{{total_amount}}' => $currency . ' ' . number_format($totalAmount, 2),
            '{{due_date}}' => $dueDate,
            // Backwards compatibility
            '{{company_name}}' => $billingName,
            '{{account_name}}' => $billingName,
        ];

        return strtr($value, $replace);
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

    private function resolveCampioInvoicePdfUrl(Invoice $invoice, bool $isTaxInvoice): string
    {
        $typeKey = $isTaxInvoice ? 'ti' : 'pi';
        $existing = collect($this->listStoredInvoicePdfVersions($invoice))
            ->filter(fn($row) => (string) ($row['type'] ?? '') === $typeKey)
            ->sortByDesc(fn($row) => (int) ($row['version'] ?? 0))
            ->first();

        if (!empty($existing['path'])) {
            return $this->buildFriendlyCampioPdfUrl($invoice, (string) $existing['path'], $isTaxInvoice);
        }

        $saved = $this->persistInvoicePdfVersion($invoice, $isTaxInvoice);
        if (!empty($saved['path'])) {
            return $this->buildFriendlyCampioPdfUrl($invoice, (string) $saved['path'], $isTaxInvoice);
        }

        // Fallback if versioning/storage fails.
        return route('invoices.pdf', ['invoice' => $invoice->invoiceid, 'type' => $isTaxInvoice ? 'tax_invoice' : 'pi']);
    }

    private function buildFriendlyCampioPdfUrl(Invoice $invoice, string $sourcePath, bool $isTaxInvoice): string
    {
        $disk = Storage::disk('public');
        $number = trim((string) ($isTaxInvoice ? ($invoice->ti_number ?: $invoice->invoice_number) : ($invoice->pi_number ?: $invoice->invoice_number)));
        $prefix = $isTaxInvoice ? 'Tax Invoice - ' : 'Proforma Invoice - ';
        $friendlyBase = $prefix . ($number !== '' ? $number : $invoice->invoiceid);
        $friendlyBase = preg_replace('/[\\\\\\/:*?"<>|]+/', '-', $friendlyBase) ?: ($isTaxInvoice ? 'Tax-Invoice' : 'Proforma-Invoice');
        $friendlyBase = preg_replace('/\s+/', '-', $friendlyBase) ?: $friendlyBase;
        $targetPath = 'clients/' . $invoice->clientid . '/invoices-share/' . $friendlyBase . '.pdf';

        if (!$disk->exists($targetPath)) {
            $disk->put($targetPath, (string) $disk->get($sourcePath));
        }

        return asset('storage/' . $targetPath);
    }

    private function buildInvoicePdfAttachment(Invoice $invoice, bool $isTaxInvoice): array
    {
        $invoice->loadMissing(['client.billingDetail', 'items', 'order', 'payments']);

        $accountid = $this->resolveAccountId();
        $account = \App\Models\Account::find($accountid);
        $accountBillingDetail = AccountBillingDetail::query()->where('accountid', $accountid)->first();

        $documentType = $isTaxInvoice ? 'Tax Invoice' : 'Proforma Invoice';

        $normalizeTaxState = fn($v) => preg_replace('/[^A-Z0-9]/', '', strtoupper(trim((string) $v)));
        $clientState = $normalizeTaxState($invoice->client->state ?? '');
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

        $invoiceTerms = is_array($invoice->terms) ? array_values(array_filter($invoice->terms)) : [];
        if (empty($invoiceTerms)) {
            $fallbackType = $isTaxInvoice ? 'billing' : 'proforma';
            $invoiceTerms = TermsCondition::query()
                ->where('accountid', $accountid)
                ->where('type', $fallbackType)
                ->where('is_active', true)
                ->where('is_default', true)
                ->orderByRaw('COALESCE(sequence, 999999), created_at ASC')
                ->pluck('content')
                ->map(fn($term) => trim((string) $term))
                ->filter()
                ->values()
                ->all();
        }

        $html = view('invoices.pdf', compact(
            'invoice',
            'account',
            'accountBillingDetail',
            'sameStateGst',
            'isTaxInvoice',
            'documentType',
            'signatureUrl',
            'invoiceTerms'
        ))->render();

        $docNumber = $isTaxInvoice ? $invoice->ti_number : $invoice->pi_number;
        $filename = $documentType . ' - ' . ($docNumber ?: $invoice->invoice_number) . '.pdf';

        $pdfBinary = $this->getBrowsershot($html)->pdf();

        return [
            'filename' => $filename,
            'binary' => $pdfBinary,
        ];
    }

    private function persistInvoicePdfVersion(Invoice $invoice, bool $isTaxInvoice): ?array
    {
        $invoice->loadMissing(['client']);

        $pdfAttachment = $this->buildInvoicePdfAttachment($invoice, $isTaxInvoice);
        $disk = Storage::disk('public');
        $typeKey = $isTaxInvoice ? 'ti' : 'pi';
        $directory = 'clients/' . $invoice->clientid . '/invoices-pdf';
        $baseName = $invoice->invoiceid . '_' . $typeKey;
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

        return [
            'type' => $typeKey,
            'version' => $nextVersion,
            'filename' => basename($relativePath),
            'path' => $relativePath,
            'url' => asset('storage/' . $relativePath),
            'saved_at' => now()->toDateTimeString(),
        ];
    }

    private function listStoredInvoicePdfVersions(Invoice $invoice): array
    {
        $disk = Storage::disk('public');
        $directory = 'clients/' . $invoice->clientid . '/invoices-pdf';
        if (!$disk->exists($directory)) {
            return [];
        }

        $versions = collect($disk->files($directory))
            ->map(function (string $path) use ($invoice, $disk) {
                $name = pathinfo($path, PATHINFO_FILENAME);
                if (!preg_match('/^' . preg_quote($invoice->invoiceid, '/') . '_(pi|ti)__v(\d+)$/', $name, $m)) {
                    return null;
                }

                return [
                    'type' => $m[1],
                    'version' => (int) $m[2],
                    'filename' => basename($path),
                    'path' => $path,
                    'url' => asset('storage/' . $path),
                    'saved_at' => optional($disk->lastModified($path) ? \Carbon\Carbon::createFromTimestamp($disk->lastModified($path)) : null)?->toDateTimeString(),
                ];
            })
            ->filter()
            ->sortBy([
                ['type', 'asc'],
                ['version', 'desc'],
            ])
            ->values()
            ->all();

        return $versions;
    }

    private function getAccountSettingsMap(string $accountid): array
    {
        return Setting::query()
            ->where('accountid', $accountid)
            ->whereIn('setting_key', [
                'MAIL_MAILER',
                'MAIL_HOST',
                'MAIL_PORT',
                'MAIL_USERNAME',
                'MAIL_PASSWORD',
                'MAIL_ENCRYPTION',
                'MAIL_FROM_ADDRESS',
                'MAIL_FROM_NAME',
            ])
            ->pluck('setting_value', 'setting_key')
            ->map(fn($v) => is_string($v) ? trim($v) : $v)
            ->toArray();
    }

    private function buildCampioRecipientRecord(Invoice $invoice, string $channel, string $toEmail, string $phone): array
    {
        $clientName = trim((string) (
            $invoice->client?->business_name
            ?: $invoice->client?->contact_name
            ?: 'Customer'
        ));

        $record = [
            'id' => (string) ($invoice->clientid ?? $invoice->invoiceid),
            'name' => $clientName,
            'invoice_number' => (string) ($invoice->invoice_number ?? ''),
            'pi_number' => (string) ($invoice->pi_number ?? ''),
            'ti_number' => (string) ($invoice->ti_number ?? ''),
            'invoice_title' => (string) ($invoice->invoice_title ?? ''),
            'due_date' => $invoice->due_date?->format('Y-m-d') ?? '',
            'amount' => (string) ($invoice->grand_total ?? '0'),
        ];

        if ($channel === 'email') {
            $record['email'] = $toEmail;
        } else {
            $record['mobile'] = $phone;
            $record['phone'] = $phone;
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
                if ($fileName === '' || $fileName === '/' || $fileName === '.') {
                    $fileName = 'attachment-' . ($index + 1) . '.pdf';
                }
            }
            $fileName = preg_replace('/[\\\\\\/]+/', '-', $fileName) ?? $fileName;
            $fileName = preg_replace('/\s+/', ' ', trim($fileName)) ?? $fileName;
            if ($fileName === '' || $fileName === '.' || $fileName === '..') {
                $fileName = 'attachment-' . ($index + 1) . '.pdf';
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
        $text = str_replace(["\r\n", "\r"], "\n", $body);

        // Remove auto-appended legacy sections that list raw URLs.
        $text = preg_replace('/\n{2,}(Attachments:|Documents:)\n(?:[^\n]*https?:\/\/[^\n]*\n?)*/i', "\n", $text) ?? $text;

        // Remove any remaining standalone numbered/bulleted URL lines.
        $text = preg_replace('/^\s*(?:\d+\.\s*|-+\s*)https?:\/\/\S+\s*$/im', '', $text) ?? $text;

        // Normalize extra blank lines.
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }

    private function sanitizeForCampioText(string $text): string
    {
        $value = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = str_replace(["\r\n", "\r"], "\n", $value);
        // Remove 4-byte Unicode chars (emoji etc.) for non-utf8mb4 downstream DB columns.
        $value = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $value) ?? $value;
        // Remove control chars except tab/newline.
        $value = preg_replace('/[^\P{C}\n\t]+/u', '', $value) ?? $value;
        $value = preg_replace("/\n{3,}/", "\n\n", $value) ?? $value;
        return trim($value);
    }

    private function resolveDefaultFyId(string $accountid): ?string
    {
        return FinancialYear::query()
            ->where('accountid', $accountid)
            ->orderByDesc('default')
            ->orderByDesc('created_at')
            ->value('fy_id');
    }

    public function invoices()
    {
        $accountid = $this->resolveAccountId();
        $clients = Client::where('accountid', $accountid)->orderBy('business_name')->get();
        $selectedClientId = request('c', request('clientid'));

        $invoiceQuery = Invoice::where('accountid', $accountid)
            ->with(['client', 'items', 'payments'])
            ->latest();

        if ($selectedClientId) {
            $invoiceQuery->where('clientid', $selectedClientId);
        }

        $allInvoices = $invoiceQuery->get();

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'invoices' => $allInvoices->map(function ($invoice) {
                    $amountPaid = (float) ($invoice->amount_paid ?? 0);
                    $grandTotal = (float) ($invoice->grand_total ?? 0);
                    $balanceDue = (float) ($invoice->balance_due ?? max(0, $grandTotal - $amountPaid));
                    $currency = $invoice->client->currency ?? 'INR';

                    $paymentStatus = 'unpaid';
                    if ($amountPaid > 0 && $balanceDue <= 0 && $grandTotal > 0) {
                        $paymentStatus = 'paid';
                    } elseif ($amountPaid > 0) {
                        $paymentStatus = 'partially paid';
                    }

                    return [
                        'record_id' => $invoice->invoiceid,
                        'number' => $invoice->invoice_number,
                        'pi_number' => $invoice->pi_number,
                        'ti_number' => $invoice->ti_number,
                        'title' => $invoice->invoice_title,
                        'clientid' => $invoice->clientid,
                        'issue_date' => $invoice->issue_date?->format('d M Y'),
                        'issue_date_raw' => $invoice->issue_date?->format('Y-m-d'),
                        'due_date' => $invoice->due_date?->format('d M Y'),
                        'due_date_raw' => $invoice->due_date?->format('Y-m-d'),
                        'invoice_for' => ucfirst(str_replace('_', ' ', $invoice->invoice_for ?? 'without orders')),
                        'amount' => $currency . ' ' . number_format($invoice->grand_total ?? 0, 0),
                        'amount_paid' => $currency . ' ' . number_format($amountPaid, 0),
                        'balance_due' => $currency . ' ' . number_format($balanceDue, 0),
                        'status' => $invoice->status ?? 'draft',
                        'payment_status' => $paymentStatus,
                        'items' => $invoice->items->map(function ($item) use ($currency) {
                            return [
                                'itemid' => $item->itemid ?? $item->invoice_itemid ?? '',
                                'name' => $item->item_name,
                                'item_name' => $item->item_name,
                                'qty' => $item->quantity,
                                'quantity' => $item->quantity,
                                'price' => $currency . ' ' . number_format($item->unit_price, 0),
                                'unit_price' => (float) $item->unit_price,
                                'tax_rate' => (float) ($item->tax_rate ?? 0),
                                'discount_percent' => (float) ($item->discount_percent ?? 0),
                                'discount_amount' => (float) ($item->discount_amount ?? 0),
                                'duration' => $item->duration,
                                'frequency' => $item->frequency,
                                'users' => (int) ($item->no_of_users ?? 1),
                                'no_of_users' => (int) ($item->no_of_users ?? 1),
                                'start_date' => $item->start_date?->format('Y-m-d'),
                                'end_date' => $item->end_date?->format('Y-m-d'),
                                'total' => $currency . ' ' . number_format($item->line_total, 0),
                                'line_total' => (float) $item->line_total,
                            ];
                        }),
                    ];
                }),
                'selectedClientId' => $selectedClientId,
            ]);
        }

        $groupedInvoices = $allInvoices->groupBy(function ($invoice) {
            return $invoice->client->business_name ?? $invoice->client->contact_name ?? 'N/A';
        });

        return view('invoices.index', [
            'title' => $selectedClientId ? 'All Invoices' : 'Manage Invoices',
            'subtitle' => $selectedClientId ? 'Filtered by selected client.' : 'Choose a client first to view invoices.',
            'clients' => $clients,
            'groupedInvoices' => $groupedInvoices,
            'selectedClientId' => $selectedClientId,
        ]);
    }

    public function invoicesCreate(): View
    {
        $user = auth()->user();
        $accountid = $this->resolveAccountId();
        $legacyAccountId = $user?->id ? (string) $user->id : null;
        $account = \App\Models\Account::find($accountid);
        $orderId = request('o', request('orderid'));
        $clientId = request('c', request('clientid'));
        $invoiceFor = request('invoice_for', session('invoice_for'));
        $currentUserId = $user?->userid ?? $user?->id;
        $draftId = request('d');

        $existingDraft = null;
        if (!empty($draftId) && !empty($currentUserId)) {
            $existingDraft = Invoice::query()
                ->where('invoiceid', $draftId)
                ->where('accountid', $accountid)
                ->first();
        }

        if (empty($invoiceFor) && !empty($existingDraft?->invoice_for)) {
            $invoiceFor = $existingDraft->invoice_for;
            session(['invoice_for' => $invoiceFor]);
        }

        if (empty($existingDraft) && empty($draftId) && !empty($clientId) && !empty($currentUserId)) {
            $existingDraft = Invoice::query()
                ->where('clientid', $clientId)
                ->where('status', 'draft')
                ->where('created_by', $currentUserId)
                ->when($invoiceFor === 'orders' && !empty($orderId), fn($q) => $q->where('orderid', $orderId))
                ->when($invoiceFor === 'orders' && empty($orderId), fn($q) => $q->whereNull('orderid'))
                ->when($invoiceFor !== 'orders', fn($q) => $q->whereNull('orderid'))
                ->where('updated_at', '>', now()->subHours(24))
                ->latest('updated_at')
                ->first();
        }

        $nextInvoiceNumber = $existingDraft?->pi_number ?: $this->generateInvoiceNumber();
        $nextTaxInvoiceNumber = $existingDraft?->ti_number ?: $this->generateTaxInvoiceNumber();

        // Get selected client currency
        $selectedClientCurrency = 'INR';
        if (!empty($clientId)) {
            $client = Client::find($clientId);
            $selectedClientCurrency = $client?->currency ?? 'INR';
        }

        $termAccountIds = array_values(array_filter(array_unique([$accountid, $legacyAccountId])));

        $billingTerms = TermsCondition::query()
            ->whereIn('accountid', $termAccountIds)
            ->where('type', 'billing')
            ->where('is_active', true)
            ->orderByRaw('COALESCE(sequence, 999999), created_at ASC')
            ->get();
        $proformaTerms = TermsCondition::query()
            ->whereIn('accountid', $termAccountIds)
            ->where('type', 'proforma')
            ->where('is_active', true)
            ->orderByRaw('COALESCE(sequence, 999999), created_at ASC')
            ->get();

        $accountBillingDetail = AccountBillingDetail::query()
            ->whereIn('accountid', $termAccountIds)
            ->orderByRaw('accountid = ? desc', [$accountid])
            ->first();

        return view('invoices.create', [
            'title' => 'Create Invoice',
            'clients' => Client::orderBy('business_name')->get(),
            'services' => Service::with(['category', 'costings'])->orderBy('sequence')->orderBy('name')->get(),
            'taxes' => ($account && $account->allow_multi_taxation) ? Tax::where('accountid', $accountid)->where('is_active', true)->orderByRaw('COALESCE(sequence, 999999), created_at DESC')->get() : collect(),
            'nextInvoiceNumber' => $nextInvoiceNumber,
            'nextTaxInvoiceNumber' => $nextTaxInvoiceNumber,
            'account' => $account,
            'accountBillingDetail' => $accountBillingDetail,
            'billingTerms' => $billingTerms,
            'proformaTerms' => $proformaTerms,
            'orderId' => $orderId,
            'clientId' => $clientId,
            'invoice' => $existingDraft,
            'isTaxInvoice' => request('tax_invoice', 0) == 1,
            'selectedClientCurrency' => $selectedClientCurrency,
        ]);
    }

    public function getClientOrders(Request $request)
    {
        $clientId = $request->input('clientid');

        if (!$clientId) {
            return response()->json([]);
        }

        $orders = \App\Models\Order::where('clientid', $clientId)
            ->where('is_verified', 'yes')
            ->whereDoesntHave('invoices')
            ->with(['client:clientid,currency', 'salesPerson'])
            ->withCount('items')
            ->orderBy('order_date', 'desc')
            ->get(['orderid', 'order_number', 'order_title', 'order_date', 'delivery_date', 'status', 'clientid', 'is_verified', 'sales_person_id', 'grand_total'])
            ->map(function ($order) {
                $currency = $order->client->currency ?? 'INR';

                return [
                    'orderid' => $order->orderid,
                    'order_number' => $order->order_number,
                    'order_title' => $order->order_title,
                    'order_date' => $order->order_date?->format('d M Y') ?? 'N/A',
                    'delivery_date' => $order->delivery_date?->format('d M Y') ?? 'N/A',
                    'grand_total' => $order->grand_total ?? '0',
                    'currency' => $currency,
                    'status' => $order->status ?? 'draft',
                    'is_verified' => $order->is_verified ?? 'no',
                    'sales_person' => $order->salesPerson->name ?? '-',
                    'item_count' => (int) ($order->items_count ?? 0),
                ];
            });

        return response()->json($orders);
    }

    public function getRenewalInvoices(Request $request)
    {
        $clientId = $request->input('clientid');

        if (!$clientId) {
            return response()->json([]);
        }

        $recurringFrequencies = ['daily', 'weekly', 'bi-weekly', 'monthly', 'yearly', 'quarterly', 'semi-annually'];
        $invoices = Invoice::where('clientid', $clientId)
            ->whereNotNull('ti_number')
            ->where('ti_number', '!=', '')
            ->with('items', 'client')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($invoice) use ($recurringFrequencies) {
                $today = now()->startOfDay();

                $renewalItems = $invoice->items
                    ->map(function ($item) use ($today, $recurringFrequencies) {
                        if (!$item->end_date) {
                            return null;
                        }

                        $hasRecurringFrequency = in_array($item->frequency, $recurringFrequencies, true);
                        if (!$hasRecurringFrequency) {
                            return null;
                        }

                        $itemEndDate = $item->end_date instanceof \Carbon\Carbon
                            ? $item->end_date
                            : \Carbon\Carbon::parse($item->end_date);

                        $isExpired = $itemEndDate <= $today;
                        if (!$isExpired) {
                            return null;
                        }

                        return [
                            'invoice_itemid' => $item->invoice_itemid,
                            'itemid' => $item->itemid,
                            'item_name' => $item->item_name,
                            'quantity' => (float) ($item->quantity ?? 0),
                            'unit_price' => (float) ($item->unit_price ?? 0),
                            'tax_rate' => (float) ($item->tax_rate ?? 0),
                            'discount_percent' => (float) ($item->discount_percent ?? 0),
                            'discount_amount' => (float) ($item->discount_amount ?? 0),
                            'duration' => $item->duration,
                            'frequency' => $item->frequency,
                            'no_of_users' => $item->no_of_users ? (int) $item->no_of_users : null,
                            'start_date' => $item->start_date?->format('Y-m-d'),
                            'end_date' => $item->end_date?->format('Y-m-d'),
                            'line_total' => (float) ($item->line_total ?? 0),
                            'is_expired' => $isExpired,
                        ];
                    })
                    ->filter()
                    ->values();

                $result = [
                    'invoiceid' => $invoice->invoiceid,
                    'invoice_number' => $invoice->invoice_number,
                    'grand_total' => $invoice->grand_total ?? '0',
                    'currency' => $invoice->client->currency ?? 'INR',
                    'total_items' => $invoice->items->count(),
                    'expired_items' => $renewalItems->where('is_expired', true)->count(),
                    'has_expired' => $renewalItems->where('is_expired', true)->isNotEmpty(),
                    'items' => $renewalItems,
                ];

                return $result;
            })
            ->filter(function ($invoice) {
                return $invoice['has_expired'];
            })
            ->values();

        return response()->json($invoices);
    }

    public function getOrderItems(Request $request, $orderid)
    {
        $order = \App\Models\Order::with([
            'items.item:itemid,user_wise',
            'client:clientid,currency',
            'salesPerson',
        ])->findOrFail($orderid);

        $items = $order->items->map(function ($item) {
            $taxRate = (float) ($item->tax_rate ?? 0);
            $lineTotal = (float) ($item->line_total ?? 0);
            return [
                'itemid' => $item->itemid,
                'item_name' => $item->item_name,
                'item_description' => $item->item_description,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'tax_rate' => $taxRate,
                'discount_percent' => $item->discount_percent ?? 0,
                'discount_amount' => $item->discount_amount ?? 0,
                'duration' => $item->duration,
                'frequency' => $item->frequency,
                'no_of_users' => $item->no_of_users,
                'start_date' => $item->start_date?->format('Y-m-d'),
                'end_date' => $item->end_date?->format('Y-m-d'),
                'delivery_date' => $item->delivery_date?->format('Y-m-d'),
                'line_total' => $lineTotal,
                'requires_user_fields' => $this->isUserWiseEnabled(optional($item->item)->user_wise),
            ];
        })->values();

        return response()->json([
            'order' => [
                'orderid' => $order->orderid,
                'order_number' => $order->order_number,
                'order_title' => $order->order_title,
                'order_date' => $order->order_date?->format('d M Y') ?? 'N/A',
                'delivery_date' => $order->delivery_date?->format('d M Y') ?? 'N/A',
                'grand_total' => $order->grand_total,
                'currency' => $order->client->currency ?? 'INR',
                'sales_person' => $order->salesPerson->name ?? '-',
                'is_verified' => $order->is_verified ?? 'no',
                'item_count' => $order->items->count(),
            ],
            'items' => $items,
        ]);
    }

    public function getRenewalItems(Request $request, $invoiceid)
    {
        $invoice = Invoice::with('items')->findOrFail($invoiceid);
        $daysFilter = (int) $request->input('days', 1);

        $today = now()->startOfDay();
        $upcomingThreshold = now()->addDays($daysFilter)->endOfDay();

        \Log::info('getRenewalItems', [
            'invoiceid' => $invoiceid,
            'invoice_number' => $invoice->invoice_number,
            'total_items' => $invoice->items->count(),
        ]);

        $items = $invoice->items->map(function ($item) use ($today, $upcomingThreshold) {
            if (!$item->end_date) {
                $isExpired = false;
                $isUpcoming = false;
            } else {
                $itemEndDate = $item->end_date instanceof \Carbon\Carbon
                    ? $item->end_date
                    : \Carbon\Carbon::parse($item->end_date);

                $isExpired = $itemEndDate <= $today;
                $isUpcoming = !$isExpired && $itemEndDate > $today && $itemEndDate <= $upcomingThreshold;
            }

            $taxRate = (float) ($item->tax_rate ?? 0);
            $lineTotal = (float) ($item->line_total ?? 0);

            return [
                'invoice_itemid' => $item->invoice_itemid,
                'itemid' => $item->itemid,
                'item_name' => $item->item_name,
                'item_description' => $item->item_description,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'tax_rate' => $taxRate,
                'discount_percent' => $item->discount_percent ?? 0,
                'discount_amount' => $item->discount_amount ?? 0,
                'duration' => $item->duration,
                'frequency' => $item->frequency,
                'no_of_users' => $item->no_of_users,
                'start_date' => $item->start_date?->format('Y-m-d'),
                'end_date' => $item->end_date?->format('Y-m-d'),
                'line_total' => $lineTotal,
                'is_expired' => $isExpired,
                'is_upcoming' => $isUpcoming,
            ];
        })->values();

        return response()->json([
            'invoice' => [
                'invoiceid' => $invoice->invoiceid,
                'invoice_number' => $invoice->invoice_number,
                'grand_total' => $invoice->grand_total,
            ],
            'items' => $items,
        ]);
    }

    private function calculateInvoiceItemAmounts(array $itemData, float $taxRate): array
    {
        $lineTotal = $this->wholeAmount($itemData['line_total'] ?? 0);
        $discountPercent = $this->wholePercent($itemData['discount_percent'] ?? 0);

        $discountAmount = ($lineTotal * $discountPercent) / 100;
        $taxableAmount = max(0, $lineTotal - $discountAmount);
        $taxAmount = ($taxableAmount * $taxRate) / 100;

        return [
            'line_total' => round($lineTotal, 0),
            'discount_percent' => $discountPercent,
            'discount_amount' => $this->roundDiscountDown($discountAmount),
            'tax_amount' => $this->roundTaxUp($taxAmount),
        ];
    }

    private function roundTaxUp(float $amount): float
    {
        return (float) ceil(max(0, $amount));
    }

    private function roundDiscountDown(float $amount): float
    {
        return (float) floor(max(0, $amount));
    }

    private function wholeAmount(mixed $value): float
    {
        return (float) round(max(0, (float) $value), 0);
    }

    private function wholePercent(mixed $value): float
    {
        return (float) min(100, max(0, round((float) $value, 0)));
    }

    private function wholeQuantity(mixed $value): int
    {
        return max(1, (int) round((float) $value, 0));
    }

    private function isUserWiseEnabled(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        $normalized = strtolower(trim((string) $value));
        return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
    }

    private function hasRecurringFrequency(mixed $frequency): bool
    {
        $normalized = strtolower(trim((string) ($frequency ?? '')));
        return $normalized !== '' && $normalized !== 'one-time';
    }

    public function storeBillingTerm(Request $request)
    {
        $validated = $request->validate([
            'content' => 'required|string',
            'type' => 'nullable|in:billing,quotation,proforma',
        ]);

        $user = auth()->user();
        $accountid = $user?->accountid ?? (string) ($user?->id ?? 'ACC0000001');
        $termType = $validated['type'] ?? 'billing';
        $maxSequence = TermsCondition::query()
            ->where('accountid', $accountid)
            ->where('type', $termType)
            ->max('sequence');

        $term = TermsCondition::create([
            'accountid' => $accountid,
            'type' => $termType,
            'content' => $validated['content'],
            'is_active' => true,
            'sequence' => ((int) ($maxSequence ?? 0)) + 1,
        ]);

        return response()->json([
            'ok' => true,
            'term' => [
                'id' => $term->tc_id,
                'content' => $term->content,
                'type' => $term->type,
            ],
        ]);
    }
    public function applyTerms(Request $request, string $invoice)
    {
        $invoice = $this->resolveInvoiceDocument($invoice);

        $request->validate([
            'terms' => 'nullable|array',
            'terms.*' => 'string',
        ]);

        $terms = array_values(array_filter($request->input('terms', []), fn($t) => trim($t) !== ''));
        $invoice->update(['terms' => $terms ?: null]);

        $this->persistInvoicePdfVersion($invoice, !empty(trim((string) $invoice->ti_number)));

        return response()->json(['ok' => true, 'count' => count($terms)]);
    }


    protected function generateInvoiceNumber(): string
    {
        $accountid = auth()->check() ? (auth()->user()->accountid ?? 'ACC0000001') : 'ACC0000001';

        // Use dedicated proforma configuration for PI generation.
        $serialConfig = \App\Models\SerialConfiguration::where('accountid', $accountid)
            ->where('document_type', 'proforma_invoice')
            ->first();

        if ($serialConfig) {
            $candidate = $serialConfig->generateNextSerialNumber();
            return $this->ensureUniqueDocumentNumber($candidate !== '' ? $candidate : 'INV-0001', $accountid, 'pi_number');
        }

        // Fallback: simple auto-increment if no serial configuration exists.
        $count = Invoice::where('accountid', $accountid)->count();
        $candidate = 'INV-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
        return $this->ensureUniqueDocumentNumber($candidate, $accountid, 'pi_number');
    }

    protected function generateTaxInvoiceNumber(): string
    {
        $accountid = auth()->check() ? (auth()->user()->accountid ?? 'ACC0000001') : 'ACC0000001';

        // Check for SerialConfiguration
        $serialConfig = \App\Models\SerialConfiguration::where('accountid', $accountid)
            ->where('document_type', 'tax_invoice')
            ->first();

        if ($serialConfig) {
            $candidate = $serialConfig->generateNextSerialNumber();
            return $this->ensureUniqueDocumentNumber($candidate !== '' ? $candidate : 'TAX-0001', $accountid, 'ti_number');
        }

        // Fallback
        $count = Invoice::where('accountid', $accountid)->whereNotNull('ti_number')->where('ti_number', '!=', '')->count();
        $candidate = 'TAX-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);

        return $this->ensureUniqueDocumentNumber($candidate, $accountid, 'ti_number');
    }

    protected function ensureUniqueDocumentNumber(string $candidate, string $accountid, ?string $numberColumn = null): string
    {
        $candidate = trim($candidate);

        if ($candidate === '') {
            $candidate = 'INV-0001';
        }

        $number = $candidate;
        $sequence = 2;

        while (
            Invoice::where('accountid', $accountid)
                ->when($numberColumn, fn($query) => $query->where($numberColumn, $number), function ($query) use ($number) {
                    $query->where(function ($inner) use ($number) {
                        $inner->where('pi_number', $number)
                            ->orWhere('ti_number', $number);
                    });
                })
                ->exists()
        ) {
            $number = $this->incrementConfiguredNumberPart($candidate, $sequence - 1);
            $sequence++;
        }

        return $number;
    }

    protected function incrementConfiguredNumberPart(string $candidate, int $incrementBy): string
    {
        if ($incrementBy <= 0) {
            return $candidate;
        }

        if (!preg_match_all('/\d+/', $candidate, $matches, PREG_OFFSET_CAPTURE) || empty($matches[0])) {
            return $candidate . '-' . ($incrementBy + 1);
        }

        $groups = $matches[0];
        $targetIndex = count($groups) - 1;

        // If the trailing numeric group looks like a year and there is a prior numeric group,
        // increment the prior group so we don't mutate the year suffix.
        if (count($groups) > 1) {
            $lastDigits = (string) $groups[$targetIndex][0];
            $lastValue = (int) $lastDigits;
            $looksLikeYear = strlen($lastDigits) === 4 && $lastValue >= 1900 && $lastValue <= 2200;
            if ($looksLikeYear) {
                $targetIndex = count($groups) - 2;
            }
        }

        $targetDigits = (string) $groups[$targetIndex][0];
        $offset = (int) $groups[$targetIndex][1];
        $newValue = (int) $targetDigits + $incrementBy;
        $replacement = str_pad((string) $newValue, strlen($targetDigits), '0', STR_PAD_LEFT);

        return substr_replace($candidate, $replacement, $offset, strlen($targetDigits));
    }

    public function invoicesStore(Request $request)
    {
        try {
            $validated = $request->validate([
                'invoiceid' => 'nullable|exists:invoices,invoiceid',
                'clientid' => 'required|exists:clients,clientid',
                'orderid' => 'nullable|exists:orders,orderid',
                'invoice_number' => 'nullable|string',
                'invoice_title' => 'required|string|max:255',
                'issue_date' => 'required|date',
                'due_date' => 'required|date|after_or_equal:issue_date',
                'notes' => 'nullable|string',
                'terms' => 'nullable|string',
                'status' => 'nullable|in:active,cancelled',
                'items_data' => 'required|json',
                'accountid' => 'nullable|size:10',
                'renewed_item_ids' => 'nullable|string',
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw ValidationException::withMessages([
                'general' => 'Invalid form data. Please check all fields and try again.',
            ]);
        }

        $itemsData = json_decode($request->items_data, true);
        if (!is_array($itemsData) || empty($itemsData)) {
            throw ValidationException::withMessages([
                'items_data' => 'Add at least one invoice item before submitting.',
            ]);
        }

        // Set default status if not provided
        $validated['status'] = $validated['status'] ?? 'active';
        if (!empty($validated['invoice_number'])) {
            $this->assertDocumentNumberAvailable($validated['invoice_number'], null, 'pi_number');
        }

        $invoiceSource = 'without_orders';
        if (!empty($validated['orderid'])) {
            $invoiceSource = 'orders';
        } elseif (!empty($validated['renewed_item_ids'])) {
            $invoiceSource = 'renewal';
        }

        if ($invoiceSource === 'orders') {
            if (empty($validated['orderid'])) {
                throw ValidationException::withMessages([
                    'orderid' => 'Select an order before creating an invoice from orders.',
                ]);
            }

            $order = \App\Models\Order::with(['invoices'])
                ->where('orderid', $validated['orderid'])
                ->where('clientid', $validated['clientid'])
                ->first();

            if (!$order) {
                throw ValidationException::withMessages([
                    'orderid' => 'The selected order does not belong to this client.',
                ]);
            }

            if ($order->invoices->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'orderid' => 'The selected order already has an invoice.',
                ]);
            }
        } elseif ($invoiceSource === 'renewal') {
            $validated['orderid'] = null;
        }

        $user = auth()->user();
        $client = Client::findOrFail($validated['clientid']);
        $validated['accountid'] = $validated['accountid'] ?? ($user->accountid ?? 'ACC0000001');
        $validated['fy_id'] = $this->resolveDefaultFyId($validated['accountid']);
        $validated['created_by'] = $user?->userid ?? $user?->id;
        // TI number is assigned only when converting PI to Tax Invoice.
        // Keep it empty for PI/draft creation so insert works on older schemas too.
        $validated['ti_number'] = $validated['ti_number'] ?? '';
        unset($validated['items_data']);

        // Check if we're updating an existing draft
        $existingDraft = null;
        if (!empty($validated['invoiceid'])) {
            $existingDraft = Invoice::whereIn('status', ['draft', 'active', 'cancelled'])
                ->find($validated['invoiceid']);
            if ($existingDraft) {
                // Use draft's existing PI number
                $validated['pi_number'] = $existingDraft->pi_number;
                if (empty($existingDraft->fy_id) && !empty($validated['fy_id'])) {
                    $existingDraft->fy_id = $validated['fy_id'];
                    $existingDraft->save();
                }
            }
        } else {
            // Generate new number
            $validated['pi_number'] = $this->generateInvoiceNumber();
            $this->assertDocumentNumberAvailable($validated['pi_number'], null, 'pi_number');
        }
        unset($validated['invoice_number']);
        unset($validated['invoice_for']);

        $itemsData = json_decode($request->items_data, true);
        if (!is_array($itemsData) || empty($itemsData)) {
            throw ValidationException::withMessages([
                'items_data' => 'Add at least one invoice item before submitting.',
            ]);
        }

        $subtotal = 0;
        $taxTotal = 0;
        $discountTotal = 0;
        $preparedItems = [];

        $accountid = auth()->check() ? (auth()->user()->accountid ?? 'ACC0000001') : 'ACC0000001';
        $account = \App\Models\Account::find($accountid);
        $accountHasUsers = (bool) ($account?->have_users ?? false);

        foreach ($itemsData as $index => $itemData) {
            $itemId = $itemData['itemid'] ?? null;
            $service = $itemId ? Service::find($itemId) : null;
            $quantity = $this->wholeQuantity($itemData['quantity'] ?? 1);
            $unitPrice = $this->wholeAmount($itemData['unit_price'] ?? 0);
            $taxRate = (float) ($itemData['tax_rate'] ?? 0);
            $amounts = $this->calculateInvoiceItemAmounts($itemData, $taxRate);
            $isUserWiseItem = $accountHasUsers && $this->isUserWiseEnabled($service?->user_wise);
            $hasRecurringFrequency = $this->hasRecurringFrequency($itemData['frequency'] ?? null);
            $users = $isUserWiseItem ? max(1, (int) ($itemData['no_of_users'] ?? 1)) : null;
            $lineTotal = $amounts['line_total'];

            if ($quantity <= 0) {
                throw ValidationException::withMessages([
                    'items_data' => 'Each invoice item must have a quantity greater than 0.',
                ]);
            }

            if ($unitPrice < 0 || $lineTotal < 0) {
                throw ValidationException::withMessages([
                    'items_data' => 'Item amounts cannot be negative.',
                ]);
            }

            $subtotal += $lineTotal;
            $discountTotal += $amounts['discount_amount'];
            $taxTotal += $amounts['tax_amount'];

            $preparedItems[] = [
                'invoiceid' => null,
                'itemid' => $itemId,
                'item_name' => $itemData['item_name'] ?? ($service?->name ?? 'Custom Item'),
                'item_description' => $itemData['item_description'] ?? null,
                'quantity' => $this->wholeQuantity($quantity),
                'unit_price' => $unitPrice,
                'tax_rate' => $taxRate,
                'discount_percent' => $amounts['discount_percent'],
                'discount_amount' => $amounts['discount_amount'],
                'duration' => $itemData['duration'] ?? null,
                'frequency' => $itemData['frequency'] ?? null,
                'no_of_users' => $users,
                'start_date' => $hasRecurringFrequency ? ($itemData['start_date'] ?? null) : null,
                'end_date' => $hasRecurringFrequency ? ($itemData['end_date'] ?? null) : null,
                'amount' => $lineTotal,
            ];
        }

        $discountTotal = $this->roundDiscountDown($discountTotal);
        $taxTotal = $this->roundTaxUp($taxTotal);
        $grandTotal = $subtotal - $discountTotal + $taxTotal;
        $invoice = null;

        try {
            DB::transaction(function () use ($validated, $preparedItems, &$invoice, $request, $existingDraft, $invoiceSource) {
                if ($existingDraft) {
                    // Update existing draft
                    $existingDraft->update($validated);
                    $invoice = $existingDraft;

                    // Delete existing items
                    InvoiceItem::where('invoiceid', $invoice->invoiceid)->delete();
                } else {
                    // Create new invoice
                    $invoice = Invoice::create($validated);
                }

                foreach ($preparedItems as $itemData) {
                    $itemData['invoiceid'] = $invoice->invoiceid;
                    InvoiceItem::create($itemData);
                }

                // Mark the linked order as completed when PI is created
                if (!empty($validated['orderid'])) {
                    \App\Models\Order::where('orderid', $validated['orderid'])
                        ->whereNotIn('status', ['cancelled'])
                        ->update(['status' => 'completed']);
                }

                // If this is a renewal, mark the original items as renewed
                if ($invoiceSource === 'renewal') {
                    $renewedItemIdsRaw = $request->input('renewed_item_ids');
                    $renewedItemIds = json_decode($renewedItemIdsRaw ?? '[]', true);

                    \Log::info('Renewal submission check', [
                        'invoice_for' => $invoiceSource,
                        'renewed_item_ids_raw' => $renewedItemIdsRaw,
                        'renewed_item_ids_parsed' => $renewedItemIds,
                        'new_invoice_id' => $invoice->invoiceid,
                    ]);

                    if (!empty($renewedItemIds)) {
                        \Log::info('Renewal item ids received; skipping legacy renewed_* column updates on merged invoice_items table', [
                            'invoice_id' => $invoice->invoiceid,
                            'renewed_invoice_item_ids' => $renewedItemIds,
                        ]);
                    } else {
                        \Log::warning('No renewed_item_ids found for renewal invoice');
                    }
                }
            });
        } catch (\Exception $e) {
            \Log::error('Failed to create invoice: ' . $e->getMessage(), [
                'validated' => $validated,
                'preparedItems' => $preparedItems,
                'trace' => $e->getTraceAsString(),
            ]);
            throw ValidationException::withMessages([
                'general' => 'Failed to create invoice. Please try again or contact support if the issue persists.',
            ]);
        }

        return redirect()->route('invoices.index')->with('success', 'Invoice created successfully with items.');
    }

    public function invoicesSaveDraft(Request $request)
    {
        $validated = $request->validate([
            'invoiceid' => 'nullable|exists:invoices,invoiceid',
            'invoice_for' => 'nullable|in:orders,renewal,without_orders',
            'clientid' => 'required|exists:clients,clientid',
            'orderid' => 'nullable|exists:orders,orderid',
            'invoice_title' => 'sometimes|required|string|max:255',
            'issue_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:issue_date',
            'notes' => 'nullable|string',
            'status' => 'nullable|in:draft,active,cancelled',
            'items_data' => 'nullable|json',
        ]);

        $user = auth()->user();
        $accountid = $this->resolveAccountId();
        $legacyAccountId = $user?->id ? (string) $user->id : null;
        $accountCandidates = array_values(array_filter(array_unique([$accountid, $legacyAccountId])));
        $client = Client::findOrFail($validated['clientid']);

        // Check if draft already exists for this client
        $orderId = $validated['orderid'] ?? null;

        $draft = null;
        $isExplicitInvoiceEdit = !empty($validated['invoiceid']);
        if (!empty($validated['invoiceid'])) {
            // Explicit edit flow: always target this exact invoice id.
            // Do not restrict by status here; older records may have different status values.
            $draft = Invoice::where('invoiceid', $validated['invoiceid'])
                ->first();
        }

        if (!$draft && !$isExplicitInvoiceEdit) {
            $draft = Invoice::where('clientid', $validated['clientid'])
                ->whereIn('accountid', $accountCandidates)
                ->whereIn('status', ['active', 'draft'])
                ->where('created_by', $user?->userid ?? $user?->id)
                ->when(!empty($orderId), fn($q) => $q->where('orderid', $orderId))
                ->when(empty($orderId), fn($q) => $q->whereNull('orderid'))
                ->where('updated_at', '>', now()->subHours(24))
                ->first();
        }

        if (!$draft && $isExplicitInvoiceEdit) {
            return response()->json([
                'ok' => false,
                'message' => 'Invoice not found for editing.',
            ], 404);
        }

        if ($draft) {
            // Update existing draft
            $draft->update([
                'invoice_title' => $validated['invoice_title'] ?? $draft->invoice_title,
                'orderid' => $orderId,
                'issue_date' => $validated['issue_date'] ?? $draft->issue_date,
                'due_date' => $validated['due_date'] ?? $draft->due_date,
                'notes' => $validated['notes'] ?? $draft->notes,
                'fy_id' => $draft->fy_id ?: $this->resolveDefaultFyId($user->accountid ?? 'ACC0000001'),
            ]);
        } else {
            // Create new draft
            $draft = Invoice::create([
                'accountid' => $accountid,
                'fy_id' => $this->resolveDefaultFyId($accountid),
                'clientid' => $validated['clientid'],
                'pi_number' => $this->generateInvoiceNumber(),
                'ti_number' => null,
                'invoice_title' => $validated['invoice_title'] ?? '',
                'orderid' => $orderId,
                'issue_date' => $validated['issue_date'] ?? now(),
                'due_date' => $validated['due_date'] ?? now()->addDays(7),
                'notes' => $validated['notes'] ?? '',
                'status' => 'active',
                'created_by' => $user?->userid ?? $user?->id,
            ]);
        }

        $calculatedSubtotal = 0;
        $calculatedDiscountTotal = 0;
        $calculatedTaxTotal = 0;
        $rawItemsData = $request->input('items_data');
        if ($rawItemsData !== null) {
            $itemsData = json_decode($rawItemsData, true);
            if (!is_array($itemsData)) {
                $itemsData = [];
            }

            $draftItems = [];
            foreach ($itemsData as $index => $itemData) {
                if (!is_array($itemData)) {
                    continue;
                }

                $itemName = trim((string) ($itemData['item_name'] ?? ''));
                if ($itemName === '') {
                    continue;
                }

                $taxRate = (float) ($itemData['tax_rate'] ?? 0);
                $amounts = $this->calculateInvoiceItemAmounts($itemData, $taxRate);
                $calculatedSubtotal += (float) $amounts['line_total'];
                $calculatedDiscountTotal += (float) $amounts['discount_amount'];
                $calculatedTaxTotal += (float) $amounts['tax_amount'];

                $draftItems[] = [
                    'itemid' => $itemData['itemid'] ?? null,
                    'item_name' => $itemName,
                    'item_description' => $itemData['item_description'] ?? null,
                    'quantity' => max(0, (int) round((float) ($itemData['quantity'] ?? 0), 0)),
                    'unit_price' => $this->wholeAmount($itemData['unit_price'] ?? 0),
                    'tax_rate' => $taxRate,
                    'discount_percent' => $this->wholePercent($itemData['discount_percent'] ?? 0),
                    'discount_amount' => max(0, (float) $amounts['discount_amount']),
                    'duration' => $itemData['duration'] ?? null,
                    'frequency' => $itemData['frequency'] ?? null,
                    'no_of_users' => !empty($itemData['no_of_users']) ? max(1, (int) $itemData['no_of_users']) : null,
                    'start_date' => $itemData['start_date'] ?? null,
                    'end_date' => $itemData['end_date'] ?? null,
                    'amount' => $this->wholeAmount($amounts['line_total'] ?? 0),
                ];
            }

            $draft->items()->delete();
            if (!empty($draftItems)) {
                $draft->items()->createMany($draftItems);
            }
        }

        if ($rawItemsData !== null) {
            $calculatedDiscountTotal = $this->roundDiscountDown($calculatedDiscountTotal);
            $calculatedTaxTotal = $this->roundTaxUp($calculatedTaxTotal);
            $calculatedGrandTotal = max(0, $calculatedSubtotal - $calculatedDiscountTotal + $calculatedTaxTotal);
            $amountPaid = (float) ($draft->amount_paid ?? 0);
            $calculatedBalanceDue = max(0, $calculatedGrandTotal - $amountPaid);

            $draft->update(['status' => 'active']);

            $this->persistInvoicePdfVersion($draft, !empty(trim((string) $draft->ti_number)));
        }

        return response()->json([
            'ok' => true,
            'invoiceid' => $draft->invoiceid,
            'invoice_number' => $draft->invoice_number,
            'pi_number' => $draft->pi_number,
            'ti_number' => $draft->ti_number,
        ]);
    }

    public function invoicesGetDraft($clientid)
    {
        $user = auth()->user();
        $accountid = $this->resolveAccountId();
        $orderId = request('o', request('orderid'));
        $draftId = request('d');

        $draft = null;
        if (!empty($draftId)) {
            $draft = Invoice::where('invoiceid', $draftId)
                ->where('accountid', $accountid)
                ->with(['items.item', 'order'])
                ->first();
        }

        if (!$draft && empty($draftId)) {
            $draft = Invoice::where('clientid', $clientid)
                ->where('accountid', $accountid)
                ->whereIn('status', ['active', 'draft'])
                ->where('created_by', $user?->userid ?? $user?->id)
                ->when(!empty($orderId), fn($q) => $q->where('orderid', $orderId))
                ->when(empty($orderId), fn($q) => $q->whereNull('orderid'))
                ->where('updated_at', '>', now()->subHours(24))
                ->with(['items.item', 'order'])
                ->first();
        }

        if (!$draft) {
            return response()->json(['ok' => false]);
        }

        return response()->json([
            'ok' => true,
            'draft' => [
                'invoiceid' => $draft->invoiceid,
                'invoice_number' => $draft->invoice_number,
                'pi_number' => $draft->pi_number,
                'ti_number' => $draft->ti_number,
                'invoice_title' => $draft->invoice_title,
                'invoice_for' => $draft->invoice_for,
                'orderid' => $draft->orderid,
                'po_number' => $draft->order?->po_number,
                'po_date' => $draft->order?->po_date?->format('Y-m-d'),
                'issue_date' => $draft->issue_date?->format('Y-m-d'),
                'due_date' => $draft->due_date?->format('Y-m-d'),
                'notes' => $draft->notes,
                'terms' => $draft->terms ?? [],
                'status' => $draft->status,
                'items' => $draft->items->map(fn($i) => [
                    'invoice_itemid' => $i->invoice_itemid,
                    'itemid' => $i->itemid,
                    'item_name' => $i->item_name,
                    'item_description' => $i->item_description,
                    'quantity' => $i->quantity,
                    'unit_price' => $i->unit_price,
                    'tax_rate' => $i->tax_rate,
                    'discount_percent' => $i->discount_percent ?? 0,
                    'discount_amount' => $i->discount_amount ?? 0,
                    'duration' => $i->duration,
                    'frequency' => $i->frequency,
                    'no_of_users' => $i->no_of_users,
                    'start_date' => $i->start_date?->format('Y-m-d'),
                    'end_date' => $i->end_date?->format('Y-m-d'),
                    'line_total' => $i->line_total,
                    'requires_user_fields' => $this->isUserWiseEnabled(optional($i->item)->user_wise),
                ]),
            ],
        ]);
    }

    public function invoicesShow(string $invoice): View
    {
        $invoice = $this->resolveInvoiceDocument($invoice);
        $invoice->loadMissing(['client', 'items.service', 'order', 'payments']);

        // Load account billing details for preview
        $accountid = auth()->check() ? (auth()->user()->accountid ?? 'ACC0000001') : 'ACC0000001';
        $account = \App\Models\Account::where('accountid', $accountid)->first();
        $accountBillingDetail = \App\Models\AccountBillingDetail::where('accountid', $accountid)->first();

        return view('invoices.show', [
            'title' => 'Invoice ' . ($invoice->invoice_number ?? 'Details'),
            'subtitle' => 'Invoice Details',
            'invoice' => $invoice,
            'account' => $account,
            'accountBillingDetail' => $accountBillingDetail,
        ]);
    }

    public function invoicesEdit(string $invoice)
    {
        $invoice = $this->resolveInvoiceDocument($invoice);
        $invoiceFor = $invoice->invoice_for ?: 'orders';
        $step = $invoiceFor === 'without_orders' ? 2 : 3;
        $query = [
            'step' => $step,
            'invoice_for' => $invoiceFor,
            'c' => request('c', $invoice->clientid),
            'd' => $invoice->invoiceid,
        ];

        if ($invoiceFor === 'orders' && !empty($invoice->orderid)) {
            $query['o'] = $invoice->orderid;
        }

        if (!empty($invoice->ti_number)) {
            $query['tax_invoice'] = 1;
        }

        return redirect()->route('invoices.create', $query);
    }

    public function invoicesUpdateItem(Request $request, string $invoice, string $item)
    {
        \Log::info('invoicesUpdateItem called', ['invoice' => $invoice, 'item' => $item, 'input' => $request->all()]);
        $invoice = $this->resolveInvoiceDocument($invoice);
        $invoiceItem = InvoiceItem::where('invoice_itemid', $item)
            ->where('invoiceid', $invoice->invoiceid)
            ->firstOrFail();

        $itemData = $request->validate([
            'item_name' => 'required|string|max:255',
            'item_description' => 'nullable|string',
            'quantity' => 'required|numeric|min:1',
            'unit_price' => 'required|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'duration' => 'nullable|numeric|min:0',
            'frequency' => 'nullable|string',
            'no_of_users' => 'nullable|integer|min:1',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'line_total' => 'nullable|numeric|min:0',
        ]);

        $taxRate = (float) ($itemData['tax_rate'] ?? 0);
        $amounts = $this->calculateInvoiceItemAmounts($itemData, $taxRate);
        $hasRecurringFrequency = $this->hasRecurringFrequency($itemData['frequency'] ?? null);

        $accountid = auth()->check() ? (auth()->user()->accountid ?? 'ACC0000001') : 'ACC0000001';
        $account = \App\Models\Account::find($accountid);
        $accountHasUsers = (bool) ($account?->have_users ?? false);
        $service = Service::find($invoiceItem->itemid);
        $isUserWiseItem = $accountHasUsers && $this->isUserWiseEnabled($service?->user_wise);

        $invoiceItem->update([
            'item_name' => $itemData['item_name'],
            'item_description' => $itemData['item_description'] ?? null,
            'quantity' => $this->wholeQuantity($itemData['quantity']),
            'unit_price' => $this->wholeAmount($itemData['unit_price']),
            'tax_rate' => $taxRate,
            'discount_percent' => $amounts['discount_percent'],
            'discount_amount' => $amounts['discount_amount'],
            'duration' => $itemData['duration'] ?? null,
            'frequency' => $itemData['frequency'] ?? null,
            'no_of_users' => $isUserWiseItem ? max(1, (int) ($itemData['no_of_users'] ?? 1)) : null,
            'start_date' => $hasRecurringFrequency ? ($itemData['start_date'] ?: null) : null,
            'end_date' => $hasRecurringFrequency ? ($itemData['end_date'] ?: null) : null,
            'amount' => $amounts['line_total'],
        ]);

        // Recalculate invoice totals
        $invoice->load('items');
        $subtotal = 0;
        $discountTotal = 0;
        $taxTotal = 0;
        foreach ($invoice->items as $it) {
            $a = $this->calculateInvoiceItemAmounts(['line_total' => $it->amount, 'discount_percent' => $it->discount_percent], (float) $it->tax_rate);
            $subtotal += $a['line_total'];
            $discountTotal += $a['discount_amount'];
            $taxTotal += $a['tax_amount'];
        }
        // Totals are derived from invoice_items and not stored on invoices table.

        return response()->json([
            'success' => true,
            'item' => [
                'invoice_itemid' => $invoiceItem->invoice_itemid,
                'item_name' => $invoiceItem->item_name,
                'item_description' => $invoiceItem->item_description,
                'quantity' => (int) $invoiceItem->quantity,
                'unit_price' => (float) $invoiceItem->unit_price,
                'tax_rate' => (float) $invoiceItem->tax_rate,
                'discount_percent' => (float) $invoiceItem->discount_percent,
                'discount_amount' => (float) $invoiceItem->discount_amount,
                'duration' => $invoiceItem->duration,
                'frequency' => $invoiceItem->frequency,
                'no_of_users' => $invoiceItem->no_of_users,
                'start_date' => $invoiceItem->start_date?->format('Y-m-d'),
                'end_date' => $invoiceItem->end_date?->format('Y-m-d'),
                'line_total' => (float) $invoiceItem->amount,
            ],
        ]);
    }

    public function invoicesUpdate(Request $request, string $invoice)
    {
        $invoice = $this->resolveInvoiceDocument($invoice);
        $invoiceNumberColumn = 'pi_number';
        $itemModel = InvoiceItem::class;

        $validated = $request->validate([
            'clientid' => 'required|exists:clients,clientid',
            'invoice_number' => 'required|string|unique:invoices,' . $invoiceNumberColumn . ',' . $invoice->invoiceid . ',invoiceid',
            'invoice_title' => 'nullable|string|max:255',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'notes' => 'nullable|string',
            'items_data' => 'required|json',
        ]);

        $itemsData = json_decode($request->items_data, true);
        $subtotal = 0;
        $taxTotal = 0;
        $discountTotal = 0;

        $this->assertDocumentNumberAvailable($validated['invoice_number'], $invoice->invoiceid);

        foreach ($itemsData as $itemData) {
            $taxRate = (float) ($itemData['tax_rate'] ?? 0);
            $amounts = $this->calculateInvoiceItemAmounts($itemData, $taxRate);
            $subtotal += $amounts['line_total'];
            $discountTotal += $amounts['discount_amount'];
            $taxTotal += $amounts['tax_amount'];
        }

        $invoice->update([
            'clientid' => $validated['clientid'],
            'pi_number' => $validated['invoice_number'],
            'invoice_title' => $validated['invoice_title'] ?? $invoice->invoice_title,
            'issue_date' => $validated['issue_date'],
            'due_date' => $validated['due_date'],
            'notes' => $validated['notes'],
        ]);

        $invoice->items()->delete();

        $accountid = auth()->check() ? (auth()->user()->accountid ?? 'ACC0000001') : 'ACC0000001';
        $account = \App\Models\Account::find($accountid);
        $accountHasUsers = (bool) ($account?->have_users ?? false);

        foreach ($itemsData as $index => $itemData) {
            $service = Service::find($itemData['itemid'] ?? null);
            $taxRate = (float) ($itemData['tax_rate'] ?? 0);
            $amounts = $this->calculateInvoiceItemAmounts($itemData, $taxRate);
            $isUserWiseItem = $accountHasUsers && $this->isUserWiseEnabled($service?->user_wise);
            $hasRecurringFrequency = $this->hasRecurringFrequency($itemData['frequency'] ?? null);
            $payload = [
                'itemid' => $itemData['itemid'] ?: null,
                'item_name' => $itemData['item_name'] ?? ($service?->name ?? 'Custom Item'),
                'item_description' => $itemData['item_description'] ?? null,
                'quantity' => $this->wholeQuantity($itemData['quantity'] ?? 1),
                'unit_price' => $this->wholeAmount($itemData['unit_price'] ?? 0),
                'tax_rate' => $taxRate,
                'discount_percent' => $amounts['discount_percent'],
                'discount_amount' => $amounts['discount_amount'],
                'duration' => $itemData['duration'] ?? null,
                'frequency' => $itemData['frequency'] ?? null,
                'no_of_users' => $isUserWiseItem ? max(1, (int) ($itemData['no_of_users'] ?? 1)) : null,
                'start_date' => $hasRecurringFrequency ? ($itemData['start_date'] ?: null) : null,
                'end_date' => $hasRecurringFrequency ? ($itemData['end_date'] ?: null) : null,
                'amount' => $amounts['line_total'],
                'invoiceid' => $invoice->invoiceid,
            ];

            $itemModel::create($payload);
        }

        // Return JSON response for AJAX requests
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Invoice updated successfully.']);
        }

        $selectedClientId = $request->input('c') ?: $request->input('clientid') ?: $invoice->clientid;

        return redirect()
            ->route('invoices.index', $selectedClientId ? ['c' => $selectedClientId] : [])
            ->with('success', 'Invoice updated successfully.');
    }

    public function invoicesDestroy(string $invoice)
    {
        $invoice = $this->resolveInvoiceDocument($invoice);
        $selectedClientId = request('c') ?: $invoice->clientid;
        $invoice->update(['status' => 'cancelled']);

        return redirect()
            ->route('invoices.index', $selectedClientId ? ['c' => $selectedClientId] : [])
            ->with('success', 'Invoice cancelled successfully.');
    }

    protected function resolveInvoiceDocument(string $invoiceid): Invoice
    {
        return Invoice::findOrFail($invoiceid);
    }

    protected function assertDocumentNumberAvailable(string $invoiceNumber, ?string $ignoreInvoiceId = null, ?string $numberColumn = null): void
    {
        $numberExists = Invoice::query()
            ->when($numberColumn, fn($query) => $query->where($numberColumn, $invoiceNumber), function ($query) use ($invoiceNumber) {
                $query->where(function ($inner) use ($invoiceNumber) {
                    $inner->where('pi_number', $invoiceNumber)
                        ->orWhere('ti_number', $invoiceNumber);
                });
            })
            ->when($ignoreInvoiceId, function ($query) use ($ignoreInvoiceId) {
                $query->where('invoiceid', '!=', $ignoreInvoiceId);
            })
            ->exists();

        if ($numberExists) {
            throw ValidationException::withMessages([
                'invoice_number' => 'The invoice number must be unique.',
            ]);
        }
    }

    public function createTaxInvoice(Request $request)
    {
        $validated = $request->validate([
            'invoiceid' => 'required|exists:invoices,invoiceid',
        ]);

        $invoice = Invoice::findOrFail($validated['invoiceid']);

        // Generate tax invoice number
        $tiNumber = $this->generateTaxInvoiceNumber();
        $this->assertDocumentNumberAvailable($tiNumber, $invoice->invoiceid, 'ti_number');

        // Update invoice with tax invoice number
        $invoice->update([
            'ti_number' => $tiNumber,
            'status' => 'active',
        ]);

        $this->persistInvoicePdfVersion($invoice, true);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Tax Invoice created successfully.',
                'ti_number' => $tiNumber,
            ]);
        }

        return redirect()->back()->with('success', 'Tax Invoice created successfully: ' . $tiNumber);
    }

    public function emailCompose(string $invoice): View
    {
        $invoice = $this->resolveInvoiceDocument($invoice);
        $invoice->loadMissing(['client.billingDetail', 'order']);

        $accountid = auth()->check() ? (auth()->user()->accountid ?? 'ACC0000001') : 'ACC0000001';
        $account = \App\Models\Account::find($accountid);
        $accountBillingDetail = AccountBillingDetail::query()->where('accountid', $accountid)->first();
        $fromEmail = (string) ($accountBillingDetail?->billing_from_email ?? '');
        $toEmail = (string) (
            $invoice->client?->billingDetail?->billing_email
            ?? $invoice->client?->billing_email
            ?? ''
        );

        $requestedType = strtolower(trim((string) request('attachment_type', '')));
        $defaultType = in_array($requestedType, ['pi', 'ti'], true)
            ? $requestedType
            : $this->mapInvoiceTemplateType($invoice);
        $defaultSubjectNumber = $defaultType === 'ti' ? $invoice->ti_number : $invoice->pi_number;
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
        $signatureLines = array_values(array_filter(array_merge([
            $signatureName,
        ], $billingAddressLines)));

        $defaultBody = "Hello,\n\nPlease find attached your invoice.\n\nInvoice No: " . ($defaultSubjectNumber ?: $invoice->invoice_number);
        if (!empty($signatureLines)) {
            $defaultBody .= "\n\nRegards,\n" . implode("\n", $signatureLines);
        }

        $user = auth()->user();
        $currentUserId = $user?->userid ?? $user?->id;
        $currentAccountId = $invoice->accountid ?? ($user?->accountid ?? $accountid);

        $template = MessageTemplate::query()
            ->where('accountid', $currentAccountId)
            ->where('channel', 'email')
            ->where('template_type', $defaultType)
            ->where('is_active', true)
            ->first();

        $allTemplates = MessageTemplate::query()
            ->where('accountid', $currentAccountId)
            ->whereIn('channel', ['email', 'whatsapp', 'sms'])
            ->whereIn('template_type', ['pi', 'ti'])
            ->where('is_active', true)
            ->get()
            ->groupBy('channel')
            ->map(function ($channelTemplates) use ($invoice, $account) {
                return $channelTemplates->mapWithKeys(function ($row) use ($invoice, $account) {
                    return [
                        $row->template_type => [
                            'subject' => $this->renderMessageTemplate((string) ($row->subject ?? ''), $invoice, $account?->name),
                            'body' => $this->renderMessageTemplate((string) ($row->body ?? ''), $invoice, $account?->name),
                        ],
                    ];
                });
            })
            ->toArray();

        $emailTemplatesByType = $allTemplates['email'] ?? [];

        $fallbackTemplatesByType = [
            'pi' => [
                'subject' => 'Invoice ' . ((string) ($invoice->pi_number ?: $invoice->invoice_number)),
                'body' => $defaultBody,
            ],
            'ti' => [
                'subject' => 'Invoice ' . ((string) ($invoice->ti_number ?: $invoice->invoice_number)),
                'body' => $defaultBody,
            ],
        ];

        // Get default channel from request or default to email
        $defaultChannel = trim((string) request('channel', 'email'));
        if (!in_array($defaultChannel, ['email', 'whatsapp', 'sms'], true)) {
            $defaultChannel = 'email';
        }

        $templateSubject = null;
        $templateBody = null;
        if ($template) {
            $templateSubject = $this->renderMessageTemplate((string) ($template->subject ?? ''), $invoice, $account?->name);
            $templateBody = $this->renderMessageTemplate((string) ($template->body ?? ''), $invoice, $account?->name);
            $templateSubject = trim($templateSubject) !== '' ? $templateSubject : null;
            $templateBody = trim($templateBody) !== '' ? $templateBody : null;
        }

        $channelTemplate = $allTemplates[$defaultChannel][$defaultType] ?? null;
        $channelTemplateSubject = trim((string) ($channelTemplate['subject'] ?? '')) ?: null;
        $channelTemplateBody = trim((string) ($channelTemplate['body'] ?? '')) ?: null;

        $requestedEmailId = trim((string) request('e', ''));

        $latestEmail = null;
        
        // 1. If specific email ID requested, load that first
        if ($requestedEmailId !== '') {
            $candidateEmail = InvoiceEmail::query()
                ->where('invoice_emailid', $requestedEmailId)
                ->where('invoiceid', $invoice->invoiceid)
                ->where('accountid', $currentAccountId)
                ->first();
            if (
                $candidateEmail
                && (string) $candidateEmail->channel === $defaultChannel
                && (string) $candidateEmail->attachment_type === $defaultType
            ) {
                $latestEmail = $candidateEmail;
            }
        }

        // 2. If no specific email, load by document type + channel
        if (!$latestEmail) {
            $latestEmail = InvoiceEmail::query()
                ->where('invoiceid', $invoice->invoiceid)
                ->where('accountid', $currentAccountId)
                ->where('attachment_type', $defaultType)
                ->where('channel', $defaultChannel)
                ->first();
        }

        $prefillSubject = $latestEmail?->subject;
        if ($prefillSubject === null || trim((string) $prefillSubject) === '') {
            $prefillSubject = trim((string) ($invoice->invoice_title ?? '')) !== ''
                ? ($channelTemplateSubject ?? $templateSubject ?? trim((string) $invoice->invoice_title))
                : ('Invoice ' . ($defaultSubjectNumber ?: $invoice->invoice_number));
        }

        $prefillBody = $latestEmail?->body;
        if ($prefillBody === null || trim((string) $prefillBody) === '') {
            $prefillBody = $channelTemplateBody ?? $templateBody ?? $defaultBody;
        }
        $prefillBody = $this->sanitizeComposedMessageBody((string) $prefillBody);

        $prefillAttachmentTypes = [];
        if (!empty($latestEmail?->attachment_type)) {
            $prefillAttachmentTypes = collect(explode(',', (string) $latestEmail->attachment_type))
                ->map(fn($type) => trim($type))
                ->filter()
                ->values()
                ->all();
        }
        if (empty($prefillAttachmentTypes)) {
            $prefillAttachmentTypes = [$defaultType];
        }
        $prefillAttachmentType = $prefillAttachmentTypes[0] ?? $defaultType;
        if (!in_array($prefillAttachmentType, ['pi', 'ti'], true)) {
            $prefillAttachmentType = $defaultType;
        }

        $prefillChannel = (string) ($latestEmail?->channel ?? 'email');
        if (!in_array($prefillChannel, ['email', 'whatsapp', 'sms'], true)) {
            $prefillChannel = 'email';
        }
        $prefillPhone = trim((string) (
            $invoice->client?->billingDetail?->billing_phone
            ?? $latestEmail?->phone_number
            ?? $invoice->client?->whatsapp_number
            ?? $invoice->client?->phone
            ?? ''
        ));

        return view('invoices.email-compose', [
            'title' => 'Compose Invoice Email',
            'subtitle' => 'Preview and store email details before sending.',
            'invoice' => $invoice,
            'fromEmail' => $fromEmail,
            'toEmail' => $toEmail,
            'defaultType' => $defaultType,
            'defaultBody' => $defaultBody,
            'prefillSubject' => $prefillSubject,
            'prefillBody' => $prefillBody,
            'prefillAttachmentTypes' => $prefillAttachmentTypes,
            'prefillAttachmentType' => $prefillAttachmentType,
            'prefillChannel' => $prefillChannel,
            'prefillPhone' => $prefillPhone,
            'emailTemplatesByType' => $emailTemplatesByType,
            'allTemplates' => $allTemplates,
            'fallbackTemplatesByType' => $fallbackTemplatesByType,
            'composeEmail' => $latestEmail,
            'customAttachmentUrl' => !empty($latestEmail?->custom_attachment_path)
                ? $this->buildPublicAttachmentUrl((string) $latestEmail->custom_attachment_path)
                : null,
            'customAttachmentName' => !empty($latestEmail?->custom_attachment_path)
                ? basename((string) $latestEmail->custom_attachment_path)
                : null,
        ]);
    }

    public function emailComposeStore(Request $request, string $invoice)
    {
        $invoice = $this->resolveInvoiceDocument($invoice);
        $invoice->loadMissing(['client.billingDetail']);

        $accountid = auth()->check() ? (auth()->user()->accountid ?? 'ACC0000001') : 'ACC0000001';
        $accountBillingDetail = AccountBillingDetail::query()->where('accountid', $accountid)->first();
        $forcedFromEmail = (string) ($accountBillingDetail?->billing_from_email ?? '');
        $forcedToEmail = (string) (
            $invoice->client?->billingDetail?->billing_email
            ?? $invoice->client?->billing_email
            ?? ''
        );

        $validated = $request->validate([
            'invoice_emailid' => 'nullable|exists:invoice_emails,invoice_emailid',
            'action' => 'nullable|in:save,send',
            'channel' => 'required|in:email,whatsapp,sms',
            'phone' => 'nullable|string|max:20',
            'subject' => 'nullable|string|max:255',
            'body' => 'nullable|string',
            'attachment_type' => 'required|in:pi,ti',
            'custom_attachment' => 'nullable|file|max:10240',
        ]);

        $selectedType = (string) ($validated['attachment_type'] ?? 'pi');
        $selectedTypes = [$selectedType];
        $channel = $validated['channel'] ?? 'email';
        if ($channel === 'email') {
            if ($forcedFromEmail === '') {
                return back()->withErrors(['from_email' => 'Set Billing From Email in Account Billing Details first.'])->withInput();
            }
            if ($forcedToEmail === '') {
                return back()->withErrors(['to_email' => 'Set Client Billing Email first.'])->withInput();
            }
        }
        $user = auth()->user();
        $currentAccountId = $invoice->accountid ?? ($user?->accountid ?? 'ACC0000001');
        $requestedDraftId = trim((string) ($validated['invoice_emailid'] ?? ''));
        $seedDraft = null;
        if ($requestedDraftId !== '') {
            $seedDraft = InvoiceEmail::query()
                ->where('invoice_emailid', $requestedDraftId)
                ->where('invoiceid', $invoice->invoiceid)
                ->where('accountid', $currentAccountId)
                ->first();
        }

        // Keep a dedicated draft for each invoice + document type + channel.
        // This prevents cross-channel overwrites and supports up to 6 rows (pi/ti x 3 channels).
        $emailDraft = InvoiceEmail::query()
            ->where('invoiceid', $invoice->invoiceid)
            ->where('accountid', $currentAccountId)
            ->where('attachment_type', $selectedType)
            ->where('channel', $channel)
            ->first();

        if (!$emailDraft) {
            $emailDraft = InvoiceEmail::create([
                'accountid' => $currentAccountId,
                'invoiceid' => $invoice->invoiceid,
                'clientid' => $invoice->clientid,
                'from_email' => $forcedFromEmail,
                'to_email' => $forcedToEmail,
                'subject' => $channel === 'email' ? ($seedDraft?->subject ?? null) : null,
                'body' => $seedDraft?->body ?? null,
                'attachment_type' => $selectedType,
                'channel' => $channel,
                'status' => 'draft',
                'created_by' => $user?->userid ?? $user?->id,
            ]);
        }

        $phone = trim((string) (
            $validated['phone']
            ?? $invoice->client?->billingDetail?->billing_phone
            ?? $invoice->client?->whatsapp_number
            ?? $invoice->client?->phone
            ?? ''
        ));
        $hasNewCustomFile = $request->hasFile('custom_attachment') && $request->file('custom_attachment')->isValid();
        $attachmentPaths = [];

        if (in_array('pi', $selectedTypes, true)) {
            $attachmentPaths[] = $this->resolveCampioInvoicePdfUrl($invoice, false);
        }
        if (in_array('ti', $selectedTypes, true)) {
            if (empty(trim((string) $invoice->ti_number))) {
                return back()->withErrors(['attachment_type' => 'Tax Invoice is not available yet.'])->withInput();
            }
            $attachmentPaths[] = $this->resolveCampioInvoicePdfUrl($invoice, true);
        }
        $customAttachmentPath = null;
        if ($hasNewCustomFile) {
            $storedPath = $request->file('custom_attachment')->store('invoice-email-attachments', 'public');
            $customAttachmentPath = $this->buildPublicAttachmentUrl($storedPath);
        }

        $action = $validated['action'] ?? 'save';
        $isSendAction = $action === 'send';
        $finalCustomAttachmentPath = $customAttachmentPath ?? $emailDraft->custom_attachment_path;

        // For non-email channels, resolve template tags.
        $finalBody = $this->sanitizeComposedMessageBody((string) ($validated['body'] ?? ''));
        if ($channel !== 'email' && $isSendAction) {
            $accountName = (string) (optional(\App\Models\Account::find($currentAccountId))->name ?? '');
            $finalBody = $this->renderMessageTemplate((string) $finalBody, $invoice, $accountName);
            $finalBody = $this->sanitizeComposedMessageBody($finalBody);
        }

        $updatePayload = [
            'subject' => ($channel === 'email') ? ($validated['subject'] ?? null) : null,
            'body' => $finalBody,
            'attachment_type' => implode(',', $selectedTypes),
            'attachment_path' => !empty($attachmentPaths) ? implode(',', $attachmentPaths) : null,
            'custom_attachment_path' => $finalCustomAttachmentPath,
            'phone_number' => $phone,
            'channel' => $channel,
        ];

        if (!$isSendAction) {
            $emailDraft->update($updatePayload + ['status' => 'draft']);
            return redirect()
                ->route('invoices.email-compose', ['invoice' => $invoice->invoiceid, 'e' => $emailDraft->invoice_emailid])
                ->with('success', 'Message draft saved successfully.')
                ->with('preserve_channel', $channel);
        }

        if ($channel === 'whatsapp' || $channel === 'sms') {
            if ($phone === '') {
                return back()->withErrors(['phone' => 'Client phone/whatsapp number is required for this channel.'])->withInput();
            }

            // Build document links so WhatsApp message includes invoice PDFs like email attachments.
            $documentLinks = [];
            if (in_array('pi', $selectedTypes, true)) {
                $documentLinks[] = [
                    'label' => 'Proforma Invoice (PDF)',
                    'url' => $this->resolveCampioInvoicePdfUrl($invoice, false),
                ];
            }
            if (in_array('ti', $selectedTypes, true)) {
                $documentLinks[] = [
                    'label' => 'Tax Invoice (PDF)',
                    'url' => $this->resolveCampioInvoicePdfUrl($invoice, true),
                ];
            }
            if (!empty($finalCustomAttachmentPath)) {
                $documentLinks[] = [
                    'label' => 'Attachment',
                    'url' => $finalCustomAttachmentPath,
                ];
            }

            // Clean HTML for messaging
            $plainBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $finalBody));
            
            // Append invoice document links only for WhatsApp (SMS should not carry attachments).
            if ($channel === 'whatsapp' && !empty($documentLinks)) {
                $plainBody .= "\n\nDocuments:\n";
                foreach ($documentLinks as $doc) {
                    $plainBody .= '- ' . $doc['label'] . ': ' . $doc['url'] . "\n";
                }
            }
            $plainBody = $this->sanitizeForCampioText($plainBody);

            $payload = [
                'account_id' => $currentAccountId,
                'campaign_name' => '',
                'schedule_at' => now()->toIso8601String(),
                'message' => $plainBody,
                'records' => [
                    $this->buildCampioRecipientRecord($invoice, $channel, $forcedToEmail, $phone),
                ],
                'source_url' => url()->current(),
                'notes' => 'Invoice communication: ' . strtoupper($channel),
            ];
            if ($channel === 'whatsapp' && !empty($documentLinks)) {
                $payload['media_url'] = (string) ($documentLinks[0]['url'] ?? '');
            }

            $campioResult = $this->sendViaCampio($channel, $payload);
            if (!$campioResult['ok']) {
                return back()->withErrors(['general' => $campioResult['message']])->withInput();
            }

            // Keep rich/original body in DB so compose view formatting is preserved.
            $updatePayload['body'] = $finalBody;
            $emailDraft->update($updatePayload + ['status' => 'sent', 'sent_at' => now()]);

            return redirect()
                ->route('invoices.email-compose', [
                    'invoice' => $invoice->invoiceid,
                    'e' => $emailDraft->invoice_emailid,
                    'channel' => $channel,
                    'attachment_type' => $selectedType,
                ])
                ->with('success', ucfirst($channel) . ' sent via Campio successfully.')
                ->with('preserve_channel', $channel);
        }

        $emailMessage = $this->sanitizeComposedMessageBody((string) ($validated['body'] ?? ''));
        $emailAttachmentUrls = [];
        $emailAttachmentItems = [];
        if (in_array('pi', $selectedTypes, true)) {
            $piUrl = $this->resolveCampioInvoicePdfUrl($invoice, false);
            $piNumber = trim((string) ($invoice->pi_number ?: $invoice->invoice_number));
            $emailAttachmentUrls[] = $piUrl;
            $emailAttachmentItems[] = [
                'url' => $piUrl,
                'name' => 'Proforma Invoice - ' . ($piNumber !== '' ? $piNumber : $invoice->invoiceid) . '.pdf',
            ];
        }
        if (in_array('ti', $selectedTypes, true)) {
            $tiUrl = $this->resolveCampioInvoicePdfUrl($invoice, true);
            $tiNumber = trim((string) ($invoice->ti_number ?: $invoice->invoice_number));
            $emailAttachmentUrls[] = $tiUrl;
            $emailAttachmentItems[] = [
                'url' => $tiUrl,
                'name' => 'Tax Invoice - ' . ($tiNumber !== '' ? $tiNumber : $invoice->invoiceid) . '.pdf',
            ];
        }
        if (!empty($finalCustomAttachmentPath)) {
            $customUrl = $this->buildPublicAttachmentUrl((string) $finalCustomAttachmentPath);
            $emailAttachmentUrls[] = $customUrl;
            $emailAttachmentItems[] = [
                'url' => $customUrl,
                'name' => basename((string) parse_url($customUrl, PHP_URL_PATH) ?: 'Attachment'),
            ];
        }

        $payload = [
            'account_id' => $currentAccountId,
            'campaign_name' => '',
            'schedule_at' => now()->toIso8601String(),
            'subject' => (string) ($validated['subject'] ?? ''),
            'message' => $emailMessage,
            'records' => [
                $this->buildCampioRecipientRecord($invoice, 'email', $forcedToEmail, $phone),
            ],
            'source_url' => url()->current(),
            'notes' => 'Invoice communication: EMAIL',
        ];
        $emailAttachments = $this->buildCampioAttachments($emailAttachmentItems);
        if (!empty($emailAttachments)) {
            $payload['attachments'] = $emailAttachments;
        }

        $campioResult = $this->sendViaCampio('email', $payload);
        if (!$campioResult['ok']) {
            return back()->withErrors(['general' => $campioResult['message']])->withInput();
        }

        $updatePayload['body'] = $finalBody;
        $emailDraft->update($updatePayload + ['status' => 'sent', 'sent_at' => now()]);

        return redirect()
            ->route('invoices.email-compose', [
                'invoice' => $invoice->invoiceid,
                'e' => $emailDraft->invoice_emailid,
                'channel' => 'email',
                'attachment_type' => $selectedType,
            ])
            ->with('success', 'Email sent via Campio successfully.')
            ->with('preserve_channel', 'email');
    }

    public function downloadPdf(Request $request, string $invoice)
    {
        $invoice = $this->resolveInvoiceDocument($invoice);
        $type = $request->query('type'); // optional: pi | tax_invoice

        // If type is not specified, prefer Tax Invoice if ti_number exists
        if ($type === 'tax_invoice') {
            $isTaxInvoice = true;
        } elseif ($type === 'pi') {
            $isTaxInvoice = false;
        } else {
            $isTaxInvoice = !empty(trim($invoice->ti_number ?? ''));
        }

        $pdfAttachment = $this->buildInvoicePdfAttachment($invoice, $isTaxInvoice);

        return response($pdfAttachment['binary'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $pdfAttachment['filename'] . '"',
        ]);
    }

    public function pdfVersions(string $invoice)
    {
        $invoice = $this->resolveInvoiceDocument($invoice);

        return response()->json([
            'ok' => true,
            'invoiceid' => $invoice->invoiceid,
            'versions' => $this->listStoredInvoicePdfVersions($invoice),
        ]);
    }

}
