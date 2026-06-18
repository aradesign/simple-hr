<?php

namespace App\Services\Recruitment;

use App\Domain\Enums\ApplicationStatus;
use App\Domain\Enums\EmploymentStatus;
use App\Domain\Enums\EmploymentType;
use App\Domain\Enums\PersonLifecycleStatus;
use App\Models\Department;
use App\Models\EmploymentApplication;
use App\Models\EmploymentRecord;
use App\Models\Person;
use App\Models\User;
use App\Services\Person\PersonMobileService;
use App\Services\Person\PersonService;
use App\Support\EmploymentFormFields;
use Illuminate\Support\Facades\DB;

class ApplicationAcceptanceService
{
    public function __construct(
        private readonly ApplicationPersonSyncService $personSyncService,
        private readonly ApplicationProfileImportService $profileImportService,
        private readonly PersonService $personService,
        private readonly PersonMobileService $personMobileService,
    ) {}

    public function handle(EmploymentApplication $application, ?User $changedBy = null): Person
    {
        if ($application->status !== ApplicationStatus::Accepted) {
            return $application->person ?? throw new \RuntimeException('Application has no person.');
        }

        $application->loadMissing('person');
        $person = $application->person;

        if (! $person) {
            throw new \RuntimeException('Application has no linked person.');
        }

        if ($person->lifecycle_status === PersonLifecycleStatus::Employee
            && $person->employmentRecords()->exists()) {
            return $this->syncAcceptedApplication($application, $person);
        }

        return DB::transaction(function () use ($application, $person, $changedBy) {
            $formData = EmploymentFormFields::normalizeFormData($application->form_data ?? []);

            $person = $this->personSyncService->sync($application, $formData);
            $person = $this->restoreRealMobile($application, $person, $formData);
            $this->profileImportService->importFromFormIfEmpty($person, $formData);

            $department = $this->resolveDepartment($formData['preferred_department'] ?? null);

            $person = $this->personService->convertApplicantToEmployee(
                $person->fresh(),
                [
                    'department_id' => $department?->id,
                    'employee_code' => $this->generateEmployeeCode(),
                    'employment_type' => EmploymentType::FullTime,
                    'status' => EmploymentStatus::Active,
                    'start_date' => now()->toDateString(),
                    'position_title' => filled($formData['preferred_department'] ?? null)
                        ? (string) $formData['preferred_department']
                        : 'کارمند',
                    'notes' => 'ایجاد خودکار از درخواست '.$application->application_number,
                ],
                $changedBy,
            );

            if ($department) {
                $person->departments()->syncWithoutDetaching([
                    $department->id => [
                        'joined_at' => now()->toDateString(),
                        'is_primary' => true,
                    ],
                ]);
            }

            return $person->fresh(['employmentRecords.department', 'departments']);
        });
    }

    private function syncAcceptedApplication(EmploymentApplication $application, Person $person): Person
    {
        $formData = EmploymentFormFields::normalizeFormData($application->form_data ?? []);

        $person = $this->personSyncService->sync($application, $formData);
        $person = $this->restoreRealMobile($application, $person, $formData);
        $this->profileImportService->importFromFormIfEmpty($person, $formData);

        return $person->fresh(['employmentRecords.department', 'departments']);
    }

    /** @param array<string, mixed> $formData */
    private function restoreRealMobile(
        EmploymentApplication $application,
        Person $person,
        array $formData,
    ): Person {
        $candidate = $this->personMobileService->normalizeMobile($formData['mobile'] ?? null)
            ?? $this->personMobileService->normalizeMobile($application->contact_mobile);

        if ($candidate === null) {
            return $person;
        }

        return $this->personMobileService->assignRealMobile($person, $candidate, $application);
    }

    private function resolveDepartment(?string $preferredDepartment): ?Department
    {
        if (! filled($preferredDepartment)) {
            return null;
        }

        $normalized = trim($preferredDepartment);

        return Department::query()
            ->active()
            ->where(function ($query) use ($normalized) {
                $query->where('name', $normalized)
                    ->orWhere('name', 'like', '%'.$normalized.'%')
                    ->orWhere('code', 'like', '%'.$normalized.'%');
            })
            ->orderBy('sort_order')
            ->first();
    }

    private function generateEmployeeCode(): string
    {
        $prefix = 'EMP-'.now()->format('Y');

        $latest = EmploymentRecord::query()
            ->withTrashed()
            ->where('employee_code', 'like', "{$prefix}-%")
            ->orderByDesc('employee_code')
            ->value('employee_code');

        $sequence = 1;

        if ($latest && preg_match('/-(\d+)$/', $latest, $matches)) {
            $sequence = (int) $matches[1] + 1;
        }

        $code = sprintf('%s-%04d', $prefix, $sequence);

        while (EmploymentRecord::query()->withTrashed()->where('employee_code', $code)->exists()) {
            $sequence++;
            $code = sprintf('%s-%04d', $prefix, $sequence);
        }

        return $code;
    }
}
