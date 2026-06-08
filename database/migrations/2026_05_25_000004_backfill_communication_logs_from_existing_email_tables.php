<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('communication_logs')) {
            return;
        }

        $idColumn = Schema::hasColumn('communication_logs', 'logid') ? 'logid' : 'communication_logid';

        if (Schema::hasTable('invoice_emails')) {
            DB::table('invoice_emails')->orderBy('created_at')->chunk(200, function ($rows) use ($idColumn) {
                foreach ($rows as $row) {
                    $id = $this->nextId($idColumn);
                    DB::table('communication_logs')->insert([
                        $idColumn => $id,
                        'accountid' => $row->accountid,
                        'invoiceid' => $row->invoiceid,
                        'quotationid' => null,
                        'clientid' => $row->clientid,
                        'from_email' => $row->from_email,
                        'to_email' => $row->to_email,
                        'cc_email' => $row->cc_email ?? null,
                        'phone_number' => $row->phone_number ?? null,
                        'subject' => $row->subject,
                        'body' => $row->body,
                        'attachment_type' => $row->attachment_type,
                        'attachment_path' => $row->attachment_path,
                        'custom_attachment_path' => $row->custom_attachment_path,
                        'status' => $row->status ?? 'draft',
                        'channel' => $row->channel ?? 'email',
                        'created_by' => $row->created_by ?? null,
                        'sent_at' => $row->sent_at ?? null,
                        'created_at' => $row->created_at ?? now(),
                        'updated_at' => $row->updated_at ?? now(),
                    ]);
                }
            });
        }

        if (Schema::hasTable('quotation_emails')) {
            DB::table('quotation_emails')->orderBy('created_at')->chunk(200, function ($rows) use ($idColumn) {
                foreach ($rows as $row) {
                    $id = $this->nextId($idColumn);
                    DB::table('communication_logs')->insert([
                        $idColumn => $id,
                        'accountid' => $row->accountid,
                        'invoiceid' => null,
                        'quotationid' => $row->quotationid,
                        'clientid' => $row->clientid,
                        'from_email' => $row->from_email,
                        'to_email' => $row->to_email,
                        'cc_email' => $row->cc_email ?? null,
                        'phone_number' => $row->phone_number ?? null,
                        'subject' => $row->subject,
                        'body' => $row->body,
                        'attachment_type' => $row->attachment_type,
                        'attachment_path' => $row->attachment_path,
                        'custom_attachment_path' => $row->custom_attachment_path,
                        'status' => $row->status ?? 'draft',
                        'channel' => $row->channel ?? 'email',
                        'created_by' => $row->created_by ?? null,
                        'sent_at' => $row->sent_at ?? null,
                        'created_at' => $row->created_at ?? now(),
                        'updated_at' => $row->updated_at ?? now(),
                    ]);
                }
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('communication_logs')) {
            return;
        }

        DB::table('communication_logs')->truncate();
    }

    private function nextId(string $idColumn): string
    {
        do {
            $id = strtoupper(Str::random(6));
            $exists = DB::table('communication_logs')->where($idColumn, $id)->exists();
        } while ($exists);

        return $id;
    }
};
