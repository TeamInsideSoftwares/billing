<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('accounts', 'allow_sync')) {
                $table->boolean('allow_sync')->default(false)->after('status');
            }

            if (!Schema::hasColumn('accounts', 'expires_at')) {
                $table->date('expires_at')->nullable()->after('allow_sync');
            }
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            if (Schema::hasColumn('accounts', 'expires_at')) {
                $table->dropColumn('expires_at');
            }

            if (Schema::hasColumn('accounts', 'allow_sync')) {
                $table->dropColumn('allow_sync');
            }
        });
    }
};
