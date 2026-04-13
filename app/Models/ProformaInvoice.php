<?php

namespace App\Models;

use App\Models\Concerns\HasAlphaNumericId;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'accountid',
    'fy_id',
    'clientid',
    'orderid',
    'proformaid',
    'invoice_number',
    'invoice_title',
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
class ProformaInvoice extends Model
{
    use HasAlphaNumericId;

    protected $table = 'proforma_invoices';
    protected $primaryKey = 'proformaid';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    public function getRouteKeyName(): string
    {
        return 'proformaid';
    }

    protected function idLength(): int
    {
        return 6;
    }

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
            'created_at' => 'datetime:Y-m-d H:i:s',
            'updated_at' => 'datetime:Y-m-d H:i:s',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProformaInvoiceItem::class, 'proformaid');
    }

    public function convertedTaxInvoice(): HasOne
    {
        return $this->hasOne(Invoice::class, 'proformaid', 'proformaid');
    }

    public function convertedTaxInvoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'proformaid', 'proformaid');
    }

    public function isProforma(): bool
    {
        return true;
    }

    public function canConvertToTaxInvoice(): bool
    {
        return $this->convertedTaxInvoice()->doesntExist();
    }

    public function hasPaymentsRecorded(): bool
    {
        return false;
    }
}
