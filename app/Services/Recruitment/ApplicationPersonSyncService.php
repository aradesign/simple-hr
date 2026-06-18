<?php

namespace App\Services\Recruitment;

use App\Domain\Enums\Gender;
use App\Domain\Enums\MaritalStatus;
use App\Helpers\JalaliHelper;
use App\Models\EmploymentApplication;
use App\Models\Person;
use App\Services\Person\PersonMobileService;
use App\Support\EmploymentFormFields;
use Carbon\Carbon;

class ApplicationPersonSyncService
{
    public function __construct(
        private readonly PersonMobileService $personMobileService,
    ) {}

    /** @var array<string, string> */
    private const PERSON_FIELD_MAP = [
        'first_name' => 'first_name',
        'last_name' => 'last_name',
        'birth_date' => 'birth_date',
        'national_id' => 'national_id',
        'gender' => 'gender',
        'marital_status' => 'marital_status',
        'mobile' => 'mobile',
        'address' => 'address',
    ];

    public function sync(EmploymentApplication $application, array $formData): Person
    {
        $formData = EmploymentFormFields::normalizeFormData($formData);
        $person = $application->person;
        $payload = [];

        foreach (self::PERSON_FIELD_MAP as $formKey => $personKey) {
            if (! array_key_exists($formKey, $formData)) {
                continue;
            }

            $value = $formData[$formKey];

            $payload[$personKey] = match ($personKey) {
                'birth_date' => $this->parseBirthDate($value),
                'gender' => $this->mapGender($value),
                'marital_status' => $this->mapMaritalStatus($value),
                'mobile' => $this->resolveMobile($person, $value, $application),
                default => is_string($value) ? trim($value) : $value,
            };
        }

        if ($payload !== []) {
            $person->update(array_filter($payload, fn ($value) => $value !== null && $value !== ''));
        }

        $person = $person->fresh();
        $contactMobile = $this->personMobileService->normalizeMobile($application->contact_mobile);

        if ($contactMobile !== null && $this->personMobileService->usesTemporaryMobile($person)) {
            $person = $this->personMobileService->assignRealMobile($person, $contactMobile, $application);
        }

        return $person->fresh();
    }

    public function computeAge(array $formData): ?int
    {
        $formData = EmploymentFormFields::normalizeFormData($formData);
        $birthDate = $this->parseBirthDate($formData['birth_date'] ?? null);

        if (! $birthDate) {
            return null;
        }

        return $birthDate->age;
    }

    private function parseBirthDate(mixed $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value->copy()->startOfDay();
        }

        try {
            return JalaliHelper::parseDate((string) $value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function mapGender(mixed $value): ?Gender
    {
        return match ((string) $value) {
            'آقا', 'male', 'm' => Gender::Male,
            'خانم', 'female', 'f' => Gender::Female,
            default => null,
        };
    }

    private function mapMaritalStatus(mixed $value): ?MaritalStatus
    {
        return match ((string) $value) {
            'مجرد' => MaritalStatus::Single,
            'متأهل' => MaritalStatus::Married,
            'متارکه' => MaritalStatus::Divorced,
            'همسر فوت شده' => MaritalStatus::Widowed,
            default => null,
        };
    }

    private function resolveMobile(Person $person, mixed $value, ?EmploymentApplication $application = null): ?string
    {
        $mobile = $this->personMobileService->normalizeMobile($value);

        if ($mobile === null) {
            return null;
        }

        if (! $this->personMobileService->canUseMobile($person, $mobile, $application)) {
            return null;
        }

        return $mobile;
    }
}
