<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('invoice_emails', 'templateid')) {
            Schema::table('invoice_emails', function (Blueprint $table) {
                $table->string('templateid', 6)->nullable()->after('channel');
            });
        }

        if (Schema::hasColumn('invoice_emails', 'selected_templateid')) {
            DB::statement("UPDATE invoice_emails SET templateid = selected_templateid WHERE templateid IS NULL AND selected_templateid IS NOT NULL");
        }

        Schema::table('invoice_emails', function (Blueprint $table) {
            if (Schema::hasColumn('invoice_emails', 'provider_template_id')) {
                $table->dropColumn('provider_template_id');
            }
            if (Schema::hasColumn('invoice_emails', 'provider_meta_template_id')) {
                $table->dropColumn('provider_meta_template_id');
            }
            if (Schema::hasColumn('invoice_emails', 'provider_sender_id')) {
                $table->dropColumn('provider_sender_id');
            }
            if (Schema::hasColumn('invoice_emails', 'selected_templateid')) {
                $table->dropColumn('selected_templateid');
            }
        });

        if (!$this->hasIndex('invoice_emails', 'invoice_emails_template_lookup_idx')) {
            Schema::table('invoice_emails', function (Blueprint $table) {
                $table->index(['invoiceid', 'channel', 'templateid'], 'invoice_emails_template_lookup_idx');
            });
        }
    }

    public function down(): void
    {
        if ($this->hasIndex('invoice_emails', 'invoice_emails_template_lookup_idx')) {
            Schema::table('invoice_emails', function (Blueprint $table) {
                $table->dropIndex('invoice_emails_template_lookup_idx');
            });
        }

        Schema::table('invoice_emails', function (Blueprint $table) {
            if (!Schema::hasColumn('invoice_emails', 'selected_templateid')) {
                $table->string('selected_templateid', 6)->nullable()->after('channel');
            }
            if (!Schema::hasColumn('invoice_emails', 'provider_template_id')) {
                $table->string('provider_template_id', 120)->nullable()->after('selected_templateid');
            }
            if (!Schema::hasColumn('invoice_emails', 'provider_meta_template_id')) {
                $table->string('provider_meta_template_id', 160)->nullable()->after('provider_template_id');
            }
            if (!Schema::hasColumn('invoice_emails', 'provider_sender_id')) {
                $table->string('provider_sender_id', 120)->nullable()->after('provider_meta_template_id');
            }
        });

        if (Schema::hasColumn('invoice_emails', 'templateid')) {
            DB::statement("UPDATE invoice_emails SET selected_templateid = templateid WHERE selected_templateid IS NULL AND templateid IS NOT NULL");
            Schema::table('invoice_emails', function (Blueprint $table) {
                $table->dropColumn('templateid');
            });
        }
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        $result = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
        return !empty($result);
    }
};
