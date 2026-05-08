<?php

namespace App\Http\Controllers;

use App\Models\AccountBillingDetail;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Ledger;
use App\Models\FinancialYear;
use App\Models\InvoiceEmail;
use App\Models\MessageTemplate;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\Service;
use App\Models\Tax;
use App\Models\TermsCondition;
use App\Services\InvoiceReminderService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

use App\Traits\ConfiguresBrowsershot;

class InvoicesController extends Controller
{
    use ConfiguresBrowsershot;

    private function normalizeInvoiceTermsByType(mixed $terms): array
    {
        if (!is_array($terms) || $terms === []) {
            return [
                'proforma' => [],
                'billing' => [],
            ];
        }

        $hasNamedBuckets = array_key_exists('proforma', $terms) || array_key_exists('billing', $terms);
        if ($hasNamedBuckets) {
            return [
                'proforma' => array_values(array_filter((array) ($terms['proforma'] ?? []))),
                'billing' => array_values(array_filter((array) ($terms['billing'] ?? []))),
            ];
        }

        // Legacy shape: one flat array used for all invoice types.
        return [
            'proforma' => array_values(array_filter($terms)),
            'billing' => [],
        ];
    }

    private function getDefaultInvoiceTermsForBucket(Invoice $invoice, string $termBucket): array
    {
        $accountid = (string) ($invoice->accountid ?: $this->resolveAccountId());
        if ($accountid === '') {
            return [];
        }

        return TermsCondition::query()
            ->where('accountid', $accountid)
            ->where('type', $termBucket)
            ->where('is_active', true)
            ->where('is_default', true)
            ->orderByRaw('COALESCE(sequence, 999999), created_at ASC')
            ->pluck('content')
            ->map(fn ($content) => trim((string) $content))
            ->filter()
            ->values()
            ->all();
    }

    private function ensureInvoiceDefaultTerms(Invoice $invoice): void
    {
        $termBucket = !empty(trim((string) $invoice->ti_number)) ? 'billing' : 'proforma';
        $storedTerms = $this->normalizeInvoiceTermsByType($invoice->terms);

        if (!empty($storedTerms[$termBucket] ?? [])) {
            return;
        }

        $defaultTerms = $this->getDefaultInvoiceTermsForBucket($invoice, $termBucket);
        if (empty($defaultTerms)) {
            return;
        }

        $storedTerms[$termBucket] = $defaultTerms;
        $invoice->update(['terms' => array_filter($storedTerms)]);
        $invoice->refresh();
    }

    private function mapInvoiceTemplateType(Invoice $invoice): string
    {
        return !empty(trim((string) $invoice->ti_number)) ? 'ti' : 'pi';
    }

    private function renderMessageTemplate(string $value, Invoice $invoice, ?string $companyName = null): string
    {
        $replace = $this->buildInvoiceMessageTemplateReplacements($invoice, $companyName);
        return strtr($value, $replace);
    }

    private function buildInvoiceMessageTemplateReplacements(Invoice $invoice, ?string $companyName = null): array
    {
        $clientBusinessName = trim((string) ($invoice->client?->business_name ?? ''));
        $clientContactPerson = trim((string) ($invoice->client?->contact_name ?? ''));
        $clientName = trim((string) ($clientBusinessName !== '' ? $clientBusinessName : $clientContactPerson));
        $currency = (string) ($invoice->client?->currency ?? 'INR');
        $totalAmount = (float) ($invoice->grand_total ?? $invoice->items->sum('line_total') ?? 0);
        $dueDate = $invoice->due_date?->format('d M Y') ?? '';
        $primaryItem = $invoice->items
            ->sortBy(function ($item) {
                return $item->end_date?->timestamp ?? PHP_INT_MAX;
            })
            ->first();
        $itemName = trim((string) ($primaryItem?->item_name ?? ''));
        $itemStartDate = $primaryItem?->start_date?->format('d M Y') ?? '';
        $itemEndDate = $primaryItem?->end_date?->format('d M Y') ?? '';
        $daysLeft = $primaryItem?->end_date ? now()->startOfDay()->diffInDays($primaryItem->end_date->startOfDay(), false) : null;
        $templateType = !empty(trim((string) $invoice->ti_number)) ? 'ti' : 'pi';
        $renewalDate = ($invoice->invoice_for ?? '') === 'renewal'
            ? ($invoice->created_at?->format('d M Y') ?? '')
            : '';
        $latestPayment = Payment::query()
            ->where('invoiceid', $invoice->invoiceid)
            ->orderByDesc('payment_date')
            ->orderByDesc('created_at')
            ->first();
        $paymentAmount = (float) ($latestPayment?->received_amount ?? 0);
        $paymentDate = $latestPayment?->payment_date?->format('d M Y') ?? '';
        $paymentReference = trim((string) ($latestPayment?->reference_number ?? ''));

        $piLink = route('invoices.pdf', ['invoice' => $invoice->invoiceid, 'type' => 'pi']);
        $tiLink = route('invoices.pdf', ['invoice' => $invoice->invoiceid, 'type' => 'tax_invoice']);

        // Get YOUR billing name from Account Billing Details
        $accountid = $this->resolveAccountId();
        $accountBillingDetail = AccountBillingDetail::query()->where('accountid', $accountid)->first();
        $billingName = $accountBillingDetail->billing_name ?? ($companyName ?? '');

        return [
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
            // Reminder/Renewal/Expiry focused tags
            '{{template_type}}' => $templateType,
            '{{reminder_type}}' => $templateType,
            '{{item_name}}' => $itemName,
            '{{item_start_date}}' => $itemStartDate,
            '{{item_end_date}}' => $itemEndDate,
            '{{expiry_date}}' => $itemEndDate, // Alias of item_end_date for backward compatibility
            '{{days_left}}' => (string) max(0, (int) ($daysLeft ?? 0)),
            '{{renewal_date}}' => $renewalDate,
            '{{payment_amount}}' => $paymentAmount > 0 ? ($currency . ' ' . number_format($paymentAmount, 2)) : '',
            '{{payment_date}}' => $paymentDate,
            '{{payment_reference}}' => $paymentReference,
            // Backwards compatibility
            '{{company_name}}' => $billingName,
            '{{account_name}}' => $billingName,
        ];
    }

    private function extractCampioTemplateVariables(string $templateBody, Invoice $invoice, ?string $companyName = null): array
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

        $replace = $this->buildInvoiceMessageTemplateReplacements($invoice, $companyName);
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

        $termsByType = $this->normalizeInvoiceTermsByType($invoice->terms);
        $invoiceTerms = $isTaxInvoice ? $termsByType['billing'] : $termsByType['proforma'];
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
            'leadid' => (string) ($invoice->clientid ?? ''),
            'student_customer_name' => $clientName,
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
            // Phone number without country code for Campio.
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
            \Illuminate\Support\Facades\Log::error('Campio: CAMPIO_BASE_URL not configured');
            return ['ok' => false, 'message' => 'CAMPIO_BASE_URL is not configured.'];
        }

        $endpoint = $baseUrl . '/api/campaigns/schedule/' . $channel;
        $token = trim((string) env('CAMPIO_AUTH_TOKEN', ''));
        $apiKey = trim((string) env('CAMPIO_API_KEY', ''));

        // Log the full payload for debugging
        \Illuminate\Support\Facades\Log::info('Campio: Sending ' . strtoupper($channel), [
            'endpoint' => $endpoint,
            'account_id' => $payload['account_id'] ?? 'MISSING',
            'campaign_name' => $payload['campaign_name'] ?? '',
            'record_count' => count($payload['records'] ?? []),
            'full_payload' => $payload,
        ]);

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
            \Illuminate\Support\Facades\Log::error('Campio: Request failed - ' . $e->getMessage());
            return ['ok' => false, 'message' => 'Campio request failed: ' . $e->getMessage()];
        }

        \Illuminate\Support\Facades\Log::info('Campio: Response received', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        $json = $response->json();
        if (!$response->successful()) {
            \Illuminate\Support\Facades\Log::error('Campio: API error', [
                'status' => $response->status(),
                'json' => $json,
            ]);
            $message = is_array($json)
                ? ((string) ($json['message'] ?? 'Campio API returned an error.'))
                : ('Campio API returned HTTP ' . $response->status() . '.');
            return ['ok' => false, 'message' => $message];
        }

        \Illuminate\Support\Facades\Log::info('Campio: SUCCESS', [
            'campaign_id' => $json['campaign_id'] ?? 'none',
            'status' => $json['status'] ?? 'none',
        ]);

        return [
            'ok' => true,
            'campaign_id' => (string) (is_array($json) ? ($json['campaign_id'] ?? '') : ''),
            'raw' => $json,
        ];
    }

    private function fetchCampioTemplateHeaderTypes(string $accountid, string $channel): array
    {
        if (!in_array($channel, ['whatsapp', 'sms'], true)) {
            return [];
        }

        $baseUrl = rtrim((string) env('CAMPIO_BASE_URL', 'http://alpha.skoolready.com/campio'), '/');
        $token = trim((string) env('CAMPIO_AUTH_TOKEN', ''));
        $apiKey = trim((string) env('CAMPIO_API_KEY', ''));

        $request = Http::acceptJson()->timeout(20);
        if ($token !== '') {
            $request = $request->withToken($token);
        }
        if ($apiKey !== '') {
            $request = $request->withHeaders(['X-API-KEY' => $apiKey]);
        }

        try {
            $response = $request->get($baseUrl . '/api/templates/' . $channel, [
                'account_id' => $accountid,
            ]);
        } catch (\Throwable $e) {
            return [];
        }

        if (!$response->successful()) {
            return [];
        }

        $rows = data_get($response->json(), 'data.templates', []);
        if (!is_array($rows)) {
            return [];
        }

        $map = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $headerType = strtolower(trim((string) ($row['header_type'] ?? '')));
            if ($headerType === '') {
                continue;
            }

            $id = trim((string) ($row['id'] ?? ''));
            $metaId = trim((string) ($row['meta_template_id'] ?? ''));

            if ($id !== '') {
                $map[$id] = $headerType;
            }
            if ($metaId !== '') {
                $map[$metaId] = $headerType;
            }
        }

        return $map;
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
        $text = trim($body);

        // If a full HTML document was pasted/saved, keep only <body> inner HTML.
        if (preg_match('/<body[^>]*>([\s\S]*?)<\/body>/i', $text, $matches) === 1) {
            $text = (string) ($matches[1] ?? '');
        }

        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = str_replace(["\\r\\n", "\\n"], "\n", $text);

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
        $value = str_replace(["\\r\\n", "\\n"], "\n", $value);
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
        $selectedTab = request('tab', 'invoices');
        $today = now()->startOfDay();
        $upcomingThreshold = now()->addDays(30)->endOfDay();

        $invoiceQuery = Invoice::where('accountid', $accountid)
            ->with(['client', 'items', 'payments'])
            ->latest();

        if ($selectedClientId) {
            $invoiceQuery->where('clientid', $selectedClientId);
        }

        $allInvoices = $invoiceQuery->get();
        $filteredInvoices = $allInvoices->values();

        // Get window for upcoming expiry
        $upcomingWindowDays = (int) request('next_days', 30);
        $upcomingThreshold = now()->addDays($upcomingWindowDays)->endOfDay();

        $expiryItemBaseQuery = InvoiceItem::query()
            ->where('accountid', $accountid)
            ->whereNotNull('end_date')
            ->with([
                'invoice:invoiceid,clientid,invoice_title,pi_number,ti_number,status',
                'invoice.client:clientid,business_name,contact_name,currency',
                'item:itemid,name',
            ])
            ->whereHas('invoice', function ($query) {
                $query->where('status', '!=', 'cancelled')
                    ->whereNotNull('ti_number')
                    ->where('ti_number', '!=', '');
            });

        if ($selectedClientId) {
            $expiryItemBaseQuery->where('clientid', $selectedClientId);
        }

        $mapExpiryItem = function (InvoiceItem $item) use ($today) {
            $expiryDate = $item->end_date?->copy()->startOfDay();
            $daysLeft = $expiryDate ? $today->diffInDays($expiryDate, false) : null;

            return [
                'invoice_itemid' => (string) $item->invoice_itemid,
                'invoiceid' => (string) $item->invoiceid,
                'clientid' => (string) ($item->invoice?->clientid ?: $item->clientid),
                'client_name' => (string) (
                    $item->invoice?->client?->business_name
                    ?: $item->invoice?->client?->contact_name
                    ?: 'Client'
                ),
                'invoice_label' => (string) (
                    $item->invoice?->invoice_title
                    ?: $item->invoice?->invoice_number
                    ?: $item->invoiceid
                ),
                'invoice_number' => (string) (
                    $item->invoice?->ti_number
                    ?: $item->invoice?->pi_number
                    ?: $item->invoice?->invoice_number
                    ?: $item->invoiceid
                ),
                'currency' => (string) ($item->invoice?->client?->currency ?? 'INR'),
                'item_name' => (string) ($item->item_name ?: $item->item?->name ?: 'Item'),
                'item_description' => (string) ($item->item_description ?? ''),
                'frequency' => (string) ($item->frequency ?? ''),
                'duration' => $item->duration,
                'status' => (string) ($item->status ?? 'active'),
                'end_date' => $expiryDate,
                'end_date_display' => $expiryDate?->format('d M Y') ?? '-',
                'days_left' => $daysLeft,
            ];
        };

        $upcomingExpiryItems = (clone $expiryItemBaseQuery)
            ->where(function ($query) {
                $query->where('status', 'active')
                    ->orWhereNull('status');
            })
            ->whereDate('end_date', '>', $today->toDateString())
            ->whereDate('end_date', '<=', $upcomingThreshold->toDateString())
            ->orderBy('end_date')
            ->get()
            ->map($mapExpiryItem)
            ->values();

        $expiredItems = (clone $expiryItemBaseQuery)
            ->where(function ($query) {
                $query->where('status', 'active')
                    ->orWhereNull('status');
            })
            ->whereDate('end_date', '<=', $today->toDateString())
            ->orderBy('end_date')
            ->get()
            ->map($mapExpiryItem)
            ->values();

        $suspendedItems = (clone $expiryItemBaseQuery)
            ->where('status', 'suspended')
            ->orderBy('end_date')
            ->get()
            ->map($mapExpiryItem)
            ->values();

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'invoices' => $filteredInvoices->map(function ($invoice) {
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
                        'due_date' => $invoice->due_date?->format('d M Y'),
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
                                'start_date' => $item->start_date?->format('d M Y'),
                                'end_date' => $item->end_date?->format('d M Y'),
                                'total' => $currency . ' ' . number_format($item->line_total, 0),
                                'line_total' => (float) $item->line_total,
                            ];
                        }),
                    ];
                })->values(),
                'upcoming' => $upcomingExpiryItems,
                'expired' => $expiredItems,
                'suspended' => $suspendedItems,
            ]);
        }

        return view('invoices.index', [
            'title' => 'Invoices',
            'subtitle' => $selectedClientId ? 'Filtered by selected client.' : 'Showing invoices for all clients.',
            'clients' => $clients,
            'allInvoices' => $filteredInvoices,
            'selectedClientId' => $selectedClientId,
            'selectedTab' => $selectedTab,
            'upcomingExpiryItems' => $upcomingExpiryItems,
            'expiredItems' => $expiredItems,
            'suspendedItems' => $suspendedItems,
            'upcomingWindowDays' => $upcomingWindowDays,
        ]);
    }

    public function invoicesExpiryList(Request $request): View|\Illuminate\Http\RedirectResponse
    {
        $accountid = $this->resolveAccountId();
        $today = now()->startOfDay();
        $nextDaysInput = $request->query('next_days');
        $nextDays = ($nextDaysInput === null || $nextDaysInput === '')
            ? 30
            : (int) $nextDaysInput;
        if ($nextDays < 0) {
            $nextDays = 0;
        }

        $expiryItems = InvoiceItem::query()
            ->where('accountid', $accountid)
            ->whereNotNull('end_date')
            ->when($nextDays > 0, function ($query) use ($today, $nextDays) {
                $query->whereDate('end_date', '<=', $today->copy()->addDays($nextDays)->toDateString());
            })
            ->with([
                'invoice:invoiceid,clientid,invoice_title,pi_number,ti_number,status',
                'invoice.client:clientid,business_name,contact_name',
                'item:itemid,name',
            ])
            ->whereHas('invoice', function ($q) {
                $q->where('status', '!=', 'cancelled')
                    ->whereNotNull('ti_number')
                    ->where('ti_number', '!=', '');
            })
            ->orderBy('end_date')
            ->paginate(15)
            ->through(function (InvoiceItem $item) use ($today) {
                $expiryDate = $item->end_date?->copy()->startOfDay();
                $daysLeft = $expiryDate ? $today->diffInDays($expiryDate, false) : 0;

                return [
                    'invoice_itemid' => $item->invoice_itemid,
                    'invoiceid' => (string) $item->invoiceid,
                    'clientid' => (string) ($item->invoice?->clientid ?: $item->clientid),
                    'invoice_label' => (string) (
                        $item->invoice?->invoice_title
                        ?: $item->invoice?->invoice_number
                        ?: $item->invoiceid
                    ),
                    'client_name' => (string) (
                        $item->invoice?->client?->business_name
                        ?: $item->invoice?->client?->contact_name
                        ?: 'Client'
                    ),
                    'item_name' => (string) ($item->item_name ?: $item->item?->name ?: 'Item'),
                    'item_description' => (string) ($item->item_description ?? ''),
                    'frequency' => (string) ($item->frequency ?? ''),
                    'end_date' => $expiryDate?->format('d M Y'),
                    'days_left' => $daysLeft,
                ];
            });

        return redirect()->route('invoices.index', [
            'tab' => 'upcoming',
            'next_days' => $nextDays,
        ]);
    }

    public function startRenewalFromItem(string $item)
    {
        $accountid = $this->resolveAccountId();

        $sourceItem = InvoiceItem::query()
            ->where('invoice_itemid', $item)
            ->where('accountid', $accountid)
            ->firstOrFail();

        $clientId = (string) ($sourceItem->clientid ?? '');
        if ($clientId === '') {
            return redirect()->route('invoices.index', ['tab' => 'expired'])
                ->with('error', 'Unable to start renewal for this item.');
        }

        return redirect()->route('invoices.create', [
            'step' => 2,
            'invoice_for' => 'renewal',
            'c' => $clientId,
            'source_item' => $sourceItem->invoice_itemid,
        ]);
    }

    public function invoicesCreate(): View
    {
        $user = Auth::user();
        $accountid = $this->resolveAccountId();
        $legacyAccountId = $user?->id ? (string) $user->id : null;
        $account = \App\Models\Account::find($accountid);
        $orderId = request('o', request('orderid'));
        $clientId = request('c', request('clientid'));
        $invoiceFor = request('invoice_for');
        $currentUserId = $user?->userid ?? $user?->id;
        $draftId = request('d');

        $existingDraft = null;
        if (!empty($draftId) && !empty($currentUserId)) {
            $existingDraft = Invoice::query()
                ->where('invoiceid', $draftId)
                ->where('accountid', $accountid)
                ->first();
        }

        if (empty($invoiceFor) && $existingDraft) {
            $invoiceFor = $existingDraft->orderid ? 'orders' : 'without_orders';
        }

        if (
            empty($existingDraft)
            && empty($draftId)
            && !empty($clientId)
            && !empty($currentUserId)
            && $invoiceFor !== 'renewal'
        ) {
            $existingDraft = Invoice::query()
                ->where('clientid', $clientId)
                ->where('accountid', $accountid)
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
        $daysFilter = max(0, (int) $request->input('days', 30));

        if (!$clientId) {
            return response()->json([]);
        }

        $today = now()->startOfDay();
        $upcomingThreshold = now()->addDays($daysFilter)->endOfDay();

        $invoices = Invoice::where('clientid', $clientId)
            ->whereNotNull('ti_number')
            ->where('ti_number', '!=', '')
            ->with('items', 'client')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($invoice) use ($today, $upcomingThreshold) {
                $renewalItems = $invoice->items
                    ->map(function ($item) use ($today, $upcomingThreshold) {
                        if (!$item->end_date) {
                            return null;
                        }

                        if (!$this->hasRecurringFrequency($item->frequency)) {
                            return null;
                        }

                        if (($item->status ?? 'active') !== 'active') {
                            return null;
                        }

                        $itemEndDate = $item->end_date instanceof \Carbon\Carbon
                            ? $item->end_date
                            : \Carbon\Carbon::parse($item->end_date);

                        $isExpired = $itemEndDate <= $today;
                        $isUpcoming = !$isExpired && $itemEndDate > $today && $itemEndDate <= $upcomingThreshold;
                        if (!$isExpired && !$isUpcoming) {
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
                            'is_upcoming' => $isUpcoming,
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
                    'upcoming_items' => $renewalItems->where('is_upcoming', true)->count(),
                    'has_renewal_candidates' => $renewalItems->isNotEmpty(),
                    'items' => $renewalItems,
                ];

                return $result;
            })
            ->filter(function ($invoice) {
                return $invoice['has_renewal_candidates'];
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
            if (($item->status ?? 'active') !== 'active') {
                return null;
            }

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
        })->filter()->values();

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

        $user = Auth::user();
        $accountid = $this->resolveAccountId();
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
            'renewed_item_ids' => 'nullable|string',
        ]);

        $termBucket = !empty(trim((string) $invoice->ti_number)) ? 'billing' : 'proforma';
        $terms = array_values(array_filter($request->input('terms', []), fn($t) => trim($t) !== ''));
        if (empty($terms)) {
            $terms = $this->getDefaultInvoiceTermsForBucket($invoice, $termBucket);
        }
        $storedTerms = $this->normalizeInvoiceTermsByType($invoice->terms);
        $storedTerms[$termBucket] = $terms;
        $updatePayload = ['terms' => array_filter($storedTerms)];
        if (($invoice->status ?? '') === 'draft') {
            $updatePayload['status'] = 'active';
        }
        $invoice->update($updatePayload);
        $renewedItemIds = json_decode((string) $request->input('renewed_item_ids', '[]'), true);
        $this->finalizeRenewedSourceItems($invoice, is_array($renewedItemIds) ? $renewedItemIds : null);

        $this->persistInvoicePdfVersion($invoice, !empty(trim((string) $invoice->ti_number)));

        return response()->json(['ok' => true, 'count' => count($terms)]);
    }


    protected function generateInvoiceNumber(): string
    {
        $accountid = $this->resolveAccountId();

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
        $accountid = $this->resolveAccountId();

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

        $user = Auth::user();
        $client = Client::findOrFail($validated['clientid']);
        $validated['accountid'] = $validated['accountid'] ?? $this->resolveAccountId();
        $validated['fy_id'] = $this->resolveDefaultFyId($validated['accountid']);
        $validated['created_by'] = $user?->userid ?? $user?->id;
        // TI number is assigned only when converting PI to Tax Invoice.
        // Keep it empty for PI/draft creation so insert works on older schemas too.
        $validated['ti_number'] = $validated['ti_number'] ?? '';
        unset($validated['items_data']);

        // Check if we're updating an existing draft
        $existingDraft = null;
        if (!empty($validated['invoiceid'])) {
            $existingDraft = Invoice::where('status', 'draft')
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

        $accountid = $this->resolveAccountId();
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
                'status' => 'active',
                'amount' => $lineTotal,
            ];
        }

        $discountTotal = $this->roundDiscountDown($discountTotal);
        $taxTotal = $this->roundTaxUp($taxTotal);
        $grandTotal = $subtotal - $discountTotal + $taxTotal;
        $invoice = null;

        try {
            DB::transaction(function () use ($validated, $preparedItems, &$invoice, $request, $existingDraft, $invoiceSource, $grandTotal) {
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

                $this->syncInvoiceLedgerEntry($invoice, $grandTotal);

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
                    if (!is_array($renewedItemIds) || empty($renewedItemIds)) {
                        $renewedItemIds = $this->extractRenewedItemIdsFromItemsData($itemsData ?? []);
                    }

                    \Log::info('Renewal submission check', [
                        'invoice_for' => $invoiceSource,
                        'renewed_item_ids_raw' => $renewedItemIdsRaw,
                        'renewed_item_ids_parsed' => $renewedItemIds,
                        'new_invoice_id' => $invoice->invoiceid,
                    ]);

                    if (!empty($renewedItemIds)) {
                        $renewedItemIds = array_values(array_filter(array_map('strval', (array) $renewedItemIds)));
                        InvoiceItem::query()
                            ->whereIn('invoice_itemid', $renewedItemIds)
                            ->update([
                                'status' => 'renewed',
                            ]);
                        $this->forgetRenewalSourceItemIds($invoice->invoiceid);

                        \Log::info('Renewal item ids marked as renewed', [
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

    private function syncInvoiceLedgerEntry(Invoice $invoice, float $grandTotal): void
    {
        $description = $invoice->invoice_title;

        if (empty($description)) {
            $description = trim((string) ($invoice->ti_number ?: $invoice->pi_number));
        }

        Ledger::query()->updateOrCreate(
            [
                'reference_number' => $invoice->invoiceid,
                'type' => 'invoice',
            ],
            [
                'accountid' => $invoice->accountid,
                'clientid' => $invoice->clientid,
                'date' => $invoice->issue_date,
                'amount' => $grandTotal,
                'description' => $description ?: null,
            ]
        );
    }

    private function renewalSourceItemsSessionKey(string $invoiceid): string
    {
        return 'invoice_renewal_source_items.' . $invoiceid;
    }

    private function extractRenewedItemIdsFromItemsData(?array $itemsData): array
    {
        if (!is_array($itemsData)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            static fn ($item) => is_array($item) ? (string) ($item['renewed_from_invoice_itemid'] ?? '') : '',
            $itemsData
        ))));
    }

    private function storeRenewalSourceItemIds(string $invoiceid, array $itemIds): void
    {
        session()->put($this->renewalSourceItemsSessionKey($invoiceid), array_values(array_unique(array_filter(array_map('strval', $itemIds)))));
    }

    private function forgetRenewalSourceItemIds(string $invoiceid): void
    {
        session()->forget($this->renewalSourceItemsSessionKey($invoiceid));
    }

    private function markRenewedSourceItemsByIds(array $renewedItemIds): void
    {
        $renewedItemIds = array_values(array_unique(array_filter(array_map('strval', $renewedItemIds))));
        if (empty($renewedItemIds)) {
            return;
        }

        InvoiceItem::query()
            ->whereIn('invoice_itemid', $renewedItemIds)
            ->update([
                'status' => 'renewed',
            ]);
    }

    private function finalizeRenewedSourceItems(Invoice $invoice, ?array $explicitRenewedItemIds = null): void
    {
        $invoiceid = (string) ($invoice->invoiceid ?? '');
        if ($invoiceid === '') {
            return;
        }

        $renewedItemIds = is_array($explicitRenewedItemIds) && !empty($explicitRenewedItemIds)
            ? $explicitRenewedItemIds
            : session()->get($this->renewalSourceItemsSessionKey($invoiceid), []);
        if (!is_array($renewedItemIds) || empty($renewedItemIds)) {
            return;
        }

        $renewedItemIds = array_values(array_unique(array_filter(array_map('strval', $renewedItemIds))));
        if (empty($renewedItemIds)) {
            $this->forgetRenewalSourceItemIds($invoiceid);
            return;
        }

        $this->markRenewedSourceItemsByIds($renewedItemIds);

        $this->forgetRenewalSourceItemIds($invoiceid);
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
            'renewed_item_ids' => 'nullable|string',
        ]);

        $user = Auth::user();
        $accountid = $this->resolveAccountId();
        $legacyAccountId = $user?->id ? (string) $user->id : null;
        $accountCandidates = array_values(array_filter(array_unique([$accountid, $legacyAccountId])));
        $client = Client::findOrFail($validated['clientid']);

        // Check if draft already exists for this client
        $orderId = $validated['orderid'] ?? null;
        $invoiceFor = $validated['invoice_for'] ?? ($orderId ? 'orders' : 'without_orders');

        $draft = null;
        $isExplicitInvoiceEdit = !empty($validated['invoiceid']);
        if (!empty($validated['invoiceid'])) {
            // Explicit edit flow: always target this exact invoice id.
            // Do not restrict by status here; older records may have different status values.
            $draft = Invoice::where('invoiceid', $validated['invoiceid'])
                ->first();
        }

        if (!$draft && !$isExplicitInvoiceEdit && $invoiceFor !== 'renewal') {
            $draft = Invoice::where('clientid', $validated['clientid'])
                ->whereIn('accountid', $accountCandidates)
                ->where('status', 'draft')
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
                'status' => 'draft',
                'fy_id' => $draft->fy_id ?: $this->resolveDefaultFyId($accountid),
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
                'status' => 'draft',
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
                    'status' => 'active',
                    'amount' => $this->wholeAmount($amounts['line_total'] ?? 0),
                ];
            }

            $draft->items()->delete();
            if (!empty($draftItems)) {
                $draft->items()->createMany($draftItems);
            }
        }

        if (($validated['invoice_for'] ?? '') === 'renewal') {
            $renewedItemIds = json_decode((string) ($validated['renewed_item_ids'] ?? '[]'), true);
            if (!is_array($renewedItemIds) || empty($renewedItemIds)) {
                $renewedItemIds = $this->extractRenewedItemIdsFromItemsData($itemsData ?? []);
            }
            if (!empty($renewedItemIds)) {
                $renewedItemIds = array_values(array_filter(array_map('strval', $renewedItemIds)));
                $this->storeRenewalSourceItemIds($draft->invoiceid, $renewedItemIds);
            }
        }

        if ($rawItemsData !== null) {
            $calculatedDiscountTotal = $this->roundDiscountDown($calculatedDiscountTotal);
            $calculatedTaxTotal = $this->roundTaxUp($calculatedTaxTotal);
            $calculatedGrandTotal = max(0, $calculatedSubtotal - $calculatedDiscountTotal + $calculatedTaxTotal);
            $amountPaid = (float) ($draft->amount_paid ?? 0);
            $calculatedBalanceDue = max(0, $calculatedGrandTotal - $amountPaid);

            $draft->update(['status' => 'draft']);
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
        $user = Auth::user();
        $accountid = $this->resolveAccountId();
        $orderId = request('o', request('orderid'));
        $draftId = request('d');
        $invoiceFor = request('invoice_for');

        $draft = null;
        if (!empty($draftId)) {
            $draft = Invoice::where('invoiceid', $draftId)
                ->where('accountid', $accountid)
                ->with(['items.item', 'order'])
                ->first();
        }

        if (!$draft && empty($draftId) && $invoiceFor !== 'renewal') {
            $draft = Invoice::where('clientid', $clientid)
                ->where('accountid', $accountid)
                ->where('status', 'draft')
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
                'terms_by_type' => $this->normalizeInvoiceTermsByType($draft->terms),
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
        $accountid = $this->resolveAccountId();
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
        $invoiceFor = $invoice->orderid ? 'orders' : 'without_orders';
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
            'status' => 'nullable|in:active,suspended,renewed',
            'line_total' => 'nullable|numeric|min:0',
        ]);

        $taxRate = (float) ($itemData['tax_rate'] ?? 0);
        $amounts = $this->calculateInvoiceItemAmounts($itemData, $taxRate);
        $hasRecurringFrequency = $this->hasRecurringFrequency($itemData['frequency'] ?? null);

        $accountid = $this->resolveAccountId();
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
            'status' => $itemData['status'] ?? ($invoiceItem->status ?? 'active'),
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
                'status' => $invoiceItem->status ?? 'active',
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

        $accountid = $this->resolveAccountId();
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
                'status' => 'active',
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

    public function invoicesRestore(string $invoice)
    {
        $invoice = $this->resolveInvoiceDocument($invoice);
        $selectedClientId = request('c') ?: $invoice->clientid;
        $invoice->update(['status' => 'active']);

        return redirect()
            ->route('invoices.index', $selectedClientId ? ['c' => $selectedClientId] : [])
            ->with('success', 'Invoice restored successfully.');
    }

    public function suspendInvoiceItem(Request $request, string $invoice, string $item)
    {
        $invoice = $this->resolveInvoiceDocument($invoice);
        $invoiceItem = InvoiceItem::query()
            ->where('invoice_itemid', $item)
            ->where('invoiceid', $invoice->invoiceid)
            ->firstOrFail();

        $invoiceItem->update(['status' => 'suspended']);

        $params = array_filter([
            'c' => request('c') ?: $invoice->clientid,
            'tab' => 'expired',
        ]);

        return redirect()
            ->route('invoices.index', $params)
            ->with('success', 'Item suspended successfully.');
    }

    public function unsuspendInvoiceItem(Request $request, string $invoice, string $item)
    {
        $invoice = $this->resolveInvoiceDocument($invoice);
        $invoiceItem = InvoiceItem::query()
            ->where('invoice_itemid', $item)
            ->where('invoiceid', $invoice->invoiceid)
            ->firstOrFail();

        $invoiceItem->update(['status' => 'active']);

        $params = array_filter([
            'c' => request('c') ?: $invoice->clientid,
            'tab' => 'suspended',
        ]);

        return redirect()
            ->route('invoices.index', $params)
            ->with('success', 'Item unsuspended successfully.');
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
        $clientContext = $request->input('c', $request->query('c', $invoice->clientid));

        $tiNumber = trim((string) ($invoice->ti_number ?? ''));
        if ($tiNumber === '') {
            $tiNumber = $this->generateTaxInvoiceNumber();
            $this->assertDocumentNumberAvailable($tiNumber, $invoice->invoiceid, 'ti_number');

            $invoice->update([
                'ti_number' => $tiNumber,
                'status' => 'active',
            ]);

            $this->persistInvoicePdfVersion($invoice, true);
        }

        $editUrl = route('invoices.edit', array_filter([
            'invoice' => $invoice->invoiceid,
            'c' => $clientContext,
        ]));

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Tax Invoice created successfully.',
                'ti_number' => $tiNumber,
                'redirect_url' => $editUrl,
            ]);
        }

        return redirect($editUrl)->with('success', 'Tax Invoice ready: ' . $tiNumber);
    }

    public function sendReminder(Request $request, string $invoice, InvoiceReminderService $invoiceReminderService)
    {
        $invoice = $this->resolveInvoiceDocument($invoice);
        $result = $invoiceReminderService->sendManualReminder($invoice);

        $sentCount = (int) ($result['sent'] ?? 0);
        if ($sentCount > 0) {
            $message = 'Reminder sent successfully on ' . $sentCount . ' channel(s).';
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => true, 'message' => $message, 'meta' => $result]);
            }

            return back()->with('success', $message);
        }

        $failureMessage = ((int) ($result['failed'] ?? 0)) > 0
            ? 'Reminder could not be delivered. Please verify reminder templates and recipient details.'
            : 'No active reminder template found for this account.';

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => false, 'message' => $failureMessage, 'meta' => $result], 422);
        }

        return back()->with('error', $failureMessage);
    }

    public function sendItemReminder(Request $request, string $invoice, string $item, InvoiceReminderService $invoiceReminderService)
    {
        $invoice = $this->resolveInvoiceDocument($invoice);
        $invoiceItem = $invoice->items()
            ->where('invoice_itemid', $item)
            ->first();

        if (!$invoiceItem) {
            return back()->with('error', 'Invoice item not found for reminder.');
        }

        $frequency = strtolower(trim((string) ($invoiceItem->frequency ?? '')));
        $isOneTime = in_array($frequency, ['', 'one_time', 'one-time', 'onetime'], true);
        if ($isOneTime) {
            return back()->with('error', 'Reminder can be sent only for recurring items.');
        }

        $result = $invoiceReminderService->sendManualReminder($invoice, $invoiceItem);

        $sentCount = (int) ($result['sent'] ?? 0);
        if ($sentCount > 0) {
            $message = 'Reminder sent for item "' . ($invoiceItem->item_name ?? 'Item') . '" on ' . $sentCount . ' channel(s).';
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => true, 'message' => $message, 'meta' => $result]);
            }

            return back()->with('success', $message);
        }

        $failureMessage = ((int) ($result['failed'] ?? 0)) > 0
            ? 'Item reminder could not be delivered. Please verify reminder templates and recipient details.'
            : 'No active reminder template found for this account.';

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => false, 'message' => $failureMessage, 'meta' => $result], 422);
        }

        return back()->with('error', $failureMessage);
    }

    public function emailCompose(Request $request, string $invoice): View
    {
        $invoice = $this->resolveInvoiceDocument($invoice);
        $invoice->loadMissing(['client.billingDetail', 'order']);

        if (($invoice->status ?? '') === 'draft') {
            $this->ensureInvoiceDefaultTerms($invoice);
            $invoice->update(['status' => 'active']);
            $renewedItemIds = json_decode((string) $request->query('renewed_item_ids', '[]'), true);
            $this->finalizeRenewedSourceItems($invoice, is_array($renewedItemIds) ? $renewedItemIds : null);
            $invoice->refresh();
        } else {
            $this->ensureInvoiceDefaultTerms($invoice);
        }

        $accountid = $this->resolveAccountId();
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
            : (!empty(trim((string) $invoice->ti_number)) ? 'ti' : $this->mapInvoiceTemplateType($invoice));
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

        $user = Auth::user();
        $currentUserId = $user?->userid ?? $user?->id;
        $currentAccountId = $invoice->accountid ?? ($user?->accountid ?? $accountid);

        $templateRows = MessageTemplate::query()
            ->where('accountid', $currentAccountId)
            ->whereIn('channel', ['email', 'whatsapp', 'sms'])
            ->whereIn('template_type', ['pi', 'ti'])
            ->where('is_active', true)
            ->orderBy('channel')
            ->orderBy('template_type')
            ->orderBy('created_at')
            ->get();
        $whatsappHeaderTypes = $this->fetchCampioTemplateHeaderTypes($currentAccountId, 'whatsapp');

        $templateCatalog = [];
        $availableChannelsByType = [
            'pi' => [],
            'ti' => [],
        ];
        $firstTemplateByContext = [];

        foreach ($templateRows as $row) {
            $channel = (string) $row->channel;
            $type = (string) $row->template_type;
            if (!in_array($channel, ['email', 'whatsapp', 'sms'], true) || !in_array($type, ['pi', 'ti'], true)) {
                continue;
            }

            $templateCatalog[$type] = $templateCatalog[$type] ?? [];
            $templateCatalog[$type][$channel] = $templateCatalog[$type][$channel] ?? [];
            $templateCatalog[$type][$channel][] = [
                'templateid' => (string) $row->templateid,
                'name' => (string) $row->name,
                'subject' => $this->renderMessageTemplate((string) ($row->subject ?? ''), $invoice, $account?->name),
                'body' => $this->renderMessageTemplate((string) ($row->body ?? ''), $invoice, $account?->name),
                'raw_body' => (string) ($row->body ?? ''),
                'header_type' => $channel === 'whatsapp'
                    ? (
                        $whatsappHeaderTypes[trim((string) ($row->template_id ?? ''))]
                        ?? $whatsappHeaderTypes[trim((string) ($row->meta_template_id ?? ''))]
                        ?? strtolower(trim((string) ($row->header_type ?? '')))
                    )
                    : strtolower(trim((string) ($row->header_type ?? ''))),
            ];

            if (!in_array($channel, $availableChannelsByType[$type], true)) {
                $availableChannelsByType[$type][] = $channel;
            }

            if (!isset($firstTemplateByContext[$type][$channel])) {
                $firstTemplateByContext[$type][$channel] = end($templateCatalog[$type][$channel]);
            }
        }

        $fallbackTemplatesByType = [
            'pi' => [
                'subject' => 'Invoice ' . ((string) ($invoice->pi_number ?: $invoice->invoice_number)),
                'body' => $defaultBody,
                'raw_body' => '',
            ],
            'ti' => [
                'subject' => 'Invoice ' . ((string) ($invoice->ti_number ?: $invoice->invoice_number)),
                'body' => $defaultBody,
                'raw_body' => '',
            ],
        ];

        // Get default channel from request or default to email
        $defaultChannel = trim((string) request('channel', 'email'));
        if (!in_array($defaultChannel, ['email', 'whatsapp', 'sms'], true)) {
            $defaultChannel = 'email';
        }

        // Keep the user-requested channel even if no template exists for it.
        // In that case we use fallback template content.

        $channelTemplate = $firstTemplateByContext[$defaultType][$defaultChannel] ?? null;
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
                ? ($channelTemplateSubject ?? trim((string) $invoice->invoice_title))
                : ('Invoice ' . ($defaultSubjectNumber ?: $invoice->invoice_number));
        }

        $prefillBody = $latestEmail?->body;
        if ($prefillBody === null || trim((string) $prefillBody) === '') {
            $prefillBody = $channelTemplateBody ?? $defaultBody;
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
            'templateCatalog' => $templateCatalog,
            'availableChannelsByType' => $availableChannelsByType,
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

        $accountid = $this->resolveAccountId();
        $accountBillingDetail = AccountBillingDetail::query()->where('accountid', $accountid)->first();
        $forcedFromEmail = (string) ($accountBillingDetail?->billing_from_email ?? '');
        $forcedFromName = trim((string) ($accountBillingDetail?->billing_name ?? ''));
        $forcedToEmail = (string) (
            $invoice->client?->billingDetail?->billing_email
            ?? $invoice->client?->billing_email
            ?? ''
        );

        $validated = $request->validate([
            'invoice_emailid' => 'nullable|exists:invoice_emails,invoice_emailid',
            'action' => 'nullable|in:save,send',
            'channel' => 'required|in:email,whatsapp,sms',
            'selected_templateid' => 'nullable|string|max:20',
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
        $user = Auth::user();
        $currentAccountId = $invoice->accountid ?? $this->resolveAccountId();
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
        $sentAt = now();
        $documentLabel = $selectedType === 'ti' ? 'Tax Invoice (TI)' : 'Proforma Invoice (PI)';
        $successTitle = $selectedType === 'ti'
            ? 'Tax Invoice sent successfully.'
            : 'Proforma Invoice sent successfully.';

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
                ->route('invoices.email-compose', [
                    'invoice' => $invoice->invoiceid,
                    'e' => $emailDraft->invoice_emailid,
                    'channel' => $channel,
                    'attachment_type' => $selectedType,
                ])
                ->with('success', 'Message draft saved successfully.')
                ->with('preserve_channel', $channel);
        }

        if ($channel === 'whatsapp' || $channel === 'sms') {
            if ($phone === '') {
                return back()->withErrors(['phone' => 'Client phone/whatsapp number is required for this channel.'])->withInput();
            }

            $selectedTemplateId = trim((string) ($validated['selected_templateid'] ?? ''));
            $channelTemplateQuery = MessageTemplate::query()
                ->where('accountid', $currentAccountId)
                ->where('template_type', $selectedType)
                ->where('channel', $channel)
                ->where('is_active', true);

            if ($selectedTemplateId !== '') {
                $channelTemplateQuery->where('templateid', $selectedTemplateId);
            }

            $channelTemplateConfig = $channelTemplateQuery->first();

            if ($selectedTemplateId !== '' && !$channelTemplateConfig) {
                return back()->withErrors([
                    'selected_templateid' => 'Selected template is invalid for this channel/type.',
                ])->withInput();
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

            $canUseWhatsappDocumentHeader = true;
            if ($channel === 'whatsapp' && $channelTemplateConfig) {
                $headerTypeMap = $this->fetchCampioTemplateHeaderTypes($currentAccountId, 'whatsapp');
                $resolvedHeaderType = strtolower(trim((string) (
                    $headerTypeMap[trim((string) ($channelTemplateConfig->template_id ?? ''))]
                    ?? $headerTypeMap[trim((string) ($channelTemplateConfig->meta_template_id ?? ''))]
                    ?? ($channelTemplateConfig->header_type ?? '')
                )));
                $canUseWhatsappDocumentHeader = $resolvedHeaderType === 'document';
            }

            // Clean HTML for messaging while preserving readable line breaks.
            $plainBodyHtml = str_replace(['<br>', '<br/>', '<br />'], "\n", $finalBody);
            $plainBodyHtml = preg_replace('/<\/(p|div|li|h[1-6])>/i', "\n", (string) $plainBodyHtml) ?? (string) $plainBodyHtml;
            $plainBodyHtml = preg_replace('/<(ul|ol)[^>]*>/i', "\n", (string) $plainBodyHtml) ?? (string) $plainBodyHtml;
            $plainBody = trim(strip_tags((string) $plainBodyHtml));
            $plainBody = $this->sanitizeForCampioText($plainBody);
            $plainBody = str_replace("\n", "\r\n", $plainBody);

            // Build payload - for WhatsApp with template, don't include message body
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
            if (!empty($channelTemplateConfig?->template_id)) {
                $payload['template_id'] = (string) $channelTemplateConfig->template_id;
            }
            if (!empty($channelTemplateConfig?->meta_template_id)) {
                $payload['meta_template_id'] = (string) $channelTemplateConfig->meta_template_id;
            } elseif (!empty($channelTemplateConfig?->template_id)) {
                $payload['meta_template_id'] = (string) $channelTemplateConfig->template_id;
            }
            if (!empty($channelTemplateConfig?->sender_id)) {
                $payload['sender_id'] = (string) $channelTemplateConfig->sender_id;
            }
            if (!empty($channelTemplateConfig?->template_id)) {
                $accountName = (string) (optional(\App\Models\Account::find($currentAccountId))->name ?? '');
                $templateVariables = $this->extractCampioTemplateVariables((string) ($channelTemplateConfig->body ?? ''), $invoice, $accountName);
                if (!empty($templateVariables)) {
                    $payload['variables'] = $templateVariables;
                    $payload['dynamic_context'] = [
                        'fields' => collect($templateVariables)->values()->map(
                            fn($value, $index) => [
                                // Campio scheduler resolves WhatsApp template vars only for Body_/Header_/Button_ prefixes.
                                'key' => 'Body_' . ((int) $index + 1),
                                'type' => 'custom',
                                'value' => (string) $value,
                            ]
                        )->all(),
                    ];
                }
            }
            if ($channel === 'whatsapp' && !empty($documentLinks) && $canUseWhatsappDocumentHeader) {
                $payload['media_url'] = (string) ($documentLinks[0]['url'] ?? '');
                if ($payload['media_url'] !== '' && !str_starts_with(strtolower($payload['media_url']), 'https://')) {
                    return back()->withErrors([
                        'general' => 'WhatsApp media delivery requires a public HTTPS document URL. Current PDF URL is HTTP. Please enable SSL/HTTPS for this domain, then try again.',
                    ])->withInput();
                }
                if ($payload['media_url'] !== '') {
                    if (!isset($payload['dynamic_context']) || !is_array($payload['dynamic_context'])) {
                        $payload['dynamic_context'] = [];
                    }
                    // Campio WhatsApp job reads media_url from dynamic_context for header media templates.
                    $payload['dynamic_context']['media_url'] = $payload['media_url'];
                }
            }

            $campioResult = $this->sendViaCampio($channel, $payload);
            if (!$campioResult['ok']) {
                return back()->withErrors(['general' => $campioResult['message']])->withInput();
            }

            // Keep rich/original body in DB so compose view formatting is preserved.
            $updatePayload['body'] = $finalBody;
            $emailDraft->update($updatePayload + ['status' => 'sent', 'sent_at' => $sentAt]);
            if (($invoice->status ?? '') === 'draft') {
                $invoice->update(['status' => 'active']);
                $this->finalizeRenewedSourceItems($invoice);
            }

            return redirect()
                ->route('invoices.email-compose', [
                    'invoice' => $invoice->invoiceid,
                    'e' => $emailDraft->invoice_emailid,
                    'channel' => $channel,
                    'attachment_type' => $selectedType,
                ])
                ->with('success', $successTitle)
                ->with('send_success_meta', [
                    'title' => $successTitle,
                    'document' => $documentLabel,
                    'channel' => strtoupper($channel),
                    'sent_at' => $sentAt->format('d M Y, h:i A'),
                    'attachment_type' => $selectedType,
                ])
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
            'sender_id' => $forcedFromName !== '' ? $forcedFromName : $forcedFromEmail,
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
        $emailDraft->update($updatePayload + ['status' => 'sent', 'sent_at' => $sentAt]);
        if (($invoice->status ?? '') === 'draft') {
            $invoice->update(['status' => 'active']);
            $this->finalizeRenewedSourceItems($invoice);
        }

        return redirect()
            ->route('invoices.email-compose', [
                'invoice' => $invoice->invoiceid,
                'e' => $emailDraft->invoice_emailid,
                'channel' => 'email',
                'attachment_type' => $selectedType,
            ])
            ->with('success', $successTitle)
            ->with('send_success_meta', [
                'title' => $successTitle,
                'document' => $documentLabel,
                'channel' => 'EMAIL',
                'sent_at' => $sentAt->format('d M Y, h:i A'),
                'attachment_type' => $selectedType,
            ])
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

    public function ajaxList(Request $request)
    {
        $clientId = $request->query('clientid');
        if (!$clientId) {
            return response()->json(['invoices' => []]);
        }

        $invoices = Invoice::where('clientid', $clientId)
            ->where('status', '!=', 'cancelled')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get(['invoiceid', 'invoice_number', 'grand_total', 'currency', 'clientid']);

        return response()->json([
            'invoices' => $invoices->map(fn($inv) => [
                'invoiceid' => $inv->invoiceid,
                'invoice_number' => $inv->invoice_number,
                'grand_total' => (float) ($inv->grand_total ?? 0),
                'currency' => $inv->currency ?? 'INR',
            ])->values(),
        ]);
    }

    protected function calculateRenewalEndDate(\Carbon\Carbon $startDate, string $frequency, int $duration): ?\Carbon\Carbon
    {
        if ($duration <= 0 || $frequency === 'One-Time') {
            return null;
        }

        $date = $startDate->copy();

        switch ($frequency) {
            case 'Day(s)':
                $date->addDays($duration);
                break;
            case 'Week(s)':
                $date->addWeeks($duration);
                break;
            case 'Month(s)':
                $date->addMonths($duration);
                break;
            case 'Quarter(s)':
                $date->addMonths($duration * 3);
                break;
            case 'Year(s)':
                $date->addYears($duration);
                break;
            default:
                return null;
        }

        // Subtract 1 day to make it inclusive (e.g., 1st Jan to 31st Dec)
        return $date->subDay();
    }
}
