<?php

namespace Database\Factories;

use App\Domain\Enums\Gender;
use App\Domain\Enums\MaritalStatus;
use App\Domain\Enums\PersonLifecycleStatus;
use App\Models\Person;
use Database\Factories\Concerns\UsesPersianFaker;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Person>
 */
class PersonFactory extends Factory
{
    use UsesPersianFaker;

    protected $model = Person::class;

    public function definition(): array
    {
        $faker = $this->persianFaker();
        $gender = fake()->randomElement(Gender::cases());

        return [
            'first_name' => $gender === Gender::Female
                ? $faker->firstNameFemale()
                : $faker->firstNameMale(),
            'last_name' => $faker->lastName(),
            'national_id' => $this->nationalId(),
            'mobile' => $this->persianMobile(),
            'birth_date' => fake()->dateTimeBetween('-55 years', '-20 years'),
            'gender' => $gender,
            'lifecycle_status' => PersonLifecycleStatus::Applicant,
            'marital_status' => fake()->randomElement(MaritalStatus::cases()),
            'address' => $faker->address(),
            'city' => $faker->city(),
            'province' => 'تهران',
            'postal_code' => fake()->numerify('##########'),
            'profile_photo' => null,
            'notes' => fake()->optional(0.2)->sentence(),
        ];
    }

    public function applicant(): static
    {
        return $this->state(fn () => [
            'lifecycle_status' => PersonLifecycleStatus::Applicant,
        ]);
    }

    public function employee(): static
    {
        return $this->state(fn () => [
            'lifecycle_status' => PersonLifecycleStatus::Employee,
        ]);
    }

    public function formerEmployee(): static
    {
        return $this->state(fn () => [
            'lifecycle_status' => PersonLifecycleStatus::FormerEmployee,
        ]);
    }
}
