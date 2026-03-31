<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        // Customer Groups
        $groups = ['Commercial', 'Government', 'Non Profit', 'Individual'];
        foreach ($groups as $group) {
            DB::table('customer_groups')->insertOrIgnore(['name' => $group, 'created_at' => now(), 'updated_at' => now()]);
        }

        // Payment Terms
        $terms = ['Immediate', 'Net 15', 'Net 30', 'Net 45', 'Net 60'];
        foreach ($terms as $term) {
            DB::table('payment_terms')->insertOrIgnore(['name' => $term, 'created_at' => now(), 'updated_at' => now()]);
        }

        // Price Lists
        $priceLists = [
            ['name' => 'Standard Selling', 'currency' => 'INR'],
            ['name' => 'Standard Selling UAE', 'currency' => 'AED'],
            ['name' => 'Standard Selling USA', 'currency' => 'USD'],
        ];
        foreach ($priceLists as $pl) {
            DB::table('price_lists')->insertOrIgnore(array_merge($pl, ['created_at' => now(), 'updated_at' => now()]));
        }
    }
}
