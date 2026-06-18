<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\User;
use Database\Factories\Concerns\UsesPersianFaker;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    use UsesPersianFaker;

    protected $model = Department::class;

    public function definition(): array
    {
        $faker = $this->persianFaker();
        $name = $faker->company();

        return [
            'name' => $name,
            'code' => 'DEPT_'.fake()->unique()->numerify('####'),
            'description' => $faker->optional(0.6)->realText(120),
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 100),
            'manager_id' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }

    public function withManager(?User $manager = null): static
    {
        return $this->state(fn () => [
            'manager_id' => $manager?->id ?? User::factory(),
        ]);
    }
}
