<?php

namespace App\Services\Recruitment;

use App\Domain\Enums\ApplicationStatus;
use App\Domain\Enums\PersonLifecycleStatus;
use App\Domain\Events\ApplicationStatusChanged;
use App\DTOs\ApplicationData;
use App\Models\EmploymentApplication;
use App\Models\Person;
use App\Models\User;
use App\Support\EmploymentFormFields;
use Illuminate\Support\Facades\DB;

class ApplicationService
{
    public function __construct(
        private readonly ApplicationAcceptanceService $acceptanceService,
    ) {}

    public function create(Person $person, ?string $contactMobile = null, ?array $formData = null): EmploymentApplication
    {
        return EmploymentApplication::query()->create([
            'person_id' => $person->id,
            'contact_mobile' => $contactMobile,
            'application_number' => $this->generateApplicationNumber(),
            'status' => ApplicationStatus::Draft,
            'form_data' => $formData ?? [],
            'current_step' => 1,
        ]);
    }

    public function createForContact(string $contactMobile): EmploymentApplication
    {
        return DB::transaction(function () use ($contactMobile) {
            $person = Person::query()
                ->where('lifecycle_status', PersonLifecycleStatus::Applicant)
                ->where(function ($query) use ($contactMobile) {
                    $query->where('mobile', $contactMobile)
                        ->orWhere('managed_by_mobile', $contactMobile);
                })
                ->first();

            if (! $person) {
                $person = Person::query()->create([
                    'first_name' => 'درخواست',
                    'last_name' => 'جدید',
                    'mobile' => '099'.substr(str_replace('.', '', uniqid('', true)), -9),
                    'managed_by_mobile' => $contactMobile,
                    'lifecycle_status' => PersonLifecycleStatus::Applicant,
                ]);

                $application = $this->create($person, $contactMobile);

                $person->update([
                    'mobile' => $this->temporaryMobile($application->id),
                ]);

                return $application->fresh(['person']);
            }

            if ($person->managed_by_mobile !== $contactMobile) {
                $person->update(['managed_by_mobile' => $contactMobile]);
            }

            return $this->create($person, $contactMobile)->fresh(['person']);
        });
    }

    public function submit(EmploymentApplication $application, array $formData): EmploymentApplication
    {
        return DB::transaction(function () use ($application, $formData) {
            $oldStatus = $application->status;
            $merged = EmploymentFormFields::normalizeFormData(array_merge(
                EmploymentFormFields::normalizeFormData($application->form_data ?? []),
                $formData,
            ));

            $application->update([
                'form_data' => $merged,
                'status' => ApplicationStatus::Submitted,
                'submitted_at' => now(),
            ]);

            if ($application->person->lifecycle_status !== PersonLifecycleStatus::Employee
                && $application->person->lifecycle_status !== PersonLifecycleStatus::FormerEmployee) {
                $application->person->update([
                    'lifecycle_status' => PersonLifecycleStatus::Applicant,
                ]);
            }

            $application = $application->fresh();

            if ($oldStatus !== ApplicationStatus::Submitted) {
                event(new ApplicationStatusChanged(
                    application: $application,
                    oldStatus: $oldStatus,
                    newStatus: ApplicationStatus::Submitted,
                ));
            }

            return $application;
        });
    }

    public function saveDraft(EmploymentApplication $application, array $formData): EmploymentApplication
    {
        $application->update([
            'form_data' => EmploymentFormFields::normalizeFormData($formData),
        ]);

        return $application->fresh();
    }

    public function update(ApplicationData $data, EmploymentApplication $application): EmploymentApplication
    {
        $application->update($data->toArray());

        return $application->fresh();
    }

    public function updateStatus(
        EmploymentApplication $application,
        ApplicationStatus $status,
        ?User $changedBy = null,
    ): EmploymentApplication {
        $oldStatus = $application->status;

        $application->update([
            'status' => $status,
            'reviewed_at' => in_array($status, [
                ApplicationStatus::Accepted,
                ApplicationStatus::Rejected,
            ], true) ? now() : $application->reviewed_at,
        ]);

        $application = $application->fresh();

        if ($status === ApplicationStatus::Accepted) {
            $this->acceptanceService->handle($application->loadMissing('person'), $changedBy);
            $application = $application->fresh();
        }

        if ($oldStatus !== $status) {
            event(new ApplicationStatusChanged(
                application: $application,
                oldStatus: $oldStatus,
                newStatus: $status,
                changedBy: $changedBy,
            ));
        }

        return $application;
    }

    public function delete(EmploymentApplication $application): void
    {
        $application->delete();
    }

    public function generateApplicationNumber(): string
    {
        $prefix = 'APP-'.now()->format('Ymd');

        $latest = EmploymentApplication::query()
            ->withTrashed()
            ->where('application_number', 'like', "{$prefix}-%")
            ->orderByDesc('application_number')
            ->value('application_number');

        $sequence = 1;

        if ($latest && preg_match('/-(\d+)$/', $latest, $matches)) {
            $sequence = (int) $matches[1] + 1;
        }

        $number = sprintf('%s-%04d', $prefix, $sequence);

        while (EmploymentApplication::query()->withTrashed()->where('application_number', $number)->exists()) {
            $sequence++;
            $number = sprintf('%s-%04d', $prefix, $sequence);
        }

        return $number;
    }

    private function temporaryMobile(int $applicationId): string
    {
        return '098'.str_pad((string) $applicationId, 8, '0', STR_PAD_LEFT);
    }
}
