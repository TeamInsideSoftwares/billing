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
        Schema::table('users_doc', function (Blueprint $table) {
            $table->renameColumn('id', 'docid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users_doc', function (Blueprint $table) {
            $table->renameColumn('docid', 'id');
        });
    }
};
