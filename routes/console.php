<?php

use App\Services\InvoiceReminderService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('reminders:dispatch', function (InvoiceReminderService $invoiceReminderService) {
    $summary = $invoiceReminderService->dispatchAutomatedDueReminders();

    $this->info('Reminder automation completed.');
    $this->line('Accounts: ' . (int) ($summary['accounts'] ?? 0));
    $this->line('Sent: ' . (int) ($summary['sent'] ?? 0));
    $this->line('Failed: ' . (int) ($summary['failed'] ?? 0));
    $this->line('Skipped: ' . (int) ($summary['skipped'] ?? 0));
})->purpose('Dispatch automated reminder, expiry, and renewal notifications');

Schedule::command('reminders:dispatch')->dailyAt('09:00');
