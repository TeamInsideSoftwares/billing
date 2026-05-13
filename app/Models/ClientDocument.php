<?php

namespace App\Models;

use App\Models\Concerns\HasAlphaNumericId;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'accountid',
    'clientid',
    'type',
    'status',
    'title',
    'document_number',
    'document_date',
    'file_path',
])]
class ClientDocument extends Model
{
    use HasAlphaNumericId;

    protected $primaryKey = 'client_docid';

    protected $fillable = [
        'client_docid',
        'accountid',
        'clientid',
        'type',
        'status',
        'title',
        'document_number',
        'document_date',
        'file_path',
    ];

    protected function idLength(): int
    {
        return 6;
    }

    protected function casts(): array
    {
        return [
            'document_date' => 'date',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'client_docid';
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'clientid');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'accountid', 'accountid');
    }
}
