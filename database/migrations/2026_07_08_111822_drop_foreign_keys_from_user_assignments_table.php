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
        Schema::table('user_assignments', function (Blueprint $table) {
            $table->dropForeign(['userid']);
            $table->dropForeign(['assigned_userid']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_assignments', function (Blueprint $table) {
            $table->foreign('userid')->references('userid')->on('account_users')->onDelete('cascade');
            $table->foreign('assigned_userid')->references('userid')->on('account_users')->onDelete('cascade');
        });
    }
};
