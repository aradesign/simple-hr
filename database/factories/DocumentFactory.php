<?php

namespace Database\Factories;

use App\Domain\Enums\DocumentType;
use App\Models\Document;
use App\Models\Person;
use Database\Factories\Concerns\UsesPersianFaker;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    use UsesPersianFaker;

    protected $model = Document::class;

    public function definition(): array
    {
        $faker = $this->persianFaker();
        $type = fake()->randomElement(DocumentType::cases());

        return [
            'person_id' => Person::factory(),
            'type' => $type,
            'title' => $this->titleForType($type, $faker),
            'expires_at' => fake()->optional(0.5)->dateTimeBetween('+1 month', '+2 years'),
            'is_active' => true,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'expires_at' => now()->subMonth(),
            'is_active' => false,
        ]);
    }

    public function ofType(DocumentType $type): static
    {
        return $this->state(fn () => [
            'type' => $type,
            'title' => $this->titleForType($type, $this->persianFaker()),
        ]);
    }

    private function titleForType(DocumentType $type, \Faker\Generator $faker): string
    {
        return match ($type) {
            DocumentType::Contract => 'قرارداد همکاری',
            DocumentType::Decree => 'حکم کارگزینی',
            DocumentType::Education => 'مدرک تحصیلی '.$faker->word(),
            DocumentType::NationalId => 'کارت ملی',
            DocumentType::BirthCertificate => 'شناسنامه',
            DocumentType::Certificate => 'گواهینامه '.$faker->word(),
            DocumentType::General => 'سند '.$faker->word(),
        };
    }
}
