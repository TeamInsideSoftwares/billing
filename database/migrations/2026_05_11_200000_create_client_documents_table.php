<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_documents', function (Blueprint $table) {
            $table->id();
            $table->string('accountid', 10);
            $table->string('clientid', 10);
            $table->string('type', 20);
            $table->string('document_number', 100)->nullable();
            $table->date('document_date')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamps();

            $table->index(['accountid', 'clientid', 'type'], 'client_documents_lookup_idx');
        });

        if (!Schema::hasTable('orders')) {
            return;
        }

        $poRows = DB::table('orders')
            ->select('accountid', 'clientid', 'po_number as document_number', 'po_date as document_date', 'po_file as file_path', 'created_at', 'updated_at')
            ->whereNotNull('clientid')
            ->where(function ($query) {
                $query->whereNotNull('po_number')
                    ->orWhereNotNull('po_date')
                    ->orWhereNotNull('po_file');
            })
            ->get();

        $agreementRows = DB::table('orders')
            ->select('accountid', 'clientid', 'agreement_ref as document_number', 'agreement_date as document_date', 'agreement_file as file_path', 'created_at', 'updated_at')
            ->whereNotNull('clientid')
            ->where(function ($query) {
                $query->whereNotNull('agreement_ref')
                    ->orWhereNotNull('agreement_date')
                    ->orWhereNotNull('agreement_file');
            })
            ->get();

        $payload = [];

        foreach ($poRows as $row) {
            $payload[] = [
                'accountid' => $row->accountid,
                'clientid' => $row->clientid,
                'type' => 'po',
                'document_number' => $row->document_number,
                'document_date' => $row->document_date,
                'file_path' => $row->file_path,
                'created_at' => $row->created_at ?? now(),
                'updated_at' => $row->updated_at ?? now(),
            ];
        }

        foreach ($agreementRows as $row) {
            $payload[] = [
                'accountid' => $row->accountid,
                'clientid' => $row->clientid,
                'type' => 'agreement',
                'document_number' => $row->document_number,
                'document_date' => $row->document_date,
                'file_path' => $row->file_path,
                'created_at' => $row->created_at ?? now(),
                'updated_at' => $row->updated_at ?? now(),
            ];
        }

        if ($payload !== []) {
            DB::table('client_documents')->insert($payload);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('client_documents');
    }
};
