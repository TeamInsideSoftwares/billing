<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'tds_amount')) {
                $table->decimal('tds_amount', 12, 2)->default(0)->after('received_amount');
            }
        });

        Schema::create('payment_details', function (Blueprint $table) {
            $table->string('detailid', 20)->primary();
            $table->string('accountid', 20)->index();
            $table->string('clientid', 10)->index();
            $table->string('paymentid', 20)->index();
            $table->string('invoiceid', 6)->index();
            $table->decimal('received_amount', 12, 2)->default(0);
            $table->decimal('tds_amount', 12, 2)->default(0);
            $table->timestamps();
            $table->unique(['paymentid', 'invoiceid'], 'payment_details_payment_invoice_unique');
        });

        if (Schema::hasColumn('payments', 'type')) {
            DB::table('payments')
                ->where('type', 'tds')
                ->update(['tds_amount' => DB::raw('received_amount')]);
        }

        $payments = DB::table('payments')
            ->select(['paymentid', 'accountid', 'clientid', 'invoiceid', 'received_amount', 'tds_amount'])
            ->whereNotNull('invoiceid')
            ->orderBy('paymentid')
            ->get();

        foreach ($payments as $payment) {
            DB::table('payment_details')->insert([
                'detailid' => strtoupper(substr(bin2hex(random_bytes(8)), 0, 20)),
                'accountid' => (string) ($payment->accountid ?? ''),
                'clientid' => (string) ($payment->clientid ?? ''),
                'paymentid' => (string) ($payment->paymentid ?? ''),
                'invoiceid' => (string) ($payment->invoiceid ?? ''),
                'received_amount' => (float) ($payment->received_amount ?? 0),
                'tds_amount' => (float) ($payment->tds_amount ?? 0),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('payments', function (Blueprint $table) {
            $dropColumns = [];
            if (Schema::hasColumn('payments', 'invoiceid')) {
                $dropColumns[] = 'invoiceid';
            }
            if (Schema::hasColumn('payments', 'type')) {
                $dropColumns[] = 'type';
            }
            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'invoiceid')) {
                $table->string('invoiceid', 6)->nullable()->after('clientid');
            }
            if (!Schema::hasColumn('payments', 'type')) {
                $table->string('type', 20)->nullable()->after('tds_amount');
            }
        });

        Schema::dropIfExists('payment_details');

        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'tds_amount')) {
                $table->dropColumn('tds_amount');
            }
        });
    }
};
