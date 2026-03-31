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
        // Seed roles and permissions first
        // $this->call(AccessSeeder::class);
        $this->call([
            AccessSeeder::class,
            SourceSeeder::class,
            CampaignSeeder::class,
        ]);

        $this->call([
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
            ['email' => 'admin@hrms.local'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );
        $admin->assignRole('admin');

        // Create HR Manager user
        $hrManager = User::firstOrCreate(
            ['email' => 'hr@hrms.local'],
            [
                'name' => 'Sarah Johnson',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );
        $hrManager->assignRole('hr');

        // Create Department Head / Manager user
        $manager = User::firstOrCreate(
            ['email' => 'manager@hrms.local'],
            [
                'name' => 'Michael Chen',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );
        $manager->assignRole('company');

        // Create Accountant user
        $accountant = User::firstOrCreate(
            ['email' => 'accountant@hrms.local'],
            [
                'name' => 'Emily Davis',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );
        $accountant->assignRole('user');

        // Create Employee user
        $employee = User::firstOrCreate(
            ['email' => 'employee@hrms.local'],
            [
                'name' => 'John Smith',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );
        $employee->assignRole('user');
    }
}
