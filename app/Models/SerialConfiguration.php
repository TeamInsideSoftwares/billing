<?php

namespace App\Models;

use App\Models\Concerns\HasAlphaNumericId;
use App\Models\Concerns\HasSerialNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class SerialConfiguration extends Model
{
    use HasAlphaNumericId, HasSerialNumber;

    protected $primaryKey = 'serial_configid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected function idLength(): int
    {
        return 6;
    }

    protected $fillable = [
        'serial_configid',
        'accountid',
        'document_type',
        'fy_id',
        'prefix_type',
        'prefix_value',
        'prefix_length',
        'prefix_separator',
        'number_type',
        'number_value',
        'number_length',
        'number_separator',
        'suffix_type',
        'suffix_value',
        'suffix_length',
        'reset_on_fy',
    ];

    protected $casts = [
        'reset_on_fy' => 'boolean',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'accountid', 'accountid');
    }

    public function financialYear(): BelongsTo
    {
        return $this->belongsTo(FinancialYear::class, 'fy_id', 'fy_id');
    }

    /**
     * Get the current serial count based on existing documents
     */
    protected function getCurrentSerialCount(): int
    {
        $currentFyId = $this->fy_id ?? FinancialYear::where('accountid', $this->accountid)
            ->where('default', true)
            ->value('fy_id');

        if ($this->document_type === 'tax_invoice') {
            return $this->getTaxInvoiceCount($currentFyId);
        }

        if ($this->document_type === 'proforma_invoice') {
            return $this->getProformaInvoiceCount($currentFyId);
        }

        if ($this->document_type === 'quotation') {
            return $this->getQuotationCount($currentFyId);
        }

        return 0;
    }

    protected function getTaxInvoiceCount(?string $fyId): int
    {
        $query = Invoice::where('accountid', $this->accountid)
            ->where('invoice_type', 'tax');

        if ($this->reset_on_fy && $fyId) {
            $query->where('fy_id', $fyId);
        }

        return $query->count();
    }

    protected function getProformaInvoiceCount(?string $fyId): int
    {
        $query = Invoice::where('accountid', $this->accountid)
            ->where('invoice_type', 'proforma');

        if ($this->reset_on_fy && $fyId) {
            $query->where('fy_id', $fyId);
        }

        return $query->count();
    }

    protected function getQuotationCount(?string $fyId): int
    {
        $query = Quotation::where('accountid', $this->accountid);

        if ($this->reset_on_fy && $fyId) {
            $query->where('fy_id', $fyId);
        }

        return $query->count();
    }

    protected function getLastAutoIncrementValueForPart(string $part): ?int
    {
        return $this->extractMaxConfiguredNumber($this->getExistingSerialNumbers(), $part);
    }

    protected function getExistingSerialNumbers(): Collection
    {
        $currentFyId = $this->fy_id ?? FinancialYear::where('accountid', $this->accountid)
            ->where('default', true)
            ->value('fy_id');

        if ($this->document_type === 'tax_invoice') {
            return $this->getTaxInvoiceNumbers($currentFyId);
        }

        if ($this->document_type === 'proforma_invoice') {
            return $this->getProformaInvoiceNumbers($currentFyId);
        }

        if ($this->document_type === 'quotation') {
            return $this->getQuotationNumbers($currentFyId);
        }

        return collect();
    }

    protected function getTaxInvoiceNumbers(?string $fyId): Collection
    {
        $query = Invoice::query()
            ->where('accountid', $this->accountid)
            ->where('invoice_type', 'tax')
            ->whereNotNull('invoice_number');

        if ($this->reset_on_fy && $fyId) {
            $query->where('fy_id', $fyId);
        }

        return $query->pluck('invoice_number');
    }

    protected function getProformaInvoiceNumbers(?string $fyId): Collection
    {
        $query = Invoice::query()
            ->where('accountid', $this->accountid)
            ->where('invoice_type', 'proforma')
            ->whereNotNull('invoice_number');

        if ($this->reset_on_fy && $fyId) {
            $query->where('fy_id', $fyId);
        }

        return $query->pluck('invoice_number');
    }

    protected function getQuotationNumbers(?string $fyId): Collection
    {
        $query = Quotation::query()
            ->where('accountid', $this->accountid)
            ->whereNotNull('quotation_number');

        if ($this->reset_on_fy && $fyId) {
            $query->where('fy_id', $fyId);
        }

        return $query->pluck('quotation_number');
    }

    protected function extractMaxConfiguredNumber(Collection $numbers, string $part): ?int
    {
        $pattern = $this->buildSerialMatchingPattern($part);

        $max = null;

        foreach ($numbers as $number) {
            if (!is_string($number) || !preg_match($pattern, $number, $matches) || !isset($matches['target'])) {
                continue;
            }

            $value = (int) $matches['target'];
            $max = $max === null ? $value : max($max, $value);
        }

        return $max;
    }
}
