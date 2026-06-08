<?php

namespace App\Models;

use App\Models\Concerns\HasAlphaNumericId;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

#[Fillable([
    'accountid',
    'fy_id',
    'clientid',
    'receipt_number',
    'received_amount',
    'tds_amount',
    'tds_input_type',
    'payment_date',
    'mode',
    'reference_number',
    'description',
    'status',
])]
class Payment extends Model
{
    protected $primaryKey = 'paymentid';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'accountid',
        'fy_id',
        'clientid',
        'receipt_number',
        'received_amount',
        'tds_amount',
        'tds_input_type',
        'payment_date',
        'mode',
        'reference_number',
        'description',
        'status',
    ];

    protected $casts = [
        'payment_date' => 'date',
    ];

    public function getRouteKeyName(): string
    {
        return 'paymentid';
    }

    protected function idLength(): int
    {
        return 6;
    }

    use HasAlphaNumericId;

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'accountid');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'clientid');
    }

    public function paymentDetails(): HasMany
    {
        return $this->hasMany(PaymentDetail::class, 'paymentid', 'paymentid');
    }

    public function invoices(): HasManyThrough
    {
        return $this->hasManyThrough(
            Invoice::class,
            PaymentDetail::class,
            'paymentid',
            'invoiceid',
            'paymentid',
            'invoiceid',
        );
    }

    public function getInvoiceAttribute(): ?Invoice
    {
        if ($this->relationLoaded('invoices')) {
            return $this->invoices->first();
        }

        $invoiceFromDetail = $this->paymentDetails()->with('invoice')->first()?->invoice;
        if ($invoiceFromDetail) {
            return $invoiceFromDetail;
        }

        return $this->invoices()->first();
    }
}
