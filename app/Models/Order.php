<?php

namespace App\Models;

use App\Models\Concerns\HasAlphaNumericId;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'accountid',
    'clientid',
    'order_number',
    'status',
    'client_docid',
    'itemid',
    'item_name',
    'item_description',
    'quantity',
    'no_of_users',
    'start_date',
    'end_date',
    'delivery_date',
])]
class Order extends Model
{
    use HasAlphaNumericId;

    protected $table = 'orders';

    protected $primaryKey = 'orderid';

    protected function idLength(): int
    {
        return 6;
    }

    public function getRouteKeyName(): string
    {
        return 'orderid';
    }

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'delivery_date' => 'date',
            'quantity' => 'integer',
            'no_of_users' => 'integer',
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

    public function clientDocument(): BelongsTo
    {
        return $this->belongsTo(ClientDocument::class, 'client_docid', 'client_docid');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'itemid', 'itemid');
    }

    public function invoices(): BelongsToMany
    {
        return $this->belongsToMany(
            Invoice::class,
            'invoice_items',
            'orderid',
            'invoiceid',
            'orderid',
            'invoiceid'
        )->distinct();
    }

    public function getSubtotalAttribute(): float
    {
        return 0.0;
    }

    public function getDiscountTotalAttribute(): float
    {
        return 0.0;
    }

    public function getTaxTotalAttribute(): float
    {
        return 0.0;
    }

    public function getGrandTotalAttribute(): float
    {
        return 0.0;
    }

    public static function generateNextOrderNumberForAccount(string $accountId): string
    {
        $serialConfig = SerialConfiguration::where('accountid', $accountId)
            ->where('document_type', 'order')
            ->first();

        if ($serialConfig) {
            $candidate = $serialConfig->generateNextSerialNumber();

            return self::ensureUniqueOrderNumberForAccount($candidate !== '' ? $candidate : 'ORD-0001', $accountId);
        }

        $count = self::where('accountid', $accountId)->count();

        return self::ensureUniqueOrderNumberForAccount('ORD-'.str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT), $accountId);
    }

    private static function ensureUniqueOrderNumberForAccount(string $candidate, string $accountId): string
    {
        $candidate = trim($candidate) ?: 'ORD-0001';
        $number = $candidate;
        $sequence = 2;

        while (self::where('accountid', $accountId)->where('order_number', $number)->exists()) {
            if (preg_match('/^(.*?)(\d+)$/', $candidate, $matches)) {
                $number = $matches[1].str_pad((string) ((int) $matches[2] + $sequence - 1), strlen($matches[2]), '0', STR_PAD_LEFT);
            } else {
                $number = $candidate.'-'.$sequence;
            }
            $sequence++;
        }

        return $number;
    }
}
