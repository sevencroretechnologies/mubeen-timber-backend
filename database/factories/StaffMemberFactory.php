<?php

namespace Database\Factories;

use App\Models\StaffMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StaffMember>
 */
class StaffMemberFactory extends Factory
{
    protected $model = StaffMember::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'personal_email' => fake()->unique()->safeEmail(),
            'phone_number' => fake()->phoneNumber(),
            'date_of_birth' => fake()->dateTimeBetween('-50 years', '-20 years'),
            'gender' => fake()->randomElement(['male', 'female', 'other']),
            'marital_status' => fake()->randomElement(['single', 'married', 'divorced', 'widowed']),
            'nationality' => fake()->country(),
            'residential_address' => fake()->address(),

            // Employment
            'employment_status' => 'active',
            'employment_type' => fake()->randomElement(['full_time', 'part_time', 'contract', 'intern']),
            'hire_date' => fake()->dateTimeBetween('-5 years', 'now'),
            'base_salary' => fake()->randomFloat(2, 30000, 150000),

            // Banking
            'bank_account_name' => fake()->name(),
            'bank_account_number' => fake()->bankAccountNumber(),
            'bank_name' => fake()->company(),
        ];
    }

    /**
     * Indicate that the staff member is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'employment_status' => 'terminated',
        ]);
    }

    /**
     * Indicate that the staff member is on probation.
     */
    public function probation(): static
    {
        return $this->state(fn (array $attributes) => [
            'employment_status' => 'probation',
            'hire_date' => now()->subDays(fake()->numberBetween(1, 90)),
        ]);
    }
}
