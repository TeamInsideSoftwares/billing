<?php

namespace App\Models;

use App\Models\Concerns\HasAlphaNumericId;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'accountid',
    'fy_id',
    'clientid',
    'orderid',
    'converted_from_invoiceid',
    'invoice_number',
    'invoice_type',
    'invoice_for',
    'status',
    'issue_date',
    'due_date',
    'subtotal',
    'tax_total',
    'discount_total',
    'grand_total',
    'amount_paid',
    'balance_due',
    'currency_code',
    'notes',
    'terms',
    'sent_at',
    'paid_at',
    'created_by',
])]
class Invoice extends Model
{
    protected $primaryKey = 'invoiceid';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;
    
    public function getRouteKeyName(): string
    {
        return 'invoiceid';
    }

    protected function idLength(): int
    {
        return 6;
    }

    use HasAlphaNumericId;

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'sent_at' => 'datetime',
            'paid_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'balance_due' => 'decimal:2',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'accountid');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'clientid');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'orderid');
    }

    public function convertedFromInvoice(): BelongsTo
    {
        return $this->belongsTo(self::class, 'converted_from_invoiceid', 'invoiceid');
    }

    public function convertedTaxInvoices(): HasMany
    {
        return $this->hasMany(self::class, 'converted_from_invoiceid', 'invoiceid')
            ->where('invoice_type', 'tax');
    }

    public function convertedTaxInvoice(): HasOne
    {
        return $this->hasOne(self::class, 'converted_from_invoiceid', 'invoiceid')
            ->where('invoice_type', 'tax');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class, 'invoiceid');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'invoiceid');
    }

    public function isProforma(): bool
    {
        return strtolower($this->invoice_type ?? 'proforma') === 'proforma';
    }

    public function canConvertToTaxInvoice(): bool
    {
        return $this->isProforma() && $this->convertedTaxInvoice()->doesntExist();
    }

    public function hasPaymentsRecorded(): bool
    {
        return (float) ($this->amount_paid ?? 0) > 0 || $this->payments()->exists();
    }

}
