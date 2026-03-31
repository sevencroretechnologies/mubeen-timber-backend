<?php

namespace Database\Seeders;

use App\Models\Status;
use Illuminate\Database\Seeder;

class StatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            ['status_name' => 'Open'],
            ['status_name' => 'Contacted'],
            ['status_name' => 'In Progress'],
            ['status_name' => 'Qualified'],
            ['status_name' => 'Unqualified'],
            ['status_name' => 'Interest'],
            ['status_name' => 'Lost'],
        ];

        foreach ($statuses as $status) {
            Status::firstOrCreate($status);
        }
    }
}
