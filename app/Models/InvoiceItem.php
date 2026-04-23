<?php

namespace App\Models;

use App\Models\Concerns\HasAlphaNumericId;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'invoiceid',
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
    'line_total',
    'amount',
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
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'discount_percent' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'amount' => 'decimal:2',
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
        });
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
