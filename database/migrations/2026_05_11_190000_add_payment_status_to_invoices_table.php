<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('invoices', 'payment_status')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->string('payment_status', 20)->default('unpaid')->after('status');
            });
        }

        DB::table('invoices')
            ->select(['invoiceid', 'status'])
            ->orderBy('invoiceid')
            ->chunk(200, function ($rows) {
                foreach ($rows as $row) {
                    $status = strtolower(trim((string) ($row->status ?? '')));

                    $paymentStatus = 'unpaid';
                    if ($status === 'paid') {
                        $paymentStatus = 'paid';
                    } elseif (in_array($status, ['partially-paid', 'partly paid', 'partially paid'], true)) {
                        $paymentStatus = 'partly_paid';
                    } elseif ($status === 'unpaid') {
                        $paymentStatus = 'unpaid';
                    }

                    $normalizedStatus = in_array($status, ['paid', 'partially-paid', 'partly paid', 'partially paid', 'unpaid'], true)
                        ? 'active'
                        : ($status !== '' ? $status : 'draft');

                    DB::table('invoices')
                        ->where('invoiceid', $row->invoiceid)
                        ->update([
                            'status' => $normalizedStatus,
                            'payment_status' => $paymentStatus,
                        ]);
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('invoices', 'payment_status')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropColumn('payment_status');
            });
        }
    }
};
