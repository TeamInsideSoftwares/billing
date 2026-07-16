<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MapProductIds extends Command
{
    protected $signature = 'billing:map-product-ids';
    protected $description = 'Safely map T499MU1L1l and S00003 product IDs resolving primary key conflicts, and insert new items';

    public function handle()
    {
        // 1. We first need to move T499MU1L1l off the Superadmin product IDs 
        // to free up the primary keys for S00003.
        // We use uppercase 6 char strings to follow the itemid format
        $tAccountMappings = [
            'N6EU9S' => strtoupper(Str::random(6)), // CRM
            'ED2U7W' => strtoupper(Str::random(6)), // CMS
            'UHA8IK' => strtoupper(Str::random(6)), // EMS
            'JOURNE' => strtoupper(Str::random(6)), // JOURNEY
            // Also free up the IDs for the 3 new items you want to add
            'H8U6T3' => strtoupper(Str::random(6)), // UPIS
            'IJ87GY' => strtoupper(Str::random(6)), // IMS
            'FR56TY' => strtoupper(Str::random(6)), // Exam
        ];

        // 2. Map S00003's old generated IDs to the freed Superadmin product IDs
        $sAccountMappings = [
            'N6EU9T' => 'N6EU9S',
            'HF624D' => 'ED2U7W',
            'QXOAWW' => 'UHA8IK',
            'HK4ZBO' => 'JOURNE',
        ];

        // 3. New items to add for S00003
        $newItems = [
            'H8U6T3' => 'UPIS',
            'IJ87GY' => 'IMS',
            'FR56TY' => 'Exam',
        ];

        DB::beginTransaction();

        try {
            $this->info('Starting ID mappings for T499MU1L1l account to prevent conflicts...');
            foreach ($tAccountMappings as $oldId => $newId) {
                // Check if the item exists AND belongs to the T account
                if (DB::table('items')->where('itemid', $oldId)->where('accountid', 'T499MU1L1l')->exists()) {
                    $this->performCascadingUpdate($oldId, $newId);
                    $this->info("Mapped conflicting item $oldId -> $newId");
                }
            }

            $this->info('Starting ID mappings for S00003 account to Superadmin products...');
            foreach ($sAccountMappings as $oldId => $newId) {
                // Check if the item exists AND belongs to the S account
                if (DB::table('items')->where('itemid', $oldId)->where('accountid', 'S00003')->exists()) {
                    $this->performCascadingUpdate($oldId, $newId);
                    $this->info("Mapped S account item $oldId -> $newId");
                }
            }

            $this->info('Adding new items for S00003 and copying CMS costings...');
            
            // At this point in the script, CMS for S00003 has already been renamed to ED2U7W.
            $cmsItem = DB::table('items')->where('itemid', 'ED2U7W')->first();
            $cmsCostings = DB::table('item_costings')->where('itemid', 'ED2U7W')->get();

            if ($cmsItem) {
                foreach ($newItems as $newId => $newName) {
                    if (!DB::table('items')->where('itemid', $newId)->exists()) {
                        // Insert item
                        DB::table('items')->insert([
                            'itemid' => $newId,
                            'accountid' => 'S00003',
                            'ps_catid' => $cmsItem->ps_catid,
                            'type' => $cmsItem->type,
                            'sync' => $cmsItem->sync,
                            'user_wise' => $cmsItem->user_wise,
                            'name' => $newName,
                            'sequence' => $cmsItem->sequence,
                            'description' => $newName,
                            'grace_period' => $cmsItem->grace_period,
                            'addons' => $cmsItem->addons,
                            'is_active' => $cmsItem->is_active,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        
                        // Insert costings
                        foreach ($cmsCostings as $costing) {
                            $newCosting = (array) $costing;
                            $newCosting['costingid'] = strtoupper(Str::random(6)); // Generate random 6-char ID
                            $newCosting['itemid'] = $newId;
                            $newCosting['created_at'] = now();
                            $newCosting['updated_at'] = now();
                            
                            DB::table('item_costings')->insert($newCosting);
                        }
                        $this->info("Added new item $newName ($newId) with CMS costings");
                    } else {
                        $this->info("Item $newName ($newId) already exists.");
                        
                        // Check if costings are missing due to a previous partial failure
                        if (!DB::table('item_costings')->where('itemid', $newId)->exists()) {
                            foreach ($cmsCostings as $costing) {
                                $newCosting = (array) $costing;
                                $newCosting['costingid'] = strtoupper(Str::random(6));
                                $newCosting['itemid'] = $newId;
                                $newCosting['created_at'] = now();
                                $newCosting['updated_at'] = now();
                                DB::table('item_costings')->insert($newCosting);
                            }
                            $this->info("Recovered: Added missing costings for $newName ($newId)");
                        } else {
                            $this->info("Skipping $newName ($newId) as it already has costings.");
                        }
                    }
                }
            } else {
                $this->error("Could not find CMS item (ED2U7W) to copy costings from!");
            }

            DB::commit();
            $this->info('Successfully finished everything!');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('An error occurred during mapping. All changes rolled back.');
            $this->error($e->getMessage());
        }
    }

    private function performCascadingUpdate($oldId, $newId)
    {
        // Temporarily disable foreign key checks in case they exist at DB level
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // 1. Update the items table (Primary Key)
        DB::table('items')
            ->where('itemid', $oldId)
            ->update(['itemid' => $newId]);

        // 2. Update item_costings table
        DB::table('item_costings')
            ->where('itemid', $oldId)
            ->update(['itemid' => $newId]);

        // 3. Update orders table
        DB::table('orders')
            ->where('itemid', $oldId)
            ->update(['itemid' => $newId]);

        // 4. Update invoice_items table
        DB::table('invoice_items')
            ->where('itemid', $oldId)
            ->update(['itemid' => $newId]);

        // 5. Update quotation_items table
        DB::table('quotation_items')
            ->where('itemid', $oldId)
            ->update(['itemid' => $newId]);

        // 6. Update items addons JSON array
        $itemsWithAddons = DB::table('items')
            ->whereNotNull('addons')
            ->get(['itemid', 'addons']);

        foreach ($itemsWithAddons as $item) {
            $addonsArray = json_decode($item->addons, true);
            if (is_array($addonsArray) && in_array($oldId, $addonsArray)) {
                // Replace the old ID with the new one
                $addonsArray = array_map(function($val) use ($oldId, $newId) {
                    return $val === $oldId ? $newId : $val;
                }, $addonsArray);
                
                DB::table('items')
                    ->where('itemid', $item->itemid)
                    ->update(['addons' => json_encode(array_values(array_unique($addonsArray)))]);
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
