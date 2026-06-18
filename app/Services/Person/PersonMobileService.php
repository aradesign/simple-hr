<?php

namespace App\Services\Person;

use App\Domain\Enums\ApplicationStatus;
use App\Domain\Enums\PersonLifecycleStatus;
use App\Models\EmploymentApplication;
use App\Models\Person;

class PersonMobileService
{
    public function usesTemporaryMobile(?Person $person): bool
    {
        return $this->isPlaceholderMobile($person?->mobile);
    }

    public function isPlaceholderMobile(?string $mobile): bool
    {
        return $mobile !== null && (str_starts_with($mobile, '098') || str_starts_with($mobile, '099'));
    }

    public function displayMobile(Person $person): ?string
    {
        if ($person->mobile && ! $this->usesTemporaryMobile($person)) {
            return $person->mobile;
        }

        if ($person->managed_by_mobile) {
            return $person->managed_by_mobile;
        }

        $contactMobile = $person->relationLoaded('employmentApplications')
            ? $person->employmentApplications->sortByDesc('id')->first()?->contact_mobile
            : $person->employmentApplications()->latest('id')->value('contact_mobile');

        return $this->normalizeMobile($contactMobile) ?? $person->mobile;
    }

    public function assignRealMobile(
        Person $person,
        mixed $mobile,
        ?EmploymentApplication $application = null,
    ): Person {
        $candidate = $this->normalizeMobile($mobile);

        if ($candidate === null) {
            return $person;
        }

        if ($person->mobile === $candidate && ! $this->usesTemporaryMobile($person)) {
            return $person;
        }

        if (! $this->canUseMobile($person, $candidate, $application)) {
            return $person;
        }

        $person->update([
            'mobile' => $candidate,
            'managed_by_mobile' => $person->managed_by_mobile === $candidate ? null : $person->managed_by_mobile,
        ]);

        return $person->fresh();
    }

    public function canUseMobile(
        Person $person,
        string $mobile,
        ?EmploymentApplication $application = null,
    ): bool {
        $holder = Person::query()
            ->withTrashed()
            ->where('mobile', $mobile)
            ->whereKeyNot($person->id)
            ->first();

        if (! $holder) {
            return true;
        }

        if ($holder->trashed()) {
            return $this->releaseMobileFromTrashedPlaceholder($holder);
        }

        if ($this->reclaimMobileFromGhostApplicant($holder, $person, $application)) {
            return true;
        }

        return false;
    }

    private function releaseMobileFromTrashedPlaceholder(Person $holder): bool
    {
        if (! $this->isStalePlaceholderApplicant($holder)) {
            return false;
        }

        $draftApplication = EmploymentApplication::query()
            ->where('person_id', $holder->id)
            ->orderByDesc('id')
            ->first();

        $holder->update([
            'mobile' => $draftApplication
                ? $this->temporaryMobileForApplication($draftApplication->id)
                : '099'.substr(str_replace('.', '', uniqid('', true)), -9),
        ]);

        return true;
    }

    public function normalizeMobile(mixed $value): ?string
    {
        $mobile = preg_replace('/\D+/', '', (string) $value);

        if ($mobile === '') {
            return null;
        }

        if (str_starts_with($mobile, '9') && strlen($mobile) === 10) {
            return '0'.$mobile;
        }

        return $mobile;
    }

    private function reclaimMobileFromGhostApplicant(
        Person $holder,
        Person $target,
        ?EmploymentApplication $application,
    ): bool {
        if (! $this->isStalePlaceholderApplicant($holder)) {
            return false;
        }

        if ($holder->employmentRecords()->exists()) {
            return false;
        }

        $hasSubmittedApplication = EmploymentApplication::query()
            ->where('person_id', $holder->id)
            ->where('status', '!=', ApplicationStatus::Draft)
            ->when($application, fn ($query) => $query->whereKeyNot($application->id))
            ->exists();

        if ($hasSubmittedApplication) {
            return false;
        }

        $draftApplication = EmploymentApplication::query()
            ->where('person_id', $holder->id)
            ->orderByDesc('id')
            ->first();

        $replacementMobile = $draftApplication
            ? $this->temporaryMobileForApplication($draftApplication->id)
            : '099'.substr(str_replace('.', '', uniqid('', true)), -9);

        $holder->update([
            'mobile' => $replacementMobile,
            'managed_by_mobile' => $holder->managed_by_mobile ?? $holder->mobile,
        ]);

        return true;
    }

    private function isStalePlaceholderApplicant(Person $holder): bool
    {
        if ($holder->lifecycle_status !== PersonLifecycleStatus::Applicant) {
            return false;
        }

        if (in_array($holder->first_name, ['درخواست', 'متقاضی'], true)) {
            return true;
        }

        return $holder->mobile !== null && $this->isPlaceholderMobile($holder->mobile);
    }

    public function temporaryMobileForApplication(int $applicationId): string
    {
        return '098'.str_pad((string) $applicationId, 8, '0', STR_PAD_LEFT);
    }

    public function generateUniquePlaceholderMobile(): string
    {
        do {
            $mobile = '099'.substr(str_replace('.', '', uniqid('', true)), -9);
        } while (Person::withTrashed()->where('mobile', $mobile)->exists());

        return $mobile;
    }
}
