<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountBillingDetail;
use App\Models\Invoice;
use App\Models\CommunicationLog;
use App\Models\InvoiceItem;
use App\Models\MessageTemplate;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InvoiceReminderService
{
    private const DEFAULT_START_DAYS = 30;

    public function getAutomationSettings(string $accountid): array
    {
        $account = Account::query()
            ->select(['accountid', 'reminder_automation_enabled', 'reminder_days_before'])
            ->find($accountid);

        return [
            'enabled' => (bool) ($account?->reminder_automation_enabled ?? false),
            'start_days' => max(1, (int) ($account?->reminder_days_before ?? self::DEFAULT_START_DAYS)),
        ];
    }

    public function updateAutomationSettings(string $accountid, bool $enabled, int $startDays): void
    {
        Account::query()
            ->where('accountid', $accountid)
            ->update([
                'reminder_automation_enabled' => $enabled,
                'reminder_days_before' => max(1, $startDays),
            ]);
    }

    public function sendManualReminder(Invoice $invoice, ?InvoiceItem $invoiceItem = null): array
    {
        $invoice->loadMissing(['client.billingDetail', 'items']);
        return $this->dispatchForInvoice(
            $invoice,
            'reminder',
            'manual',
            Carbon::today(),
            ['selected_item' => $invoiceItem]
        );
    }

    public function dispatchAutomatedDueReminders(): array
    {
        $today = Carbon::today();
        $enabledAccountIds = Account::query()
            ->where('reminder_automation_enabled', true)
            ->pluck('accountid');

        $summary = [
            'accounts' => $enabledAccountIds->count(),
            'sent' => 0,
            'failed' => 0,
            'skipped' => 0,
        ];

        foreach ($enabledAccountIds as $accountid) {
            $settings = $this->getAutomationSettings((string) $accountid);
            $startDays = max(1, (int) $settings['start_days']);

            $invoices = Invoice::query()
                ->where('accountid', $accountid)
                ->where('status', '!=', 'cancelled')
                ->with([
                    'client.billingDetail',
                    'items' => fn($q) => $q->whereNotNull('end_date'),
                ])
                ->get();

            foreach ($invoices as $invoice) {
                foreach ($invoice->items as $item) {
                    if (!$item->end_date) {
                        continue;
                    }

                    $expiryDate = $item->end_date instanceof Carbon
                        ? $item->end_date->copy()->startOfDay()
                        : Carbon::parse((string) $item->end_date)->startOfDay();
                    $daysUntilExpiry = $today->diffInDays($expiryDate, false);

                    $templateType = null;
                    if ($this->hasRenewalEntryForItem($invoice, $item)) {
                        $templateType = 'renewal';
                    } elseif ($daysUntilExpiry <= 0) {
                        $templateType = 'expiry';
                    } elseif ($daysUntilExpiry >= 1 && $daysUntilExpiry <= 3) {
                        $templateType = 'reminder';
                    } elseif (
                        $daysUntilExpiry > 3
                        && $daysUntilExpiry <= $startDays
                        && (($startDays - $daysUntilExpiry) % 7) === 0
                    ) {
                        $templateType = 'reminder';
                    }

                    if (!$templateType) {
                        continue;
                    }

                    $result = $this->dispatchForInvoice(
                        $invoice,
                        $templateType,
                        'automated',
                        $today,
                        [
                            'expiry_date' => $expiryDate->copy(),
                            'days_left' => $daysUntilExpiry,
                            'selected_item' => $item,
                        ]
                    );

                    $summary['sent'] += (int) ($result['sent'] ?? 0);
                    $summary['failed'] += (int) ($result['failed'] ?? 0);
                    $summary['skipped'] += (int) ($result['skipped'] ?? 0);
                }
            }
        }

        return $summary;
    }

    private function hasRenewalEntryForItem(Invoice $invoice, InvoiceItem $item): bool
    {
        if (!$item->end_date || empty($item->itemid)) {
            return false;
        }

        $itemEndDate = $item->end_date instanceof Carbon
            ? $item->end_date->copy()->startOfDay()
            : Carbon::parse((string) $item->end_date)->startOfDay();

        return InvoiceItem::query()
            ->where('accountid', $invoice->accountid)
            ->where('clientid', $invoice->clientid)
            ->where('itemid', $item->itemid)
            ->where('invoiceid', '!=', $invoice->invoiceid)
            ->whereNotNull('start_date')
            ->whereDate('start_date', '>', $itemEndDate->toDateString())
            ->whereHas('invoice', function ($q) {
                $q->where('status', '!=', 'cancelled');
            })
            ->exists();
    }

    private function dispatchForInvoice(
        Invoice $invoice,
        string $templateType,
        string $source,
        Carbon $dispatchDate,
        array $context = []
    ): array {
        $accountid = (string) $invoice->accountid;
        $templates = MessageTemplate::query()
            ->where('accountid', $accountid)
            ->where('template_type', $templateType)
            ->where('is_active', true)
            ->whereIn('channel', ['email', 'whatsapp', 'sms'])
            ->orderBy('channel')
            ->get();

        if ($templates->isEmpty()) {
            return ['sent' => 0, 'failed' => 0, 'skipped' => 1];
        }

        $result = ['sent' => 0, 'failed' => 0, 'skipped' => 0];
        foreach ($templates as $template) {
            $channel = (string) $template->channel;

            if ($source === 'automated') {
                $alreadySentQuery = CommunicationLog::query()
                    ->where('accountid', $accountid)
                    ->where('invoiceid', $invoice->invoiceid)
                    ->where('attachment_type', $templateType)
                    ->where('channel', $channel)
                    ->where('status', 'sent')
                    ->where('created_by', 'SYSTEM');

                $alreadySent = $templateType === 'renewal'
                    ? $alreadySentQuery->exists()
                    : $alreadySentQuery->whereDate('created_at', $dispatchDate->toDateString())->exists();

                if ($alreadySent) {
                    $result['skipped']++;
                    continue;
                }
            }

            $send = $this->sendForTemplate($invoice, $templateType, $template, $context);
            $status = $send['ok'] ? 'sent' : 'failed';

            $emailLog = new CommunicationLog();
            $emailLog->accountid = $accountid;
            $emailLog->invoiceid = (string) $invoice->invoiceid;
            $emailLog->clientid = (string) ($invoice->clientid ?? '');
            $emailLog->from_email = (string) ($send['from_email'] ?? '');
            $emailLog->to_email = (string) ($send['to_email'] ?? '');
            $emailLog->phone_number = (string) ($send['phone'] ?? '');
            $emailLog->subject = $send['subject'] ?? null;
            $emailLog->body = $send['body'] ?? null;
            $emailLog->attachment_type = $templateType;
            $emailLog->channel = $channel;
            $emailLog->status = $status;
            $emailLog->created_by = $source === 'automated'
                ? 'SYSTEM'
                : (string) (auth()->user()?->userid ?? auth()->id() ?? 'SYSTEM');
            $emailLog->save();

            if ($send['ok']) {
                $result['sent']++;
            } else {
                $result['failed']++;
            }
        }

        return $result;
    }

    private function sendForTemplate(Invoice $invoice, string $templateType, MessageTemplate $template, array $context = []): array
    {
        $channel = (string) $template->channel;
        $accountid = (string) $invoice->accountid;
        $account = Account::query()->find($accountid);
        $accountBilling = AccountBillingDetail::query()->where('accountid', $accountid)->first();

        $toEmail = trim((string) (
            $invoice->client?->billingDetail?->billing_email
            ?? $invoice->client?->billing_email
            ?? ''
        ));
        $phone = trim((string) (
            $invoice->client?->billingDetail?->billing_phone
            ?? $invoice->client?->whatsapp_number
            ?? $invoice->client?->phone
            ?? ''
        ));

        if ($channel === 'email' && $toEmail === '') {
            return [
                'ok' => false,
                'message' => 'Email channel skipped: client billing email missing.',
                'from_email' => (string) ($accountBilling?->billing_from_email ?? ''),
                'to_email' => '',
                'phone' => $phone,
                'subject' => null,
                'body' => null,
            ];
        }
        if ($channel !== 'email' && $phone === '') {
            return [
                'ok' => false,
                'message' => 'Messaging channel skipped: client phone/whatsapp missing.',
                'from_email' => (string) ($accountBilling?->billing_from_email ?? ''),
                'to_email' => $toEmail,
                'phone' => '',
                'subject' => null,
                'body' => null,
            ];
        }

        $expiryDate = data_get($context, 'expiry_date');
        if (!$expiryDate) {
            $expiryValue = $invoice->items->max('end_date');
            if ($expiryValue instanceof Carbon) {
                $expiryDate = $expiryValue->copy();
            } elseif (!empty($expiryValue)) {
                $expiryDate = Carbon::parse((string) $expiryValue);
            } else {
                $expiryDate = null;
            }
        }
        $daysLeft = (int) data_get(
            $context,
            'days_left',
            $expiryDate ? Carbon::today()->diffInDays($expiryDate, false) : 0
        );

        $replace = $this->buildTemplateReplacements(
            $invoice,
            (string) ($accountBilling?->billing_name ?? $account?->name ?? ''),
            $expiryDate,
            $daysLeft,
            $templateType,
            data_get($context, 'selected_item')
        );
        $subject = strtr((string) ($template->subject ?? ''), $replace);
        $body = strtr((string) ($template->body ?? ''), $replace);

        $record = $this->buildRecipientRecord($invoice, $channel, $toEmail, $phone);
        $payload = [
            'account_id' => $accountid,
            'campaign_name' => '',
            'schedule_at' => now()->toIso8601String(),
            'source_url' => config('app.url'),
            'notes' => 'Invoice ' . strtoupper($templateType) . ' notification',
            'records' => [$record],
        ];

        if ($channel === 'email') {
            $payload['subject'] = $this->sanitizeForCampioText($subject);
            $payload['message'] = $this->sanitizeForCampioText(strip_tags($body));
            $payload['sender_id'] = (string) ($accountBilling?->billing_name ?: $accountBilling?->billing_from_email ?: '');
        } else {
            $payload['message'] = $this->sanitizeForCampioText(strip_tags($body));
            if (!empty($template->template_id)) {
                $payload['template_id'] = (string) $template->template_id;
            }
            if (!empty($template->meta_template_id)) {
                $payload['meta_template_id'] = (string) $template->meta_template_id;
            } elseif (!empty($template->template_id)) {
                $payload['meta_template_id'] = (string) $template->template_id;
            }
            if (!empty($template->sender_id)) {
                $payload['sender_id'] = (string) $template->sender_id;
            }
        }

        $sendResult = $this->sendViaCampio($channel, $payload);

        return $sendResult + [
            'from_email' => (string) ($accountBilling?->billing_from_email ?? ''),
            'to_email' => $toEmail,
            'phone' => $phone,
            'subject' => $channel === 'email' ? $subject : null,
            'body' => $body,
        ];
    }

    private function buildRecipientRecord(Invoice $invoice, string $channel, string $toEmail, string $phone): array
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
            return $record;
        }

        $phoneDigits = preg_replace('/[^0-9]/', '', (string) $phone);
        $localPhone = $phoneDigits;
        if (strlen($phoneDigits) === 12 && str_starts_with($phoneDigits, '91')) {
            $localPhone = substr($phoneDigits, 2);
        }

        $record['mobile'] = $localPhone !== '' ? $localPhone : $phone;
        return $record;
    }

    private function buildTemplateReplacements(
        Invoice $invoice,
        string $businessName,
        mixed $expiryDate,
        int $daysLeft,
        string $templateType,
        mixed $selectedItem = null
    ): array
    {
        $clientBusinessName = trim((string) ($invoice->client?->business_name ?? ''));
        $clientContactPerson = trim((string) ($invoice->client?->contact_name ?? ''));
        $clientName = trim((string) ($clientBusinessName !== '' ? $clientBusinessName : $clientContactPerson));
        $currency = (string) ($invoice->client?->currency ?? 'INR');
        $totalAmount = (float) ($invoice->grand_total ?? $invoice->items->sum('line_total') ?? 0);
        $primaryItem = $selectedItem instanceof InvoiceItem
            ? $selectedItem
            : $invoice->items
            ->sortBy(function ($item) {
                return $item->end_date?->timestamp ?? PHP_INT_MAX;
            })
            ->first();
        $itemName = trim((string) ($primaryItem?->item_name ?? ''));
        $itemStartDate = $primaryItem?->start_date?->format('d M Y') ?? '';
        $itemEndDate = $primaryItem?->end_date?->format('d M Y') ?? '';
        $calculatedDaysLeft = $primaryItem?->end_date
            ? now()->startOfDay()->diffInDays($primaryItem->end_date->startOfDay(), false)
            : $daysLeft;
        $renewalDate = $templateType === 'renewal'
            ? ($invoice->created_at?->format('d M Y') ?? now()->format('d M Y'))
            : '';
        $latestPayment = Payment::query()
            ->where('accountid', $invoice->accountid)
            ->where('invoiceid', $invoice->invoiceid)
            ->orderByDesc('payment_date')
            ->orderByDesc('created_at')
            ->first();
        $paymentAmount = (float) ($latestPayment?->received_amount ?? 0);
        $paymentDate = $latestPayment?->payment_date?->format('d M Y') ?? '';
        $paymentReference = trim((string) ($latestPayment?->reference_number ?? ''));

        return [
            '{{client_business_name}}' => $clientBusinessName,
            '{{client_contact_person}}' => $clientContactPerson,
            '{{client_name}}' => $clientName,
            '{{business_name}}' => $businessName,
            '{{company_name}}' => $businessName,
            '{{account_name}}' => $businessName,
            '{{invoice_number}}' => (string) ($invoice->invoice_number ?? ''),
            '{{invoice_title}}' => (string) ($invoice->invoice_title ?? ''),
            '{{pi_number}}' => (string) ($invoice->pi_number ?? ''),
            '{{ti_number}}' => (string) ($invoice->ti_number ?? ''),
            '{{total_amount}}' => $currency . ' ' . number_format($totalAmount, 2),
            '{{due_date}}' => $invoice->due_date?->format('d M Y') ?? '',
            '{{template_type}}' => $templateType,
            '{{reminder_type}}' => $templateType,
            '{{item_name}}' => $itemName,
            '{{item_start_date}}' => $itemStartDate,
            '{{item_end_date}}' => $itemEndDate,
            '{{expiry_date}}' => $itemEndDate !== '' ? $itemEndDate : ($expiryDate?->format('d M Y') ?? ''), // Alias of item_end_date
            '{{days_left}}' => (string) max(0, (int) $calculatedDaysLeft),
            '{{renewal_date}}' => $renewalDate,
            '{{payment_amount}}' => $paymentAmount > 0 ? ($currency . ' ' . number_format($paymentAmount, 2)) : '',
            '{{payment_date}}' => $paymentDate,
            '{{payment_reference}}' => $paymentReference,
            '{{pi_link}}' => route('invoices.pdf', ['invoice' => $invoice->invoiceid, 'type' => 'pi']),
            '{{ti_link}}' => route('invoices.pdf', ['invoice' => $invoice->invoiceid, 'type' => 'tax_invoice']),
        ];
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
            Log::error('Campio reminder dispatch failed', [
                'channel' => $channel,
                'status' => $response->status(),
                'response' => $json,
            ]);
            return ['ok' => false, 'message' => $message];
        }

        return [
            'ok' => true,
            'campaign_id' => (string) (is_array($json) ? ($json['campaign_id'] ?? '') : ''),
            'raw' => $json,
        ];
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

}
