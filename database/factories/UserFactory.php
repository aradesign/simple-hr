<?php

namespace Database\Factories;

use App\Domain\Enums\UserRole;
use App\Models\Person;
use App\Models\User;
use Database\Factories\Concerns\UsesPersianFaker;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    use UsesPersianFaker;

    protected static ?string $password;

    protected $model = User::class;

    public function definition(): array
    {
        $faker = $this->persianFaker();
        $firstName = $faker->firstName();
        $lastName = $faker->lastName();

        return [
            'name' => "{$firstName} {$lastName}",
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => UserRole::Employee,
            'hr_access' => false,
            'person_id' => null,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn () => [
            'email_verified_at' => null,
        ]);
    }

    public function candidate(): static
    {
        return $this->state(fn () => [
            'role' => UserRole::Candidate,
            'hr_access' => false,
            'person_id' => Person::factory()->applicant(),
        ]);
    }

    public function employee(?Person $person = null): static
    {
        return $this->state(fn () => [
            'role' => UserRole::Employee,
            'hr_access' => false,
            'person_id' => $person?->id ?? Person::factory()->employee(),
        ]);
    }

    public function hr(): static
    {
        return $this->state(fn () => [
            'role' => UserRole::Hr,
            'hr_access' => true,
            'person_id' => null,
        ]);
    }

    public function hrManager(): static
    {
        return $this->state(fn () => [
            'role' => UserRole::HrManager,
            'hr_access' => true,
            'person_id' => null,
        ]);
    }

    public function superAdmin(): static
    {
        return $this->state(fn () => [
            'role' => UserRole::SuperAdmin,
            'hr_access' => true,
            'person_id' => null,
        ]);
    }

    public function withHrAccess(): static
    {
        return $this->state(fn () => [
            'hr_access' => true,
        ]);
    }

    public function withPerson(?Person $person = null): static
    {
        return $this->state(fn () => [
            'person_id' => $person?->id ?? Person::factory(),
        ]);
    }
}
