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

        static::created(function (self $item): void {
            if (! empty($item->orderid)) {
                $invoiceNumber = $item->invoice?->invoice_number ?? $item->invoiceid;
                $item->logOrderTimeline(
                    orderId: $item->orderid,
                    actionType: 'invoice_item_billed',
                    description: "Billed on Invoice #{$invoiceNumber} (Amount: {$item->amount}, Qty: {$item->quantity}, Discount: {$item->discount_amount})"
                );
            }
        });

        static::updated(function (self $item): void {
            $invoiceNumber = $item->invoice?->invoice_number ?? $item->invoiceid;

            if ($item->wasChanged('orderid')) {
                $newOrderId = $item->orderid;
                if (! empty($newOrderId)) {
                    $item->logOrderTimeline(
                        orderId: $newOrderId,
                        actionType: 'invoice_item_billed',
                        description: "Billed on Invoice #{$invoiceNumber} (Amount: {$item->amount}, Qty: {$item->quantity}, Discount: {$item->discount_amount})"
                    );
                }
            } elseif (! empty($item->orderid)) {
                $financialFields = ['quantity', 'unit_price', 'discount_percent', 'discount_amount', 'amount'];
                $changed = false;
                foreach ($financialFields as $field) {
                    if ($item->wasChanged($field)) {
                        $changed = true;
                        break;
                    }
                }

                if ($changed) {
                    $item->logOrderTimeline(
                        orderId: $item->orderid,
                        actionType: 'invoice_item_updated',
                        description: "Invoice item details updated on Invoice #{$invoiceNumber} (Amount: {$item->amount}, Qty: {$item->quantity}, Discount: {$item->discount_amount})"
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
