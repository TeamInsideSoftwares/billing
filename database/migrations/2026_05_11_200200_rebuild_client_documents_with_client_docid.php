<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('client_documents')) {
            return;
        }

        $columns = Schema::getColumnListing('client_documents');
        if (in_array('client_docid', $columns, true) && !in_array('id', $columns, true)) {
            return;
        }

        $generatedIds = [];
        $nextClientDocId = static function () use (&$generatedIds): string {
            do {
                $candidate = strtoupper(Str::random(6));
            } while (isset($generatedIds[$candidate]));

            $generatedIds[$candidate] = true;

            return $candidate;
        };

        Schema::create('client_documents_rebuild', function (Blueprint $table) {
            $table->string('client_docid', 6)->primary();
            $table->string('accountid', 10);
            $table->string('clientid', 6);
            $table->string('type', 20);
            $table->string('document_number', 100)->nullable();
            $table->date('document_date')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamps();

            $table->index(['accountid', 'clientid', 'type'], 'client_documents_lookup_idx');
        });

        $sourceRows = DB::table('client_documents')
            ->orderBy('id')
            ->get();

        $payload = [];
        foreach ($sourceRows as $row) {
            $payload[] = [
                'client_docid' => !empty($row->client_docid) ? (string) $row->client_docid : $nextClientDocId(),
                'accountid' => $row->accountid,
                'clientid' => $row->clientid,
                'type' => $row->type,
                'document_number' => $row->document_number,
                'document_date' => $row->document_date,
                'file_path' => $row->file_path,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ];
        }

        if ($payload !== []) {
            DB::table('client_documents_rebuild')->insert($payload);
        }

        Schema::drop('client_documents');
        Schema::rename('client_documents_rebuild', 'client_documents');
    }

    public function down(): void
    {
        if (!Schema::hasTable('client_documents')) {
            return;
        }

        $columns = Schema::getColumnListing('client_documents');
        if (in_array('id', $columns, true)) {
            return;
        }

        Schema::create('client_documents_legacy', function (Blueprint $table) {
            $table->id();
            $table->string('accountid', 10);
            $table->string('clientid', 6);
            $table->string('type', 20);
            $table->string('document_number', 100)->nullable();
            $table->date('document_date')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamps();

            $table->index(['accountid', 'clientid', 'type'], 'client_documents_lookup_idx');
        });

        $sourceRows = DB::table('client_documents')
            ->orderBy('created_at')
            ->get();

        $payload = [];
        foreach ($sourceRows as $row) {
            $payload[] = [
                'accountid' => $row->accountid,
                'clientid' => $row->clientid,
                'type' => $row->type,
                'document_number' => $row->document_number,
                'document_date' => $row->document_date,
                'file_path' => $row->file_path,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ];
        }

        if ($payload !== []) {
            DB::table('client_documents_legacy')->insert($payload);
        }

        Schema::drop('client_documents');
        Schema::rename('client_documents_legacy', 'client_documents');
    }
};
