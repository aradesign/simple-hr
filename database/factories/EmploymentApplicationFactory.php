<?php

namespace Database\Factories;

use App\Domain\Enums\ApplicationStatus;
use App\Models\EmploymentApplication;
use App\Models\Person;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmploymentApplication>
 */
class EmploymentApplicationFactory extends Factory
{
    protected $model = EmploymentApplication::class;

    public function definition(): array
    {
        return [
            'person_id' => Person::factory(),
            'application_number' => 'APP-'.now()->format('Y').'-'.fake()->unique()->numerify('#####'),
            'status' => ApplicationStatus::Draft,
            'form_data' => [],
            'current_step' => 1,
            'assigned_to' => null,
            'reviewer_id' => null,
            'submitted_at' => null,
            'reviewed_at' => null,
            'hr_notes' => null,
        ];
    }

    public function submitted(): static
    {
        return $this->state(fn () => [
            'status' => ApplicationStatus::Submitted,
            'submitted_at' => now(),
            'form_data' => [
                'motivation' => fake()->sentence(),
                'expected_salary' => fake()->numberBetween(20_000_000, 80_000_000),
            ],
        ]);
    }

    public function underReview(): static
    {
        return $this->submitted()->state(fn () => [
            'status' => ApplicationStatus::UnderReview,
            'assigned_to' => User::factory(),
            'reviewer_id' => User::factory(),
        ]);
    }

    public function accepted(): static
    {
        return $this->state(fn () => [
            'status' => ApplicationStatus::Accepted,
            'submitted_at' => now()->subDays(fake()->numberBetween(7, 30)),
            'reviewed_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => ApplicationStatus::Rejected,
            'submitted_at' => now()->subDays(fake()->numberBetween(7, 30)),
            'reviewed_at' => now(),
            'hr_notes' => fake()->sentence(),
        ]);
    }
}
