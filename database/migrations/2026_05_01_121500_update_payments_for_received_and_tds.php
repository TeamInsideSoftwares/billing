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
            if (!Schema::hasColumn('payments', 'received_amount')) {
                $table->decimal('received_amount', 12, 2)->default(0)->after('invoiceid');
            }
            if (!Schema::hasColumn('payments', 'tds_amount')) {
                $table->decimal('tds_amount', 12, 2)->default(0)->after('received_amount');
            }
        });

        if (Schema::hasColumn('payments', 'credit') || Schema::hasColumn('payments', 'debit')) {
            DB::table('payments')->select(['paymentid', 'credit', 'debit'])->orderBy('paymentid')->chunk(200, function ($rows) {
                foreach ($rows as $row) {
                    $credit = (float) ($row->credit ?? 0);
                    $debit = (float) ($row->debit ?? 0);
                    $received = max(0, $credit - $debit);

                    DB::table('payments')
                        ->where('paymentid', $row->paymentid)
                        ->update([
                            'received_amount' => $received,
                            'tds_amount' => DB::raw('COALESCE(tds_amount, 0)'),
                        ]);
                }
            });
        }

        Schema::table('payments', function (Blueprint $table) {
            $dropColumns = [];
            if (Schema::hasColumn('payments', 'debit')) {
                $dropColumns[] = 'debit';
            }
            if (Schema::hasColumn('payments', 'credit')) {
                $dropColumns[] = 'credit';
            }
            if (Schema::hasColumn('payments', 'status')) {
                $dropColumns[] = 'status';
            }

            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'debit')) {
                $table->decimal('debit', 12, 2)->default(0)->after('invoiceid');
            }
            if (!Schema::hasColumn('payments', 'credit')) {
                $table->decimal('credit', 12, 2)->default(0)->after('debit');
            }
            if (!Schema::hasColumn('payments', 'status')) {
                $table->string('status', 20)->default('completed')->after('reference_number');
            }
        });

        if (Schema::hasColumn('payments', 'received_amount')) {
            DB::table('payments')->select(['paymentid', 'received_amount'])->orderBy('paymentid')->chunk(200, function ($rows) {
                foreach ($rows as $row) {
                    $received = (float) ($row->received_amount ?? 0);

                    DB::table('payments')
                        ->where('paymentid', $row->paymentid)
                        ->update([
                            'credit' => $received,
                            'debit' => 0,
                            'status' => 'completed',
                        ]);
                }
            });
        }

        Schema::table('payments', function (Blueprint $table) {
            $dropColumns = [];
            if (Schema::hasColumn('payments', 'tds_amount')) {
                $dropColumns[] = 'tds_amount';
            }
            if (Schema::hasColumn('payments', 'received_amount')) {
                $dropColumns[] = 'received_amount';
            }
            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
