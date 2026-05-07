<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $rows = DB::table('account_templates')
            ->select('accountid', 'template_type', 'channel', DB::raw('COUNT(*) as total'))
            ->groupBy('accountid', 'template_type', 'channel')
            ->having('total', '>', 1)
            ->get();

        foreach ($rows as $row) {
            $ids = DB::table('account_templates')
                ->where('accountid', $row->accountid)
                ->where('template_type', $row->template_type)
                ->where('channel', $row->channel)
                ->orderByDesc('updated_at')
                ->orderByDesc('created_at')
                ->orderByDesc('templateid')
                ->pluck('templateid')
                ->toArray();

            $keep = array_shift($ids);
            if (!empty($ids)) {
                DB::table('account_templates')->whereIn('templateid', $ids)->delete();
            }
        }

        $hasPerTemplate = !empty(DB::select("SHOW INDEX FROM account_templates WHERE Key_name = 'account_templates_unique_per_template'"));
        if ($hasPerTemplate) {
            DB::statement('ALTER TABLE account_templates DROP INDEX account_templates_unique_per_template');
        }

        $hasPerContext = !empty(DB::select("SHOW INDEX FROM account_templates WHERE Key_name = 'account_templates_unique_per_context'"));
        if (!$hasPerContext) {
            Schema::table('account_templates', function (Blueprint $table) {
                $table->unique(['accountid', 'channel', 'template_type'], 'account_templates_unique_per_context');
            });
        }
    }

    public function down(): void
    {
        $hasPerContext = !empty(DB::select("SHOW INDEX FROM account_templates WHERE Key_name = 'account_templates_unique_per_context'"));
        if ($hasPerContext) {
            DB::statement('ALTER TABLE account_templates DROP INDEX account_templates_unique_per_context');
        }
        $hasPerTemplate = !empty(DB::select("SHOW INDEX FROM account_templates WHERE Key_name = 'account_templates_unique_per_template'"));
        if (!$hasPerTemplate) {
            Schema::table('account_templates', function (Blueprint $table) {
                $table->unique(['accountid', 'channel', 'template_type', 'template_id'], 'account_templates_unique_per_template');
            });
        }
    }
};
