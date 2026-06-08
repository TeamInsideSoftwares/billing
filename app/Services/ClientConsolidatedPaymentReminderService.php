<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountBillingDetail;
use App\Models\CommunicationLog;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ClientConsolidatedPaymentReminderService
{
    private const ATTACHMENT_TYPE = 'consolidated_payment_due_reminder';

    public function dispatchAutomatedConsolidatedPaymentReminders(?string $accountId = null, ?string $clientId = null): array
    {
        if ((bool) config('communications.pause_automated_all_channels', false)) {
            Log::info('Consolidated payment reminder skipped', [
                'reason' => 'automated reminders paused',
            ]);

            return [
                'accounts' => 0,
                'sent' => 0,
                'failed' => 0,
                'skipped' => 1,
            ];
        }

        $today = Carbon::today();
        $todayStr = $today->toDateString();

        // 1. Find all active/sent invoices that are due or overdue as of today, and not fully paid
        $dueInvoicesQuery = Invoice::query()
            ->whereNotIn('status', ['cancelled', 'draft'])
            ->whereNotIn('payment_status', ['paid'])
            ->where('due_date', '<=', $todayStr)
            ->when(
                ! empty($accountId),
                fn ($query) => $query->where('accountid', (string) $accountId)
            )
            ->when(
                ! empty($clientId),
                fn ($query) => $query->where('clientid', (string) $clientId)
            )
            ->with(['items', 'payments', 'client.billingDetail'])
            ->get();

        // Group by accountid and clientid to process per client
        $groupedByClient = $dueInvoicesQuery->groupBy(fn (Invoice $invoice) => (string) $invoice->accountid.'|'.(string) $invoice->clientid);

        $summary = [
            'accounts' => 0,
            'sent' => 0,
            'failed' => 0,
            'skipped' => 0,
        ];

        $processedAccounts = [];

        foreach ($groupedByClient as $compositeKey => $clientInvoices) {
            // Filter to make sure they have actual positive balance due
            $unpaidDueInvoices = $clientInvoices->filter(fn (Invoice $inv) => $inv->balance_due > 0);

            if ($unpaidDueInvoices->isEmpty()) {
                $summary['skipped']++;
                $this->logSkipReason('no unpaid due invoices', [
                    'accountid' => (string) ($clientInvoices->first()?->accountid ?? ''),
                    'clientid' => (string) ($clientInvoices->first()?->clientid ?? ''),
                ]);

                continue;
            }

            $firstInvoice = $unpaidDueInvoices->first();
            if (! $firstInvoice) {
                continue;
            }

            $accountId = (string) $firstInvoice->accountid;
            $clientId = (string) $firstInvoice->clientid;
            $processedAccounts[] = $accountId;

            $client = $firstInvoice->client;
            $toEmail = $this->resolveRecipientEmailFromInvoice($firstInvoice);
            if ($toEmail === '') {
                $summary['skipped']++;
                $this->logSkipReason('recipient email missing', [
                    'accountid' => $accountId,
                    'clientid' => $clientId,
                ]);

                continue;
            }

            // Prevent sending duplicate consolidated payment reminders on the same day
            $alreadySent = CommunicationLog::query()
                ->where('accountid', $accountId)
                ->where('clientid', $clientId)
                ->where('attachment_type', self::ATTACHMENT_TYPE)
                ->where('channel', 'email')
                ->where('status', 'sent')
                ->where('created_by', 'SYSTEM')
                ->whereDate('created_at', $todayStr)
                ->exists();

            if ($alreadySent) {
                $summary['skipped']++;
                $this->logSkipReason('duplicate reminder already sent today', [
                    'accountid' => $accountId,
                    'clientid' => $clientId,
                ]);

                continue;
            }

            $account = Account::query()->find($accountId);
            $accountBilling = AccountBillingDetail::query()->where('accountid', $accountId)->first();

            $currency = $client?->currency ?? 'INR';

            $items = [];
            $totalOverdueAmount = 0.0;
            $emailAttachments = [];

            foreach ($unpaidDueInvoices as $invoice) {
                $pdfType = ! empty($invoice->ti_number) ? 'tax_invoice' : 'pi';
                $pdfLink = $this->resolveStoredInvoicePdfUrl($invoice, $pdfType === 'tax_invoice');
                $payLink = route('invoices.show', ['invoice' => $invoice->invoiceid]);
                $pdfName = $this->buildInvoicePdfFilename($invoice, $pdfType === 'tax_invoice');
                $issueDate = $invoice->issue_date ?? $invoice->created_at ?? null;
                $overdueDays = $issueDate ? $today->diffInDays(Carbon::parse($issueDate)->startOfDay(), false) : null;

                $items[] = [
                    'invoice_number' => (string) $invoice->invoice_number,
                    'invoice_title' => (string) ($invoice->invoice_title ?? ''),
                    'overdue_days' => $overdueDays,
                    'balance_due' => $invoice->balance_due,
                    'pdf_link' => $pdfLink,
                    'pay_link' => $payLink,
                ];
                if ($pdfLink !== '') {
                    $emailAttachments[] = [
                        'url' => $pdfLink,
                        'name' => $pdfName,
                    ];
                }

                $totalOverdueAmount += $invoice->balance_due;
            }

            $subject = 'Outstanding Payments Summary - '.(string) ($client?->business_name ?: $client?->contact_name ?: 'Client');
            $senderName = (string) ($accountBilling?->billing_name ?: $account?->name ?: 'Team');
            $senderEmail = (string) ($accountBilling?->billing_from_email ?: $account?->email ?: '');
            $senderPhone = (string) ($account?->phone ?? '');
            $senderPostal = (string) ($accountBilling?->postal_code ?: $account?->postal_code ?: '');
            $senderAddressParts = array_values(array_filter([
                (string) ($accountBilling?->address ?: $account?->address_line_1 ?: ''),
                (string) ($accountBilling?->city ?: $account?->city ?: ''),
                (string) ($accountBilling?->state ?: $account?->state ?: ''),
                (string) ($senderPostal ?: ''),
                (string) ($accountBilling?->country ?: $account?->country ?: ''),
            ]));

            $message = view('emails.consolidated-client-payments', [
                'clientName' => (string) ($client?->business_name ?: $client?->contact_name ?: 'Client'),
                'today' => $today->format('d M Y'),
                'currency' => $currency,
                'items' => $items,
                'totalOverdueAmount' => $totalOverdueAmount,
                'senderName' => $senderName,
                'senderEmail' => $senderEmail,
                'senderPhone' => $senderPhone,
                'senderAddressLine' => implode(' | ', $senderAddressParts),
            ])->render();

            $payload = [
                'account_id' => $accountId,
                'campaign_name' => '',
                'schedule_at' => now()->toIso8601String(),
                'subject' => $subject,
                'message' => $message,
                'sender_id' => (string) ($accountBilling?->billing_name ?: $accountBilling?->billing_from_email ?: ''),
                'records' => [[
                    'id' => $clientId,
                    'name' => (string) ($client?->business_name ?: $client?->contact_name ?: 'Client'),
                    'leadid' => strtoupper(substr($clientId, 0, 6)),
                    'student_customer_name' => (string) ($client?->business_name ?: $client?->contact_name ?: 'Client'),
                    'email' => $toEmail,
                ]],
                'source_url' => config('app.url'),
                'notes' => 'Automated consolidated payment due reminder',
            ];
            $campioAttachments = $this->buildCampioAttachments($emailAttachments);
            if (! empty($campioAttachments)) {
                $payload['attachments'] = $campioAttachments;
            }

            $sendResult = $this->sendViaCampio('email', $payload);
            $status = $sendResult['ok'] ? 'sent' : 'failed';

            CommunicationLog::query()->create([
                'accountid' => $accountId,
                'invoiceid' => '',
                'clientid' => $clientId,
                'from_email' => (string) ($accountBilling?->billing_from_email ?? ''),
                'to_email' => $toEmail,
                'phone_number' => '',
                'subject' => $subject,
                'body' => $message,
                'attachment_type' => self::ATTACHMENT_TYPE,
                'channel' => 'email',
                'status' => $status,
                'created_by' => 'SYSTEM',
            ]);

            if ($sendResult['ok']) {
                $summary['sent']++;
            } else {
                $summary['failed']++;
            }
        }

        $summary['accounts'] = count(array_unique($processedAccounts));

        return $summary;
    }

    private function resolveRecipientEmailFromInvoice(Invoice $invoice): string
    {
        $candidates = [
            (string) ($invoice->client?->billingDetail?->billing_email ?? ''),
            (string) ($invoice->client?->billing_email ?? ''),
            (string) ($invoice->client?->primary_email ?? ''),
            (string) ($invoice->client?->email ?? ''),
        ];

        foreach ($candidates as $candidate) {
            $parts = preg_split('/[,;]+/', (string) $candidate) ?: [];
            foreach ($parts as $part) {
                $email = trim($part);
                if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    return $email;
                }
            }
        }

        return '';
    }

    private function sendViaCampio(string $channel, array $payload): array
    {
        $baseUrl = rtrim((string) env('CAMPIO_BASE_URL', 'http://alpha.skoolready.com/campio'), '/');
        if ($baseUrl === '') {
            return ['ok' => false, 'message' => 'CAMPIO_BASE_URL is not configured.'];
        }

        $endpoint = $baseUrl.'/api/campaigns/schedule/'.$channel;
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
            return ['ok' => false, 'message' => 'Campio request failed: '.$e->getMessage()];
        }

        $json = $response->json();
        if (! $response->successful()) {
            Log::error('Campio consolidated payment reminder dispatch failed', [
                'channel' => $channel,
                'status' => $response->status(),
                'response' => $json,
            ]);

            return [
                'ok' => false,
                'message' => is_array($json)
                    ? ((string) ($json['message'] ?? 'Campio API returned an error.'))
                    : ('Campio API returned HTTP '.$response->status().'.'),
            ];
        }

        return [
            'ok' => true,
            'campaign_id' => (string) (is_array($json) ? ($json['campaign_id'] ?? '') : ''),
        ];
    }

    private function resolveStoredInvoicePdfUrl(Invoice $invoice, bool $isTaxInvoice): string
    {
        $typeKey = $isTaxInvoice ? 'ti' : 'pi';
        $stored = collect($this->listStoredInvoicePdfVersions($invoice))
            ->filter(fn (array $row) => (string) ($row['type'] ?? '') === $typeKey)
            ->sortByDesc(fn (array $row) => (int) ($row['version'] ?? 0))
            ->first();

        if (! empty($stored['url'])) {
            return (string) $stored['url'];
        }

        return route('invoices.pdf', [
            'invoice' => $invoice->invoiceid,
            'type' => $isTaxInvoice ? 'tax_invoice' : 'pi',
        ]);
    }

    private function listStoredInvoicePdfVersions(Invoice $invoice): array
    {
        $disk = Storage::disk('public');
        $directory = 'clients/'.$invoice->clientid.'/invoices-pdf';
        if (! $disk->exists($directory)) {
            return [];
        }

        return collect($disk->files($directory))
            ->map(function (string $path) use ($invoice, $disk) {
                $name = pathinfo($path, PATHINFO_FILENAME);
                if (! preg_match('/^'.preg_quote($invoice->invoiceid, '/').'_(pi|ti)__v(\d+)$/', $name, $matches)) {
                    return null;
                }

                return [
                    'type' => $matches[1],
                    'version' => (int) $matches[2],
                    'filename' => basename($path),
                    'path' => $path,
                    'url' => asset('storage/'.$path),
                    'saved_at' => optional($disk->lastModified($path) ? Carbon::createFromTimestamp($disk->lastModified($path)) : null)?->toDateTimeString(),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function buildInvoicePdfFilename(Invoice $invoice, bool $isTaxInvoice): string
    {
        $documentType = $isTaxInvoice ? 'Tax Invoice' : 'Proforma Invoice';
        $number = trim((string) ($isTaxInvoice ? ($invoice->ti_number ?: $invoice->invoice_number) : ($invoice->pi_number ?: $invoice->invoice_number)));

        return $documentType.' - '.($number !== '' ? $number : $invoice->invoiceid).'.pdf';
    }

    private function buildCampioAttachments(array $attachmentsInput): array
    {
        $attachments = [];

        foreach ($attachmentsInput as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            $url = trim((string) ($item['url'] ?? ''));
            if ($url === '') {
                continue;
            }

            $name = trim((string) ($item['name'] ?? ''));
            if ($name === '') {
                $name = 'attachment-'.($index + 1).'.pdf';
            }

            $attachments[] = [
                'url' => $url,
                'name' => $name,
            ];
        }

        return $attachments;
    }

    private function logSkipReason(string $reason, array $context = []): void
    {
        Log::info('Consolidated payment reminder skipped', array_merge([
            'reason' => $reason,
        ], array_filter($context, fn ($value) => $value !== '' && $value !== null)));
    }
}
