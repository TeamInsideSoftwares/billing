<?php

/**
 * Test Script for Serial Number Generation
 * 
 * Run this from the project root:
 * php test_serial_number.php
 * 
 * Or include it in Laravel tinker:
 * include 'test_serial_number.php';
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\AccountBillingDetail;
use App\Models\Invoice;
use App\Models\FinancialYear;

echo "=== Serial Number Generation Test ===\n\n";

// Get the account ID (default: ACC0000001)
$accountId = 'ACC0000001';

echo "Account ID: $accountId\n\n";

// Check if billing detail exists
$billingDetail = AccountBillingDetail::where('accountid', $accountId)->first();

if (!$billingDetail) {
    echo "❌ No billing detail found for account $accountId\n";
    echo "Please configure your serial number settings in Settings → Billing Details\n\n";
    
    // Show existing invoice count
    $invoiceCount = Invoice::where('accountid', $accountId)->count();
    echo "Existing invoices: $invoiceCount\n";
    
    if ($invoiceCount > 0) {
        echo "Recent invoice numbers:\n";
        Invoice::where('accountid', $accountId)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['invoice_number', 'created_at'])
            ->each(function ($inv) {
                echo "  - {$inv->invoice_number} ({$inv->created_at})\n";
            });
    }
    
    exit(1);
}

echo "✓ Billing detail found\n\n";

// Display current configuration
echo "--- Current Configuration ---\n";
echo "Prefix: {$billingDetail->prefix} (Type: {$billingDetail->prefix_type})\n";
echo "Number Type: {$billingDetail->number_type}\n";
echo "Number Value: {$billingDetail->number_value}\n";
echo "Number Length: {$billingDetail->number_length}\n";
echo "Suffix: {$billingDetail->suffix} (Type: {$billingDetail->suffix_type})\n";
echo "Reset on FY: " . ($billingDetail->reset_on_fy ? 'Yes' : 'No') . "\n\n";

// Test serial number generation
echo "--- Testing Serial Number Generation ---\n";

// Count existing invoices
$invoiceCount = Invoice::where('accountid', $accountId)->count();
echo "Existing invoice count: $invoiceCount\n";

// Get current financial year
$fy = FinancialYear::where('default', true)->first();
if ($fy) {
    echo "Current Financial Year: {$fy->fy_id}\n";
}

// Generate next serial number
try {
    $nextNumber = $billingDetail->generateNextSerialNumber();
    echo "✓ Next invoice number will be: $nextNumber\n\n";
} catch (Exception $e) {
    echo "❌ Error generating serial number: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Show recent invoices
echo "--- Recent Invoices (Last 10) ---\n";
$invoices = Invoice::where('accountid', $accountId)
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get(['invoice_number', 'grand_total', 'created_at']);

if ($invoices->isEmpty()) {
    echo "No invoices found yet\n";
} else {
    foreach ($invoices as $inv) {
        echo "  {$inv->invoice_number} - ₹" . number_format($inv->grand_total, 2) . " ({$inv->created_at})\n";
    }
}

echo "\n=== Test Complete ===\n";
echo "\nTo test manually:\n";
echo "1. Go to Invoices → Create Invoice\n";
echo "2. Select a client\n";
echo "3. Check the 'Invoice Number' field (it's readonly)\n";
echo "4. It should show: $nextNumber\n";
