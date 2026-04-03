<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('services')) {
            return;
        }

        if (Schema::hasTable('invoice_items') && Schema::hasColumn('invoice_items', 'serviceid')) {
            $this->dropForeignIfExists('invoice_items', 'serviceid');
        }

        if (Schema::hasTable('quotation_items') && Schema::hasColumn('quotation_items', 'serviceid')) {
            $this->dropForeignIfExists('quotation_items', 'serviceid');
        }

        if (Schema::hasTable('subscriptions') && Schema::hasColumn('subscriptions', 'serviceid')) {
            $this->dropForeignIfExists('subscriptions', 'serviceid');
        }

        if (Schema::hasTable('service_costings') && Schema::hasColumn('service_costings', 'serviceid')) {
            $this->dropForeignIfExists('service_costings', 'serviceid');
        }

        if (Schema::hasTable('service_addons') && Schema::hasColumn('service_addons', 'serviceid')) {
            $this->dropForeignIfExists('service_addons', 'serviceid');
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

        if (Schema::hasTable('invoice_items') && Schema::hasColumn('invoice_items', 'itemid')) {
            Schema::table('invoice_items', function (Blueprint $table) {
                $table->foreign('itemid')->references('itemid')->on('items')->onDelete('set null');
            });
        }

        if (Schema::hasTable('quotation_items') && Schema::hasColumn('quotation_items', 'itemid')) {
            Schema::table('quotation_items', function (Blueprint $table) {
                $table->foreign('itemid')->references('itemid')->on('items')->onDelete('set null');
            });
        }

        if (Schema::hasTable('subscriptions') && Schema::hasColumn('subscriptions', 'itemid')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->foreign('itemid')->references('itemid')->on('items')->onDelete('restrict');
            });
        }

        if (Schema::hasTable('item_costings') && Schema::hasColumn('item_costings', 'itemid')) {
            Schema::table('item_costings', function (Blueprint $table) {
                $table->foreign('itemid')->references('itemid')->on('items')->onDelete('cascade');
            });
        }

        if (Schema::hasTable('service_addons') && Schema::hasColumn('service_addons', 'itemid')) {
            Schema::table('service_addons', function (Blueprint $table) {
                $table->foreign('itemid')->references('itemid')->on('items')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('items')) {
            return;
        }

        if (Schema::hasTable('invoice_items') && Schema::hasColumn('invoice_items', 'itemid')) {
            $this->dropForeignIfExists('invoice_items', 'itemid');
        }

        if (Schema::hasTable('quotation_items') && Schema::hasColumn('quotation_items', 'itemid')) {
            $this->dropForeignIfExists('quotation_items', 'itemid');
        }

        if (Schema::hasTable('subscriptions') && Schema::hasColumn('subscriptions', 'itemid')) {
            $this->dropForeignIfExists('subscriptions', 'itemid');
        }

        if (Schema::hasTable('item_costings') && Schema::hasColumn('item_costings', 'itemid')) {
            $this->dropForeignIfExists('item_costings', 'itemid');
        }

        if (Schema::hasTable('service_addons') && Schema::hasColumn('service_addons', 'itemid')) {
            $this->dropForeignIfExists('service_addons', 'itemid');
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

        if (Schema::hasTable('invoice_items') && Schema::hasColumn('invoice_items', 'serviceid')) {
            Schema::table('invoice_items', function (Blueprint $table) {
                $table->foreign('serviceid')->references('serviceid')->on('services')->onDelete('set null');
            });
        }

        if (Schema::hasTable('quotation_items') && Schema::hasColumn('quotation_items', 'serviceid')) {
            Schema::table('quotation_items', function (Blueprint $table) {
                $table->foreign('serviceid')->references('serviceid')->on('services')->onDelete('set null');
            });
        }

        if (Schema::hasTable('subscriptions') && Schema::hasColumn('subscriptions', 'serviceid')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->foreign('serviceid')->references('serviceid')->on('services')->onDelete('restrict');
            });
        }

        if (Schema::hasTable('service_costings') && Schema::hasColumn('service_costings', 'serviceid')) {
            Schema::table('service_costings', function (Blueprint $table) {
                $table->foreign('serviceid')->references('serviceid')->on('services')->onDelete('cascade');
            });
        }

        if (Schema::hasTable('service_addons') && Schema::hasColumn('service_addons', 'serviceid')) {
            Schema::table('service_addons', function (Blueprint $table) {
                $table->foreign('serviceid')->references('serviceid')->on('services')->onDelete('cascade');
            });
        }
    }

    private function dropForeignIfExists(string $table, string $column): void
    {
        $database = DB::getDatabaseName();

        $constraint = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->value('CONSTRAINT_NAME');

        if ($constraint) {
            DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$constraint}`");
        }
    }
};
