<?php

namespace App\Models;

use App\Models\Concerns\HasAlphaNumericId;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'invoiceid',
    'orderid',
    'accountid',
    'clientid',
    'itemid',
    'item_name',
    'item_description',
    'quantity',
    'unit_price',
    'tax_rate',
    'discount_percent',
    'discount_amount',
    'duration',
    'frequency',
    'no_of_users',
    'start_date',
    'end_date',
    'status',
    'line_total',
    'amount',
    'sequence',
])]
class InvoiceItem extends Model
{
    protected $table = 'invoice_items';

    protected $primaryKey = 'invoice_itemid';

    protected function idLength(): int
    {
        return 6;
    }

    use HasAlphaNumericId;

    protected function casts(): array
    {
        return [
            'orderid' => 'string',
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'discount_percent' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'amount' => 'decimal:2',
            'sequence' => 'integer',
            'no_of_users' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        $formatValue = function ($val) {
            $val = (float) $val;
            if ($val == (int) $val) {
                return number_format($val, 0);
            }

            return rtrim(rtrim(number_format($val, 2), '0'), '.');
        };

        /**
         * Calculate the true billed amount using the same rounding as the invoice totals:
         * - discount value floors down  (matches JS roundDiscountDown / PHP roundDiscountDown)
         * - tax amount ceilings up      (matches JS roundTaxUp        / PHP roundTaxUp)
         *
         * amount           = raw line total (pre-discount, pre-tax): qty × unit_price × users × duration
         * discount_percent = percentage discount
         * tax_rate         = tax percentage applied on the post-discount total
         */
        $billedAmount = function (self $item): float {
            $lineTotal = (float) $item->amount;
            $discountPercent = (float) ($item->discount_percent ?? 0);
            $taxRate = (float) ($item->tax_rate ?? 0);

            $discountValue = floor($lineTotal * $discountPercent / 100);
            $discounted = max(0, $lineTotal - $discountValue);
            $tax = ceil($discounted * $taxRate / 100);

            return $discounted + $tax;
        };

        static::creating(function (self $item): void {
            if (empty($item->accountid) || empty($item->clientid)) {
                $invoice = Invoice::query()
                    ->select(['accountid', 'clientid'])
                    ->where('invoiceid', $item->invoiceid)
                    ->first();

                if ($invoice) {
                    $item->accountid = $item->accountid ?: $invoice->accountid;
                    $item->clientid = $item->clientid ?: $invoice->clientid;
                }
            }

            if (empty($item->status)) {
                $item->status = 'active';
            }
        });

        static::created(function (self $item) use ($formatValue, $billedAmount): void {
            if (! empty($item->orderid)) {
                $invoiceNumber = $item->invoice?->invoice_number ?? $item->invoiceid;
                $amount = $formatValue($billedAmount($item));
                $qty = $formatValue($item->quantity);
                $item->logOrderTimeline(
                    orderId: $item->orderid,
                    actionType: 'invoice_item_billed',
                    description: "Billed on Invoice #{$invoiceNumber} (Amount: {$amount}, Qty: {$qty})"
                );
            }
        });

        static::updated(function (self $item) use ($formatValue, $billedAmount): void {
            $invoiceNumber = $item->invoice?->invoice_number ?? $item->invoiceid;

            if ($item->wasChanged('orderid')) {
                $newOrderId = $item->orderid;
                if (! empty($newOrderId)) {
                    $amount = $formatValue($billedAmount($item));
                    $qty = $formatValue($item->quantity);
                    $item->logOrderTimeline(
                        orderId: $newOrderId,
                        actionType: 'invoice_item_billed',
                        description: "Billed on Invoice #{$invoiceNumber} (Amount: {$amount}, Qty: {$qty})"
                    );
                }
            } elseif (! empty($item->orderid)) {
                $changes = [];

                // Financial changes — show the new billed amount and qty
                $financialFields = ['quantity', 'unit_price', 'discount_percent', 'discount_amount', 'amount', 'tax_rate', 'no_of_users'];
                $financialChanged = false;
                foreach ($financialFields as $field) {
                    if ($item->wasChanged($field)) {
                        $financialChanged = true;
                        break;
                    }
                }
                if ($financialChanged) {
                    $amount = $formatValue($billedAmount($item));
                    $qty = $formatValue($item->quantity);
                    $changes[] = "Amount: {$amount}, Qty: {$qty}";
                }

                // Frequency change
                if ($item->wasChanged('frequency')) {
                    $old = $item->getOriginal('frequency') ?: 'None';
                    $new = $item->frequency ?: 'None';
                    $changes[] = "Frequency: {$old} → {$new}";
                }

                // Duration change
                if ($item->wasChanged('duration')) {
                    $old = $formatValue($item->getOriginal('duration') ?? 0);
                    $new = $formatValue($item->duration ?? 0);
                    $changes[] = "Duration: {$old} → {$new}";
                }

                // Date range changes
                $dateChanged = $item->wasChanged('start_date') || $item->wasChanged('end_date');
                if ($dateChanged) {
                    $startDate = $item->start_date?->format('d M Y') ?? '-';
                    $endDate = $item->end_date?->format('d M Y') ?? '-';
                    $changes[] = "Period: {$startDate} to {$endDate}";
                }

                // Description change
                if ($item->wasChanged('item_description')) {
                    $newDesc = trim($item->item_description ?? '');
                    $changes[] = 'Description updated'.($newDesc !== '' ? ": {$newDesc}" : ' (cleared)');
                }

                if ($changes !== []) {
                    $detail = implode(', ', $changes);
                    $item->logOrderTimeline(
                        orderId: $item->orderid,
                        actionType: 'invoice_item_updated',
                        description: "Invoice item updated on Invoice #{$invoiceNumber} ({$detail})"
                    );
                }
            }
        });

        static::deleted(function (self $item): void {
            if (! empty($item->orderid)) {
                $invoiceNumber = $item->invoice?->invoice_number ?? $item->invoiceid;
                $item->logOrderTimeline(
                    orderId: $item->orderid,
                    actionType: 'invoice_item_deleted',
                    description: "Billed item removed: {$item->item_name} on Invoice #{$invoiceNumber}"
                );
            }
        });
    }

    public function logOrderTimeline(string $orderId, string $actionType, string $description): void
    {
        OrderTimeline::create([
            'accountid' => $this->accountid ?: (Invoice::where('invoiceid', $this->invoiceid)->value('accountid') ?? 'SYSTEM'),
            'clientid' => $this->clientid ?: (Invoice::where('invoiceid', $this->invoiceid)->value('clientid') ?? (Order::where('orderid', $orderId)->value('clientid') ?? 'SYSTEM')),
            'orderid' => $orderId,
            'action_type' => $actionType,
            'field_name' => null,
            'old_value' => null,
            'new_value' => null,
            'description' => $description,
            'created_by' => (string) (auth()->user()?->userid ?? auth()->id() ?? 'SYSTEM'),
        ]);
    }

    public function getLineTotalAttribute(): mixed
    {
        return $this->attributes['amount'] ?? 0;
    }

    public function setLineTotalAttribute(mixed $value): void
    {
        $this->attributes['amount'] = $value;
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoiceid');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'itemid', 'itemid');
    }

    public function service(): BelongsTo
    {
        return $this->item();
    }
}
