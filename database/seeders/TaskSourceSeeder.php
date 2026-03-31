<?php

namespace Database\Seeders;

use App\Enums\TaskSource as TaskSourceEnum;
use App\Models\TaskSource;
use Illuminate\Database\Seeder;

class TaskSourceSeeder extends Seeder
{
    public function run(): void
    {
        foreach (TaskSourceEnum::cases() as $source) {
            $record = TaskSource::withTrashed()->find($source->value);

            if ($record) {
                $record->update(['name' => $source->label()]);
                if ($record->trashed()) {
                    $record->restore();
                }
            } else {
                TaskSource::create([
                    'id' => $source->value,
                    'name' => $source->label()
                ]);
            }
        }
    }
}
