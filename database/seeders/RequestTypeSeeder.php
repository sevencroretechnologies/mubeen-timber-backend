<?php

namespace Database\Seeders;

use App\Models\RequestType;
use Illuminate\Database\Seeder;

class RequestTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $requestTypes = [
            ['name' => 'Product Inquiry'],
            ['name' => 'Service Inquiry'],
            ['name' => 'Suggestions'],
            ['name' => 'Pricing Request'],
            ['name' => 'Quotation Request'],
        ];

        foreach ($requestTypes as $requestType) {
            RequestType::firstOrCreate($requestType);
        }
    }
}
