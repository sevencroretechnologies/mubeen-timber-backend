<?php

namespace Database\Seeders;

use App\Models\Campaign;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CampaignSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $campaigns = [
            [
                'name' => 'Spring Sale',
                'campaign_code' => 'SPR'
            ],
            [
                'name' => 'Summer Promotion',
                'campaign_code' => 'SUM'
            ],
            [
                'name' => 'Black Friday Campaign',
                'campaign_code' => 'BF'
            ],
            [
                'name' => 'New Year Special',
                'campaign_code' => 'NYS'
            ],
            [
                'name' => 'Customer Loyalty Program',
                'campaign_code' => 'CLP'
            ],
            [
                'name' => 'Email Marketing Campaign',
                'campaign_code' => null
            ],
            [
                'name' => 'Social Media Awareness',
                'campaign_code' => 'SMA'
            ],
            [
                'name' => 'Referral Program',
                'campaign_code' => 'REF'
            ],
        ];

        foreach ($campaigns as $campaign) {

        Campaign::firstOrCreate($campaign);
        }
    }
}
