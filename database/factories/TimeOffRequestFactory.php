<?php

namespace Database\Factories;

use App\Models\TimeOffRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TimeOffRequest>
 */
class TimeOffRequestFactory extends Factory
{
    protected $model = TimeOffRequest::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('now', '+30 days');
        $endDate = (clone $startDate)->modify('+'.fake()->numberBetween(1, 5).' days');

        return [
            'request_date' => now(),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_days' => fake()->numberBetween(1, 5),
            'reason' => fake()->sentence(10),
            'approval_status' => 'pending',
        ];
    }

    /**
     * Indicate that the request is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'approval_status' => 'approved',
            'approved_at' => now(),
        ]);
    }

    /**
     * Indicate that the request is declined.
     */
    public function declined(): static
    {
        return $this->state(fn (array $attributes) => [
            'approval_status' => 'declined',
            'approval_remarks' => fake()->sentence(),
        ]);
    }
}
