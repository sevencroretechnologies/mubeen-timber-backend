<?php

namespace Database\Seeders;

use App\Models\Source;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sources = [
            ['name' => 'Website', 'source_code' => 'WEB'],
            ['name' => 'Phone Call', 'source_code' => 'PHONE'],
            ['name' => 'Email', 'source_code' => 'EMAIL'],
            ['name' => 'Referral', 'source_code' => 'REFERRAL'],
            ['name' => 'Social Media', 'source_code' => 'SOCIAL_MEDIA'],
            ['name' => 'Walk-In', 'source_code' => 'WALK_IN'],
            ['name' => 'Other', 'source_code' => null], // Example of nullable
        ];

        foreach ($sources as $source) {
            Source::firstOrCreate($source);
        }
    }
}
