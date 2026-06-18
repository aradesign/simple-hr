<?php

namespace Database\Factories;

use App\Domain\Enums\EmploymentStatus;
use App\Domain\Enums\EmploymentType;
use App\Models\Department;
use App\Models\EmploymentRecord;
use App\Models\Person;
use Database\Factories\Concerns\UsesPersianFaker;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmploymentRecord>
 */
class EmploymentRecordFactory extends Factory
{
    use UsesPersianFaker;

    protected $model = EmploymentRecord::class;

    public function definition(): array
    {
        $faker = $this->persianFaker();
        $startDate = fake()->dateTimeBetween('-5 years', '-1 month');

        return [
            'person_id' => Person::factory()->employee(),
            'department_id' => Department::factory(),
            'employee_code' => 'EMP-'.fake()->unique()->numerify('#####'),
            'employment_type' => fake()->randomElement(EmploymentType::cases()),
            'status' => EmploymentStatus::Active,
            'start_date' => $startDate,
            'end_date' => null,
            'probation_end_date' => (clone $startDate)->modify('+3 months'),
            'contract_end_date' => fake()->optional(0.7)->dateTimeBetween('+1 month', '+2 years'),
            'salary' => fake()->numberBetween(25_000_000, 120_000_000),
            'position_title' => $faker->jobTitle(),
            'notes' => fake()->optional(0.2)->sentence(),
        ];
    }

    public function terminated(): static
    {
        return $this->state(fn () => [
            'status' => EmploymentStatus::Terminated,
            'end_date' => now()->subMonth(),
        ]);
    }

    public function onLeave(): static
    {
        return $this->state(fn () => [
            'status' => EmploymentStatus::OnLeave,
        ]);
    }
}
