<?php

namespace App\Models;

use App\Models\Concerns\HasAlphaNumericId;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'accountid',
    'groupid',
    'logo_path',
    'business_name',
    'contact_name',
    'primary_email',
    'email',
    'type',
    'phone',
    'whatsapp_number',
    'billing_email',
    'tax_number',
    'status',
    'currency',
    'address_line_1',
    'address_line_2',
    'city',
    'state',
    'postal_code',
    'country',
    'notes',
    'bd_id',
])]
class Client extends Model
{
    protected $primaryKey = 'clientid';
    public function getRouteKeyName(): string
    {
        return 'clientid';
    }

    protected function idLength(): int
    {
        return 10;
    }

    use HasAlphaNumericId;

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'accountid', 'accountid');
    }

    public function billingDetail(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ClientBillingDetail::class, 'bd_id', 'bd_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'clientid');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'clientid');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'clientid');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'clientid');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ClientDocument::class, 'clientid');
    }

    public function latestPurchaseOrder(): ?ClientDocument
    {
        return $this->documents()
            ->where('type', 'po')
            ->where('status', 'running')
            ->latest('document_date')
            ->latest('created_at')
            ->first();
    }

    public function latestAgreement(): ?ClientDocument
    {
        return $this->documents()
            ->where('type', 'agreement')
            ->where('status', 'running')
            ->latest('document_date')
            ->latest('created_at')
            ->first();
    }

    public function scopeRegular(Builder $query): Builder
    {
        return $query->where('type', 'regular');
    }

    public function scopeTrial(Builder $query): Builder
    {
        return $query->where('type', 'trial');
    }
}
