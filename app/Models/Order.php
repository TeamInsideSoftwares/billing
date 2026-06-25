<?php

namespace App\Models;

use App\Models\Concerns\HasAlphaNumericId;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
    'type',
])]
class Order extends Model
{
    use HasAlphaNumericId;

    protected $table = 'orders';

    protected $primaryKey = 'orderid';

    public function timeline(): HasMany
    {
        return $this->hasMany(OrderTimeline::class, 'orderid', 'orderid')->orderBy('created_at', 'desc');
    }

    protected static function booted(): void
    {
        static::created(function (self $order): void {
            $order->logTimeline(
                actionType: 'order_created',
                description: "Order #{$order->order_number} created for client ".($order->client?->business_name ?? $order->clientid)
            );
        });

        static::updated(function (self $order): void {
            $fields = [
                'status' => ['label' => 'Status', 'action' => 'status_changed'],
                'item_description' => ['label' => 'Description', 'action' => 'description_changed'],
                'quantity' => ['label' => 'Quantity', 'action' => 'quantity_changed'],
                'no_of_users' => ['label' => 'Number of users', 'action' => 'users_changed'],
                'start_date' => ['label' => 'Start date', 'action' => 'start_date_changed'],
                'end_date' => ['label' => 'Expiry date', 'action' => 'expiry_date_changed'],
                'delivery_date' => ['label' => 'Delivery date', 'action' => 'delivery_date_changed'],
                'client_docid' => ['label' => 'Purchase Order', 'action' => 'po_changed'],
            ];

            foreach ($fields as $field => $meta) {
                if ($order->isDirty($field)) {
                    $old = $order->getOriginal($field);
                    $new = $order->$field;

                    $oldFormatted = self::formatTimelineValue($old);
                    $newFormatted = self::formatTimelineValue($new);

                    if ($field === 'client_docid') {
                        if ($old) {
                            $oldDoc = ClientDocument::find($old);
                            $oldFormatted = $oldDoc ? ($oldDoc->document_number ?: ($oldDoc->title ?: $old)) : $old;
                        }
                        if ($new) {
                            $newDoc = ClientDocument::find($new);
                            $newFormatted = $newDoc ? ($newDoc->document_number ?: ($newDoc->title ?: $new)) : $new;
                        }
                    }

                    $desc = "{$meta['label']} changed from {$oldFormatted} to {$newFormatted}";

                    if (in_array($field, ['start_date', 'end_date', 'delivery_date', 'client_docid'])) {
                        if (is_null($old) || $old === '') {
                            $desc = "{$meta['label']} set to {$newFormatted}";
                        } elseif (is_null($new) || $new === '') {
                            $desc = "{$meta['label']} removed (was {$oldFormatted})";
                        }
                    }

                    $order->logTimeline(
                        actionType: $meta['action'],
                        fieldName: $field,
                        oldValue: self::formatRawValue($old),
                        newValue: self::formatRawValue($new),
                        description: $desc
                    );
                }
            }
        });
    }

    public function logTimeline(
        string $actionType,
        string $description,
        ?string $fieldName = null,
        ?string $oldValue = null,
        ?string $newValue = null
    ): void {
        OrderTimeline::create([
            'accountid' => $this->accountid,
            'clientid' => $this->clientid,
            'orderid' => $this->orderid,
            'action_type' => $actionType,
            'field_name' => $fieldName,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'description' => $description,
            'created_by' => (string) (auth()->user()?->userid ?? auth()->id() ?? 'SYSTEM'),
        ]);
    }

    private static function formatTimelineValue(mixed $value): string
    {
        if (is_null($value)) {
            return 'None';
        }
        if ($value instanceof Carbon || $value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        return (string) $value;
    }

    private static function formatRawValue(mixed $value): ?string
    {
        if (is_null($value)) {
            return null;
        }
        if ($value instanceof Carbon || $value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return (string) $value;
    }

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

    public function scopeRegular(Builder $query): Builder
    {
        return $query->where('type', 'regular');
    }

    public function scopeTrial(Builder $query): Builder
    {
        return $query->where('type', 'trial');
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

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class, 'orderid', 'orderid');
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
