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
        if (! Schema::hasTable('client_billing_details')) {
            return;
        }

        $alreadyRestructured = Schema::hasColumn('client_billing_details', 'bd_id')
            && ! Schema::hasColumn('client_billing_details', 'clientid')
            && ! Schema::hasColumn('client_billing_details', 'id');

        if (! $alreadyRestructured) {
            if (Schema::hasColumn('client_billing_details', 'id')) {
                Schema::table('client_billing_details', function (Blueprint $table) {
                    $table->dropColumn('id');
                });
            }

            if (! Schema::hasColumn('client_billing_details', 'bd_id')) {
                Schema::table('client_billing_details', function (Blueprint $table) {
                    $table->string('bd_id', 6)->primary()->after('clientid');
                });
            }

            if (Schema::hasColumn('client_billing_details', 'clientid')) {
                Schema::table('client_billing_details', function (Blueprint $table) {
                    $table->dropColumn('clientid');
                });
            }

            if (! Schema::hasColumn('client_billing_details', 'accountid')) {
                Schema::table('client_billing_details', function (Blueprint $table) {
                    $table->string('accountid', 10)->after('bd_id');
                });
            }

            if (! Schema::hasColumn('client_billing_details', 'business_name')) {
                Schema::table('client_billing_details', function (Blueprint $table) {
                    $table->string('business_name', 150)->after('accountid');
                });
            }

        }

        if (Schema::hasTable('clients') && ! Schema::hasColumn('clients', 'bd_id')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->string('bd_id', 6)->nullable()->after('accountid');
            });
        }

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('client_billing_details')) {
            if (Schema::hasColumn('client_billing_details', 'accountid')) {
                Schema::table('client_billing_details', function (Blueprint $table) {
                    $table->dropColumn('accountid');
                });
            }

            if (Schema::hasColumn('client_billing_details', 'business_name')) {
                Schema::table('client_billing_details', function (Blueprint $table) {
                    $table->dropColumn('business_name');
                });
            }

            if (! Schema::hasColumn('client_billing_details', 'clientid')) {
                Schema::table('client_billing_details', function (Blueprint $table) {
                    $table->string('clientid', 6)->after('bd_id');
                });
            }

        }

        if (Schema::hasTable('clients') && Schema::hasColumn('clients', 'bd_id')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->dropColumn('bd_id');
            });
        }
    }
};
