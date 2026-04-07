<?php

namespace App\Models;

use App\Models\Concerns\HasAlphaNumericId;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'invoiceid',
    'itemid',
    'item_name',
    'item_description',
    'quantity',
    'unit_price',
    'tax_rate',
    'duration',
    'frequency',
    'no_of_users',
    'start_date',
    'end_date',
    'line_total',
    'sort_order',
])]
class InvoiceItem extends Model
{
    protected $primaryKey = 'invoiceitemid';

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
            'line_total' => 'decimal:2',
            'no_of_users' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
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
