<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\InvalidatesOrderCache;
use App\Models\Account;
use App\Models\AccountBillingDetail;
use App\Models\Client;
use App\Models\CommunicationLog;
use App\Models\FinancialYear;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Ledger;
use App\Models\MessageTemplate;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentDetail;
use App\Models\SerialConfiguration;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Tax;
use App\Models\TermsCondition;
use App\Services\InvoiceReminderService;
use App\Traits\ConfiguresBrowsershot;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class InvoicesController extends Controller
{
    use ConfiguresBrowsershot, InvalidatesOrderCache;

    private function normalizeInvoiceTermsByType(mixed $terms): array
    {
        if (! is_array($terms) || $terms === []) {
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

    private function latestPaymentForInvoice(Invoice $invoice): ?Payment
    {
        return Payment::query()
            ->select('payments.*')
            ->join('payment_details', 'payment_details.paymentid', '=', 'payments.paymentid')
            ->where('payment_details.invoiceid', $invoice->invoiceid)
            ->orderByDesc('payments.payment_date')
            ->orderByDesc('payments.created_at')
            ->first();
    }

    private function paymentIdsForInvoice(Invoice $invoice)
    {
        return PaymentDetail::query()
            ->where('invoiceid', $invoice->invoiceid)
            ->distinct()
            ->pluck('paymentid');
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
        $termBucket = ! empty(trim((string) $invoice->ti_number)) ? 'billing' : 'proforma';
        $storedTerms = $this->normalizeInvoiceTermsByType($invoice->terms);

        if (! empty($storedTerms[$termBucket] ?? [])) {
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
        return ! empty(trim((string) $invoice->ti_number)) ? 'ti' : 'pi';
    }

    private function resolvePrimaryOrderIdFromItems(Invoice $invoice): ?string
    {
        $invoice->loadMissing('invoiceItems');
        $orderId = $invoice->invoiceItems
            ->sortBy('sequence')
            ->pluck('orderid')
            ->map(fn ($value) => trim((string) $value))
            ->first(fn ($value) => $value !== '');

        return $orderId !== null ? (string) $orderId : null;
    }

    private function renderMessageTemplate(string $value, Invoice $invoice, ?string $companyName = null): string
    {
        $replace = $this->buildInvoiceMessageTemplateReplacements($invoice, $companyName);

        return preg_replace_callback('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', function ($matches) use ($replace) {
            $key = '{{'.$matches[1].'}}';

            return array_key_exists($key, $replace) ? $replace[$key] : $matches[0];
        }, $value);
    }

    private function buildInvoiceMessageTemplateReplacements(Invoice $invoice, ?string $companyName = null): array
    {
        $clientBusinessName = trim((string) ($invoice->client?->business_name ?? ''));
        $clientContactPerson = trim((string) ($invoice->client?->contact_name ?? ''));
        $clientName = trim((string) ($clientBusinessName !== '' ? $clientBusinessName : $clientContactPerson));
        $currency = (string) ($invoice->client?->currency ?? 'INR');
        $totalAmount = (float) ($invoice->grand_total ?? $invoice->invoiceItems->sum('line_total') ?? 0);
        $dueDate = $invoice->due_date?->format('d M Y') ?? '';
        $primaryItem = $invoice->invoiceItems
            ->sortBy(function ($item) {
                return $item->end_date?->timestamp ?? PHP_INT_MAX;
            })
            ->first();
        $itemName = trim((string) ($primaryItem?->item_name ?? ''));
        $itemDescription = trim((string) ($primaryItem?->item_description ?? ''));
        $itemStartDate = $primaryItem?->start_date?->format('d M Y') ?? '';
        $itemEndDate = $primaryItem?->end_date?->format('d M Y') ?? '';
        $daysLeft = $primaryItem?->end_date ? now()->startOfDay()->diffInDays($primaryItem->end_date->startOfDay(), false) : null;
        $daysAgo = ((int) ($daysLeft ?? 0)) < 0 ? abs((int) $daysLeft) : 0;
        $templateType = ! empty(trim((string) $invoice->ti_number)) ? 'ti' : 'pi';
        $renewalDate = '';
        $latestPayment = $this->latestPaymentForInvoice($invoice);
        $paymentAmount = (float) ($latestPayment?->received_amount ?? 0);
        $paymentDate = $latestPayment?->payment_date?->format('d M Y') ?? '';
        $paymentReference = trim((string) ($latestPayment?->reference_number ?? ''));

        $hasInvoiceId = ! empty((string) ($invoice->invoiceid ?? ''));
        $piLink = $hasInvoiceId ? route('invoices.pdf', ['invoice' => $invoice->invoiceid, 'type' => 'pi']) : '';
        $tiLink = $hasInvoiceId ? route('invoices.pdf', ['invoice' => $invoice->invoiceid, 'type' => 'tax_invoice']) : '';

        // Get YOUR billing name from Account Billing Details
        $accountid = $this->resolveAccountId();
        $accountBillingDetail = AccountBillingDetail::query()->where('accountid', $accountid)->first();
        $billingName = $accountBillingDetail->billing_name ?? ($companyName ?? '');
        $designation = trim((string) ($accountBillingDetail?->designation ?? ''));
        $sourceOrder = null;
        if ($primaryItem?->itemid) {
            $sourceOrder = Order::query()
                ->where('accountid', $invoice->accountid)
                ->where('clientid', $invoice->clientid)
                ->where('itemid', $primaryItem->itemid)
                ->when(
                    $primaryItem?->end_date,
                    fn ($q) => $q->whereDate('end_date', $primaryItem->end_date->toDateString())
                )
                ->orderByDesc('created_at')
                ->first();
        }
        $primaryOrderId = $this->resolvePrimaryOrderIdFromItems($invoice);
        if (! $sourceOrder && ! empty($primaryOrderId)) {
            $sourceOrder = Order::query()
                ->where('accountid', $invoice->accountid)
                ->find($primaryOrderId);
        }
        if ($itemDescription === '') {
            $itemDescription = trim((string) ($sourceOrder?->item_description ?? ''));
        }

        return [
            // Client info - clear unambiguous tags
            '{{client_business_name}}' => $clientBusinessName,
            '{{client_contact_person}}' => $clientContactPerson,
            '{{client_name}}' => $clientName, // Legacy - use client_business_name instead
            // YOUR business info - from Billing Details tab (or Account as fallback)
            '{{business_name}}' => $billingName,
            '{{designation}}' => $designation,
            // Invoice info
            '{{invoice_number}}' => (string) ($invoice->invoice_number ?? ''),
            '{{invoice_title}}' => (string) ($invoice->invoice_title ?? ''),
            '{{pi_number}}' => (string) ($invoice->pi_number ?? ''),
            '{{ti_number}}' => (string) ($invoice->ti_number ?? ''),
            '{{pi_link}}' => $piLink,
            '{{ti_link}}' => $tiLink,
            '{{total_amount}}' => $currency.' '.number_format($totalAmount, 2),
            '{{due_date}}' => $dueDate,
            // Reminder/Renewal/Expiry focused tags
            '{{template_type}}' => $templateType,
            '{{reminder_type}}' => $templateType,
            '{{item_name}}' => $itemName,
            '{{item_description}}' => $itemDescription,
            '{{item_start_date}}' => $itemStartDate,
            '{{item_end_date}}' => $itemEndDate,
            '{{expiry_date}}' => $itemEndDate, // Alias of item_end_date for backward compatibility
            '{{days_left}}' => (string) max(0, (int) ($daysLeft ?? 0)),
            '{{days_ago}}' => (string) $daysAgo,
            '{{renewal_date}}' => $renewalDate,
            '{{order_number}}' => (string) ($sourceOrder?->order_number ?? ''),
            '{{order_start_date}}' => $sourceOrder?->start_date?->format('d M Y') ?? '',
            '{{order_end_date}}' => $sourceOrder?->end_date?->format('d M Y') ?? '',
            '{{payment_amount}}' => $paymentAmount > 0 ? ($currency.' '.number_format($paymentAmount, 2)) : '',
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
            $placeholder = '{{'.$token.'}}';
            $resolved = array_key_exists($placeholder, $replace)
                ? (string) $replace[$placeholder]
                : (string) $token;
            $variables[] = [
                'name' => $token,
                'value' => $this->sanitizeForCampioText($resolved),
            ];
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

        return asset('storage/'.ltrim($relative, '/'));
    }

    private function resolveCampioInvoicePdfUrl(Invoice $invoice, bool $isTaxInvoice, bool $forceRegenerate = false): string
    {
        $typeKey = $isTaxInvoice ? 'ti' : 'pi';

        if (! $forceRegenerate) {
            $existing = collect($this->listStoredInvoicePdfVersions($invoice))
                ->filter(fn ($row) => (string) ($row['type'] ?? '') === $typeKey)
                ->sortByDesc(fn ($row) => (int) ($row['version'] ?? 0))
                ->first();

            if (! empty($existing['path'])) {
                return $this->buildFriendlyCampioPdfUrl($invoice, (string) $existing['path'], $isTaxInvoice);
            }
        }

        $saved = $this->persistInvoicePdfVersion($invoice, $isTaxInvoice);
        if (! empty($saved['path'])) {
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
        $friendlyBase = $prefix.($number !== '' ? $number : $invoice->invoiceid);
        $friendlyBase = preg_replace('/[\\\\\\/:*?"<>|]+/', '-', $friendlyBase) ?: ($isTaxInvoice ? 'Tax-Invoice' : 'Proforma-Invoice');
        $friendlyBase = preg_replace('/\s+/', '-', $friendlyBase) ?: $friendlyBase;
        $friendlyBase = preg_replace('/-+/', '-', $friendlyBase);
        $versionSuffix = '';
        if (preg_match('/__v(\d+)\.pdf$/', $sourcePath, $m)) {
            $versionSuffix = '-v'.$m[1];
        }

        $targetPath = 'clients/'.$invoice->clientid.'/invoices-share/'.$friendlyBase.$versionSuffix.'.pdf';

        if (! $disk->exists($targetPath) && $disk->exists($sourcePath)) {
            $disk->put($targetPath, (string) $disk->get($sourcePath));
        }

        return asset('storage/'.$targetPath);
    }

    public function buildInvoicePdfAttachment(Invoice $invoice, bool $isTaxInvoice): array
    {
        $invoice->loadMissing(['client.billingDetail', 'invoiceItems', 'paymentDetails.payment']);

        $accountid = $invoice->accountid;
        $account = Account::find($accountid);
        $accountBillingDetail = AccountBillingDetail::query()->where('accountid', $accountid)->first();

        $documentType = $isTaxInvoice ? 'Tax Invoice' : 'Proforma Invoice';

        $normalizeTaxState = fn ($v) => preg_replace('/[^A-Z0-9]/', '', strtoupper(trim((string) $v)));
        $clientState = $normalizeTaxState($invoice->client->state ?? '');
        $accountState = $normalizeTaxState($account->state ?? '');
        $sameStateGst = $clientState !== '' && $accountState !== '' && $clientState === $accountState;

        $signatureUrl = null;
        $sigPath = optional($accountBillingDetail)->signature_upload;
        if (! empty($sigPath)) {
            if (str_starts_with($sigPath, 'http://') || str_starts_with($sigPath, 'https://')) {
                $signatureUrl = $sigPath;
            } else {
                $relative = str_starts_with($sigPath, 'storage/') ? $sigPath : 'storage/'.ltrim($sigPath, '/');
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
                ->map(fn ($term) => trim((string) $term))
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
        $filename = $documentType.' - '.($docNumber ?: $invoice->invoice_number).'.pdf';

        $pdfBinary = $this->getPdf($html);

        return [
            'filename' => $filename,
            'binary' => $pdfBinary,
        ];
    }

    public function persistInvoicePdfVersion(Invoice $invoice, bool $isTaxInvoice): ?array
    {
        $invoice->loadMissing(['client']);

        $disk = Storage::disk('public');
        $typeKey = $isTaxInvoice ? 'ti' : 'pi';
        $directory = 'clients/'.$invoice->clientid.'/invoices-pdf';
        $baseName = $invoice->invoiceid.'_'.$typeKey;

        $currentHash = $this->getInvoiceDataHash($invoice, $isTaxInvoice);
        $hashFile = $directory.'/'.$baseName.'.hash';
        $storedHash = $disk->exists($hashFile) ? trim((string) $disk->get($hashFile)) : '';

        if ($currentHash === $storedHash) {
            $existingVersions = $this->listStoredInvoicePdfVersions($invoice);
            $latestExisting = collect($existingVersions)
                ->filter(fn ($row) => (string) ($row['type'] ?? '') === $typeKey)
                ->sortByDesc(fn ($row) => (int) ($row['version'] ?? 0))
                ->first();

            if ($latestExisting) {
                return $latestExisting;
            }
        }

        $pdfAttachment = $this->buildInvoicePdfAttachment($invoice, $isTaxInvoice);

        $existing = collect($disk->files($directory))
            ->map(function (string $path) use ($baseName) {
                $name = pathinfo($path, PATHINFO_FILENAME);
                if (preg_match('/^'.preg_quote($baseName, '/').'__v(\d+)$/', $name, $m)) {
                    return (int) $m[1];
                }

                return null;
            })
            ->filter(fn ($v) => $v !== null)
            ->values();

        $nextVersion = ((int) ($existing->max() ?? 0)) + 1;
        $relativePath = $directory.'/'.$baseName.'__v'.$nextVersion.'.pdf';
        $disk->put($relativePath, $pdfAttachment['binary']);
        $disk->put($hashFile, $currentHash);

        return [
            'type' => $typeKey,
            'version' => $nextVersion,
            'filename' => basename($relativePath),
            'path' => $relativePath,
            'url' => asset('storage/'.$relativePath),
            'saved_at' => now()->toDateTimeString(),
        ];
    }

    private function getInvoiceDataHash(Invoice $invoice, bool $isTaxInvoice): string
    {
        $invoice->loadMissing(['client.billingDetail', 'invoiceItems', 'paymentDetails.payment']);
        $accountid = $invoice->accountid;
        $account = Account::find($accountid);
        $accountBillingDetail = AccountBillingDetail::query()->where('accountid', $accountid)->first();

        $data = [
            'is_tax' => $isTaxInvoice,
            'invoice' => $invoice->toArray(),
            'client' => $invoice->client?->toArray() ?? [],
            'client_billing' => $invoice->client?->billingDetail?->toArray() ?? [],
            'account' => $account?->toArray() ?? [],
            'account_billing' => $accountBillingDetail?->toArray() ?? [],
            'items' => $invoice->invoiceItems->toArray(),
            'payments' => $invoice->paymentDetails->toArray(),
        ];

        $volatileFields = [
            'updated_at', 'created_at', 'last_emailed_at', 'status', 'email_status', 'sms_status', 'whatsapp_status',
        ];

        $cleanData = $this->recursiveRemoveVolatileFields($data, $volatileFields);

        return md5(json_encode($cleanData));
    }

    private function recursiveRemoveVolatileFields(array $array, array $volatileFields): array
    {
        foreach ($array as $key => &$value) {
            if (in_array($key, $volatileFields, true)) {
                unset($array[$key]);
            } elseif (is_array($value)) {
                $value = $this->recursiveRemoveVolatileFields($value, $volatileFields);
            }
        }

        return $array;
    }

    private function listStoredInvoicePdfVersions(Invoice $invoice): array
    {
        $disk = Storage::disk('public');
        $directory = 'clients/'.$invoice->clientid.'/invoices-pdf';
        if (! $disk->exists($directory)) {
            return [];
        }

        $versions = collect($disk->files($directory))
            ->map(function (string $path) use ($invoice, $disk) {
                $name = pathinfo($path, PATHINFO_FILENAME);
                if (! preg_match('/^'.preg_quote($invoice->invoiceid, '/').'_(pi|ti)__v(\d+)$/', $name, $m)) {
                    return null;
                }

                return [
                    'type' => $m[1],
                    'version' => (int) $m[2],
                    'filename' => basename($path),
                    'path' => $path,
                    'url' => asset('storage/'.$path),
                    'saved_at' => optional($disk->lastModified($path) ? Carbon::createFromTimestamp($disk->lastModified($path)) : null)?->toDateTimeString(),
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
            ->map(fn ($v) => is_string($v) ? trim($v) : $v)
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
            Log::error('Campio: CAMPIO_BASE_URL not configured');

            return ['ok' => false, 'message' => 'CAMPIO_BASE_URL is not configured.'];
        }

        $endpoint = $baseUrl.'/api/campaigns/schedule/'.$channel;
        $token = trim((string) env('CAMPIO_AUTH_TOKEN', ''));
        $apiKey = trim((string) env('CAMPIO_API_KEY', ''));

        // Log the full payload for debugging
        Log::info('Campio: Sending '.strtoupper($channel), [
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
            Log::error('Campio: Request failed - '.$e->getMessage());

            return ['ok' => false, 'message' => 'Campio request failed: '.$e->getMessage()];
        }

        Log::info('Campio: Response received', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        $json = $response->json();
        if (! $response->successful()) {
            Log::error('Campio: API error', [
                'status' => $response->status(),
                'json' => $json,
            ]);
            $message = is_array($json)
                ? ((string) ($json['message'] ?? 'Campio API returned an error.'))
                : ('Campio API returned HTTP '.$response->status().'.');

            return ['ok' => false, 'message' => $message];
        }

        Log::info('Campio: SUCCESS', [
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
        if (! in_array($channel, ['whatsapp', 'sms'], true)) {
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
            $response = $request->get($baseUrl.'/api/templates/'.$channel, [
                'account_id' => $accountid,
            ]);
        } catch (\Throwable $e) {
            return [];
        }

        if (! $response->successful()) {
            return [];
        }

        $rows = data_get($response->json(), 'data.templates', []);
        if (! is_array($rows)) {
            return [];
        }

        $map = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
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
                $fileName = basename($path ?: ('attachment-'.($index + 1).'.pdf'));
                if ($fileName === '' || $fileName === '/' || $fileName === '.') {
                    $fileName = 'attachment-'.($index + 1).'.pdf';
                }
            }
            $fileName = preg_replace('/[\\\\\\/]+/', '-', $fileName) ?? $fileName;
            $fileName = preg_replace('/\s+/', ' ', trim($fileName)) ?? $fileName;
            if ($fileName === '' || $fileName === '.' || $fileName === '..') {
                $fileName = 'attachment-'.($index + 1).'.pdf';
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
        $encodedSegments = array_map(fn ($s) => rawurlencode($s), $segments);
        $encodedPath = implode('/', $encodedSegments);

        $rebuilt = $parts['scheme'].'://'.$parts['host']
            .(isset($parts['port']) ? ':'.$parts['port'] : '')
            .$encodedPath
            .(isset($parts['query']) ? '?'.$parts['query'] : '')
            .(isset($parts['fragment']) ? '#'.$parts['fragment'] : '');

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
        $text = str_replace(['\\r\\n', '\\n'], "\n", $text);

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
        $value = str_replace(['\\r\\n', '\\n'], "\n", $value);
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
        $clients = Client::where('accountid', $accountid)->regular()->orderBy('business_name')->get();
        $selectedClientId = request('c', request('clientid'));
        $selectedTab = request('tab', 'invoices');
        $selectedType = trim((string) request('type', ''));
        $selectedStatus = strtolower(trim((string) request('status', 'active')));

        $invoiceQuery = Invoice::where('accountid', $accountid)
            ->with(['client', 'items', 'paymentDetails.payment'])
            ->orderBy('issue_date', 'desc');

        if ($selectedClientId) {
            $invoiceQuery->where('clientid', $selectedClientId);
        }

        if ($selectedTab === 'proforma' && $selectedType === '') {
            $selectedType = 'pi';
            $selectedTab = 'invoices';
        } elseif ($selectedTab === 'tax' && $selectedType === '') {
            $selectedType = 'tax';
            $selectedTab = 'invoices';
        }

        $allInvoices = $invoiceQuery->get()->values();

        $resolvePaymentStatus = function (Invoice $invoice): string {
            $amountPaid = (float) ($invoice->amount_paid ?? 0);
            $grandTotal = (float) ($invoice->grand_total ?? 0);
            $balanceDue = (float) ($invoice->balance_due ?? max(0, $grandTotal - $amountPaid));

            $paymentStatus = strtolower(trim((string) ($invoice->payment_status ?? '')));
            if (! in_array($paymentStatus, ['paid', 'partly_paid', 'unpaid'], true)) {
                $paymentStatus = 'unpaid';
                if ($amountPaid > 0 && $balanceDue <= 0 && $grandTotal > 0) {
                    $paymentStatus = 'paid';
                } elseif ($amountPaid > 0) {
                    $paymentStatus = 'partly_paid';
                }
            }

            return $paymentStatus;
        };

        $selectedTab = in_array($selectedTab, ['invoices', 'outstanding', 'partly_paid', 'unpaid', 'draft', 'cancelled', 'proforma', 'tax', 'paid'], true)
            ? $selectedTab
            : 'invoices';
        $selectedType = in_array($selectedType, ['', 'pi', 'tax'], true) ? $selectedType : '';
        $selectedStatus = in_array($selectedStatus, ['active', 'cancelled'], true) ? $selectedStatus : 'active';
        if ($selectedTab === 'cancelled') {
            $selectedStatus = 'cancelled';
        } elseif ($selectedStatus === 'cancelled') {
            $selectedStatus = 'active';
        }

        if (in_array($selectedTab, ['partly_paid', 'unpaid'], true)) {
            $selectedTab = 'outstanding';
        }

        $typedInvoices = $allInvoices;
        if ($selectedType !== '') {
            $typedInvoices = $typedInvoices->filter(function (Invoice $invoice) use ($selectedType) {
                $isTaxInvoice = trim((string) ($invoice->ti_number ?? '')) !== '';

                return $selectedType === 'tax' ? $isTaxInvoice : ! $isTaxInvoice;
            })->values();
        }

        $draftInvoices = $typedInvoices->filter(function (Invoice $invoice) {
            return strtolower(trim((string) ($invoice->status ?? ''))) === 'draft';
        })->values();

        $cancelledInvoices = $typedInvoices->filter(function (Invoice $invoice) {
            return strtolower(trim((string) ($invoice->status ?? ''))) === 'cancelled';
        })->values();

        $activeInvoices = $typedInvoices->filter(function (Invoice $invoice) {
            $status = strtolower(trim((string) ($invoice->status ?? 'active')));

            return ! in_array($status, ['cancelled', 'draft'], true);
        })->values();

        $paidInvoices = $activeInvoices->filter(fn (Invoice $invoice) => $resolvePaymentStatus($invoice) === 'paid')->values();
        $partlyPaidInvoices = $activeInvoices->filter(fn (Invoice $invoice) => $resolvePaymentStatus($invoice) === 'partly_paid')->values();
        $unpaidInvoices = $activeInvoices->filter(fn (Invoice $invoice) => $resolvePaymentStatus($invoice) === 'unpaid')->values();
        $outstandingInvoices = $activeInvoices
            ->filter(fn (Invoice $invoice) => in_array($resolvePaymentStatus($invoice), ['partly_paid', 'unpaid'], true))
            ->values();

        $filteredInvoices = $typedInvoices;
        if ($selectedTab === 'draft') {
            $filteredInvoices = $draftInvoices;
        } elseif ($selectedTab === 'cancelled') {
            $filteredInvoices = $cancelledInvoices;
        } elseif ($selectedTab === 'outstanding') {
            $filteredInvoices = $outstandingInvoices;
        } elseif ($selectedTab === 'paid') {
            $filteredInvoices = $paidInvoices;
        }

        if (request()->wantsJson() || request()->ajax()) {
            $forCreatePicker = request()->boolean('for_create_picker');
            $jsonInvoices = $forCreatePicker
                ? $typedInvoices->filter(function (Invoice $invoice) {
                    $status = strtolower(trim((string) ($invoice->status ?? 'active')));

                    return $status !== 'cancelled';
                })->values()
                : $filteredInvoices;

            return response()->json([
                'invoices' => $jsonInvoices->map(function ($invoice) {
                    $amountPaid = (float) ($invoice->amount_paid ?? 0);
                    $grandTotal = (float) ($invoice->grand_total ?? 0);
                    $balanceDue = (float) ($invoice->balance_due ?? max(0, $grandTotal - $amountPaid));
                    $currency = $invoice->client->currency ?? 'INR';

                    $statusValue = strtolower(trim((string) ($invoice->status ?? 'draft')));
                    $paymentStatus = strtolower(trim((string) ($invoice->payment_status ?? '')));
                    if (! in_array($paymentStatus, ['paid', 'partly_paid', 'unpaid'], true)) {
                        $paymentStatus = 'unpaid';
                        if ($amountPaid > 0 && $balanceDue <= 0 && $grandTotal > 0) {
                            $paymentStatus = 'paid';
                        } elseif ($amountPaid > 0) {
                            $paymentStatus = 'partly_paid';
                        }
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
                        'amount' => $currency.' '.number_format($invoice->grand_total ?? 0, 0),
                        'amount_paid' => $currency.' '.number_format($amountPaid, 0),
                        'balance_due' => $currency.' '.number_format($balanceDue, 0),
                        'status' => $statusValue !== '' ? $statusValue : 'draft',
                        'payment_status' => $paymentStatus,
                        'items' => $invoice->items->map(function ($item) use ($currency) {
                            return [
                                'itemid' => $item->itemid ?? $item->invoice_itemid ?? '',
                                'name' => $item->item_name,
                                'item_name' => $item->item_name,
                                'qty' => $item->quantity,
                                'quantity' => $item->quantity,
                                'price' => $currency.' '.number_format($item->unit_price, 0),
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
                                'total' => $currency.' '.number_format($item->line_total, 0),
                                'line_total' => (float) $item->line_total,
                            ];
                        }),
                    ];
                })->values(),
                'selected_type' => $selectedType,
                'selected_status' => $selectedStatus,
                'selected_tab' => $selectedTab,
            ]);
        }

        return view('invoices.index', [
            'title' => 'Invoices',
            'clients' => $clients,
            'allInvoices' => $filteredInvoices,
            'allInvoiceRows' => $allInvoices,
            'selectedClientId' => $selectedClientId,
            'selectedTab' => $selectedTab,
            'selectedType' => $selectedType,
            'selectedStatus' => $selectedStatus,
            'draftInvoicesCount' => $draftInvoices->count(),
            'cancelledInvoicesCount' => $cancelledInvoices->count(),
            'paidInvoicesCount' => $paidInvoices->count(),
            'outstandingInvoicesCount' => $outstandingInvoices->count(),
            'partlyPaidInvoicesCount' => $partlyPaidInvoices->count(),
            'unpaidInvoicesCount' => $unpaidInvoices->count(),
            'allInvoicesCount' => $typedInvoices->count(),
        ]);
    }

    public function invoicesExpiryList(Request $request): View
    {
        $accountid = $this->resolveAccountId();
        $clients = Client::where('accountid', $accountid)->orderBy('business_name')->get();
        $hasTrialClients = Client::where('accountid', $accountid)->trial()->exists();
        $selectedClientId = $request->query('c', $request->query('clientid'));
        $selectedClient = $selectedClientId ? Client::where('accountid', $accountid)->find($selectedClientId) : null;
        $selectedTab = $request->query('tab', 'expired');
        if ($selectedClient && strtolower((string) ($selectedClient->type ?? '')) === 'trial') {
            $selectedTab = 'trial';
        }

        $allowedTabs = $hasTrialClients
            ? ['upcoming', 'expired', 'suspended', 'trial']
            : ['upcoming', 'expired', 'suspended'];
        $selectedTab = in_array($selectedTab, $allowedTabs, true)
            ? $selectedTab
            : 'expired';
        $fromDate = trim((string) $request->query('from', ''));
        $toDate = trim((string) $request->query('to', ''));
        $today = now()->startOfDay();
        $nextDays = (int) $request->query('next_days', 60);
        if ($nextDays <= 0) {
            $nextDays = 60;
        }
        $upcomingThreshold = $today->copy()->addDays($nextDays)->endOfDay();

        $expiryItemBaseQuery = Order::query()
            ->where('accountid', $accountid)
            ->whereNotNull('end_date')
            ->with([
                'client',
                'invoices:invoiceid,invoice_title,pi_number,ti_number,status,created_at',
                'item:itemid,name',
                'invoiceItems',
            ]);

        if ($selectedClientId) {
            $expiryItemBaseQuery->where('clientid', $selectedClientId);
        }
        if ($fromDate !== '') {
            $expiryItemBaseQuery->whereDate('end_date', '>=', $fromDate);
        }
        if ($toDate !== '') {
            $expiryItemBaseQuery->whereDate('end_date', '<=', $toDate);
        }

        $mapExpiryItem = function (Order $order) use ($today) {
            $expiryDate = $order->end_date?->copy()->startOfDay();
            $daysLeft = $expiryDate ? $today->diffInDays($expiryDate, false) : null;
            $linkedInvoice = $this->resolveActiveInvoiceForOrder($order);
            $latestInvoiceItem = $order->invoiceItems->sortByDesc('created_at')->sortByDesc('invoice_itemid')->first();

            return [
                'orderid' => (string) $order->orderid,
                'order_number' => (string) ($order->order_number ?? '-'),
                'invoiceid' => (string) ($linkedInvoice?->invoiceid ?? ''),
                'clientid' => (string) $order->clientid,
                'client_type' => (string) ($order->type ?? $order->client?->type ?? 'regular'),
                'client_name' => (string) (
                    $order->client?->business_name
                    ?: $order->client?->contact_name
                    ?: 'Client'
                ),
                'invoice_label' => (string) (
                    $linkedInvoice?->invoice_title
                    ?: $linkedInvoice?->ti_number
                    ?: $linkedInvoice?->pi_number
                    ?: '-'
                ),
                'invoice_number' => (string) (
                    $linkedInvoice?->ti_number
                    ?: $linkedInvoice?->pi_number
                    ?: '-'
                ),
                'currency' => (string) ($order->client?->currency ?? 'INR'),
                'item_name' => (string) ($order->item_name ?: $order->item?->name ?: 'Item'),
                'item_description' => (string) ($order->item_description ?? ''),
                'frequency' => (string) ($latestInvoiceItem?->frequency ?? ''),
                'duration' => $latestInvoiceItem ? (int) $latestInvoiceItem->duration : null,
                'status' => (string) ($order->status ?? 'active'),
                'start_date' => $order->start_date?->copy()->startOfDay(),
                'start_date_display' => $order->start_date?->format('d M Y') ?? '-',
                'end_date' => $expiryDate,
                'end_date_display' => $expiryDate?->format('d M Y') ?? '-',
                'days_left' => $daysLeft,
            ];
        };

        $upcomingItems = (clone $expiryItemBaseQuery)
            ->regular()
            ->where(function ($query) {
                $query->whereNotIn('status', ['cancelled', 'suspended'])
                    ->orWhereNull('status');
            })
            ->whereDate('end_date', '>', $today->toDateString())
            ->whereDate('end_date', '<=', $upcomingThreshold->toDateString())
            ->orderBy('end_date')
            ->get()
            ->map($mapExpiryItem)
            ->values();

        $expiredItems = (clone $expiryItemBaseQuery)
            ->regular()
            ->where(function ($query) {
                $query->whereNotIn('status', ['cancelled', 'suspended'])
                    ->orWhereNull('status');
            })
            ->whereDate('end_date', '<=', $today->toDateString())
            ->orderBy('end_date')
            ->get()
            ->map($mapExpiryItem)
            ->values();

        $suspendedItems = (clone $expiryItemBaseQuery)
            ->regular()
            ->where('status', 'suspended')
            ->orderBy('end_date')
            ->get()
            ->map($mapExpiryItem)
            ->values();

        $trialItems = collect();
        if ($hasTrialClients) {
            $trialItems = (clone $expiryItemBaseQuery)
                ->trial()
                ->where(function ($query) {
                    $query->whereNotIn('status', ['cancelled', 'suspended'])
                        ->orWhereNull('status');
                })
                ->orderBy('end_date')
                ->get()
                ->map($mapExpiryItem)
                ->values();
        }

        return view('invoices.expiry-list', [
            'title' => 'Renew/View Expiry',
            'clients' => $clients,
            'selectedClientId' => $selectedClientId,
            'selectedTab' => $selectedTab,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'nextDays' => $nextDays,
            'hasTrialClients' => $hasTrialClients,
            'upcomingItems' => $upcomingItems,
            'expiredItems' => $expiredItems,
            'suspendedItems' => $suspendedItems,
            'trialItems' => $trialItems,
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
            return redirect()->route('invoices.expiry-list', ['tab' => 'expired'])
                ->with('error', 'Unable to start renewal for this item.');
        }

        return redirect()->route('invoices.create', [
            'step' => 2,
            'c' => $clientId,
        ]);
    }

    public function invoicesCreate(): View
    {
        $user = Auth::user();
        $accountid = $this->resolveAccountId();
        $legacyAccountId = $user?->id ? (string) $user->id : null;
        $account = Account::find($accountid);
        $invoiceDateBounds = $this->resolveFinancialYearDateBounds($accountid);
        $orderId = request('o', request('orderid'));
        $clientId = request('c', request('clientid'));
        $currentUserId = $user?->userid ?? $user?->id;
        $draftId = request('d');

        $existingDraft = null;
        if (! empty($draftId) && ! empty($currentUserId)) {
            $existingDraft = Invoice::query()
                ->where('invoiceid', $draftId)
                ->where('accountid', $accountid)
                ->first();
        }

        if (
            empty($existingDraft)
            && empty($draftId)
            && ! empty($clientId)
            && ! empty($currentUserId)
        ) {
            $existingDraft = Invoice::query()
                ->where('clientid', $clientId)
                ->where('accountid', $accountid)
                ->where('status', 'draft')
                ->where('created_by', $currentUserId)
                ->when(! empty($orderId), fn ($q) => $q->whereHas('invoiceItems', fn ($itemQ) => $itemQ->where('orderid', $orderId)))
                ->when(empty($orderId), fn ($q) => $q->whereDoesntHave('invoiceItems', fn ($itemQ) => $itemQ->whereNotNull('orderid')))
                ->where('updated_at', '>', now()->subHours(24))
                ->latest('updated_at')
                ->first();
        }

        $orderItemsForClient = collect();
        if (! empty($clientId)) {
            $orderItemsForClient = Order::query()
                ->where('accountid', $accountid)
                ->where('clientid', $clientId)
                ->where('status', '!=', 'cancelled')
                ->with([
                    'item:itemid,user_wise',
                    'item.costings',
                    'client:clientid,currency',
                ])
                ->orderByDesc('created_at')
                ->get()
                ->map(function ($order) {
                    $pricing = $this->deriveOrderPricing($order);

                    return [
                        'orderid' => (string) $order->orderid,
                        'order_number' => (string) ($order->order_number ?? ''),
                        'display_order_number' => (string) ($order->order_number ?: $order->orderid),
                        'itemid' => (string) ($order->itemid ?? ''),
                        'item_name' => (string) ($order->item_name ?? 'Item'),
                        'item_description' => (string) ($order->item_description ?? ''),
                        'quantity' => (int) max(1, (int) ($order->quantity ?? 1)),
                        'unit_price' => (float) ($pricing['unit_price'] ?? 0),
                        'tax_rate' => (float) ($pricing['tax_rate'] ?? 0),
                        'no_of_users' => $order->no_of_users ? (int) $order->no_of_users : null,
                        'frequency' => 'One-Time',
                        'duration' => null,
                        'start_date' => $order->start_date?->format('Y-m-d'),
                        'end_date' => $order->end_date?->format('Y-m-d'),
                        'requires_user_fields' => $this->isUserWiseEnabled(optional($order->item)->user_wise),
                    ];
                })
                ->values();
        }

        $nextInvoiceNumber = $existingDraft?->pi_number ?: $this->generateInvoiceNumber();
        $nextTaxInvoiceNumber = $existingDraft?->ti_number ?: $this->generateTaxInvoiceNumber();

        // Get selected client currency
        $selectedClientCurrency = 'INR';
        if (! empty($clientId)) {
            $client = Client::query()
                ->where('accountid', $accountid)
                ->find($clientId);
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
            'title' => 'Manage Invoices',
            'clients' => Client::where('accountid', $accountid)->regular()->active()->orderBy('business_name')->get(),
            'services' => Service::where('accountid', $accountid)->with(['category', 'costings'])->orderBy('sequence')->orderBy('name')->get(),
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
            'orderItemsForClient' => $orderItemsForClient,
            'invoiceDateBounds' => $invoiceDateBounds,
        ]);
    }

    public function getClientOrders(Request $request)
    {
        $clientId = $request->input('clientid');

        if (! $clientId) {
            return response()->json([]);
        }

        $orders = Order::where('clientid', $clientId)
            ->where('status', '!=', 'cancelled')
            ->whereDoesntHave('invoices')
            ->with(['client:clientid,currency', 'item.costings'])
            ->orderBy('created_at', 'desc')
            ->get(['orderid', 'order_number', 'delivery_date', 'status', 'clientid', 'item_name', 'itemid', 'quantity', 'no_of_users', 'created_at'])
            ->map(function ($order) {
                $currency = $order->client->currency ?? 'INR';
                $pricing = $this->deriveOrderPricing($order);

                return [
                    'orderid' => $order->orderid,
                    'order_number' => $order->order_number,
                    'order_title' => $order->item_name,
                    'order_date' => $order->created_at?->format('d M Y') ?? 'N/A',
                    'delivery_date' => $order->delivery_date?->format('d M Y') ?? 'N/A',
                    'grand_total' => $pricing['grand_total'],
                    'currency' => $currency,
                    'status' => $order->status ?? 'draft',
                    'is_verified' => 'yes',
                    'sales_person' => '-',
                    'item_count' => 1,
                ];
            });

        return response()->json($orders);
    }

    public function getClientOrderItems(Request $request)
    {
        $clientId = $request->input('clientid');
        $accountid = $this->resolveAccountId();

        if (! $clientId) {
            return response()->json([]);
        }

        $orderItemsForClient = Order::query()
            ->where('accountid', $accountid)
            ->where('clientid', $clientId)
            ->where('status', '!=', 'cancelled')
            ->with([
                'item:itemid,user_wise',
                'item.costings',
                'client:clientid,currency',
            ])
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($order) {
                $pricing = $this->deriveOrderPricing($order);

                return [
                    'orderid' => (string) $order->orderid,
                    'order_number' => (string) ($order->order_number ?? ''),
                    'display_order_number' => (string) ($order->order_number ?: $order->orderid),
                    'itemid' => (string) ($order->itemid ?? ''),
                    'item_name' => (string) ($order->item_name ?? 'Item'),
                    'item_description' => (string) ($order->item_description ?? ''),
                    'quantity' => (int) max(1, (int) ($order->quantity ?? 1)),
                    'unit_price' => (float) ($pricing['unit_price'] ?? 0),
                    'tax_rate' => (float) ($pricing['tax_rate'] ?? 0),
                    'no_of_users' => $order->no_of_users ? (int) $order->no_of_users : null,
                    'frequency' => 'One-Time',
                    'duration' => null,
                    'start_date' => $order->start_date?->format('Y-m-d'),
                    'end_date' => $order->end_date?->format('Y-m-d'),
                    'requires_user_fields' => $this->isUserWiseEnabled(optional($order->item)->user_wise),
                ];
            })
            ->values();

        return response()->json($orderItemsForClient);
    }

    public function getRenewalInvoices(Request $request)
    {
        $clientId = $request->input('clientid');
        $daysFilter = max(0, (int) $request->input('days', 30));

        if (! $clientId) {
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
                        if (! $item->end_date) {
                            return null;
                        }

                        if (! $this->hasRecurringFrequency($item->frequency)) {
                            return null;
                        }

                        if (($item->status ?? 'active') !== 'active') {
                            return null;
                        }

                        $itemEndDate = $item->end_date instanceof Carbon
                            ? $item->end_date
                            : Carbon::parse($item->end_date);

                        $isExpired = $itemEndDate <= $today;
                        $isUpcoming = ! $isExpired && $itemEndDate > $today && $itemEndDate <= $upcomingThreshold;
                        if (! $isExpired && ! $isUpcoming) {
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
        $order = Order::with([
            'item:itemid,user_wise',
            'item.costings',
            'client:clientid,currency',
        ])->findOrFail($orderid);

        $pricing = $this->deriveOrderPricing($order);
        $items = collect([[
            'itemid' => $order->itemid,
            'item_name' => $order->item_name,
            'item_description' => $order->item_description,
            'quantity' => $order->quantity,
            'unit_price' => $pricing['unit_price'],
            'tax_rate' => $pricing['tax_rate'],
            'discount_percent' => 0,
            'discount_amount' => 0,
            'duration' => null,
            'frequency' => 'One-Time',
            'no_of_users' => $order->no_of_users,
            'start_date' => $order->start_date?->format('Y-m-d'),
            'end_date' => $order->end_date?->format('Y-m-d'),
            'delivery_date' => $order->delivery_date?->format('Y-m-d'),
            'line_total' => $pricing['line_total'],
            'requires_user_fields' => $this->isUserWiseEnabled(optional($order->item)->user_wise),
        ]]);

        return response()->json([
            'order' => [
                'orderid' => $order->orderid,
                'order_number' => $order->order_number,
                'order_title' => $order->item_name,
                'order_date' => $order->created_at?->format('d M Y') ?? 'N/A',
                'delivery_date' => $order->delivery_date?->format('d M Y') ?? 'N/A',
                'grand_total' => $pricing['grand_total'],
                'currency' => $order->client->currency ?? 'INR',
                'sales_person' => '-',
                'is_verified' => 'yes',
                'item_count' => 1,
            ],
            'items' => $items,
            'date_prefill' => $this->resolveOrderDatePrefill($order),
        ]);
    }

    private function resolveOrderDatePrefill(Order $order): array
    {
        $orderStartDate = $order->start_date?->toDateString() ?: now()->toDateString();
        $orderEndDate = $order->end_date?->toDateString();

        $result = [
            'source' => 'orders',
            'order_start_date' => $orderStartDate,
            'order_end_date' => $orderEndDate,
            'suggested_start_date' => $orderStartDate,
        ];

        if (! Schema::hasColumn('invoice_items', 'orderid')) {
            return $result;
        }

        $maxEndDate = InvoiceItem::query()
            ->where('clientid', $order->clientid)
            ->where('orderid', $order->orderid)
            ->whereNotNull('end_date')
            ->whereDate('end_date', '<', '2099-12-31')
            ->max('end_date');

        if (! empty($maxEndDate)) {
            $result['source'] = 'invoice_items';
            $result['suggested_start_date'] = Carbon::parse((string) $maxEndDate)->addDay()->toDateString();
        }

        return $result;
    }

    private function deriveOrderPricing(Order $order): array
    {
        $account = Account::find($order->accountid);
        $serviceCosting = $order->item?->costings?->sortBy('currency_code')->first();
        $unitPrice = (float) ($serviceCosting->selling_price ?? 0);
        $taxRate = (float) ($serviceCosting->tax_rate ?? 0);

        if ($account && ! $account->allow_multi_taxation && (float) ($account->fixed_tax_rate ?? 0) > 0) {
            $taxRate = (float) $account->fixed_tax_rate;
        }

        $quantity = max(1, (int) round((float) ($order->quantity ?? 1), 0));
        $users = max(1, (int) round((float) ($order->no_of_users ?? 1), 0));
        $lineTotal = (float) round($quantity * $users * $unitPrice, 0);
        $taxAmount = (float) ceil($lineTotal * ($taxRate / 100));

        return [
            'unit_price' => $unitPrice,
            'tax_rate' => $taxRate,
            'line_total' => $lineTotal,
            'grand_total' => max(0, $lineTotal + $taxAmount),
        ];
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

            if (! $item->end_date) {
                $isExpired = false;
                $isUpcoming = false;
            } else {
                $itemEndDate = $item->end_date instanceof Carbon
                    ? $item->end_date
                    : Carbon::parse($item->end_date);

                $isExpired = $itemEndDate <= $today;
                $isUpcoming = ! $isExpired && $itemEndDate > $today && $itemEndDate <= $upcomingThreshold;
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

    /**
     * Sync invoice items for an existing invoice using a proper upsert strategy:
     * - UPDATE rows whose invoice_itemid is present in $preparedItems
     * - INSERT new rows that have no invoice_itemid (fires created event → timeline log)
     * - DELETE rows that are in the DB but absent from $preparedItems (fires deleted event → timeline log)
     *
     * @param  array<int, array<string, mixed>>  $preparedItems
     */
    private function syncInvoiceItems(Invoice $invoice, array $preparedItems): void
    {
        $incomingIds = array_values(array_filter(
            array_map(fn ($d) => $d['invoice_itemid'] ?? null, $preparedItems)
        ));

        // Delete rows that were removed by the user (genuine removal → fires deleted event).
        foreach ($invoice->items as $existing) {
            if (! in_array($existing->invoice_itemid, $incomingIds, true)) {
                $existing->delete();
            }
        }

        // Update or create each submitted item.
        foreach ($preparedItems as $index => $itemData) {
            $itemData['invoiceid'] = $invoice->invoiceid;
            $itemData['sequence'] = $index + 1;
            $existingId = $itemData['invoice_itemid'] ?? null;
            unset($itemData['invoice_itemid']);

            if ($existingId) {
                $existingItem = InvoiceItem::where('invoice_itemid', $existingId)
                    ->where('invoiceid', $invoice->invoiceid)
                    ->first();

                if ($existingItem) {
                    // Update in place — triggers updated event which logs specific changes.
                    $existingItem->update($itemData);

                    continue;
                }
            }

            // No matching row — genuinely new item, fire created event → "Billed on Invoice" log.
            $itemData['accountid'] = $invoice->accountid;
            $itemData['clientid'] = $invoice->clientid;
            InvoiceItem::create($itemData);
        }
    }

    private function calculateInvoiceItemAmounts(array $itemData, float $taxRate): array
    {
        $quantity = $this->wholeQuantity($itemData['quantity'] ?? 1);
        $users = ! empty($itemData['no_of_users']) ? max(1, (int) $itemData['no_of_users']) : 1;
        $duration = ! empty($itemData['duration']) ? max(1, (int) $itemData['duration']) : 1;
        $durationMultiplier = $this->hasRecurringFrequency($itemData['frequency'] ?? null) ? $duration : 1;

        $unitPrice = $this->moneyAmount($itemData['unit_price'] ?? 0);
        $lineTotal = $this->moneyAmount($itemData['line_total'] ?? 0);
        if ($lineTotal <= 0) {
            $lineTotal = $this->moneyAmount($quantity * $unitPrice * $users * $durationMultiplier);
        }
        if ($unitPrice <= 0 && $lineTotal > 0) {
            $divisor = max(1, $quantity * $users * $durationMultiplier);
            $unitPrice = $this->moneyAmount($lineTotal / $divisor);
        }
        $discountPercent = $this->wholePercent($itemData['discount_percent'] ?? 0);

        $discountValue = ($lineTotal * $discountPercent) / 100;
        $discountedLineTotal = max(0, $lineTotal - $discountValue);
        $taxAmount = ($discountedLineTotal * $taxRate) / 100;
        $discountedUnitPrice = $this->moneyAmount($unitPrice * ((100 - $discountPercent) / 100));

        return [
            'line_total' => $this->moneyAmount($lineTotal),
            'discount_percent' => $discountPercent,
            // discount_amount stores discounted unit price.
            'discount_amount' => $discountedUnitPrice,
            'discounted_line_total' => $this->moneyAmount($discountedLineTotal),
            'discount_value_total' => $this->moneyAmount(max(0, $discountValue)),
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

    private function moneyAmount(mixed $value): float
    {
        return (float) round(max(0, (float) $value), 2);
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

        $termBucket = ! empty(trim((string) $invoice->ti_number)) ? 'billing' : 'proforma';
        $terms = array_values(array_filter($request->input('terms', []), fn ($t) => trim($t) !== ''));
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

        $this->persistInvoicePdfVersion($invoice, ! empty(trim((string) $invoice->ti_number)));

        return response()->json(['ok' => true, 'count' => count($terms)]);
    }

    protected function generateInvoiceNumber(): string
    {
        $accountid = $this->resolveAccountId();

        // Use dedicated proforma configuration for PI generation.
        $serialConfig = SerialConfiguration::where('accountid', $accountid)
            ->where('document_type', 'proforma_invoice')
            ->first();

        if ($serialConfig) {
            $candidate = $serialConfig->generateNextSerialNumber();

            return $this->ensureUniqueDocumentNumber($candidate !== '' ? $candidate : 'PI-0001', $accountid, 'pi_number');
        }

        // Fallback: simple auto-increment if no serial configuration exists.
        $count = Invoice::where('accountid', $accountid)->count();
        $candidate = 'PI-'.str_pad($count + 1, 4, '0', STR_PAD_LEFT);

        return $this->ensureUniqueDocumentNumber($candidate, $accountid, 'pi_number');
    }

    protected function generateTaxInvoiceNumber(): string
    {
        $accountid = $this->resolveAccountId();

        // Check for SerialConfiguration
        $serialConfig = SerialConfiguration::where('accountid', $accountid)
            ->where('document_type', 'tax_invoice')
            ->first();

        if ($serialConfig) {
            $candidate = $serialConfig->generateNextSerialNumber();

            return $this->ensureUniqueDocumentNumber($candidate !== '' ? $candidate : 'INV-0001', $accountid, 'ti_number');
        }

        // Fallback
        $count = Invoice::where('accountid', $accountid)->whereNotNull('ti_number')->where('ti_number', '!=', '')->count();
        $candidate = 'INV-'.str_pad($count + 1, 4, '0', STR_PAD_LEFT);

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
                ->when($numberColumn, fn ($query) => $query->where($numberColumn, $number), function ($query) use ($number) {
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

        if (! preg_match_all('/\d+/', $candidate, $matches, PREG_OFFSET_CAPTURE) || empty($matches[0])) {
            return $candidate.'-'.($incrementBy + 1);
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
            $accountid = $this->resolveAccountId();
            $invoiceDateBounds = $this->resolveFinancialYearDateBounds($accountid);
            $validated = $request->validate([
                'invoiceid' => 'nullable|exists:invoices,invoiceid',
                'clientid' => 'required|exists:clients,clientid',
                'orderid' => 'nullable|exists:orders,orderid',
                'invoice_number' => 'nullable|string',
                'invoice_title' => 'required|string|max:255',
                'issue_date' => 'required|date|after_or_equal:'.$invoiceDateBounds['min_date'].'|before_or_equal:'.($invoiceDateBounds['issue_max_date'] ?? $invoiceDateBounds['max_date']),
                'due_date' => 'required|date|after_or_equal:issue_date|before_or_equal:'.($invoiceDateBounds['due_max_date'] ?? $invoiceDateBounds['max_date']),
                'notes' => 'nullable|string',
                'terms' => 'nullable|string',
                'status' => 'nullable|in:active,cancelled',
                'items_data' => 'required|json',
                'accountid' => 'nullable|string|max:10',
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw ValidationException::withMessages([
                'general' => 'Invalid form data. Please check all fields and try again.',
            ]);
        }

        $itemsData = json_decode($request->items_data, true);
        if (! is_array($itemsData) || empty($itemsData)) {
            throw ValidationException::withMessages([
                'items_data' => 'Add at least one invoice item before submitting.',
            ]);
        }

        // Set default status if not provided
        $validated['status'] = $validated['status'] ?? 'active';
        if (! empty($validated['invoice_number'])) {
            $this->assertDocumentNumberAvailable($validated['invoice_number'], null, 'pi_number');
        }

        $invoiceSource = ! empty($validated['orderid']) ? 'orders' : 'without_orders';

        if ($invoiceSource === 'orders') {
            if (empty($validated['orderid'])) {
                throw ValidationException::withMessages([
                    'orderid' => 'Select an order before creating an invoice from orders.',
                ]);
            }

            $order = Order::with(['invoices'])
                ->where('orderid', $validated['orderid'])
                ->where('clientid', $validated['clientid'])
                ->first();

            if (! $order) {
                throw ValidationException::withMessages([
                    'orderid' => 'The selected order does not belong to this client.',
                ]);
            }

            if ($order->invoices->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'orderid' => 'The selected order already has an invoice.',
                ]);
            }
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

        // Check if we're updating an existing invoice (draft or active)
        $existingDraft = null;
        if (! empty($validated['invoiceid'])) {
            $existingDraft = Invoice::find($validated['invoiceid']);
            if ($existingDraft) {
                // Use existing PI number
                $validated['pi_number'] = $existingDraft->pi_number;
                if (empty($existingDraft->fy_id) && ! empty($validated['fy_id'])) {
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

        $itemsData = json_decode($request->items_data, true);
        if (! is_array($itemsData) || empty($itemsData)) {
            throw ValidationException::withMessages([
                'items_data' => 'Add at least one invoice item before submitting.',
            ]);
        }

        $subtotal = 0;
        $taxTotal = 0;
        $discountTotal = 0;
        $preparedItems = [];

        $accountid = $this->resolveAccountId();
        $account = Account::find($accountid);
        $accountHasUsers = (bool) ($account?->have_users ?? false);

        foreach ($itemsData as $index => $itemData) {
            $itemId = $itemData['itemid'] ?? null;
            $service = $itemId
                ? Service::where('accountid', $accountid)->find($itemId)
                : null;
            $quantity = $this->wholeQuantity($itemData['quantity'] ?? 1);
            $unitPrice = $this->moneyAmount($itemData['unit_price'] ?? 0);
            $taxRate = (float) ($itemData['tax_rate'] ?? 0);
            $amounts = $this->calculateInvoiceItemAmounts($itemData, $taxRate);
            $isUserWiseItem = $accountHasUsers && $this->isUserWiseEnabled($service?->user_wise);
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
            $discountTotal += (float) ($amounts['discount_value_total'] ?? 0);
            $taxTotal += $amounts['tax_amount'];

            $preparedItems[] = [
                'invoice_itemid' => $itemData['invoice_itemid'] ?? null,
                'invoiceid' => null,
                'orderid' => $itemData['orderid'] ?? ($validated['orderid'] ?? null),
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
                'start_date' => ! empty($itemData['start_date']) ? $itemData['start_date'] : null,
                'end_date' => ! empty($itemData['end_date']) ? $itemData['end_date'] : null,
                'status' => 'active',
                'amount' => $lineTotal,
                'sequence' => $index + 1,
            ];
        }

        $discountTotal = $this->roundDiscountDown($discountTotal);
        $taxTotal = $this->roundTaxUp($taxTotal);
        $grandTotal = $subtotal - $discountTotal + $taxTotal;
        $invoice = null;

        try {
            DB::transaction(function () use ($validated, $preparedItems, &$invoice, $existingDraft, $grandTotal) {
                if ($existingDraft) {
                    // Update existing draft/invoice
                    $existingDraft->update($validated);
                    $invoice = $existingDraft;
                } else {
                    // Create new invoice
                    $invoice = Invoice::create($validated);
                }

                if ($existingDraft) {
                    $this->syncInvoiceItems($invoice, $preparedItems);
                } else {
                    foreach ($preparedItems as $itemData) {
                        $itemData['invoiceid'] = $invoice->invoiceid;
                        InvoiceItem::create($itemData);
                    }
                }

                $this->syncInvoiceLedgerEntry($invoice, $grandTotal);

                // Mark the linked order as completed when PI is created
                if (! empty($validated['orderid'])) {
                    Order::where('orderid', $validated['orderid'])
                        ->whereNotIn('status', ['cancelled'])
                        ->update(['status' => 'completed']);
                }

            });
        } catch (\Exception $e) {
            \Log::error('Failed to create invoice: '.$e->getMessage(), [
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
        $referenceNumber = trim((string) ($invoice->ti_number ?: $invoice->pi_number ?: $invoice->invoiceid));

        if (empty($description)) {
            $description = $referenceNumber;
        }

        $ledgerPayload = [
            'accountid' => $invoice->accountid,
            'clientid' => $invoice->clientid,
            'date' => $invoice->issue_date,
            'amount' => $grandTotal,
            'mode' => 'invoice',
            'reference_number' => $referenceNumber,
            'description' => $description ?: null,
        ];
        if (Schema::hasColumn('ledger', 'status')) {
            $ledgerPayload['status'] = strtolower(trim((string) ($invoice->status ?? 'active')));
        }

        Ledger::query()->updateOrCreate(
            [
                'invoiceid_paymentid' => $invoice->invoiceid,
                'type' => 'dr',
            ],
            $ledgerPayload
        );
    }

    private function renewalSourceItemsSessionKey(string $invoiceid): string
    {
        return 'invoice_renewal_source_items.'.$invoiceid;
    }

    private function extractRenewedItemIdsFromItemsData(?array $itemsData): array
    {
        if (! is_array($itemsData)) {
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

        $renewedItemIds = is_array($explicitRenewedItemIds) && ! empty($explicitRenewedItemIds)
            ? $explicitRenewedItemIds
            : session()->get($this->renewalSourceItemsSessionKey($invoiceid), []);
        if (! is_array($renewedItemIds) || empty($renewedItemIds)) {
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
        $accountid = $this->resolveAccountId();
        $invoiceDateBounds = $this->resolveFinancialYearDateBounds($accountid);
        $validated = $request->validate([
            'invoiceid' => 'nullable|exists:invoices,invoiceid',
            'clientid' => 'required|exists:clients,clientid',
            'orderid' => 'nullable|exists:orders,orderid',
            'invoice_title' => 'sometimes|required|string|max:255',
            'issue_date' => 'nullable|date|after_or_equal:'.$invoiceDateBounds['min_date'].'|before_or_equal:'.($invoiceDateBounds['issue_max_date'] ?? $invoiceDateBounds['max_date']),
            'due_date' => 'nullable|date|after_or_equal:issue_date|before_or_equal:'.($invoiceDateBounds['due_max_date'] ?? $invoiceDateBounds['max_date']),
            'notes' => 'nullable|string',
            'status' => 'nullable|in:draft,active,cancelled',
            'items_data' => 'nullable|json',
        ]);

        $user = Auth::user();
        $legacyAccountId = $user?->id ? (string) $user->id : null;
        $accountCandidates = array_values(array_filter(array_unique([$accountid, $legacyAccountId])));
        $client = Client::findOrFail($validated['clientid']);

        // Check if draft already exists for this client
        $orderId = $validated['orderid'] ?? null;
        $draft = null;
        $isExplicitInvoiceEdit = ! empty($validated['invoiceid']);
        if (! empty($validated['invoiceid'])) {
            // Explicit edit flow: always target this exact invoice id.
            // Do not restrict by status here; older records may have different status values.
            $draft = Invoice::where('invoiceid', $validated['invoiceid'])
                ->first();
        }

        if (! $draft && ! $isExplicitInvoiceEdit) {
            $draft = Invoice::where('clientid', $validated['clientid'])
                ->whereIn('accountid', $accountCandidates)
                ->where('status', 'draft')
                ->where('created_by', $user?->userid ?? $user?->id)
                ->when(! empty($orderId), fn ($q) => $q->whereHas('invoiceItems', fn ($itemQ) => $itemQ->where('orderid', $orderId)))
                ->when(empty($orderId), fn ($q) => $q->whereDoesntHave('invoiceItems', fn ($itemQ) => $itemQ->whereNotNull('orderid')))
                ->where('updated_at', '>', now()->subHours(24))
                ->first();
        }

        if (! $draft && $isExplicitInvoiceEdit) {
            return response()->json([
                'ok' => false,
                'message' => 'Invoice not found for editing.',
            ], 404);
        }

        $wasCreated = false;
        $shouldKeepActiveStatus = $draft && strtolower(trim((string) ($draft->status ?? ''))) === 'active';
        if ($draft) {
            // Update existing draft
            $draft->update([
                'invoice_title' => $validated['invoice_title'] ?? $draft->invoice_title,
                'issue_date' => $validated['issue_date'] ?? $draft->issue_date,
                'due_date' => $validated['due_date'] ?? $draft->due_date,
                'notes' => $validated['notes'] ?? $draft->notes,
                'status' => $shouldKeepActiveStatus ? 'active' : 'draft',
                'fy_id' => $draft->fy_id ?: $this->resolveDefaultFyId($accountid),
            ]);
        } else {
            // Create new draft
            $wasCreated = true;
            $draft = Invoice::create([
                'accountid' => $accountid,
                'fy_id' => $this->resolveDefaultFyId($accountid),
                'clientid' => $validated['clientid'],
                'pi_number' => $this->generateInvoiceNumber(),
                'ti_number' => null,
                'invoice_title' => $validated['invoice_title'] ?? '',
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
            if (! is_array($itemsData)) {
                $itemsData = [];
            }

            $draftItems = [];
            foreach ($itemsData as $index => $itemData) {
                if (! is_array($itemData)) {
                    continue;
                }

                $itemName = trim((string) ($itemData['item_name'] ?? ''));
                if ($itemName === '') {
                    continue;
                }

                $taxRate = (float) ($itemData['tax_rate'] ?? 0);
                $amounts = $this->calculateInvoiceItemAmounts($itemData, $taxRate);
                $calculatedSubtotal += (float) $amounts['line_total'];
                $calculatedDiscountTotal += (float) ($amounts['discount_value_total'] ?? 0);
                $calculatedTaxTotal += (float) $amounts['tax_amount'];

                $draftItems[] = [
                    'invoice_itemid' => $itemData['invoice_itemid'] ?? null,
                    'orderid' => $itemData['orderid'] ?? $orderId,
                    'itemid' => $itemData['itemid'] ?? null,
                    'item_name' => $itemName,
                    'item_description' => $itemData['item_description'] ?? null,
                    'quantity' => max(0, (int) round((float) ($itemData['quantity'] ?? 0), 0)),
                    'unit_price' => $this->moneyAmount($itemData['unit_price'] ?? 0),
                    'tax_rate' => $taxRate,
                    'discount_percent' => $this->wholePercent($itemData['discount_percent'] ?? 0),
                    'discount_amount' => max(0, (float) $amounts['discount_amount']),
                    'duration' => $itemData['duration'] ?? null,
                    'frequency' => $itemData['frequency'] ?? null,
                    'no_of_users' => ! empty($itemData['no_of_users']) ? max(1, (int) $itemData['no_of_users']) : null,
                    'start_date' => ! empty($itemData['start_date']) ? $itemData['start_date'] : null,
                    'end_date' => ! empty($itemData['end_date']) ? $itemData['end_date'] : null,
                    'status' => 'active',
                    'amount' => $this->moneyAmount($amounts['line_total'] ?? 0),
                    'sequence' => $index + 1,
                ];
            }

            $this->syncInvoiceItems($draft, $draftItems);
        }

        if ($rawItemsData !== null) {
            $calculatedDiscountTotal = $this->roundDiscountDown($calculatedDiscountTotal);
            $calculatedTaxTotal = $this->roundTaxUp($calculatedTaxTotal);
            $calculatedGrandTotal = max(0, $calculatedSubtotal - $calculatedDiscountTotal + $calculatedTaxTotal);
            $amountPaid = (float) ($draft->amount_paid ?? 0);
            $calculatedBalanceDue = max(0, $calculatedGrandTotal - $amountPaid);

            $draft->update(['status' => $shouldKeepActiveStatus ? 'active' : 'draft']);
            $this->syncInvoiceLedgerEntry($draft->fresh(), $calculatedGrandTotal);
        }

        return response()->json([
            'ok' => true,
            'was_created' => $wasCreated,
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

        $draft = null;
        if (! empty($draftId)) {
            $draft = Invoice::where('invoiceid', $draftId)
                ->where('accountid', $accountid)
                ->with(['invoiceItems.item'])
                ->first();
        }

        if (! $draft && empty($draftId)) {
            $draft = Invoice::where('clientid', $clientid)
                ->where('accountid', $accountid)
                ->where('status', 'draft')
                ->where('created_by', $user?->userid ?? $user?->id)
                ->when(! empty($orderId), fn ($q) => $q->whereHas('invoiceItems', fn ($itemQ) => $itemQ->where('orderid', $orderId)))
                ->when(empty($orderId), fn ($q) => $q->whereDoesntHave('invoiceItems', fn ($itemQ) => $itemQ->whereNotNull('orderid')))
                ->where('updated_at', '>', now()->subHours(24))
                ->with(['invoiceItems.item'])
                ->first();
        }

        if (! $draft) {
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
                'orderid' => $draft->invoiceItems->pluck('orderid')->filter()->first(),
                'po_number' => $draft->client?->latestPurchaseOrder()?->document_number,
                'po_date' => $draft->client?->latestPurchaseOrder()?->document_date?->format('Y-m-d'),
                'issue_date' => $draft->issue_date?->format('Y-m-d'),
                'due_date' => $draft->due_date?->format('Y-m-d'),
                'notes' => $draft->notes,
                'terms' => $draft->terms ?? [],
                'terms_by_type' => $this->normalizeInvoiceTermsByType($draft->terms),
                'status' => $draft->status,
                'items' => $draft->items->map(fn ($i) => [
                    'invoice_itemid' => $i->invoice_itemid,
                    'orderid' => $i->orderid,
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
                    'sequence' => $i->sequence,
                    'line_total' => $i->line_total,
                    'requires_user_fields' => $this->isUserWiseEnabled(optional($i->item)->user_wise),
                ]),
            ],
        ]);
    }

    public function invoicesEdit(string $invoice)
    {
        $invoice = $this->resolveInvoiceDocument($invoice);
        $invoiceFor = 'without_orders';
        $step = 2;
        $query = [
            'step' => $step,
            'c' => request('c', $invoice->clientid),
            'd' => $invoice->invoiceid,
        ];

        $primaryOrderId = $this->resolvePrimaryOrderIdFromItems($invoice);
        if (! empty($primaryOrderId)) {
            $query['o'] = $primaryOrderId;
        }

        if (! empty($invoice->ti_number)) {
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
        $account = Account::find($accountid);
        $accountHasUsers = (bool) ($account?->have_users ?? false);
        $service = Service::where('accountid', $accountid)->find($invoiceItem->itemid);
        $isUserWiseItem = $accountHasUsers && $this->isUserWiseEnabled($service?->user_wise);

        $invoiceItem->update([
            'item_name' => $itemData['item_name'],
            'item_description' => $itemData['item_description'] ?? null,
            'quantity' => $this->wholeQuantity($itemData['quantity']),
            'unit_price' => $this->moneyAmount($itemData['unit_price']),
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
            $a = $this->calculateInvoiceItemAmounts([
                'line_total' => $it->amount,
                'discount_percent' => $it->discount_percent,
                'unit_price' => $it->unit_price,
                'quantity' => $it->quantity,
                'no_of_users' => $it->no_of_users,
                'frequency' => $it->frequency,
                'duration' => $it->duration,
            ], (float) $it->tax_rate);
            $subtotal += $a['line_total'];
            $discountTotal += (float) ($a['discount_value_total'] ?? 0);
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
        $accountid = $this->resolveAccountId();
        $invoiceDateBounds = $this->resolveFinancialYearDateBounds($accountid);
        $invoiceNumberColumn = 'pi_number';
        $itemModel = InvoiceItem::class;

        $validated = $request->validate([
            'clientid' => 'required|exists:clients,clientid',
            'invoice_number' => 'required|string|unique:invoices,'.$invoiceNumberColumn.','.$invoice->invoiceid.',invoiceid,accountid,'.$accountid,
            'invoice_title' => 'nullable|string|max:255',
            'issue_date' => 'required|date|after_or_equal:'.$invoiceDateBounds['min_date'].'|before_or_equal:'.($invoiceDateBounds['issue_max_date'] ?? $invoiceDateBounds['max_date']),
            'due_date' => 'required|date|after_or_equal:issue_date|before_or_equal:'.($invoiceDateBounds['due_max_date'] ?? $invoiceDateBounds['max_date']),
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
            $discountTotal += (float) ($amounts['discount_value_total'] ?? 0);
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

        $fallbackOrderIdFromExistingItems = $this->resolvePrimaryOrderIdFromItems($invoice);
        $invoice->invoiceItems()->delete();

        $accountid = $this->resolveAccountId();
        $account = Account::find($accountid);
        $accountHasUsers = (bool) ($account?->have_users ?? false);

        foreach ($itemsData as $index => $itemData) {
            $service = Service::where('accountid', $accountid)->find($itemData['itemid'] ?? null);
            $taxRate = (float) ($itemData['tax_rate'] ?? 0);
            $amounts = $this->calculateInvoiceItemAmounts($itemData, $taxRate);
            $isUserWiseItem = $accountHasUsers && $this->isUserWiseEnabled($service?->user_wise);
            $payload = [
                'orderid' => $itemData['orderid'] ?? $fallbackOrderIdFromExistingItems,
                'itemid' => $itemData['itemid'] ?: null,
                'item_name' => $itemData['item_name'] ?? ($service?->name ?? 'Custom Item'),
                'item_description' => $itemData['item_description'] ?? null,
                'quantity' => $this->wholeQuantity($itemData['quantity'] ?? 1),
                'unit_price' => $this->moneyAmount($itemData['unit_price'] ?? 0),
                'tax_rate' => $taxRate,
                'discount_percent' => $amounts['discount_percent'],
                'discount_amount' => $amounts['discount_amount'],
                'duration' => $itemData['duration'] ?? null,
                'frequency' => $itemData['frequency'] ?? null,
                'no_of_users' => $isUserWiseItem ? max(1, (int) ($itemData['no_of_users'] ?? 1)) : null,
                'start_date' => ! empty($itemData['start_date']) ? $itemData['start_date'] : null,
                'end_date' => ! empty($itemData['end_date']) ? $itemData['end_date'] : null,
                'status' => 'active',
                'amount' => $amounts['line_total'],
                'sequence' => $index + 1,
                'invoiceid' => $invoice->invoiceid,
            ];

            $itemModel::create($payload);
        }

        $discountTotal = $this->roundDiscountDown($discountTotal);
        $taxTotal = $this->roundTaxUp($taxTotal);
        $grandTotal = max(0, $subtotal - $discountTotal + $taxTotal);
        $this->syncInvoiceLedgerEntry($invoice->fresh(), $grandTotal);

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
        $selectedClientId = trim((string) request('c', ''));

        if (strtolower((string) $invoice->status) === 'draft') {
            DB::transaction(function () use ($invoice) {
                // Delete related ledger entries
                Ledger::query()
                    ->where('invoiceid_paymentid', $invoice->invoiceid)
                    ->delete();

                // Delete related invoice items
                foreach ($invoice->items as $item) {
                    $item->delete();
                }

                // Delete the invoice itself
                $invoice->delete();
            });

            return redirect()
                ->route('invoices.index', $selectedClientId ? ['c' => $selectedClientId] : [])
                ->with('success', 'Draft invoice deleted successfully.');
        }

        DB::transaction(function () use ($invoice) {
            $invoice->update(['status' => 'cancelled']);

            if (Schema::hasColumn('ledger', 'status')) {
                Ledger::query()
                    ->where('invoiceid_paymentid', $invoice->invoiceid)
                    ->where('type', 'dr')
                    ->update(['status' => 'cancelled']);
            }

            $paymentIds = $this->paymentIdsForInvoice($invoice);

            if (Schema::hasColumn('ledger', 'status')) {
                Ledger::query()
                    ->whereIn('invoiceid_paymentid', $paymentIds)
                    ->where('type', 'cr')
                    ->update(['status' => 'cancelled']);
            }
        });

        return redirect()
            ->route('invoices.index', $selectedClientId ? ['c' => $selectedClientId] : [])
            ->with('success', 'Invoice cancelled successfully.');
    }

    public function invoicesRestore(string $invoice)
    {
        $invoice = $this->resolveInvoiceDocument($invoice);
        $selectedClientId = trim((string) request('c', ''));
        DB::transaction(function () use ($invoice) {
            $invoice->update(['status' => 'active']);

            if (Schema::hasColumn('ledger', 'status')) {
                Ledger::query()
                    ->where('invoiceid_paymentid', $invoice->invoiceid)
                    ->where('type', 'dr')
                    ->update(['status' => 'active']);
            }

            $paymentIds = $this->paymentIdsForInvoice($invoice);

            if (Schema::hasColumn('ledger', 'status')) {
                Ledger::query()
                    ->whereIn('invoiceid_paymentid', $paymentIds)
                    ->where('type', 'cr')
                    ->update(['status' => 'active']);
            }
        });

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

        $requestedClient = trim((string) $request->input('c', ''));

        $params = array_filter([
            'c' => $requestedClient,
            'tab' => request('tab', 'expired'),
        ]);

        return redirect()
            ->route('invoices.expiry-list', $params)
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

        $requestedClient = trim((string) $request->input('c', ''));

        $params = array_filter([
            'c' => $requestedClient,
            'tab' => request('tab', 'suspended'),
        ]);

        return redirect()
            ->route('invoices.expiry-list', $params)
            ->with('success', 'Item unsuspended successfully.');
    }

    public function suspendExpiryOrder(Request $request, string $order)
    {
        $orderModel = Order::query()
            ->where('accountid', $this->resolveAccountId())
            ->findOrFail($order);

        if (($orderModel->status ?? '') === 'cancelled') {
            return $this->redirectExpiryListWithFilters($request, 'expired')
                ->with('error', 'Cancelled order cannot be suspended.');
        }

        $orderModel->update(['status' => 'suspended']);

        $this->invalidateOrderCache();

        return $this->redirectExpiryListWithFilters($request, 'expired')
            ->with('success', 'Order suspended successfully.');
    }

    public function unsuspendExpiryOrder(Request $request, string $order)
    {
        $orderModel = Order::query()
            ->where('accountid', $this->resolveAccountId())
            ->findOrFail($order);

        if (($orderModel->status ?? '') !== 'suspended') {
            return $this->redirectExpiryListWithFilters($request, 'suspended')
                ->with('error', 'Only suspended orders can be unsuspended.');
        }

        $orderModel->update(['status' => 'active']);

        $this->invalidateOrderCache();

        return $this->redirectExpiryListWithFilters($request, 'suspended')
            ->with('success', 'Order unsuspended successfully.');
    }

    public function renewExpiryOrder(Request $request, string $order)
    {
        $orderModel = Order::query()
            ->where('accountid', $this->resolveAccountId())
            ->findOrFail($order);

        $validated = $request->validate([
            'end_date' => 'required|date',
        ]);

        $newEndDate = (string) $validated['end_date'];
        $startDate = $orderModel->start_date?->toDateString();
        if (! empty($startDate) && $newEndDate < $startDate) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'End date cannot be before start date.',
                ], 422);
            }

            return $this->redirectExpiryListWithFilters($request, request('tab', 'expired'))
                ->with('error', 'End date cannot be before start date.');
        }

        $orderModel->update([
            'end_date' => $newEndDate,
        ]);

        $this->invalidateOrderCache();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Order expiry date updated successfully.',
                'end_date' => $newEndDate,
            ]);
        }

        if ($request->input('return_to') === 'orders') {
            $clientId = (string) ($request->input('c') ?: $orderModel->clientid);
            $params = array_filter([
                'c' => $clientId,
            ], fn ($value) => (string) $value !== '');

            return redirect()
                ->route('orders.index', $params)
                ->with('success', 'Order renewed successfully.');
        }

        return $this->redirectExpiryListWithFilters($request, request('tab', 'expired'))
            ->with('success', 'Order renewed successfully.');
    }

    public function sendExpiryOrderReminder(Request $request, string $order, InvoiceReminderService $invoiceReminderService)
    {
        $orderModel = Order::query()
            ->where('accountid', $this->resolveAccountId())
            ->with(['client.billingDetail', 'item'])
            ->findOrFail($order);

        if (strtolower(trim((string) ($orderModel->status ?? ''))) === 'cancelled') {
            return $this->redirectExpiryListWithFilters($request, request('tab', 'expired'))
                ->with('error', 'Reminder cannot be sent for cancelled orders.');
        }

        $templateType = ($orderModel->end_date && $orderModel->end_date->startOfDay()->lte(now()->startOfDay()))
            ? 'expiry'
            : 'reminder';
        $result = $invoiceReminderService->sendManualForOrderByTemplateType($orderModel, $templateType);
        $sentCount = (int) ($result['sent'] ?? 0);

        if ($sentCount > 0) {
            return $this->redirectExpiryListWithFilters($request, request('tab', 'expired'))
                ->with('success', ucfirst($templateType).' reminder sent on '.$sentCount.' channel(s).');
        }

        $failureMessage = ((int) ($result['failed'] ?? 0)) > 0
            ? ucfirst($templateType).' reminder could not be delivered. Please verify templates and recipient details.'
            : 'No active '.$templateType.' template found for this account.';
        $firstReason = collect((array) ($result['reasons'] ?? []))->filter()->first();
        if (! empty($firstReason)) {
            $failureMessage .= ' Reason: '.$firstReason;
        }

        return $this->redirectExpiryListWithFilters($request, request('tab', 'expired'))
            ->with('error', $failureMessage);
    }

    private function redirectExpiryListWithFilters(Request $request, string $fallbackTab)
    {
        if ($request->input('redirect') === 'back') {
            return redirect()->back();
        }

        $requestedClient = trim((string) $request->input('c', ''));

        $params = array_filter([
            'c' => $requestedClient,
            'tab' => (string) ($request->input('tab') ?: $fallbackTab),
            'from' => trim((string) $request->input('from', '')),
            'to' => trim((string) $request->input('to', '')),
            'next_days' => trim((string) $request->input('next_days', '')),
        ], fn ($value) => (string) $value !== '');

        return redirect()->route('invoices.expiry-list', $params);
    }

    private function resolveActiveInvoiceForOrder(Order $order): ?Invoice
    {
        $fromDirectLink = $order->invoices
            ->where('status', '!=', 'cancelled')
            ->sortByDesc('created_at')
            ->first();

        if ($fromDirectLink) {
            return $fromDirectLink;
        }

        if (empty($order->itemid) || ! $order->end_date) {
            return null;
        }

        return Invoice::query()
            ->where('accountid', $order->accountid)
            ->where('clientid', $order->clientid)
            ->where('status', '!=', 'cancelled')
            ->whereHas('items', function ($q) use ($order) {
                $q->where('itemid', $order->itemid)
                    ->whereDate('end_date', $order->end_date->toDateString());
            })
            ->orderByDesc('created_at')
            ->first();
    }

    private function buildReminderInvoiceContextFromOrder(Order $order): Invoice
    {
        $invoice = new Invoice;
        $invoice->accountid = (string) $order->accountid;
        $invoice->clientid = (string) $order->clientid;
        $invoice->invoiceid = '';
        $invoice->invoice_number = '';
        $invoice->invoice_title = '';
        $invoice->pi_number = '';
        $invoice->ti_number = '';
        $invoice->due_date = $order->end_date;
        $invoice->grand_total = 0;
        $invoice->created_at = $order->updated_at ?? now();
        $invoice->setRelation('client', $order->client);

        $item = new InvoiceItem;
        $item->accountid = (string) $order->accountid;
        $item->clientid = (string) $order->clientid;
        $item->invoiceid = '';
        $item->itemid = (string) ($order->itemid ?? '');
        $item->orderid = (string) ($order->orderid ?? '');
        $item->item_name = (string) ($order->item_name ?? 'Item');
        $item->item_description = (string) ($order->item_description ?? '');
        $item->start_date = $order->start_date;
        $item->end_date = $order->end_date;
        $item->line_total = 0;

        // Reminder service reads invoiceItems; keep both relations for compatibility.
        $invoice->setRelation('invoiceItems', collect([$item]));
        $invoice->setRelation('items', collect([$item]));

        return $invoice;
    }

    protected function resolveInvoiceDocument(string $invoiceid): Invoice
    {
        return Invoice::query()
            ->where('accountid', $this->resolveAccountId())
            ->findOrFail($invoiceid);
    }

    protected function assertDocumentNumberAvailable(string $invoiceNumber, ?string $ignoreInvoiceId = null, ?string $numberColumn = null): void
    {
        $numberExists = Invoice::query()
            ->where('accountid', $this->resolveAccountId())
            ->when($numberColumn, fn ($query) => $query->where($numberColumn, $invoiceNumber), function ($query) use ($invoiceNumber) {
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

        $editUrl = route('invoices.create', array_filter([
            'step' => 2,
            'd' => $invoice->invoiceid,
            'c' => $clientContext,
            'tax_invoice' => 1,
        ]));

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Tax Invoice created successfully.',
                'ti_number' => $tiNumber,
                'redirect_url' => $editUrl,
            ]);
        }

        return redirect($editUrl)->with('success', 'Tax Invoice ready: '.$tiNumber);
    }

    public function sendReminder(Request $request, string $invoice, InvoiceReminderService $invoiceReminderService)
    {
        $invoice = $this->resolveInvoiceDocument($invoice);
        $result = $invoiceReminderService->sendManualReminder($invoice);

        $sentCount = (int) ($result['sent'] ?? 0);
        if ($sentCount > 0) {
            $message = 'Reminder sent successfully on '.$sentCount.' channel(s).';
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => true, 'message' => $message, 'meta' => $result]);
            }

            return back()->with('success', $message);
        }

        $failureMessage = ((int) ($result['failed'] ?? 0)) > 0
            ? 'Reminder could not be delivered. Please verify reminder templates and recipient details.'
            : 'No active reminder template found for this account.';
        $firstReason = collect((array) ($result['reasons'] ?? []))->filter()->first();
        if (! empty($firstReason)) {
            $failureMessage .= ' Reason: '.$firstReason;
        }

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

        if (! $invoiceItem) {
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
            $message = 'Reminder sent for item "'.($invoiceItem->item_name ?? 'Item').'" on '.$sentCount.' channel(s).';
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => true, 'message' => $message, 'meta' => $result]);
            }

            return back()->with('success', $message);
        }

        $failureMessage = ((int) ($result['failed'] ?? 0)) > 0
            ? 'Item reminder could not be delivered. Please verify reminder templates and recipient details.'
            : 'No active reminder template found for this account.';
        $firstReason = collect((array) ($result['reasons'] ?? []))->filter()->first();
        if (! empty($firstReason)) {
            $failureMessage .= ' Reason: '.$firstReason;
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => false, 'message' => $failureMessage, 'meta' => $result], 422);
        }

        return back()->with('error', $failureMessage);
    }

    public function emailCompose(Request $request, string $invoice): View
    {
        $invoice = $this->resolveInvoiceDocument($invoice);
        $invoice->loadMissing(['client.billingDetail']);

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
        $account = Account::find($accountid);
        $accountBillingDetail = AccountBillingDetail::query()->where('accountid', $accountid)->first();
        $fromEmail = (string) ($accountBillingDetail?->billing_from_email ?? '');
        $toEmail = (string) (
            $invoice->client?->billingDetail?->billing_email
            ?? $invoice->client?->billing_email
            ?? $invoice->client?->primary_email
            ?? $invoice->client?->email
            ?? ''
        );

        $requestedType = strtolower(trim((string) request('attachment_type', '')));
        $defaultType = in_array($requestedType, ['pi', 'ti'], true)
            ? $requestedType
            : (! empty(trim((string) $invoice->ti_number)) ? 'ti' : $this->mapInvoiceTemplateType($invoice));
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

        $defaultBody = '';

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
            if (! in_array($channel, ['email', 'whatsapp', 'sms'], true) || ! in_array($type, ['pi', 'ti'], true)) {
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

            if (! in_array($channel, $availableChannelsByType[$type], true)) {
                $availableChannelsByType[$type][] = $channel;
            }

            if (! isset($firstTemplateByContext[$type][$channel])) {
                $firstTemplateByContext[$type][$channel] = end($templateCatalog[$type][$channel]);
            }
        }
        foreach (['pi', 'ti'] as $typeKey) {
            if (empty($availableChannelsByType[$typeKey])) {
                $availableChannelsByType[$typeKey] = ['email'];
            }
        }

        $fallbackTemplatesByType = [
            'pi' => [
                'subject' => 'Invoice '.((string) ($invoice->pi_number ?: $invoice->invoice_number)),
                'body' => $defaultBody,
                'raw_body' => '',
            ],
            'ti' => [
                'subject' => 'Invoice '.((string) ($invoice->ti_number ?: $invoice->invoice_number)),
                'body' => $defaultBody,
                'raw_body' => '',
            ],
        ];

        // Retrieve requested draft ID if any
        $requestedEmailId = trim((string) request('e', ''));

        $emailDraft = null;
        $whatsappDraft = null;
        $smsDraft = null;

        if ($requestedEmailId !== '') {
            $candidateDraft = CommunicationLog::query()
                ->where('logid', $requestedEmailId)
                ->where('invoiceid', $invoice->invoiceid)
                ->where('accountid', $currentAccountId)
                ->first();
            if ($candidateDraft && (string) $candidateDraft->attachment_type === $defaultType) {
                $candidateChannel = (string) $candidateDraft->channel;
                if ($candidateChannel === 'email') {
                    $emailDraft = $candidateDraft;
                } elseif ($candidateChannel === 'whatsapp') {
                    $whatsappDraft = $candidateDraft;
                } elseif ($candidateChannel === 'sms') {
                    $smsDraft = $candidateDraft;
                }
            }
        }

        // Query standard drafts for all three channels if not loaded yet
        if (! $emailDraft) {
            $emailDraft = CommunicationLog::query()
                ->where('invoiceid', $invoice->invoiceid)
                ->where('accountid', $currentAccountId)
                ->where('attachment_type', $defaultType)
                ->where('channel', 'email')
                ->first();
        }
        if (! $whatsappDraft) {
            $whatsappDraft = CommunicationLog::query()
                ->where('invoiceid', $invoice->invoiceid)
                ->where('accountid', $currentAccountId)
                ->where('attachment_type', $defaultType)
                ->where('channel', 'whatsapp')
                ->first();
        }
        if (! $smsDraft) {
            $smsDraft = CommunicationLog::query()
                ->where('invoiceid', $invoice->invoiceid)
                ->where('accountid', $currentAccountId)
                ->where('attachment_type', $defaultType)
                ->where('channel', 'sms')
                ->first();
        }

        // Email Prefill
        $emailTemplate = $firstTemplateByContext[$defaultType]['email'] ?? null;
        $emailTemplateSubject = trim((string) ($emailTemplate['subject'] ?? '')) ?: null;
        $emailTemplateBody = trim((string) ($emailTemplate['body'] ?? '')) ?: null;

        $emailSubject = $emailDraft?->subject;
        if ($emailSubject === null || trim((string) $emailSubject) === '') {
            $emailSubject = trim((string) ($invoice->invoice_title ?? '')) !== ''
                ? ($emailTemplateSubject ?? trim((string) $invoice->invoice_title))
                : ('Invoice '.($defaultSubjectNumber ?: $invoice->invoice_number));
        }

        $emailBody = $emailDraft?->body;
        if ($emailBody === null || trim((string) $emailBody) === '') {
            $emailBody = $emailTemplateBody ?? $defaultBody;
        }
        $emailBody = $this->sanitizeComposedMessageBody((string) $emailBody);

        $emailTo = (string) ($emailDraft?->to_email ?? $toEmail);
        $emailCc = (string) ($emailDraft?->cc_email ?? '');

        $emailCustomAttachmentUrls = collect(explode(',', (string) ($emailDraft?->custom_attachment_path ?? '')))
            ->map(fn ($path) => trim((string) $path))
            ->filter()
            ->map(fn ($path) => $this->buildPublicAttachmentUrl($path))
            ->values()
            ->all();

        $emailCustomAttachmentNames = collect(explode(',', (string) ($emailDraft?->custom_attachment_path ?? '')))
            ->map(fn ($path) => trim((string) $path))
            ->filter()
            ->map(function ($path) {
                $url = $this->buildPublicAttachmentUrl($path);

                return basename((string) parse_url($url, PHP_URL_PATH) ?: 'Attachment');
            })
            ->values()
            ->all();

        // WhatsApp Prefill
        $whatsappTemplate = $firstTemplateByContext[$defaultType]['whatsapp'] ?? null;
        $whatsappTemplateBody = trim((string) ($whatsappTemplate['body'] ?? '')) ?: null;

        $whatsappBody = $whatsappDraft?->body;
        if ($whatsappBody === null || trim((string) $whatsappBody) === '') {
            $whatsappBody = $whatsappTemplateBody ?? $defaultBody;
        }
        $whatsappBody = $this->sanitizeComposedMessageBody((string) $whatsappBody);

        $whatsappPhone = trim((string) (
            $whatsappDraft?->phone_number
            ?? $invoice->client?->billingDetail?->billing_phone
            ?? $invoice->client?->whatsapp_number
            ?? $invoice->client?->phone
            ?? ''
        ));

        // SMS Prefill
        $smsTemplate = $firstTemplateByContext[$defaultType]['sms'] ?? null;
        $smsTemplateBody = trim((string) ($smsTemplate['body'] ?? '')) ?: null;

        $smsBody = $smsDraft?->body;
        if ($smsBody === null || trim((string) $smsBody) === '') {
            $smsBody = $smsTemplateBody ?? $defaultBody;
        }
        $smsBody = $this->sanitizeComposedMessageBody((string) $smsBody);

        $smsPhone = trim((string) (
            $smsDraft?->phone_number
            ?? $invoice->client?->billingDetail?->billing_phone
            ?? $invoice->client?->whatsapp_number
            ?? $invoice->client?->phone
            ?? ''
        ));

        // Keep fallback support for older variables if needed by templates
        $prefillSubject = $emailSubject;
        $prefillBody = $emailBody;
        $prefillChannel = trim((string) request('channel', 'email'));
        if (! in_array($prefillChannel, ['email', 'whatsapp', 'sms'], true)) {
            $prefillChannel = 'email';
        }
        $prefillAttachmentType = $defaultType;
        $prefillAttachmentTypes = [$defaultType];
        $prefillPhone = $prefillChannel === 'whatsapp' ? $whatsappPhone : ($prefillChannel === 'sms' ? $smsPhone : '');

        return view('invoices.email-compose', [
            'title' => 'Compose Invoice Communications',
            'invoice' => $invoice,
            'fromEmail' => $fromEmail,
            'toEmail' => $toEmail,
            'ccEmail' => $emailCc,
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
            'composeEmail' => $emailDraft ?: $whatsappDraft ?: $smsDraft,
            'customAttachmentUrls' => $emailCustomAttachmentUrls,
            'customAttachmentNames' => $emailCustomAttachmentNames,

            // Channel-specific drafts & fields
            'emailDraft' => $emailDraft,
            'emailSubject' => $emailSubject,
            'emailBody' => $emailBody,
            'emailTo' => $emailTo,
            'emailCc' => $emailCc,
            'emailCustomAttachmentUrls' => $emailCustomAttachmentUrls,
            'emailCustomAttachmentNames' => $emailCustomAttachmentNames,

            'whatsappDraft' => $whatsappDraft,
            'whatsappPhone' => $whatsappPhone,
            'whatsappBody' => $whatsappBody,

            'smsDraft' => $smsDraft,
            'smsPhone' => $smsPhone,
            'smsBody' => $smsBody,
        ]);
    }

    public function emailComposeStore(Request $request, string $invoice)
    {
        $invoice = $this->resolveInvoiceDocument($invoice);
        $invoice->loadMissing(['client.billingDetail']);

        $accountid = $this->resolveAccountId();
        $accountBillingDetail = AccountBillingDetail::query()->where('accountid', $accountid)->first();
        $forcedFromEmail = (string) ($accountBillingDetail?->billing_from_email ?? '');
        $forcedFromName = trim((string) ($accountBillingDetail?->billing_from_name ?? ''));
        $forcedToEmail = (string) (
            $invoice->client?->billingDetail?->billing_email
            ?? $invoice->client?->billing_email
            ?? $invoice->client?->primary_email
            ?? $invoice->client?->email
            ?? ''
        );

        $validated = $request->validate([
            'logid' => 'nullable|exists:communication_logs,logid',
            'action' => 'nullable|in:save,send',
            'channel' => 'required|in:email,whatsapp,sms',
            'selected_templateid' => 'nullable|string|max:20',
            'phone' => 'nullable|string',
            'to_email' => [
                'nullable',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    $emails = preg_split('/[\s,;]+/', (string) $value);
                    foreach ($emails as $email) {
                        $email = trim($email);
                        if (! empty($email) && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $fail("The {$attribute} must contain only valid email addresses.");

                            return;
                        }
                    }
                },
            ],
            'cc_email' => [
                'nullable',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    $emails = preg_split('/[\s,;]+/', (string) $value);
                    foreach ($emails as $email) {
                        $email = trim($email);
                        if (! empty($email) && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $fail("The {$attribute} must contain only valid email addresses.");

                            return;
                        }
                    }
                },
            ],
            'subject' => 'nullable|string|max:255',
            'body' => 'nullable|string',
            'attachment_type' => 'required|in:pi,ti',
            'custom_attachments' => 'nullable|array',
            'custom_attachments.*' => 'file|max:10240',
            'existing_custom_attachment_paths' => 'nullable|string|max:10000',
        ]);

        $selectedType = (string) ($validated['attachment_type'] ?? 'pi');
        $selectedTypes = [$selectedType];
        $channel = $validated['channel'] ?? 'email';

        $toEmailValue = trim((string) ($validated['to_email'] ?? ''));
        if ($toEmailValue === '') {
            $toEmailValue = $forcedToEmail;
        }

        $ccEmailValue = trim((string) ($validated['cc_email'] ?? ''));

        if ($channel === 'email') {
            if ($forcedFromEmail === '') {
                $msg = 'Set Billing From Email in Account Billing Details first.';
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $msg], 422);
                }

                return back()->withErrors(['from_email' => $msg])->withInput();
            }
            if ($toEmailValue === '') {
                $msg = 'Set Client Billing Email first.';
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $msg], 422);
                }

                return back()->withErrors(['to_email' => $msg])->withInput();
            }
        }
        $user = Auth::user();
        $currentAccountId = $invoice->accountid ?? $this->resolveAccountId();
        $requestedDraftId = trim((string) ($validated['logid'] ?? ''));
        $seedDraft = null;
        if ($requestedDraftId !== '') {
            $seedDraft = CommunicationLog::query()
                ->where('logid', $requestedDraftId)
                ->where('invoiceid', $invoice->invoiceid)
                ->where('accountid', $currentAccountId)
                ->first();
        }

        // Keep a dedicated draft for each invoice + document type + channel.
        $emailDraft = CommunicationLog::query()
            ->where('invoiceid', $invoice->invoiceid)
            ->where('accountid', $currentAccountId)
            ->where('attachment_type', $selectedType)
            ->where('channel', $channel)
            ->first();

        if (! $emailDraft) {
            $emailDraft = CommunicationLog::create([
                'accountid' => $currentAccountId,
                'invoiceid' => $invoice->invoiceid,
                'clientid' => $invoice->clientid,
                'from_email' => $forcedFromEmail,
                'to_email' => $toEmailValue,
                'cc_email' => $ccEmailValue,
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
        $existingPathsSource = $request->exists('existing_custom_attachment_paths')
            ? (string) $request->input('existing_custom_attachment_paths')
            : ($emailDraft->custom_attachment_path ?? '');
        $existingCustomAttachmentPaths = collect(explode(',', $existingPathsSource))
            ->map(fn ($path) => trim((string) $path))
            ->filter()
            ->values()
            ->all();
        $uploadedCustomAttachmentPaths = [];
        $attachmentPaths = [];

        $action = $validated['action'] ?? 'save';
        $isSendAction = $action === 'send';

        $piPdfUrl = null;
        $tiPdfUrl = null;

        if (in_array('pi', $selectedTypes, true)) {
            $piPdfUrl = $this->resolveCampioInvoicePdfUrl($invoice, false, $isSendAction);
            $attachmentPaths[] = $piPdfUrl;
        }
        if (in_array('ti', $selectedTypes, true)) {
            if (empty(trim((string) $invoice->ti_number))) {
                $msg = 'Tax Invoice is not available yet.';
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $msg], 422);
                }

                return back()->withErrors(['attachment_type' => $msg])->withInput();
            }
            $tiPdfUrl = $this->resolveCampioInvoicePdfUrl($invoice, true, $isSendAction);
            $attachmentPaths[] = $tiPdfUrl;
        }
        foreach ((array) $request->file('custom_attachments', []) as $file) {
            if ($file && $file->isValid()) {
                $storedPath = $file->store('invoice-email-attachments', 'public');
                $uploadedCustomAttachmentPaths[] = $this->buildPublicAttachmentUrl($storedPath);
            }
        }

        $finalCustomAttachmentPaths = array_values(array_unique(array_filter(array_merge(
            $existingCustomAttachmentPaths,
            $uploadedCustomAttachmentPaths
        ))));
        $finalCustomAttachmentPath = ! empty($finalCustomAttachmentPaths) ? implode(',', $finalCustomAttachmentPaths) : null;
        $storeAttachmentPath = (! empty($attachmentPaths)) ? implode(',', $attachmentPaths) : null;
        $storeCustomAttachmentPath = $finalCustomAttachmentPath;
        $sentAt = now();
        $documentLabel = $selectedType === 'ti' ? 'Tax Invoice (TI)' : 'Proforma Invoice (PI)';
        $successTitle = $selectedType === 'ti'
            ? 'Tax Invoice sent successfully.'
            : 'Proforma Invoice sent successfully.';

        // For non-email channels, resolve template tags.
        $finalBody = $this->sanitizeComposedMessageBody((string) ($validated['body'] ?? ''));
        if ($channel !== 'email' && $isSendAction) {
            $accountName = (string) (optional(Account::find($currentAccountId))->name ?? '');
            $finalBody = $this->renderMessageTemplate((string) $finalBody, $invoice, $accountName);
            $finalBody = $this->sanitizeComposedMessageBody($finalBody);
        }

        $updatePayload = [
            'to_email' => $toEmailValue,
            'cc_email' => $ccEmailValue,
            'subject' => ($channel === 'email') ? ($validated['subject'] ?? null) : null,
            'body' => $finalBody,
            'attachment_type' => implode(',', $selectedTypes),
            'attachment_path' => $storeAttachmentPath,
            'custom_attachment_path' => $storeCustomAttachmentPath,
            'phone_number' => $phone,
            'channel' => $channel,
        ];

        if (! $isSendAction) {
            $emailDraft->update($updatePayload + ['status' => 'draft']);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Message draft saved successfully.',
                    'logid' => $emailDraft->logid,
                    'customAttachmentUrls' => collect(explode(',', (string) ($emailDraft->custom_attachment_path ?? '')))
                        ->map(fn ($path) => trim((string) $path))
                        ->filter()
                        ->map(fn ($path) => $this->buildPublicAttachmentUrl($path))
                        ->values()
                        ->all(),
                ]);
            }

            return redirect()
                ->route('invoices.email-compose', [
                    'invoice' => $invoice->invoiceid,
                    'e' => $emailDraft->logid,
                    'channel' => $channel,
                    'attachment_type' => $selectedType,
                ])
                ->with('success', 'Message draft saved successfully.')
                ->with('preserve_channel', $channel);
        }

        if ($channel === 'whatsapp' || $channel === 'sms') {
            if ($phone === '') {
                $msg = 'Client phone/whatsapp number is required for this channel.';
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $msg], 422);
                }

                return back()->withErrors(['phone' => $msg])->withInput();
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

            if ($selectedTemplateId !== '' && ! $channelTemplateConfig) {
                $msg = 'Selected template is invalid for this channel/type.';
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $msg], 422);
                }

                return back()->withErrors([
                    'selected_templateid' => $msg,
                ])->withInput();
            }

            $canUseWhatsappDocumentHeader = true;
            if ($channel === 'whatsapp' && $channelTemplateConfig) {
                $headerTypeMap = $this->fetchCampioTemplateHeaderTypes($currentAccountId, 'whatsapp');
                $resolvedHeaderType = strtolower(trim((string) (
                    $headerTypeMap[trim((string) ($channelTemplateConfig->template_id ?? ''))]
                    ?? $headerTypeMap[trim((string) ($channelTemplateConfig->meta_template_id ?? ''))]
                    ?? ($channelTemplateConfig->header_type ?? '')
                )));
                $canUseWhatsappDocumentHeader = in_array($resolvedHeaderType, ['document', 'image', 'video', 'media']);
            }
            $templateBodySource = (string) ($channelTemplateConfig?->body ?? ($validated['body'] ?? ''));
            $templateWantsDocLinks = preg_match('/\{\{\s*(pi_link|ti_link)\s*\}\}/i', $templateBodySource) === 1;
            $allowDocumentPayloadForChannel = ($channel === 'whatsapp' && $canUseWhatsappDocumentHeader) || $templateWantsDocLinks;
            $documentLinks = [];
            if ($allowDocumentPayloadForChannel) {
                if (in_array('pi', $selectedTypes, true) && $piPdfUrl) {
                    $piNumber = trim((string) ($invoice->pi_number ?: $invoice->invoice_number));
                    $documentLinks[] = [
                        'label' => 'Proforma Invoice (PDF)',
                        'url' => $piPdfUrl,
                        'name' => 'Proforma Invoice - '.($piNumber !== '' ? $piNumber : $invoice->invoiceid).'.pdf',
                    ];
                }
                if (in_array('ti', $selectedTypes, true) && $tiPdfUrl) {
                    $tiNumber = trim((string) ($invoice->ti_number ?: $invoice->invoice_number));
                    $documentLinks[] = [
                        'label' => 'Tax Invoice (PDF)',
                        'url' => $tiPdfUrl,
                        'name' => 'Tax Invoice - '.($tiNumber !== '' ? $tiNumber : $invoice->invoiceid).'.pdf',
                    ];
                }
                foreach ($finalCustomAttachmentPaths as $customPath) {
                    $documentLinks[] = [
                        'label' => 'Attachment',
                        'url' => $customPath,
                    ];
                }
            }

            // Clean HTML for messaging while preserving readable line breaks.
            $plainBodyHtml = str_replace(['<br>', '<br/>', '<br />'], "\n", $finalBody);
            $plainBodyHtml = preg_replace('/<\/(p|div|li|h[1-6])>/i', "\n", (string) $plainBodyHtml) ?? (string) $plainBodyHtml;
            $plainBodyHtml = preg_replace('/<(ul|ol)[^>]*>/i', "\n", (string) $plainBodyHtml) ?? (string) $plainBodyHtml;
            $plainBody = trim(strip_tags((string) $plainBodyHtml));
            $plainBody = $this->sanitizeForCampioText($plainBody);
            $plainBody = str_replace("\n", "\r\n", $plainBody);

            $phoneNumbers = collect(preg_split('/[\s,;]+/', $phone))
                ->map(fn ($p) => trim((string) $p))
                ->filter()
                ->values()
                ->unique();

            // Build payload
            $payload = [
                'account_id' => $currentAccountId,
                'campaign_name' => '',
                'schedule_at' => now()->toIso8601String(),
                'message' => $plainBody,
                'records' => $phoneNumbers->map(function ($p) use ($invoice, $channel, $toEmailValue) {
                    return $this->buildCampioRecipientRecord($invoice, $channel, $toEmailValue, $p);
                })->all(),
                'source_url' => url()->current(),
                'notes' => 'Invoice communication: '.strtoupper($channel),
            ];
            if (! empty($channelTemplateConfig?->template_id)) {
                $payload['template_id'] = (string) $channelTemplateConfig->template_id;
            }
            if (! empty($channelTemplateConfig?->meta_template_id)) {
                $payload['meta_template_id'] = (string) $channelTemplateConfig->meta_template_id;
            } elseif (! empty($channelTemplateConfig?->template_id)) {
                $payload['meta_template_id'] = (string) $channelTemplateConfig->template_id;
            }
            if (! empty($channelTemplateConfig?->sender_id)) {
                $payload['sender_id'] = (string) $channelTemplateConfig->sender_id;
            }
            if (! empty($channelTemplateConfig?->template_id)) {
                $accountName = (string) (optional(Account::find($currentAccountId))->name ?? '');
                $templateVariables = $this->extractCampioTemplateVariables((string) ($channelTemplateConfig->body ?? ''), $invoice, $accountName);
                if (! empty($templateVariables)) {
                    $payload['variables'] = collect($templateVariables)->pluck('value')->all();
                    $payload['components'] = [
                        [
                            'type' => 'body',
                            'parameters' => collect($templateVariables)->map(
                                fn ($var, $index) => [
                                    'type' => 'text',
                                    'parameter_name' => (string) $var['name'],
                                    'text' => (string) $var['value'],
                                ]
                            )->all(),
                        ],
                    ];
                    $payload['dynamic_context'] = [
                        'fields' => collect($templateVariables)->map(
                            fn ($var, $index) => [
                                'key' => 'Body_'.((int) $index + 1),
                                'type' => 'custom',
                                'value' => (string) $var['value'],
                            ]
                        )->all(),
                    ];
                }
            }
            if ($channel === 'whatsapp' && ! empty($documentLinks) && $canUseWhatsappDocumentHeader) {
                $payload['media_url'] = (string) ($documentLinks[0]['url'] ?? '');
                // $payload['media_url'] = 'https://billing.skoolready.com/public/storage/clients/P9AEGIF6Q8/invoices-share/Proforma-Invoice-PI-1011-2026-v23.pdf';
                if ($payload['media_url'] !== '' && ! str_starts_with(strtolower($payload['media_url']), 'https://')) {
                    $msg = 'WhatsApp media delivery requires a public HTTPS document URL. Current PDF URL is HTTP. Please enable SSL/HTTPS for this domain, then try again.';
                    if ($request->wantsJson()) {
                        return response()->json(['success' => false, 'message' => $msg], 422);
                    }

                    return back()->withErrors([
                        'general' => $msg,
                    ])->withInput();
                }
                if ($payload['media_url'] !== '') {
                    $beautifulName = (string) ($documentLinks[0]['name'] ?? 'Document.pdf');
                    $payload['media_filename'] = $beautifulName;
                    $payload['filename'] = $beautifulName; // Keep for fallback just in case
                    $payload['media_name'] = $beautifulName; // Keep for fallback just in case

                    if (! isset($payload['dynamic_context']) || ! is_array($payload['dynamic_context'])) {
                        $payload['dynamic_context'] = [];
                    }
                    $payload['dynamic_context']['media_url'] = $payload['media_url'];

                    if (! isset($payload['components'])) {
                        $payload['components'] = [];
                    }
                    array_unshift($payload['components'], [
                        'type' => 'header',
                        'parameters' => [
                            [
                                'type' => 'document',
                                'document' => [
                                    'link' => $payload['media_url'],
                                    'filename' => $beautifulName,
                                ],
                            ],
                        ],
                    ]);
                }
            }

            $campioResult = $this->sendViaCampio($channel, $payload);
            if (! $campioResult['ok']) {
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $campioResult['message']], 422);
                }

                return back()->withErrors(['general' => $campioResult['message']])->withInput();
            }

            $updatePayload['body'] = $finalBody;
            $emailDraft->update($updatePayload + ['status' => 'sent']);
            if (($invoice->status ?? '') === 'draft') {
                $invoice->update(['status' => 'active']);
                $this->finalizeRenewedSourceItems($invoice);
            }

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $successTitle,
                    'logid' => $emailDraft->logid,
                    'sent_at' => $sentAt->format('d M Y, h:i A'),
                ]);
            }

            return redirect()
                ->route('invoices.email-compose', [
                    'invoice' => $invoice->invoiceid,
                    'e' => $emailDraft->logid,
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
        if (in_array('pi', $selectedTypes, true) && $piPdfUrl) {
            $piUrl = $piPdfUrl;
            $piNumber = trim((string) ($invoice->pi_number ?: $invoice->invoice_number));
            $emailAttachmentUrls[] = $piUrl;
            $emailAttachmentItems[] = [
                'url' => $piUrl,
                'name' => 'Proforma Invoice - '.($piNumber !== '' ? $piNumber : $invoice->invoiceid).'.pdf',
            ];
        }
        if (in_array('ti', $selectedTypes, true) && $tiPdfUrl) {
            $tiUrl = $tiPdfUrl;
            $tiNumber = trim((string) ($invoice->ti_number ?: $invoice->invoice_number));
            $emailAttachmentUrls[] = $tiUrl;
            $emailAttachmentItems[] = [
                'url' => $tiUrl,
                'name' => 'Tax Invoice - '.($tiNumber !== '' ? $tiNumber : $invoice->invoiceid).'.pdf',
            ];
        }
        foreach ($finalCustomAttachmentPaths as $customPath) {
            $customUrl = $this->buildPublicAttachmentUrl((string) $customPath);
            $emailAttachmentUrls[] = $customUrl;
            $emailAttachmentItems[] = [
                'url' => $customUrl,
                'name' => basename((string) parse_url($customUrl, PHP_URL_PATH) ?: 'Attachment'),
            ];
        }

        $emails = collect(preg_split('/[\s,;]+/', $toEmailValue))
            ->map(fn ($email) => trim((string) $email))
            ->filter()
            ->values()
            ->all();

        $recipientRecords = [];
        if (empty($emails)) {
            $recipientRecords[] = $this->buildCampioRecipientRecord($invoice, 'email', $toEmailValue, $phone);
        } else {
            foreach ($emails as $email) {
                $recipientRecords[] = $this->buildCampioRecipientRecord($invoice, 'email', $email, $phone);
            }
        }

        $ccEmails = collect(preg_split('/[\s,;]+/', $ccEmailValue))
            ->map(fn ($email) => trim((string) $email))
            ->filter()
            ->values()
            ->all();

        foreach ($ccEmails as $ccEmail) {
            $recipientRecords[] = $this->buildCampioRecipientRecord($invoice, 'email', $ccEmail, $phone);
        }

        $payload = [
            'account_id' => $currentAccountId,
            'campaign_name' => '',
            'schedule_at' => now()->toIso8601String(),
            'subject' => (string) ($validated['subject'] ?? ''),
            'message' => $emailMessage,
            'sender_id' => $forcedFromName !== '' ? $forcedFromName : $forcedFromEmail,
            'from_name' => $forcedFromName,
            'from_email' => $forcedFromEmail,
            'records' => $recipientRecords,
            'source_url' => url()->current(),
            'notes' => 'Invoice communication: EMAIL',
        ];
        $emailAttachments = $this->buildCampioAttachments($emailAttachmentItems);
        if (! empty($emailAttachments)) {
            $payload['attachments'] = $emailAttachments;
        }

        $campioResult = $this->sendViaCampio('email', $payload);
        if (! $campioResult['ok']) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $campioResult['message']], 422);
            }

            return back()->withErrors(['general' => $campioResult['message']])->withInput();
        }

        $updatePayload['body'] = $finalBody;
        $emailDraft->update($updatePayload + ['status' => 'sent']);
        if (($invoice->status ?? '') === 'draft') {
            $invoice->update(['status' => 'active']);
            $this->finalizeRenewedSourceItems($invoice);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $successTitle,
                'logid' => $emailDraft->logid,
                'sent_at' => $sentAt->format('d M Y, h:i A'),
            ]);
        }

        return redirect()
            ->route('invoices.email-compose', [
                'invoice' => $invoice->invoiceid,
                'e' => $emailDraft->logid,
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
            $isTaxInvoice = ! empty(trim($invoice->ti_number ?? ''));
        }

        $pdfAttachment = $this->buildInvoicePdfAttachment($invoice, $isTaxInvoice);

        return response($pdfAttachment['binary'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$pdfAttachment['filename'].'"',
        ]);
    }

    public function sharePdf(Request $request, string $invoice)
    {
        $inv = Invoice::findOrFail($invoice);

        $type = $request->query('type'); // optional: pi | tax_invoice

        if ($type === 'tax_invoice') {
            $isTaxInvoice = true;
        } elseif ($type === 'pi') {
            $isTaxInvoice = false;
        } else {
            $isTaxInvoice = ! empty(trim($inv->ti_number ?? ''));
        }

        $pdfAttachment = $this->buildInvoicePdfAttachment($inv, $isTaxInvoice);

        return response($pdfAttachment['binary'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$pdfAttachment['filename'].'"',
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
        if (! $clientId) {
            return response()->json(['invoices' => []]);
        }

        $invoices = Invoice::where('clientid', $clientId)
            ->where('status', '!=', 'cancelled')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get(['invoiceid', 'invoice_number', 'grand_total', 'currency', 'clientid']);

        return response()->json([
            'invoices' => $invoices->map(fn ($inv) => [
                'invoiceid' => $inv->invoiceid,
                'invoice_number' => $inv->invoice_number,
                'grand_total' => (float) ($inv->grand_total ?? 0),
                'currency' => $inv->currency ?? 'INR',
            ])->values(),
        ]);
    }

    protected function calculateRenewalEndDate(Carbon $startDate, string $frequency, int $duration): ?Carbon
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

    public function renderInvoiceTemplateExternal(string $value, Invoice $invoice): string
    {
        return $this->renderMessageTemplate($value, $invoice);
    }
}
