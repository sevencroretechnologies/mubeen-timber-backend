<?php

namespace Database\Seeders;

use App\Models\OpportunityStage;
use Illuminate\Database\Seeder;

class OpportunityStageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stages = [
            ['name' => 'Qualification', 'description' => 'Initial qualification of the opportunity'],
            ['name' => 'Proposal', 'description' => 'Proposal has been sent to the prospect'],
            ['name' => 'Negotiation', 'description' => 'Negotiating terms and conditions'],
            ['name' => 'Closed Won', 'description' => 'Opportunity successfully closed'],
            ['name' => 'Closed Lost', 'description' => 'Opportunity was lost'],
        ];

        foreach ($stages as $stage) {
            OpportunityStage::firstOrCreate(
                ['name' => $stage['name']],
                ['description' => $stage['description']]
            );
        }
    }
}
