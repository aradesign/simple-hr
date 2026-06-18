<?php

namespace App\Services\Recruitment;

use App\Domain\Enums\ApplicationStatus;
use App\Domain\Enums\PersonLifecycleStatus;
use App\Models\EmploymentApplication;
use App\Models\Person;
use App\Services\Person\PersonMobileService;
use App\Helpers\JalaliHelper;
use App\Support\EmploymentFormFields;
use App\Support\IranianNationalId;
use App\Support\PersianDigits;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GravityFormsCsvImportService
{
    public function __construct(
        private readonly ApplicationService $applicationService,
        private readonly ApplicationPersonSyncService $personSyncService,
        private readonly ApplicationProfileImportService $profileImportService,
        private readonly PersonMobileService $personMobileService,
    ) {}

    /** @return array{imported: int, updated: int, skipped: int, errors: list<string>} */
    public function importFromFile(string $path): array
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new \InvalidArgumentException("Cannot open CSV file: {$path}");
        }

        $headers = fgetcsv($handle, escape: '\\');

        if ($headers === false) {
            fclose($handle);

            throw new \InvalidArgumentException('CSV file is empty.');
        }

        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        while (($row = fgetcsv($handle, escape: '\\')) !== false) {
            if ($this->isEmptyRow($row)) {
                continue;
            }

            try {
                $result = $this->importRow($row);

                match ($result) {
                    'imported' => $imported++,
                    'updated' => $updated++,
                    default => $skipped++,
                };
            } catch (\Throwable $exception) {
                $errors[] = $this->rowLabel($row).': '.$exception->getMessage();
            }
        }

        fclose($handle);

        return compact('imported', 'updated', 'skipped', 'errors');
    }

    public function countImportableRows(string $path): int
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new \InvalidArgumentException("Cannot open CSV file: {$path}");
        }

        fgetcsv($handle, escape: '\\');

        $count = 0;

        while (($row = fgetcsv($handle, escape: '\\')) !== false) {
            if (! $this->isEmptyRow($row)) {
                $count++;
            }
        }

        fclose($handle);

        return $count;
    }

    /**
     * @return array{
     *     processed: int,
     *     imported: int,
     *     updated: int,
     *     skipped: int,
     *     errors: list<string>,
     *     current_label: string|null,
     *     completed: bool
     * }
     */
    public function importBatch(string $path, int $skipRows, int $limit = 1): array
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new \InvalidArgumentException("Cannot open CSV file: {$path}");
        }

        fgetcsv($handle, escape: '\\');

        while ($skipRows > 0 && ($row = fgetcsv($handle, escape: '\\')) !== false) {
            if ($this->isEmptyRow($row)) {
                continue;
            }

            $skipRows--;
        }

        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];
        $processed = 0;
        $currentLabel = null;

        while ($processed < $limit && ($row = fgetcsv($handle, escape: '\\')) !== false) {
            if ($this->isEmptyRow($row)) {
                continue;
            }

            $currentLabel = $this->rowLabel($row);

            try {
                $result = $this->importRow($row);

                match ($result) {
                    'imported' => $imported++,
                    'updated' => $updated++,
                    default => $skipped++,
                };
            } catch (\Throwable $exception) {
                $errors[] = $currentLabel.': '.$exception->getMessage();
            }

            $processed++;
        }

        $completed = true;

        while (($row = fgetcsv($handle, escape: '\\')) !== false) {
            if (! $this->isEmptyRow($row)) {
                $completed = false;

                break;
            }
        }

        fclose($handle);

        return [
            'processed' => $processed,
            'imported' => $imported,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
            'current_label' => $currentLabel,
            'completed' => $completed,
        ];
    }

    /** @return array{fixed: int, invalid_national_ids: list<string>} */
    public function repairImportedApplications(): array
    {
        $fixed = 0;
        $invalidNationalIds = [];

        EmploymentApplication::query()
            ->where('form_data->_import_source', 'gravity_forms_csv')
            ->with('person')
            ->each(function (EmploymentApplication $application) use (&$fixed, &$invalidNationalIds) {
                $formData = EmploymentFormFields::normalizeFormData($application->form_data ?? []);

                if (filled($formData['national_id'] ?? null) && ! IranianNationalId::isValid($formData['national_id'])) {
                    $invalidNationalIds[] = (string) $formData['national_id'];
                    unset($formData['national_id']);
                }

                $age = $this->personSyncService->computeAge($formData);

                if ($age !== null) {
                    $formData['age'] = $age;
                }

                if ($application->submitted_at) {
                    $formData['entry_date'] = JalaliHelper::toDateTimeString($application->submitted_at);
                }

                $application->update([
                    'form_data' => array_merge($formData, array_filter([
                        '_import_source' => $application->form_data['_import_source'] ?? null,
                        '_import_fingerprint' => $application->form_data['_import_fingerprint'] ?? null,
                    ])),
                ]);

                $this->personSyncService->sync($application->fresh(), $formData);
                $fixed++;
            });

        return [
            'fixed' => $fixed,
            'invalid_national_ids' => array_values(array_unique($invalidNationalIds)),
        ];
    }

    /** @param list<string|null> $row */
    private function importRow(array $row): string
    {
        $formData = $this->mapRowToFormData($row);
        $formData = EmploymentFormFields::normalizeFormData($formData);

        if (filled($formData['national_id'] ?? null) && ! IranianNationalId::isValid($formData['national_id'])) {
            unset($formData['national_id']);
        }

        $age = $this->personSyncService->computeAge($formData);

        if ($age !== null) {
            $formData['age'] = $age;
        }

        $submittedAt = $this->parseEntryDate($this->cell($row, 0));
        $formData['entry_date'] = JalaliHelper::toDateTimeString($submittedAt);
        $mobile = $this->personMobileService->normalizeMobile($formData['mobile'] ?? null);

        if ($mobile === null) {
            throw new \RuntimeException('شماره موبایل معتبر نیست.');
        }

        $fingerprint = $this->fingerprint($mobile, $submittedAt, $formData);
        $existing = $this->findExistingApplication($mobile, $submittedAt, $formData);

        if ($existing) {
            return $this->updateImportedApplication($existing, $formData, $mobile, $submittedAt, $fingerprint);
        }

        return DB::transaction(function () use ($formData, $mobile, $submittedAt, $fingerprint) {
            $person = $this->resolvePerson($mobile, $formData);
            $application = EmploymentApplication::query()->create([
                'person_id' => $person->id,
                'contact_mobile' => $mobile,
                'application_number' => $this->applicationService->generateApplicationNumber(),
                'status' => ApplicationStatus::Submitted,
                'form_data' => array_merge($formData, [
                    '_import_source' => 'gravity_forms_csv',
                    '_import_fingerprint' => $fingerprint,
                ]),
                'current_step' => 1,
                'submitted_at' => $submittedAt,
            ]);

            if ($this->personMobileService->isPlaceholderMobile($person->mobile)) {
                $person->update([
                    'mobile' => $this->personMobileService->temporaryMobileForApplication($application->id),
                ]);
            }

            $this->personSyncService->sync($application->fresh(), $formData);
            $this->profileImportService->importFromFormIfEmpty($application->person->fresh(), $formData);

            return 'imported';
        });
    }

    /** @param array<string, mixed> $formData */
    private function updateImportedApplication(
        EmploymentApplication $application,
        array $formData,
        string $mobile,
        Carbon $submittedAt,
        string $fingerprint,
    ): string {
        return DB::transaction(function () use ($application, $formData, $mobile, $submittedAt, $fingerprint) {
            $person = $this->resolvePerson($mobile, $formData);

            $application->update([
                'person_id' => $person->id,
                'contact_mobile' => $mobile,
                'status' => ApplicationStatus::Submitted,
                'form_data' => array_merge($formData, [
                    '_import_source' => 'gravity_forms_csv',
                    '_import_fingerprint' => $fingerprint,
                ]),
                'submitted_at' => $submittedAt,
            ]);

            $this->personSyncService->sync($application->fresh(), $formData);
            $this->profileImportService->importFromFormIfEmpty($application->person->fresh(), $formData);

            return 'updated';
        });
    }

    /** @param array<string, mixed> $formData */
    private function findExistingApplication(string $mobile, Carbon $submittedAt, array $formData): ?EmploymentApplication
    {
        return EmploymentApplication::query()
            ->where('form_data->_import_fingerprint', $this->fingerprint($mobile, $submittedAt, $formData))
            ->first();
    }

    /** @param list<string|null> $row */
    private function mapRowToFormData(array $row): array
    {
        $data = [
            'first_name' => $this->cell($row, 1),
            'last_name' => $this->cell($row, 2),
            'father_name' => $this->cell($row, 3),
            'mother_name' => $this->cell($row, 4),
            'birth_date' => $this->cell($row, 5),
            'age' => $this->cell($row, 6),
            'birth_place' => $this->cell($row, 7),
            'national_id' => $this->cell($row, 8),
            'id_card_number' => $this->cell($row, 9),
            'gender' => $this->cell($row, 11),
            'height_cm' => $this->cell($row, 12),
            'weight_kg' => $this->cell($row, 13),
            'military_service_status' => $this->cell($row, 14),
            'marital_status' => $this->cell($row, 15),
            'children_count' => $this->cell($row, 16),
            'child_custody' => $this->cell($row, 17),
            'mobile' => $this->cell($row, 18),
            'home_phone' => $this->cell($row, 19),
            'has_vehicle' => $this->cell($row, 20),
            'address' => $this->cell($row, 21),
            'education_level' => $this->cell($row, 27),
            'has_work_experience' => $this->cell($row, 35),
            'currently_employed' => $this->cell($row, 43),
            'biggest_career_challenge' => $this->cell($row, 44),
            'preferred_department' => $this->cell($row, 46),
            'medical_conditions' => $this->cell($row, 49),
            'medical_condition_other' => $this->cell($row, 50),
            'had_surgery' => $this->cell($row, 51),
            'surgery_details' => $this->cell($row, 52),
            'has_insurance_history' => $this->cell($row, 53),
            'insurance_years' => $this->cell($row, 54),
            'smoking' => $this->cell($row, 55),
            'knows_company_employee' => $this->cell($row, 56),
            'company_employee_name' => $this->cell($row, 57),
            'currently_studying' => $this->cell($row, 32),
        ];

        $profilePhoto = $this->cell($row, 10);

        if ($profilePhoto !== null) {
            $data['profile_photo'] = $profilePhoto;
        }

        $familyRow = $this->buildFamilyRow($row);

        if ($familyRow !== null) {
            $data['family_members'] = [$familyRow];
        }

        $educationRow = $this->buildEducationRow($row);

        if ($educationRow !== null) {
            $data['education_history'] = [$educationRow];
        }

        $studyRow = $this->buildStudyRow($row);

        if ($studyRow !== null) {
            $data['study_employment_status'] = [$studyRow];
        }

        $workRow = $this->buildWorkRow($row);

        if ($workRow !== null) {
            $data['work_experience'] = [$workRow];
        }

        $data['technical_skills'] = $this->toItemList($this->cell($row, 45), 'skill');
        $data['strengths'] = $this->toItemList($this->cell($row, 47), 'item');
        $data['weaknesses'] = $this->toItemList($this->cell($row, 48), 'item');

        return array_filter($data, fn ($value) => $value !== null && $value !== '' && $value !== []);
    }

    /** @param list<string|null> $row */
    private function buildFamilyRow(array $row): ?array
    {
        $member = [
            'relation' => $this->cell($row, 22),
            'full_name' => $this->cell($row, 23),
            'phone' => $this->normalizePhoneDigits($this->cell($row, 24)),
            'gender' => null,
            'education_level' => $this->cell($row, 26),
        ];

        return $this->rowHasMeaningfulContent($member, ['relation', 'full_name', 'phone']) ? $member : null;
    }

    /** @param list<string|null> $row */
    private function buildEducationRow(array $row): ?array
    {
        $education = [
            'major' => $this->cell($row, 28),
            'university' => $this->cell($row, 29),
            'gpa' => $this->cell($row, 30),
            'graduation_year' => $this->cell($row, 31),
        ];

        return $this->rowHasMeaningfulContent($education, ['major', 'university', 'gpa', 'graduation_year']) ? $education : null;
    }

    /** @param list<string|null> $row */
    private function buildStudyRow(array $row): ?array
    {
        $study = [
            'current_term' => $this->cell($row, 33),
            'major' => $this->cell($row, 34),
        ];

        if (! filled($this->cell($row, 32)) && ! $this->rowHasMeaningfulContent($study, ['current_term', 'major'])) {
            return null;
        }

        return $study;
    }

    /** @param list<string|null> $row */
    private function buildWorkRow(array $row): ?array
    {
        if ($this->cell($row, 35) === 'خیر') {
            return null;
        }

        $work = [
            'company_name' => $this->cell($row, 36),
            'business_type' => $this->cell($row, 37),
            'position' => $this->cell($row, 38),
            'duration_years' => $this->cell($row, 39),
            'company_phone' => $this->normalizePhoneDigits($this->cell($row, 40)),
            'leave_reason' => $this->cell($row, 41),
            'last_salary' => $this->cell($row, 42),
        ];

        return $this->rowHasMeaningfulContent($work, ['company_name', 'position', 'business_type', 'duration_years', 'leave_reason']) ? $work : null;
    }

    /** @return list<array<string, string>> */
    private function toItemList(?string $value, string $key): array
    {
        if (! filled($value)) {
            return [];
        }

        return collect(preg_split('/\s*,\s*/u', $value) ?: [])
            ->map(fn ($item) => trim((string) $item))
            ->filter(fn ($item) => mb_strlen($item) > 1)
            ->map(fn ($item) => [$key => $item])
            ->values()
            ->all();
    }

    /** @param array<string, mixed> $formData */
    private function resolvePerson(string $mobile, array $formData): Person
    {
        $nationalId = filled($formData['national_id'] ?? null) && IranianNationalId::isValid($formData['national_id'])
            ? IranianNationalId::normalize($formData['national_id'])
            : null;

        if ($nationalId) {
            $person = Person::withTrashed()->where('national_id', $nationalId)->first();

            if ($person) {
                if ($person->trashed()) {
                    $person->restore();
                }

                $this->mergeImportPersonIdentity($person, $formData, $mobile);

                return $person->fresh();
            }
        }

        if ($nationalId === null) {
            $person = Person::query()
                ->where('lifecycle_status', PersonLifecycleStatus::Applicant)
                ->where('managed_by_mobile', $mobile)
                ->first();

            if ($person) {
                $this->mergeImportPersonIdentity($person, $formData, $mobile);

                return $person->fresh();
            }
        }

        return Person::query()->create([
            'first_name' => $formData['first_name'] ?? 'متقاضی',
            'last_name' => $formData['last_name'] ?? 'وارداتی',
            'national_id' => $nationalId,
            'lifecycle_status' => PersonLifecycleStatus::Applicant,
            'mobile' => $this->personMobileService->generateUniquePlaceholderMobile(),
            'managed_by_mobile' => $mobile,
        ]);
    }

    /** @param array<string, mixed> $formData */
    private function mergeImportPersonIdentity(Person $person, array $formData, string $contactMobile): void
    {
        $updates = [];

        if (filled($formData['first_name'] ?? null)) {
            $updates['first_name'] = trim((string) $formData['first_name']);
        }

        if (filled($formData['last_name'] ?? null)) {
            $updates['last_name'] = trim((string) $formData['last_name']);
        }

        if ($person->managed_by_mobile !== $contactMobile) {
            $updates['managed_by_mobile'] = $contactMobile;
        }

        $nationalId = filled($formData['national_id'] ?? null) && IranianNationalId::isValid($formData['national_id'])
            ? IranianNationalId::normalize($formData['national_id'])
            : null;

        if ($nationalId && ! $person->national_id) {
            $updates['national_id'] = $nationalId;
        }

        if ($updates !== []) {
            $person->update($updates);
        }
    }

    /** @param array<string, mixed> $formData */
    private function fingerprint(string $mobile, Carbon $submittedAt, array $formData): string
    {
        $nationalId = IranianNationalId::normalize($formData['national_id'] ?? null) ?? '';

        return hash('sha256', implode('|', [
            $nationalId,
            $submittedAt->toDateTimeString(),
            $mobile,
        ]));
    }

    private function parseEntryDate(?string $value): Carbon
    {
        return JalaliHelper::parseGregorianDateTime($value) ?? now();
    }

    /** @param list<string|null> $row */
    private function isEmptyRow(array $row): bool
    {
        return trim(implode('', array_map(fn ($cell) => (string) ($cell ?? ''), $row))) === '';
    }

    /** @param list<string|null> $row */
    private function rowLabel(array $row): string
    {
        return trim(($this->cell($row, 1) ?? 'ردیف').' '.($this->cell($row, 2) ?? ''));
    }

    /** @param list<string|null> $row */
    private function cell(array $row, int $index): ?string
    {
        $value = $row[$index] ?? null;

        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    /** @param array<string, mixed> $row */
    private function rowHasMeaningfulContent(array $row, array $keys): bool
    {
        foreach ($keys as $key) {
            $value = $row[$key] ?? null;

            if (! filled($value)) {
                continue;
            }

            if (is_string($value) && mb_strlen(trim($value)) <= 1) {
                continue;
            }

            return true;
        }

        return false;
    }

    private function normalizePhoneDigits(?string $value): ?string
    {
        if (! filled($value)) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', PersianDigits::toEnglish($value));

        return $digits === '' ? null : $digits;
    }
}
