<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('order_items')) {
            return;
        }

        Schema::create('orders_new', function (Blueprint $table) {
            $table->string('orderid', 6)->primary();
            $table->string('accountid', 10);
            $table->string('clientid', 10);
            $table->string('order_number', 30)->unique();
            $table->string('status', 20)->default('draft');
            $table->string('client_docid', 6)->nullable();
            $table->string('itemid', 6)->nullable();
            $table->string('item_name', 150);
            $table->text('item_description')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedInteger('no_of_users')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('delivery_date')->nullable();
            $table->timestamps();

            $table->index(['accountid', 'status']);
            $table->index(['clientid', 'created_at']);
        });

        $headerRows = Schema::hasTable('orders')
            ? DB::table('orders')->get()->keyBy('orderid')
            : collect();

        $itemRows = DB::table('order_items')->orderBy('orderitemid')->get();
        $usedOrderIds = [];
        $usedOrderNumbers = [];
        $rowsToInsert = [];
        $invoiceOrderMap = [];

        foreach ($itemRows as $itemRow) {
            $header = $headerRows->get($itemRow->orderid);
            $sourceOrderId = (string) ($itemRow->orderid ?? '');
            $newOrderId = $this->generateUniqueOrderId($usedOrderIds);

            if ($sourceOrderId !== '' && ! array_key_exists($sourceOrderId, $invoiceOrderMap)) {
                $invoiceOrderMap[$sourceOrderId] = $newOrderId;
            }

            $candidateOrderNumber = trim((string) ($header->order_number ?? ''));
            if ($candidateOrderNumber === '') {
                $candidateOrderNumber = 'ORD-'.str_pad((string) (count($usedOrderNumbers) + 1), 4, '0', STR_PAD_LEFT);
            }

            $rowsToInsert[] = [
                'orderid' => $newOrderId,
                'accountid' => (string) ($header->accountid ?? ''),
                'clientid' => (string) ($header->clientid ?? ''),
                'order_number' => $this->generateUniqueOrderNumber($candidateOrderNumber, $usedOrderNumbers),
                'status' => (string) ($header->status ?? 'draft'),
                'client_docid' => null,
                'itemid' => isset($itemRow->itemid) ? (string) $itemRow->itemid : null,
                'item_name' => (string) ($itemRow->item_name ?? 'Item'),
                'item_description' => $itemRow->item_description ?? null,
                'quantity' => $itemRow->quantity ?? 1,
                'no_of_users' => $itemRow->no_of_users ?? null,
                'start_date' => $itemRow->start_date ?? null,
                'end_date' => $itemRow->end_date ?? null,
                'delivery_date' => $itemRow->delivery_date ?? ($header->delivery_date ?? null),
                'created_at' => $itemRow->created_at ?? ($header->created_at ?? now()),
                'updated_at' => $itemRow->updated_at ?? ($header->updated_at ?? now()),
            ];
        }

        foreach (array_chunk($rowsToInsert, 200) as $chunk) {
            DB::table('orders_new')->insert($chunk);
        }

        if (! empty($invoiceOrderMap) && Schema::hasTable('invoices') && Schema::hasColumn('invoices', 'orderid')) {
            foreach ($invoiceOrderMap as $legacyOrderId => $newOrderId) {
                DB::table('invoices')
                    ->where('orderid', $legacyOrderId)
                    ->update(['orderid' => $newOrderId]);
            }
        }

        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::rename('orders_new', 'orders');
    }

    public function down(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        Schema::rename('orders', 'orders_flat_backup');

        Schema::create('orders', function (Blueprint $table) {
            $table->string('orderid', 6)->primary();
            $table->string('accountid', 10);
            $table->string('clientid', 10);
            $table->string('order_number', 30)->unique();
            $table->string('status', 20)->default('draft');
            $table->date('order_date');
            $table->date('delivery_date')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->string('duration')->nullable();
            $table->string('frequency')->nullable();
            $table->unsignedInteger('no_of_users')->nullable();
            $table->string('sales_person_id', 10)->nullable();
            $table->string('is_verified', 10)->default('no');
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->string('orderitemid', 6)->primary();
            $table->string('orderid', 6);
            $table->string('itemid', 6)->nullable();
            $table->string('item_name', 150);
            $table->text('item_description')->nullable();
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('line_total', 12, 2)->default(0);
            $table->unsignedInteger('sort_order')->default(1);
            $table->string('frequency')->nullable();
            $table->unsignedInteger('no_of_users')->nullable();
            $table->string('duration')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('delivery_date')->nullable();
            $table->timestamps();
        });

        $rows = DB::table('orders_flat_backup')->orderBy('orderid')->get();
        foreach ($rows as $row) {
            DB::table('orders')->insert([
                'orderid' => $row->orderid,
                'accountid' => $row->accountid,
                'clientid' => $row->clientid,
                'order_number' => $row->order_number,
                'status' => $row->status,
                'order_date' => $row->created_at ? Carbon::parse($row->created_at)->toDateString() : now()->toDateString(),
                'delivery_date' => $row->delivery_date,
                'subtotal' => $row->line_total,
                'tax_total' => ceil(max(0, ((float) $row->line_total - (float) $row->discount_amount) * ((float) $row->tax_rate / 100))),
                'discount_total' => $row->discount_amount,
                'grand_total' => max(0, (float) $row->line_total - (float) $row->discount_amount + ceil(max(0, ((float) $row->line_total - (float) $row->discount_amount) * ((float) $row->tax_rate / 100)))),
                'duration' => $row->duration,
                'frequency' => $row->frequency,
                'no_of_users' => $row->no_of_users,
                'sales_person_id' => null,
                'is_verified' => $row->is_verified,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);

            DB::table('order_items')->insert([
                'orderitemid' => strtoupper(Str::random(6)),
                'orderid' => $row->orderid,
                'itemid' => $row->itemid,
                'item_name' => $row->item_name,
                'item_description' => $row->item_description,
                'quantity' => $row->quantity,
                'unit_price' => $row->unit_price,
                'tax_rate' => $row->tax_rate,
                'discount_percent' => $row->discount_percent,
                'discount_amount' => $row->discount_amount,
                'line_total' => $row->line_total,
                'sort_order' => 1,
                'frequency' => $row->frequency,
                'no_of_users' => $row->no_of_users,
                'duration' => $row->duration,
                'start_date' => $row->start_date,
                'end_date' => $row->end_date,
                'delivery_date' => $row->delivery_date,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        }

        Schema::dropIfExists('orders_flat_backup');
    }

    private function generateUniqueOrderId(array &$usedOrderIds): string
    {
        do {
            $candidate = strtoupper(Str::random(6));
        } while (in_array($candidate, $usedOrderIds, true));

        $usedOrderIds[] = $candidate;

        return $candidate;
    }

    private function generateUniqueOrderNumber(string $candidate, array &$usedOrderNumbers): string
    {
        $candidate = trim($candidate);
        if ($candidate === '') {
            $candidate = 'ORD-0001';
        }

        $value = $candidate;
        $sequence = 2;

        while (in_array($value, $usedOrderNumbers, true)) {
            if (preg_match('/^(.*?)(\d+)$/', $candidate, $matches)) {
                $value = $matches[1].str_pad((string) ((int) $matches[2] + $sequence - 1), strlen($matches[2]), '0', STR_PAD_LEFT);
            } else {
                $value = $candidate.'-'.$sequence;
            }

            $sequence++;
        }

        $usedOrderNumbers[] = $value;

        return $value;
    }
};
