<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WellBeingResponse>
 */
class WellBeingResponseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-1 year', 'now');
        $endDate = $this->faker->dateTimeBetween($startDate, '+3 months');

        return [
            'employee_id' => \App\Models\Employee::inRandomOrder()->first()?->id ?? 1,
            'user_id' => \App\Models\User::inRandomOrder()->first()?->id ?? 1,
            'assessment_type' => $this->faker->randomElement(['daily', 'weekly', 'monthly']),
            'period_start_date' => $startDate,
            'period_end_date' => $endDate,
            'frequency' => $this->faker->randomElement(['monthly', 'quarterly', 'annual']),
            'stress_level' => $this->faker->numberBetween(1, 10),
            'work_life_balance' => $this->faker->numberBetween(1, 10),
            'job_satisfaction' => $this->faker->numberBetween(1, 10),
            'support_level' => $this->faker->numberBetween(1, 10),
            'comments' => $this->faker->optional()->paragraph(),
            'additional_metrics' => $this->faker->optional()->randomElements([
                'sleep_quality' => $this->faker->numberBetween(1, 10),
                'physical_activity' => $this->faker->numberBetween(1, 10),
                'mental_health_days' => $this->faker->numberBetween(0, 30),
            ], $this->faker->numberBetween(0, 3)),
        ];
    }
}
