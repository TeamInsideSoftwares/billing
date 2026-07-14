<?php

use App\Services\ClientConsolidatedPaymentReminderService;
use App\Services\ClientConsolidatedReminderService;
use App\Services\InvoiceReminderService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('reminders:dispatch {--account=}', function (InvoiceReminderService $invoiceReminderService) {
    $accountId = trim((string) ($this->option('account') ?? ''));
    $summary = $invoiceReminderService->dispatchAutomatedDueReminders($accountId !== '' ? $accountId : null);

    $this->info('Reminder automation completed.');
    $this->line('Accounts: '.(int) ($summary['accounts'] ?? 0));
    if ($accountId !== '') {
        $this->line('Filtered Account: '.$accountId);
    }
    $this->line('Sent: '.(int) ($summary['sent'] ?? 0));
    $this->line('Failed: '.(int) ($summary['failed'] ?? 0));
    $this->line('Skipped: '.(int) ($summary['skipped'] ?? 0));
})->purpose('Dispatch automated reminder and expiry notifications');

Artisan::command('reminders:dispatch-consolidated {--account=} {--client=}', function (ClientConsolidatedReminderService $consolidatedReminderService) {
    $accountId = trim((string) ($this->option('account') ?? ''));
    $clientId = trim((string) ($this->option('client') ?? ''));
    $summary = $consolidatedReminderService->dispatchAutomatedConsolidatedEmails($accountId !== '' ? $accountId : null, $clientId !== '' ? $clientId : null);

    $this->info('Consolidated reminder automation completed.');
    $this->line('Accounts: '.(int) ($summary['accounts'] ?? 0));
    if ($accountId !== '') {
        $this->line('Filtered Account: '.$accountId);
    }
    $this->line('Sent: '.(int) ($summary['sent'] ?? 0));
    $this->line('Failed: '.(int) ($summary['failed'] ?? 0));
    $this->line('Skipped: '.(int) ($summary['skipped'] ?? 0));
})->purpose('Dispatch automated consolidated client order summary emails');

// Single automated reminders disabled in favor of consolidated emails.
// Schedule::command('reminders:dispatch')->dailyAt('09:00');
Schedule::command('reminders:dispatch-consolidated')->dailyAt('09:10');

Artisan::command('reminders:dispatch-consolidated-payments {--account=} {--client=}', function (ClientConsolidatedPaymentReminderService $consolidatedPaymentReminderService) {
    $accountId = trim((string) ($this->option('account') ?? ''));
    $clientId = trim((string) ($this->option('client') ?? ''));
    $summary = $consolidatedPaymentReminderService->dispatchAutomatedConsolidatedPaymentReminders(
        $accountId !== '' ? $accountId : null,
        $clientId !== '' ? $clientId : null
    );

    $this->info('Consolidated payment due reminder automation completed.');
    $this->line('Accounts: '.(int) ($summary['accounts'] ?? 0));
    if ($accountId !== '') {
        $this->line('Filtered Account: '.$accountId);
    }
    if ($clientId !== '') {
        $this->line('Filtered Client: '.$clientId);
    }
    $this->line('Sent: '.(int) ($summary['sent'] ?? 0));
    $this->line('Failed: '.(int) ($summary['failed'] ?? 0));
    $this->line('Skipped: '.(int) ($summary['skipped'] ?? 0));
})->purpose('Dispatch automated consolidated client payment due reminders');

Schedule::command('reminders:dispatch-consolidated-payments')->dailyAt('09:15');
