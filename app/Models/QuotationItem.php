<?php

namespace App\Models;

use App\Models\Concerns\HasAlphaNumericId;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'quotationid',
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
class QuotationItem extends Model
{
    protected $table = 'quotation_items';

    protected $primaryKey = 'quo_itemid';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = true;

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
                $quotation = Quotation::query()
                    ->select(['accountid', 'clientid'])
                    ->where('quotationid', $item->quotationid)
                    ->first();

                if ($quotation) {
                    $item->accountid = $item->accountid ?: $quotation->accountid;
                    $item->clientid = $item->clientid ?: $quotation->clientid;
                }
            }

            if (empty($item->status)) {
                $item->status = 'active';
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

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class, 'quotationid');
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
