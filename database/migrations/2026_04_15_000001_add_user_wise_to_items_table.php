<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('items') && ! Schema::hasColumn('items', 'user_wise')) {
            Schema::table('items', function (Blueprint $table) {
                $table->boolean('user_wise')->default(false)->after('sync');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('items') && Schema::hasColumn('items', 'user_wise')) {
            Schema::table('items', function (Blueprint $table) {
                $table->dropColumn('user_wise');
            });
        }
    }
};
