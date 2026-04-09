<?php

namespace App\Models;

use App\Models\Concerns\HasAlphaNumericId;
use App\Models\Concerns\HasSerialNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class AccountBillingDetail extends Model
{
    use HasAlphaNumericId, HasSerialNumber;

    protected $primaryKey = 'account_bdid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected function idLength(): int
    {
        return 6;
    }

    protected $fillable = [
        'account_bdid',
        'accountid',
        'serial_number',
        'prefix',
        'prefix_type',
        'prefix_value',
        'prefix_length',
        'prefix_separator',
        'suffix',
        'suffix_type',
        'suffix_value',
        'suffix_length',
        'number_type',
        'number_value',
        'number_length',
        'number_separator',
        'reset_on_fy',
        'billing_name',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'gstin',
        'tin',
        'authorize_signatory',
        'signature_upload',
        'billing_from_email',
        'terms_conditions',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'accountid', 'accountid');
    }

    /**
     * Override the getCurrentSerialCount from HasSerialNumber trait
     * Count existing invoices to determine the next number in sequence
     */
    protected function getCurrentSerialCount(): int
    {
        // Get the current financial year if needed for reset logic
        $currentFyId = null;
        $fy = FinancialYear::where('accountid', $this->accountid)->where('default', true)->first();
        if ($fy) {
            $currentFyId = $fy->fy_id;
        }

        // Count existing invoices for this account
        $query = Invoice::where('accountid', $this->accountid);
        
        // If reset_on_fy is enabled, only count invoices from current FY
        if ($this->reset_on_fy && $currentFyId) {
            $query->where('fy_id', $currentFyId);
        }

        return $query->count();
    }

    protected function getLastAutoIncrementValueForPart(string $part): ?int
    {
        return $this->extractMaxConfiguredNumber($this->getExistingSerialNumbers(), $part);
    }

    protected function getExistingSerialNumbers(): Collection
    {
        $query = Invoice::query()
            ->where('accountid', $this->accountid)
            ->whereNotNull('invoice_number');

        $currentFyId = FinancialYear::where('accountid', $this->accountid)
            ->where('default', true)
            ->value('fy_id');

        if ($this->reset_on_fy && $currentFyId) {
            $query->where('fy_id', $currentFyId);
        }

        return $query->pluck('invoice_number');
    }

    protected function extractMaxConfiguredNumber(Collection $numbers, string $part): ?int
    {
        $pattern = $this->buildSerialMatchingPattern($part);

        $max = null;

        foreach ($numbers as $number) {
            if (! is_string($number) || ! preg_match($pattern, $number, $matches) || ! isset($matches['target'])) {
                continue;
            }

            $value = (int) $matches['target'];
            $max = $max === null ? $value : max($max, $value);
        }

        return $max;
    }
}
