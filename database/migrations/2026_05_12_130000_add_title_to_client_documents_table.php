<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('client_documents') || Schema::hasColumn('client_documents', 'title')) {
            return;
        }

        Schema::table('client_documents', function (Blueprint $table) {
            $table->string('title', 150)->nullable()->after('type');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('client_documents') || !Schema::hasColumn('client_documents', 'title')) {
            return;
        }

        Schema::table('client_documents', function (Blueprint $table) {
            $table->dropColumn('title');
        });
    }
};
