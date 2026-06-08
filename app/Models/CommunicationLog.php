<?php

namespace App\Models;

use App\Models\Concerns\HasAlphaNumericId;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'accountid',
    'invoiceid',
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
])]
class CommunicationLog extends Model
{
    use HasAlphaNumericId;

    protected $table = 'communication_logs';

    protected $primaryKey = 'logid';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = true;

    protected function idLength(): int
    {
        return 6;
    }

    protected function casts(): array
    {
        return [];
    }
}
