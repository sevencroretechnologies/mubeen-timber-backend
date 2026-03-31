<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\TaskSource;
use App\Models\TaskType;
use App\Models\User;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $taskSources = TaskSource::all();
        $taskTypes = TaskType::all();
        $user = User::first();

        if ($taskSources->isEmpty() || $taskTypes->isEmpty()) {
            return;
        }

        for ($i = 0; $i < 10; $i++) {
            Task::create([
                'title' => 'Task ' . ($i + 1),
                'description' => 'Description for task ' . ($i + 1),
                'task_source_id' => $taskSources->random()->id,
                'task_type_id' => $taskTypes->random()->id,
                'related_id' => rand(1, 10),
                'due_date' => now()->addDays(rand(1, 30)),
                'status' => 'Open',
                'user_id' => $user ? $user->id : null,
            ]);
        }
    }
}
