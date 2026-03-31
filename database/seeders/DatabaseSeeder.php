<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed roles, permissions, and CRM master data
        $this->call([
            AccessSeeder::class,
            SourceSeeder::class,
            CampaignSeeder::class,
            StatusSeeder::class,
            RequestTypeSeeder::class,
            IndustryTypeSeeder::class,
            MasterDataSeeder::class,
            OpportunityStageSeeder::class,
            OpportunityTypeSeeder::class,
            TaskSourceSeeder::class,
            TaskTypeSeeder::class,
        ]);



        // Create Super Admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@crm.local'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );
        $admin->assignRole('admin');

        // Create Sales Manager user
        $salesManager = User::firstOrCreate(
            ['email' => 'sales@crm.local'],
            [
                'name' => 'Sarah Johnson',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );
        $salesManager->assignRole('company');

        // Create Sales Rep user
        $salesRep = User::firstOrCreate(
            ['email' => 'rep@crm.local'],
            [
                'name' => 'John Smith',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );
        $salesRep->assignRole('user');

        // Seed Timber demo data
        $this->call(TimberDemoSeeder::class);
    }
}
