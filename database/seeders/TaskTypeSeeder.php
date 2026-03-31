<?php

namespace Database\Seeders;

use App\Enums\TaskType as TaskTypeEnum;
use App\Models\TaskType;
use Illuminate\Database\Seeder;

class TaskTypeSeeder extends Seeder
{
    public function run(): void
    {
        foreach (TaskTypeEnum::cases() as $type) {
            $record = TaskType::withTrashed()->find($type->value);

            if ($record) {
                $record->update(['name' => $type->label()]);
                if ($record->trashed()) {
                    $record->restore();
                }
            } else {
                TaskType::create([
                    'id' => $type->value,
                    'name' => $type->label()
                ]);
            }
        }
    }
}
