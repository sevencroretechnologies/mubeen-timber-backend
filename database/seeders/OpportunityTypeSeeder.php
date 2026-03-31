<?php

namespace Database\Seeders;

use App\Models\OpportunityType;
use Illuminate\Database\Seeder;

class OpportunityTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['name' => 'Sales', 'description' => 'Sales opportunity'],
            ['name' => 'Support', 'description' => 'Support opportunity'],
            ['name' => 'Maintenance', 'description' => 'Maintenance opportunity'],
            ['name' => 'Partnership', 'description' => 'Partnership opportunity'],
        ];

        foreach ($types as $type) {
            OpportunityType::firstOrCreate(
                ['name' => $type['name']],
                ['description' => $type['description']]
            );
        }
    }
}
