<?php

namespace App\Models;

use App\Models\Concerns\HasAlphaNumericId;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'accountid',
    'invoiceid',
    'clientid',
    'from_email',
    'to_email',
    'subject',
    'body',
    'attachment_type',
    'attachment_path',
    'custom_attachment_path',
    'phone_number',
    'channel',
    'status',
    'created_by',
])]
class InvoiceEmail extends Model
{
    use HasAlphaNumericId;

    protected $table = 'invoice_emails';
    protected $primaryKey = 'invoice_emailid';
    public $incrementing = false;
    protected $keyType = 'string';

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoiceid', 'invoiceid');
    }
}
