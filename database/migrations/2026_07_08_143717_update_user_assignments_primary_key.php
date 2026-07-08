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
            $table->string('user_assignid', 6)->nullable()->first();
        });

        // Populate existing rows
        $assignments = \Illuminate\Support\Facades\DB::table('user_assignments')->get();
        foreach ($assignments as $assignment) {
            \Illuminate\Support\Facades\DB::table('user_assignments')
                ->where('id', $assignment->id)
                ->update(['user_assignid' => \Illuminate\Support\Str::random(6)]);
        }

        Schema::table('user_assignments', function (Blueprint $table) {
            $table->dropColumn('id');
        });
        
        Schema::table('user_assignments', function (Blueprint $table) {
            $table->string('user_assignid', 6)->nullable(false)->primary()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_assignments', function (Blueprint $table) {
            $table->dropPrimary();
            $table->dropColumn('user_assignid');
            $table->id()->first();
        });
    }
};
