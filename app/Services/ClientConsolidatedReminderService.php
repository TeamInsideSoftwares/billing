<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountBillingDetail;
use App\Models\CommunicationLog;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClientConsolidatedReminderService
{
    private const WINDOW_DAYS = 10;

    private const ATTACHMENT_TYPE = 'consolidated_order_summary';

    public function dispatchAutomatedConsolidatedEmails(?string $accountId = null): array
    {
        if ((bool) config('communications.pause_automated_all_channels', false)) {
            return [
                'accounts' => 0,
                'sent' => 0,
                'failed' => 0,
                'skipped' => 1,
            ];
        }

        $today = Carbon::today();
        $triggerFromDate = $today->copy()->toDateString();
        $triggerToDate = $today->copy()->addDays(self::WINDOW_DAYS)->toDateString();

        // Trigger only when client has at least one order expiring in next N days.
        $triggerOrders = Order::query()
            ->whereNotNull('end_date')
            ->whereDate('end_date', '<', '2099-01-01')
            ->whereBetween('end_date', [$triggerFromDate, $triggerToDate])
            ->when(
                ! empty($accountId),
                fn ($query) => $query->where('accountid', (string) $accountId)
            )
            ->where(function ($query) {
                $query->whereNotIn('status', ['cancelled', 'suspended'])
                    ->orWhereNull('status');
            })
            ->orderBy('accountid')
            ->orderBy('clientid')
            ->orderBy('end_date')
            ->get();

        $summary = [
            'accounts' => (int) $triggerOrders->pluck('accountid')->filter()->unique()->count(),
            'sent' => 0,
            'failed' => 0,
            'skipped' => 0,
        ];

        $grouped = $triggerOrders->groupBy(fn (Order $order) => (string) $order->accountid.'|'.(string) $order->clientid);

        foreach ($grouped as $rows) {
            /** @var Order $first */
            $first = $rows->first();
            if (! $first) {
                $summary['skipped']++;

                continue;
            }

            $accountId = (string) $first->accountid;
            $clientId = (string) $first->clientid;
            $allClientOrders = Order::query()
                ->where('accountid', $accountId)
                ->where('clientid', $clientId)
                ->whereNotNull('end_date')
                ->whereDate('end_date', '<', '2099-01-01')
                ->where(function ($query) {
                    $query->whereNotIn('status', ['cancelled', 'suspended'])
                        ->orWhereNull('status');
                })
                ->with(['client.billingDetail'])
                ->orderBy('end_date')
                ->get();

            $client = $allClientOrders->first()?->client;
            if ($allClientOrders->isEmpty()) {
                $summary['skipped']++;

                continue;
            }

            $toEmail = $this->resolveRecipientEmailFromOrder($first);
            if ($toEmail === '') {
                $summary['skipped']++;

                continue;
            }

            $alreadySent = CommunicationLog::query()
                ->where('accountid', $accountId)
                ->where('clientid', $clientId)
                ->where('attachment_type', self::ATTACHMENT_TYPE)
                ->where('channel', 'email')
                ->where('status', 'sent')
                ->where('created_by', 'SYSTEM')
                ->whereDate('created_at', $today->toDateString())
                ->exists();

            if ($alreadySent) {
                $summary['skipped']++;

                continue;
            }

            $account = Account::query()->find($accountId);
            $accountBilling = AccountBillingDetail::query()->where('accountid', $accountId)->first();

            $items = $allClientOrders->map(function (Order $order) use ($today) {
                $endDate = $order->end_date ? $order->end_date->copy()->startOfDay() : null;
                $daysDiff = $endDate ? $today->diffInDays($endDate, false) : null;

                return [
                    'order_number' => (string) ($order->order_number ?? $order->orderid),
                    'item_name' => (string) ($order->item_name ?? 'Item'),
                    'item_description' => (string) ($order->item_description ?? ''),
                    'qty' => (int) ($order->quantity ?? 0),
                    'end_date' => $endDate?->format('d M Y') ?? '-',
                    'days_label' => $daysDiff === null
                        ? '-'
                        : ($daysDiff >= 0 ? ($daysDiff.' day(s) left') : (abs($daysDiff).' day(s) ago')),
                ];
            })->values();

            $subject = 'Order Expiry Summary - '.(string) ($client?->business_name ?: $client?->contact_name ?: 'Client');
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

            $message = view('emails.consolidated-client-orders', [
                'clientName' => (string) ($client?->business_name ?: $client?->contact_name ?: 'Client'),
                'windowDays' => self::WINDOW_DAYS,
                'today' => $today->format('d M Y'),
                'items' => $items,
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
                'notes' => 'Automated consolidated order reminder',
            ];

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

        return $summary;
    }

    private function resolveRecipientEmailFromOrder(Order $order): string
    {
        $candidates = [
            (string) ($order->client?->billingDetail?->billing_email ?? ''),
            (string) ($order->client?->billing_email ?? ''),
            (string) ($order->client?->primary_email ?? ''),
            (string) ($order->client?->email ?? ''),
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
            Log::error('Campio consolidated reminder dispatch failed', [
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
}
