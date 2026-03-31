<?php

namespace Database\Seeders;

use App\Models\IndustryType;
use Illuminate\Database\Seeder;

class IndustryTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $industryTypes = [
            ['name' => 'Information Technology'],
            ['name' => 'Software Development'],
            ['name' => 'IT Services'],
            ['name' => 'Cyber Security'],
            ['name' => 'Data Science'],
        ];

        foreach ($industryTypes as $industryType) {
            IndustryType::firstOrCreate($industryType);
        }
    }
}
