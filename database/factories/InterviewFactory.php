<?php

namespace Database\Factories;

use App\Domain\Enums\InterviewResult;
use App\Domain\Enums\InterviewStatus;
use App\Domain\Enums\InterviewType;
use App\Models\EmploymentApplication;
use App\Models\Interview;
use App\Models\Person;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Interview>
 */
class InterviewFactory extends Factory
{
    protected $model = Interview::class;

    public function definition(): array
    {
        $type = fake()->randomElement(InterviewType::cases());

        return [
            'person_id' => Person::factory(),
            'employment_application_id' => EmploymentApplication::factory(),
            'type' => $type,
            'status' => InterviewStatus::Scheduled,
            'result' => InterviewResult::Pending,
            'scheduled_at' => fake()->dateTimeBetween('+1 day', '+2 weeks'),
            'duration_minutes' => fake()->randomElement([30, 45, 60, 90]),
            'location' => $type === InterviewType::InPerson ? fake()->address() : null,
            'meeting_url' => $type === InterviewType::Online ? fake()->url() : null,
            'interviewer_id' => User::factory(),
            'notes' => fake()->optional(0.3)->sentence(),
            'feedback' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => InterviewStatus::Completed,
            'result' => fake()->randomElement([InterviewResult::Passed, InterviewResult::Failed, InterviewResult::NextRound]),
            'feedback' => fake()->paragraph(),
        ]);
    }

    public function online(): static
    {
        return $this->state(fn () => [
            'type' => InterviewType::Online,
            'location' => null,
            'meeting_url' => fake()->url(),
        ]);
    }

    public function inPerson(): static
    {
        return $this->state(fn () => [
            'type' => InterviewType::InPerson,
            'location' => fake()->address(),
            'meeting_url' => null,
        ]);
    }
}
