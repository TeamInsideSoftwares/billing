<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_assignments', function (Blueprint $table) {
            $table->id();
            $table->string('userid', 6);
            $table->string('assigned_userid', 6);
            $table->string('team_name')->nullable();
            $table->timestamps();
            
            $table->foreign('userid')->references('userid')->on('account_users')->onDelete('cascade');
            $table->foreign('assigned_userid')->references('userid')->on('account_users')->onDelete('cascade');
            $table->unique(['userid', 'assigned_userid']);
        });

        // Migrate existing assigned_users from account_users
        $users = DB::table('account_users')->whereNotNull('assigned_users')->get();
        foreach ($users as $user) {
            $assignedUsers = json_decode($user->assigned_users, true);
            if (is_array($assignedUsers) && !empty($assignedUsers)) {
                $now = now();
                $teamName = $user->name . "'s Team";
                foreach ($assignedUsers as $assignedId) {
                    $exists = DB::table('account_users')->where('userid', $assignedId)->exists();
                    if ($exists) {
                        DB::table('user_assignments')->insert([
                            'userid' => $user->userid,
                            'assigned_userid' => $assignedId,
                            'team_name' => $teamName,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }
            }
        }

        Schema::table('account_users', function (Blueprint $table) {
            $table->dropColumn('assigned_users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('account_users', function (Blueprint $table) {
            $table->json('assigned_users')->nullable();
        });

        Schema::dropIfExists('user_assignments');
    }
};
