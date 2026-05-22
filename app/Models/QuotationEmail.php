<?php

namespace App\Models;

use App\Models\Concerns\HasAlphaNumericId;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'accountid',
    'quotationid',
    'clientid',
    'from_email',
    'to_email',
    'cc_email',
    'phone_number',
    'subject',
    'body',
    'attachment_type',
    'attachment_path',
    'custom_attachment_path',
    'status',
    'channel',
    'created_by',
    'sent_at',
])]
class QuotationEmail extends Model
{
    use HasAlphaNumericId;

    protected $table = 'quotation_emails';
    protected $primaryKey = 'quotation_emailid';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected function idLength(): int
    {
        return 6;
    }

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class, 'quotationid', 'quotationid');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'clientid', 'clientid');
    }
}
