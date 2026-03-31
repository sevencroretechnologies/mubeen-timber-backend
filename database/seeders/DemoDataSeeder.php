<?php

namespace Database\Seeders;

use App\Models\CompanyEvent;
use App\Models\CompanyHoliday;
use App\Models\CompanyNotice;
use App\Models\Division;
use App\Models\JobTitle;
use App\Models\OfficeLocation;
use App\Models\StaffMember;
use App\Models\TimeOffCategory;
use App\Models\User;
use App\Models\WorkLog;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    /**
     * Seed demo data for testing.
     */
    public function run(): void
    {
        // Office Locations
        $locations = [
            ['title' => 'Head Office', 'address' => 'New York, NY', 'is_active' => true],
            ['title' => 'Regional Office West', 'address' => 'Los Angeles, CA', 'is_active' => true],
            ['title' => 'Regional Office East', 'address' => 'Boston, MA', 'is_active' => true],
        ];

        foreach ($locations as $loc) {
            OfficeLocation::create($loc);
        }

        $headOffice = OfficeLocation::first();

        // Divisions
        $divisions = [
            ['title' => 'Engineering', 'office_location_id' => $headOffice->id],
            ['title' => 'Human Resources', 'office_location_id' => $headOffice->id],
            ['title' => 'Finance', 'office_location_id' => $headOffice->id],
            ['title' => 'Marketing', 'office_location_id' => $headOffice->id],
            ['title' => 'Operations', 'office_location_id' => $headOffice->id],
        ];

        foreach ($divisions as $div) {
            Division::create($div);
        }

        $engineering = Division::where('title', 'Engineering')->first();
        $hr = Division::where('title', 'Human Resources')->first();

        // Job Titles
        $jobTitles = [
            ['title' => 'Senior Developer', 'division_id' => $engineering->id],
            ['title' => 'Junior Developer', 'division_id' => $engineering->id],
            ['title' => 'Team Lead', 'division_id' => $engineering->id],
            ['title' => 'HR Manager', 'division_id' => $hr->id],
            ['title' => 'HR Coordinator', 'division_id' => $hr->id],
        ];

        foreach ($jobTitles as $jt) {
            JobTitle::create($jt);
        }

        // Time Off Categories
        $leaveTypes = [
            ['title' => 'Annual Leave', 'annual_quota' => 20, 'is_paid' => true],
            ['title' => 'Sick Leave', 'annual_quota' => 10, 'is_paid' => true],
            ['title' => 'Personal Leave', 'annual_quota' => 5, 'is_paid' => true],
            ['title' => 'Unpaid Leave', 'annual_quota' => 30, 'is_paid' => false],
        ];

        foreach ($leaveTypes as $lt) {
            TimeOffCategory::create($lt);
        }

        // Company Holidays 2025
        $holidays = [
            ['title' => "New Year's Day", 'holiday_date' => '2025-01-01'],
            ['title' => 'Martin Luther King Jr. Day', 'holiday_date' => '2025-01-20'],
            ['title' => "Presidents' Day", 'holiday_date' => '2025-02-17'],
            ['title' => 'Memorial Day', 'holiday_date' => '2025-05-26'],
            ['title' => 'Independence Day', 'holiday_date' => '2025-07-04'],
            ['title' => 'Labor Day', 'holiday_date' => '2025-09-01'],
            ['title' => 'Thanksgiving Day', 'holiday_date' => '2025-11-27'],
            ['title' => 'Christmas Day', 'holiday_date' => '2025-12-25'],
        ];

        foreach ($holidays as $h) {
            CompanyHoliday::create($h);
        }

        // Create demo users with different roles
        $demoUsers = [
            [
                'name' => 'Admin User',
                'email' => 'admin@hrms.local',
                'role' => 'admin',
                'staff_code' => 'EMP001',
                'base_salary' => 120000,
            ],
            [
                'name' => 'HR Officer',
                'email' => 'hr@hrms.local',
                'role' => 'hr',
                'staff_code' => 'EMP002',
                'base_salary' => 85000,
            ],
            [
                'name' => 'Manager User',
                'email' => 'manager@hrms.local',
                'role' => 'company',
                'staff_code' => 'EMP003',
                'base_salary' => 95000,
            ],
            [
                'name' => 'Staff User',
                'email' => 'staff@hrms.local',
                'role' => 'user',
                'staff_code' => 'EMP004',
                'base_salary' => 55000,
            ],
        ];

        $jobTitleModels = JobTitle::all();

        foreach ($demoUsers as $index => $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make('password'),
                ]
            );

            if (! $user->hasRole($userData['role'])) {
                $user->assignRole($userData['role']);
            }

            StaffMember::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'full_name' => $userData['name'],
                    'personal_email' => $userData['email'],
                    'staff_code' => $userData['staff_code'],
                    'office_location_id' => $headOffice->id,
                    'division_id' => $engineering->id,
                    'job_title_id' => $jobTitleModels[$index % count($jobTitleModels)]->id,
                    'employment_status' => 'active',
                    'base_salary' => $userData['base_salary'],
                    'hire_date' => Carbon::now()->subMonths(rand(6, 36)),
                ]
            );
        }

        // Additional staff members for testing
        $additionalStaff = [
            ['full_name' => 'John Smith', 'personal_email' => 'john.smith@demo.com', 'gender' => 'male', 'base_salary' => 95000],
            ['full_name' => 'Sarah Johnson', 'personal_email' => 'sarah.j@demo.com', 'gender' => 'female', 'base_salary' => 65000],
            ['full_name' => 'Michael Williams', 'personal_email' => 'm.williams@demo.com', 'gender' => 'male', 'base_salary' => 110000],
            ['full_name' => 'Emily Brown', 'personal_email' => 'emily.b@demo.com', 'gender' => 'female', 'base_salary' => 55000],
            ['full_name' => 'David Davis', 'personal_email' => 'd.davis@demo.com', 'gender' => 'male', 'base_salary' => 85000],
        ];

        foreach ($additionalStaff as $index => $sd) {
            $user = User::firstOrCreate(
                ['email' => $sd['personal_email']],
                [
                    'name' => $sd['full_name'],
                    'password' => Hash::make('password'),
                ]
            );
            if (! $user->hasRole('user')) {
                $user->assignRole('user');
            }

            StaffMember::firstOrCreate(
                ['user_id' => $user->id],
                array_merge($sd, [
                    'staff_code' => 'EMP'.str_pad($index + 5, 3, '0', STR_PAD_LEFT),
                    'office_location_id' => $headOffice->id,
                    'division_id' => $engineering->id,
                    'job_title_id' => $jobTitleModels[$index % count($jobTitleModels)]->id,
                    'employment_status' => 'active',
                    'hire_date' => Carbon::now()->subMonths(rand(6, 36)),
                ])
            );
        }

        // Generate attendance for last 30 days
        $staffMembers = StaffMember::all();
        $today = Carbon::today();

        foreach ($staffMembers as $staff) {
            for ($i = 30; $i >= 0; $i--) {
                $date = $today->copy()->subDays($i);

                // Skip weekends
                if ($date->isWeekend()) {
                    continue;
                }

                // Random status
                $statuses = ['present', 'present', 'present', 'present', 'present', 'absent', 'half_day'];
                $status = $statuses[array_rand($statuses)];

                WorkLog::create([
                    'staff_member_id' => $staff->id,
                    'log_date' => $date,
                    'status' => $status,
                    'clock_in' => $status === 'present' ? '09:'.str_pad(rand(0, 15), 2, '0', STR_PAD_LEFT) : null,
                    'clock_out' => $status === 'present' ? '18:'.str_pad(rand(0, 30), 2, '0', STR_PAD_LEFT) : null,
                    'late_minutes' => $status === 'present' ? rand(0, 20) : 0,
                ]);
            }
        }

        // Company Notice
        CompanyNotice::create([
            'title' => 'Welcome to the New HRMS',
            'content' => 'We are excited to announce the launch of our new Human Resource Management System. This platform will streamline all HR processes including attendance tracking, leave management, and payroll processing.',
            'is_company_wide' => true,
            'publish_date' => now(),
        ]);

        // Company Event
        CompanyEvent::create([
            'title' => 'Annual Company Meeting',
            'description' => 'Join us for our annual company meeting where we will discuss the achievements of the past year and goals for the upcoming year.',
            'event_start' => now()->addDays(14),
            'event_end' => now()->addDays(14),
            'start_time' => '10:00',
            'end_time' => '16:00',
            'location' => 'Main Conference Room',
            'is_all_day' => false,
            'is_company_wide' => true,
            'color' => '#3b82f6',
        ]);

        $this->command->info('Demo data seeded successfully!');
    }
}
