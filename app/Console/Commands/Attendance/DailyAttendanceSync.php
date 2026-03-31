<?php

namespace App\Console\Commands\Attendance;

use App\Services\Attendance\AttendanceService;
use Illuminate\Console\Command;

class DailyAttendanceSync extends Command
{
    protected $signature = 'attendance:sync-daily';
    protected $description = 'Sync daily attendance with leaves and auto-mark absents';

    public function handle(AttendanceService $attendanceService): int
    {
        $this->info('Starting daily attendance sync...');
        
        try {
            // Sync with approved leaves for today
            $attendanceService->syncWithApprovedLeaves();
            $this->info('✓ Synced with approved leaves');
            
            // Auto-mark absent for yesterday
            $attendanceService->autoMarkAbsent();
            $this->info('✓ Auto-marked absents for yesterday');
            
            $this->info('Daily attendance sync completed successfully.');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to sync daily attendance: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}