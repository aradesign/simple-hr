<?php

namespace App\Services\Person;

use App\Domain\Enums\ApplicationStatus;
use App\Domain\Enums\EmploymentStatus;
use App\Domain\Enums\EmploymentType;
use App\Domain\Enums\PersonLifecycleStatus;
use App\Helpers\JalaliHelper;
use App\Models\Department;
use App\Models\EmploymentApplication;
use App\Models\EmploymentRecord;
use App\Models\Person;
use App\Services\Recruitment\ApplicationPersonSyncService;
use App\Services\Recruitment\ApplicationProfileImportService;
use App\Services\Recruitment\ApplicationService;
use App\Support\EmploymentFormFields;
use App\Support\IranianNationalId;
use App\Support\PersianDigits;
use App\Support\PersonnelCsvColumnMap;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PersonnelCsvImportService
{
    public function __construct(
        private readonly PersonnelCsvColumnMap $columnMap,
        private readonly ApplicationService $applicationService,
        private readonly ApplicationPersonSyncService $personSyncService,
        private readonly ApplicationProfileImportService $profileImportService,
        private readonly PersonMobileService $personMobileService,
    ) {}

    /** @return array{imported: int, updated: int, skipped: int, errors: list<string>} */
    public function importFromFile(string $path): array
    {
        [$headers, $rows] = $this->readCsv($path);

        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $row) {
            try {
                $result = $this->importRow($headers, $row);

                match ($result) {
                    'imported' => $imported++,
                    'updated' => $updated++,
                    default => $skipped++,
                };
            } catch (\Throwable $exception) {
                $errors[] = $this->rowLabel($headers, $row).': '.$exception->getMessage();
            }
        }

        return compact('imported', 'updated', 'skipped', 'errors');
    }

    public function countImportableRows(string $path): int
    {
        [, $rows] = $this->readCsv($path);

        return count($rows);
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
        [$headers, $rows] = $this->readCsv($path);
        $rows = array_slice($rows, $skipRows, $limit);

        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];
        $currentLabel = null;

        foreach ($rows as $row) {
            $currentLabel = $this->rowLabel($headers, $row);

            try {
                $result = $this->importRow($headers, $row);

                match ($result) {
                    'imported' => $imported++,
                    'updated' => $updated++,
                    default => $skipped++,
                };
            } catch (\Throwable $exception) {
                $errors[] = $currentLabel.': '.$exception->getMessage();
            }
        }

        $totalRows = $this->countImportableRows($path);

        return [
            'processed' => count($rows),
            'imported' => $imported,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
            'current_label' => $currentLabel,
            'completed' => ($skipRows + count($rows)) >= $totalRows,
        ];
    }

    /** @param list<string|null> $headers @param list<string|null> $row */
    private function importRow(array $headers, array $row): string
    {
        [$formData, $meta] = $this->mapRowToFormData($headers, $row);
        $formData = $this->appendMetaExtras($formData, $meta);
        $formData = EmploymentFormFields::normalizeFormData($formData);

        if (filled($formData['national_id'] ?? null) && ! IranianNationalId::isValid($formData['national_id'])) {
            unset($formData['national_id']);
        }

        $age = $this->personSyncService->computeAge($formData);

        if ($age !== null) {
            $formData['age'] = $age;
        }

        $mobile = $this->personMobileService->normalizeMobile($formData['mobile'] ?? null);

        if ($mobile === null) {
            throw new \RuntimeException('شماره موبایل معتبر نیست.');
        }

        $formData['mobile'] = $mobile;
        $fingerprint = $this->fingerprint($formData, $meta);
        $existing = $this->findExistingApplication($fingerprint, $formData, $meta);

        if ($existing) {
            return $this->updateImportedPersonnel($existing, $formData, $meta, $mobile, $fingerprint);
        }

        return DB::transaction(function () use ($formData, $meta, $mobile, $fingerprint) {
            $person = $this->resolvePerson($mobile, $formData);
            $application = EmploymentApplication::query()->create([
                'person_id' => $person->id,
                'contact_mobile' => $mobile,
                'application_number' => $this->applicationService->generateApplicationNumber(),
                'status' => ApplicationStatus::Draft,
                'form_data' => array_merge($formData, [
                    '_import_source' => 'personnel_csv',
                    '_import_fingerprint' => $fingerprint,
                    '_import_source_user_id' => $meta['source_user_id'] ?? null,
                ]),
                'current_step' => 1,
            ]);

            $this->personSyncService->sync($application, $formData);
            $this->assignRealMobile($person->fresh(), $mobile);
            $this->profileImportService->importFromFormIfEmpty($application->person->fresh(), $formData);
            $this->ensureEmployee($application->person->fresh(), $formData, $meta);

            return 'imported';
        });
    }

    /** @param array<string, mixed> $formData @param array<string, mixed> $meta */
    private function updateImportedPersonnel(
        EmploymentApplication $application,
        array $formData,
        array $meta,
        string $mobile,
        string $fingerprint,
    ): string {
        return DB::transaction(function () use ($application, $formData, $meta, $mobile, $fingerprint) {
            $person = $this->resolvePerson($mobile, $formData);

            $application->update([
                'person_id' => $person->id,
                'contact_mobile' => $mobile,
                'form_data' => array_merge($formData, [
                    '_import_source' => 'personnel_csv',
                    '_import_fingerprint' => $fingerprint,
                    '_import_source_user_id' => $meta['source_user_id'] ?? $application->form_data['_import_source_user_id'] ?? null,
                ]),
            ]);

            $this->personSyncService->sync($application->fresh(), $formData);
            $this->assignRealMobile($person->fresh(), $mobile);
            $this->profileImportService->importFromFormIfEmpty($application->person->fresh(), $formData);
            $this->ensureEmployee($application->person->fresh(), $formData, $meta);

            return 'updated';
        });
    }

    /**
     * @param list<string|null> $headers
     * @param list<string|null> $row
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    private function mapRowToFormData(array $headers, array $row): array
    {
        $formData = [];
        $meta = [];
        $extras = [];

        foreach ($headers as $index => $header) {
            if (! filled($header)) {
                continue;
            }

            $value = $this->cell($row, $index);

            if ($value === null) {
                continue;
            }

            $target = $this->columnMap->resolve((string) $header);

            if ($target === null) {
                continue;
            }

            if (str_starts_with($target, '_meta:')) {
                $meta[substr($target, 6)] = $value;

                continue;
            }

            if (str_starts_with($target, '_extra:')) {
                $extras[substr($target, 7)] = $value;

                continue;
            }

            $formData[$target] = $value;
        }

        $formData = $this->transformMappedValues($formData);

        if ($extras !== []) {
            $formData['_personnel_import_extra'] = array_merge(
                is_array($formData['_personnel_import_extra'] ?? null) ? $formData['_personnel_import_extra'] : [],
                $extras,
            );
        }

        return [$formData, $meta];
    }

    /** @param array<string, mixed> $formData @param array<string, mixed> $meta */
    private function appendMetaExtras(array $formData, array $meta): array
    {
        $extras = is_array($formData['_personnel_import_extra'] ?? null)
            ? $formData['_personnel_import_extra']
            : [];

        foreach ($meta as $key => $value) {
            if ($key === 'source_user_id' || $key === 'employment_start_date') {
                continue;
            }

            $extras[$key] = $value;
        }

        if ($extras !== []) {
            $formData['_personnel_import_extra'] = $extras;
        }

        return $formData;
    }

    /** @param array<string, mixed> $formData */
    private function transformMappedValues(array $formData): array
    {
        if (filled($formData['gender'] ?? null)) {
            $formData['gender'] = match (trim((string) $formData['gender'])) {
                'مرد', 'male', 'm' => 'آقا',
                'زن', 'female', 'f' => 'خانم',
                default => $formData['gender'],
            };
        }

        if (filled($formData['birth_date'] ?? null)) {
            $formData['birth_date'] = $this->normalizeBirthDate((string) $formData['birth_date']);
        }

        if (filled($formData['entry_date'] ?? null)) {
            $parsed = JalaliHelper::parseGregorianDateTime((string) $formData['entry_date'])
                ?? $this->parseLooseJalaliDateTime((string) $formData['entry_date']);

            if ($parsed) {
                $formData['entry_date'] = JalaliHelper::toDateTimeString($parsed);
            }
        }

        if (filled($formData['education_level'] ?? null) && ! isset($formData['education_history'])) {
            $formData['education_history'] = [[
                'major' => null,
                'university' => null,
                'gpa' => null,
                'graduation_year' => null,
            ]];
        }

        return $formData;
    }

    private function normalizeBirthDate(string $value): ?string
    {
        $value = trim(PersianDigits::toEnglish($value));

        if ($value === '') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/', $value)) {
            $parts = explode('-', $value);
            $jalali = sprintf('%s/%02d/%02d', $parts[0], (int) $parts[1], (int) $parts[2]);

            return JalaliHelper::normalizeDateString($jalali);
        }

        return JalaliHelper::normalizeDateString($value);
    }

    private function parseLooseJalaliDateTime(string $value): ?Carbon
    {
        $value = trim(PersianDigits::toEnglish($value));

        if (preg_match('/(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})/', $value, $matches)) {
            return JalaliHelper::parseDate(sprintf('%s/%s/%s', $matches[1], $matches[2], $matches[3]));
        }

        return null;
    }

    /** @param array<string, mixed> $formData @param array<string, mixed> $meta */
    private function fingerprint(array $formData, array $meta): string
    {
        $nationalId = IranianNationalId::normalize($formData['national_id'] ?? null) ?? '';
        $sourceUserId = (string) ($meta['source_user_id'] ?? '');

        return hash('sha256', implode('|', [
            'personnel_csv',
            $sourceUserId,
            $nationalId,
            $this->personMobileService->normalizeMobile($formData['mobile'] ?? null) ?? '',
        ]));
    }

    /** @param array<string, mixed> $formData @param array<string, mixed> $meta */
    private function findExistingApplication(string $fingerprint, array $formData, array $meta): ?EmploymentApplication
    {
        $existing = EmploymentApplication::query()
            ->where('form_data->_import_fingerprint', $fingerprint)
            ->first();

        if ($existing) {
            return $existing;
        }

        if (filled($meta['source_user_id'] ?? null)) {
            $existing = EmploymentApplication::query()
                ->where('form_data->_import_source', 'personnel_csv')
                ->where('form_data->_import_source_user_id', (string) $meta['source_user_id'])
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        $nationalId = IranianNationalId::normalize($formData['national_id'] ?? null);

        if ($nationalId) {
            return EmploymentApplication::query()
                ->where('form_data->_import_source', 'personnel_csv')
                ->whereHas('person', fn ($query) => $query->where('national_id', $nationalId))
                ->orderByDesc('id')
                ->first();
        }

        return null;
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

                $this->mergePersonIdentity($person, $formData, $mobile);

                return $person->fresh();
            }
        }

        $person = Person::query()
            ->where('mobile', $mobile)
            ->orWhere('managed_by_mobile', $mobile)
            ->first();

        if ($person) {
            $this->mergePersonIdentity($person, $formData, $mobile);

            return $person->fresh();
        }

        return Person::query()->create([
            'first_name' => $formData['first_name'] ?? 'پرسنل',
            'last_name' => $formData['last_name'] ?? 'وارداتی',
            'national_id' => $nationalId,
            'lifecycle_status' => PersonLifecycleStatus::Employee,
            'mobile' => $mobile,
            'managed_by_mobile' => null,
        ]);
    }

    /** @param array<string, mixed> $formData */
    private function mergePersonIdentity(Person $person, array $formData, string $mobile): void
    {
        $updates = [];

        if (filled($formData['first_name'] ?? null)) {
            $updates['first_name'] = trim((string) $formData['first_name']);
        }

        if (filled($formData['last_name'] ?? null)) {
            $updates['last_name'] = trim((string) $formData['last_name']);
        }

        if ($person->mobile !== $mobile && $this->personMobileService->canUseMobile($person, $mobile)) {
            $updates['mobile'] = $mobile;
            $updates['managed_by_mobile'] = null;
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

    private function assignRealMobile(Person $person, string $mobile): void
    {
        if ($person->mobile === $mobile && ! $this->personMobileService->usesTemporaryMobile($person)) {
            return;
        }

        if ($this->personMobileService->canUseMobile($person, $mobile)) {
            $person->update([
                'mobile' => $mobile,
                'managed_by_mobile' => null,
            ]);
        }
    }

    /** @param array<string, mixed> $formData @param array<string, mixed> $meta */
    private function ensureEmployee(Person $person, array $formData, array $meta): void
    {
        if ($person->lifecycle_status !== PersonLifecycleStatus::Employee) {
            $person->update(['lifecycle_status' => PersonLifecycleStatus::Employee]);
        }

        if (filled($formData['profile_photo'] ?? null) && ! $person->profile_photo) {
            $person->update(['profile_photo' => (string) $formData['profile_photo']]);
        }

        if ($person->employmentRecords()->exists()) {
            return;
        }

        $startDate = $this->resolveEmploymentStartDate($meta, $formData);
        $department = $this->resolveDepartment($formData['preferred_department'] ?? null);

        EmploymentRecord::query()->create([
            'person_id' => $person->id,
            'department_id' => $department?->id,
            'employee_code' => $this->generateEmployeeCode(),
            'employment_type' => EmploymentType::FullTime,
            'status' => EmploymentStatus::Active,
            'start_date' => $startDate,
            'position_title' => filled($formData['preferred_department'] ?? null)
                ? (string) $formData['preferred_department']
                : 'کارمند',
            'notes' => 'ایجاد خودکار از import پرسنل',
        ]);

        if ($department) {
            $person->departments()->syncWithoutDetaching([
                $department->id => [
                    'joined_at' => $startDate,
                    'is_primary' => true,
                ],
            ]);
        }
    }

    /** @param array<string, mixed> $meta @param array<string, mixed> $formData */
    private function resolveEmploymentStartDate(array $meta, array $formData): string
    {
        foreach (['employment_start_date'] as $key) {
            if (! filled($meta[$key] ?? null)) {
                continue;
            }

            $parsed = $this->parseEmploymentStartDate((string) $meta[$key]);

            if ($parsed) {
                return $parsed;
            }
        }

        if (filled($formData['entry_date'] ?? null)) {
            $parsed = JalaliHelper::parseGregorianDateTime((string) $formData['entry_date'])
                ?? $this->parseLooseJalaliDateTime((string) $formData['entry_date']);

            if ($parsed) {
                return $parsed->toDateString();
            }
        }

        return now()->toDateString();
    }

    private function parseEmploymentStartDate(string $value): ?string
    {
        $value = trim(PersianDigits::toEnglish($value));

        if (preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/', $value)) {
            $parts = explode('-', $value);
            $jalali = sprintf('%s/%02d/%02d', $parts[0], (int) $parts[1], (int) $parts[2]);
            $date = JalaliHelper::parseDate($jalali);

            return $date?->toDateString();
        }

        $parsed = $this->parseLooseJalaliDateTime($value);

        return $parsed?->toDateString();
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

    /** @return array{0: list<string|null>, 1: list<list<string|null>>} */
    private function readCsv(string $path): array
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

        $rows = [];

        while (($row = fgetcsv($handle, escape: '\\')) !== false) {
            if ($this->isEmptyRow($row)) {
                continue;
            }

            $rows[] = $row;
        }

        fclose($handle);

        return [$headers, $rows];
    }

    /** @param list<string|null> $headers @param list<string|null> $row */
    private function rowLabel(array $headers, array $row): string
    {
        $firstName = null;
        $lastName = null;

        foreach ($headers as $index => $header) {
            $target = $this->columnMap->resolve((string) $header);

            if ($target === 'first_name') {
                $firstName = $this->cell($row, $index);
            }

            if ($target === 'last_name') {
                $lastName = $this->cell($row, $index);
            }
        }

        return trim(($firstName ?? 'ردیف').' '.($lastName ?? ''));
    }

    /** @param list<string|null> $row */
    private function isEmptyRow(array $row): bool
    {
        return trim(implode('', array_map(fn ($cell) => (string) ($cell ?? ''), $row))) === '';
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
}
