<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('client_categories', function (Blueprint $table) {
            $table->dropForeign(['accountid']);
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropForeign(['categoryid']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_categories', function (Blueprint $table) {
            $table->foreign('accountid')->references('accountid')->on('accounts')->onDelete('cascade');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->foreign('categoryid')->references('categoryid')->on('client_categories')->onDelete('set null');
        });
    }
};
