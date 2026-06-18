<?php

namespace App\Services\Recruitment;

use App\Domain\Enums\FamilyRelation;
use App\Models\Person;
use App\Models\PersonEducation;
use App\Models\PersonFamilyMember;
use App\Models\PersonWorkExperience;

class ApplicationProfileImportService
{
    /** @param array<string, mixed> $formData */
    public function importFromFormIfEmpty(Person $person, array $formData): void
    {
        if ($person->educations()->doesntExist()) {
            $this->importEducation($person, $formData);
        }

        if ($person->workExperiences()->doesntExist()) {
            $this->importWorkExperience($person, $formData);
        }

        if ($person->familyMembers()->doesntExist()) {
            $this->importFamilyMembers($person, $formData);
        }
    }

    /** @param array<string, mixed> $formData */
    private function importEducation(Person $person, array $formData): void
    {
        $rows = $formData['education_history'] ?? [];

        if (! is_array($rows) || $rows === []) {
            if (filled($formData['education_level'] ?? null)) {
                PersonEducation::query()->create([
                    'person_id' => $person->id,
                    'degree' => (string) $formData['education_level'],
                    'field_of_study' => '—',
                    'institution' => '—',
                    'is_current' => false,
                ]);
            }

            return;
        }

        foreach ($rows as $row) {
            if (! is_array($row) || ! $this->rowHasContent($row, ['major', 'university', 'gpa', 'graduation_year'])) {
                continue;
            }

            PersonEducation::query()->create([
                'person_id' => $person->id,
                'degree' => (string) ($formData['education_level'] ?? '—'),
                'field_of_study' => $this->stringOrDash($row['major'] ?? null),
                'institution' => $this->stringOrDash($row['university'] ?? null),
                'end_date' => null,
                'is_current' => false,
            ]);
        }
    }

    /** @param array<string, mixed> $formData */
    private function importWorkExperience(Person $person, array $formData): void
    {
        if ($this->hasNoWorkExperience($formData)) {
            return;
        }

        $rows = $formData['work_experience'] ?? [];

        if (! is_array($rows)) {
            return;
        }

        foreach ($rows as $row) {
            if (! is_array($row) || ! $this->rowHasContent($row, ['company_name', 'position', 'business_type', 'duration_years', 'leave_reason'])) {
                continue;
            }

            $durationYears = (int) preg_replace('/\D+/', '', (string) ($row['duration_years'] ?? '0'));
            $startDate = $durationYears > 0
                ? now()->subYears($durationYears)->startOfYear()->toDateString()
                : null;

            PersonWorkExperience::query()->create([
                'person_id' => $person->id,
                'company_name' => $this->stringOrDash($row['company_name'] ?? null),
                'position' => $this->stringOrDash($row['position'] ?? null),
                'start_date' => $startDate,
                'is_current' => false,
                'description' => collect([
                    $row['business_type'] ?? null,
                    $row['leave_reason'] ?? null,
                ])->filter()->implode(' — ') ?: null,
            ]);
        }
    }

    /** @param array<string, mixed> $formData */
    private function importFamilyMembers(Person $person, array $formData): void
    {
        $rows = $formData['family_members'] ?? [];

        if (! is_array($rows)) {
            return;
        }

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $fullName = trim((string) ($row['full_name'] ?? ''));

            if ($fullName === '') {
                continue;
            }

            PersonFamilyMember::query()->create([
                'person_id' => $person->id,
                'full_name' => $fullName,
                'relation' => $this->mapFamilyRelation($row['relation'] ?? null),
                'mobile' => $row['phone'] ?? null,
            ]);
        }
    }

    private function mapFamilyRelation(mixed $value): FamilyRelation
    {
        $text = trim((string) $value);

        return match (true) {
            str_contains($text, 'همسر') => FamilyRelation::Spouse,
            str_contains($text, 'فرزند') => FamilyRelation::Child,
            str_contains($text, 'پدر'), str_contains($text, 'مادر'), str_contains($text, 'والد') => FamilyRelation::Parent,
            str_contains($text, 'برادر'), str_contains($text, 'خواهر') => FamilyRelation::Sibling,
            default => FamilyRelation::Other,
        };
    }

    /** @param array<string, mixed> $formData */
    private function hasNoWorkExperience(array $formData): bool
    {
        $answer = trim((string) ($formData['has_work_experience'] ?? ''));

        return in_array($answer, ['خیر', '0', 'false', 'no'], true);
    }

    /** @param array<string, mixed> $row */
    private function rowHasContent(array $row, array $keys): bool
    {
        foreach ($keys as $key) {
            if (filled($row[$key] ?? null)) {
                return true;
            }
        }

        return false;
    }

    private function stringOrDash(mixed $value): string
    {
        $text = trim((string) $value);

        return $text !== '' ? $text : '—';
    }
}
