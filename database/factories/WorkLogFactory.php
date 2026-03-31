<?php

namespace Database\Factories;

use App\Models\WorkLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WorkLog>
 */
class WorkLogFactory extends Factory
{
    protected $model = WorkLog::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $status = fake()->randomElement(['present', 'present', 'present', 'absent', 'half_day', 'on_leave']);
        $clockIn = $status === 'present' ? '09:'.str_pad(fake()->numberBetween(0, 30), 2, '0', STR_PAD_LEFT) : null;
        $clockOut = $status === 'present' ? '18:'.str_pad(fake()->numberBetween(0, 30), 2, '0', STR_PAD_LEFT) : null;

        return [
            'log_date' => fake()->dateTimeBetween('-30 days', 'now'),
            'status' => $status,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'late_minutes' => $status === 'present' ? fake()->randomElement([0, 0, 0, 5, 10, 15, 30]) : 0,
            'early_leave_minutes' => 0,
            'overtime_minutes' => fake()->randomElement([0, 0, 0, 30, 60, 90]),
            'working_minutes' => $status === 'present' ? 480 : 0,
        ];
    }

    /**
     * Indicate that the log is for a present day.
     */
    public function present(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'present',
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'working_minutes' => 480,
        ]);
    }

    /**
     * Indicate that the log is for an absent day.
     */
    public function absent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'absent',
            'clock_in' => null,
            'clock_out' => null,
            'working_minutes' => 0,
        ]);
    }
}
