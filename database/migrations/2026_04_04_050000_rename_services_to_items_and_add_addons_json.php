<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('services')) {
            return;
        }

        Schema::rename('services', 'items');

        if (Schema::hasTable('service_costings')) {
            Schema::rename('service_costings', 'item_costings');
        }

        Schema::table('items', function (Blueprint $table) {
            if (Schema::hasColumn('items', 'serviceid')) {
                $table->renameColumn('serviceid', 'itemid');
            }

            if (! Schema::hasColumn('items', 'addons')) {
                $table->json('addons')->nullable()->after('description');
            }
        });

        if (Schema::hasTable('invoice_items')) {
            Schema::table('invoice_items', function (Blueprint $table) {
                if (Schema::hasColumn('invoice_items', 'serviceid')) {
                    $table->renameColumn('serviceid', 'itemid');
                }
            });
        }

        if (Schema::hasTable('quotation_items')) {
            Schema::table('quotation_items', function (Blueprint $table) {
                if (Schema::hasColumn('quotation_items', 'serviceid')) {
                    $table->renameColumn('serviceid', 'itemid');
                }
            });
        }

        if (Schema::hasTable('subscriptions')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                if (Schema::hasColumn('subscriptions', 'serviceid')) {
                    $table->renameColumn('serviceid', 'itemid');
                }
            });
        }

        if (Schema::hasTable('item_costings')) {
            Schema::table('item_costings', function (Blueprint $table) {
                if (Schema::hasColumn('item_costings', 'serviceid')) {
                    $table->renameColumn('serviceid', 'itemid');
                }
            });
        }

        if (Schema::hasTable('service_addons')) {
            Schema::table('service_addons', function (Blueprint $table) {
                if (Schema::hasColumn('service_addons', 'serviceid')) {
                    $table->renameColumn('serviceid', 'itemid');
                }
            });
        }

    }

    public function down(): void
    {
        if (! Schema::hasTable('items')) {
            return;
        }

        Schema::table('items', function (Blueprint $table) {
            if (Schema::hasColumn('items', 'addons')) {
                $table->dropColumn('addons');
            }

            if (Schema::hasColumn('items', 'itemid')) {
                $table->renameColumn('itemid', 'serviceid');
            }
        });

        if (Schema::hasTable('invoice_items')) {
            Schema::table('invoice_items', function (Blueprint $table) {
                if (Schema::hasColumn('invoice_items', 'itemid')) {
                    $table->renameColumn('itemid', 'serviceid');
                }
            });
        }

        if (Schema::hasTable('quotation_items')) {
            Schema::table('quotation_items', function (Blueprint $table) {
                if (Schema::hasColumn('quotation_items', 'itemid')) {
                    $table->renameColumn('itemid', 'serviceid');
                }
            });
        }

        if (Schema::hasTable('subscriptions')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                if (Schema::hasColumn('subscriptions', 'itemid')) {
                    $table->renameColumn('itemid', 'serviceid');
                }
            });
        }

        if (Schema::hasTable('item_costings')) {
            Schema::table('item_costings', function (Blueprint $table) {
                if (Schema::hasColumn('item_costings', 'itemid')) {
                    $table->renameColumn('itemid', 'serviceid');
                }
            });
            Schema::rename('item_costings', 'service_costings');
        }

        if (Schema::hasTable('service_addons')) {
            Schema::table('service_addons', function (Blueprint $table) {
                if (Schema::hasColumn('service_addons', 'itemid')) {
                    $table->renameColumn('itemid', 'serviceid');
                }
            });
        }

        Schema::rename('items', 'services');

    }
};
