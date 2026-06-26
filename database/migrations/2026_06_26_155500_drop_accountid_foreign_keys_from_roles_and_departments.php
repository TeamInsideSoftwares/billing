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
        Schema::table('account_departments', function (Blueprint $table) {
            $table->dropForeign(['accountid']);
        });

        Schema::table('account_roles', function (Blueprint $table) {
            $table->dropForeign(['accountid']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('account_departments', function (Blueprint $table) {
            $table->foreign('accountid')->references('accountid')->on('accounts')->onDelete('cascade');
        });

        Schema::table('account_roles', function (Blueprint $table) {
            $table->foreign('accountid')->references('accountid')->on('accounts')->onDelete('cascade');
        });
    }
};
