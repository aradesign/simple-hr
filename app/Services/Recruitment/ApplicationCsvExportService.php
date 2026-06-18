<?php

namespace App\Services\Recruitment;

use App\Domain\Enums\FormFieldType;
use App\Helpers\JalaliHelper;
use App\Models\ApplicationFormField;
use App\Models\EmploymentApplication;
use App\Services\Person\PersonMobileService;
use App\Support\EmploymentFormFields;
use Illuminate\Support\Collection;

class ApplicationCsvExportService
{
    /** @var Collection<string, ApplicationFormField> */
    private Collection $fieldsByKey;

    public function __construct(
        private readonly ApplicationFormSchemaService $schemaService,
        private readonly ApplicationFormDisplayService $displayService,
        private readonly PersonMobileService $personMobileService,
    ) {}

    /** @param Collection<int, EmploymentApplication>|iterable<EmploymentApplication> $applications */
    public function writeToStream(iterable $applications): void
    {
        $applications = $applications instanceof Collection
            ? $applications
            : collect($applications);

        $fields = $this->schemaService->getAllFields();

        $this->fieldsByKey = $fields->keyBy('field_key');
        $maxListRows = $this->maxListRowsByField($applications, $fields);
        $columns = $this->buildColumns($fields, $maxListRows, $this->collectExtraKeys($applications, $fields));

        $handle = fopen('php://output', 'w');

        if ($handle === false) {
            throw new \RuntimeException('Cannot open output stream.');
        }

        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, array_column($columns, 'label'), escape: '\\');

        foreach ($applications as $application) {
            fputcsv($handle, $this->rowValues($application, $columns), escape: '\\');
        }

        fclose($handle);
    }

    /**
     * @param Collection<int, ApplicationFormField> $fields
     * @param array<string, int> $maxListRows
     * @param list<string> $extraKeys
     * @return list<array{key: string, label: string, field_key?: string, list_index?: int, list_col_key?: string}>
     */
    private function buildColumns(Collection $fields, array $maxListRows, array $extraKeys): array
    {
        $columns = [
            ['key' => '_application_number', 'label' => 'شماره درخواست'],
            ['key' => '_status', 'label' => 'وضعیت'],
            ['key' => '_submitted_at', 'label' => 'تاریخ ارسال'],
            ['key' => '_created_at', 'label' => 'تاریخ ایجاد'],
            ['key' => '_contact_mobile', 'label' => 'موبایل تماس'],
            ['key' => '_person_name', 'label' => 'نام پرسنل'],
            ['key' => '_display_mobile', 'label' => 'موبایل نمایشی'],
            ['key' => '_hr_notes', 'label' => 'یادداشت منابع انسانی'],
        ];

        foreach ($fields as $field) {
            if ($field->field_type === FormFieldType::List) {
                $listColumns = $field->list_columns
                    ?? EmploymentFormFields::LIST_COLUMNS[$field->field_key]
                    ?? [];
                $rowCount = max(1, $maxListRows[$field->field_key] ?? 1);

                for ($index = 0; $index < $rowCount; $index++) {
                    foreach ($listColumns as $listColumn) {
                        $columns[] = [
                            'key' => $field->field_key.'.'.$index.'.'.$listColumn['key'],
                            'label' => $field->label.' '.($index + 1).' - '.$listColumn['label'],
                            'field_key' => $field->field_key,
                            'list_index' => $index,
                            'list_col_key' => $listColumn['key'],
                        ];
                    }
                }

                continue;
            }

            $columns[] = [
                'key' => $field->field_key,
                'label' => $field->label,
                'field_key' => $field->field_key,
            ];
        }

        foreach ($extraKeys as $key) {
            $columns[] = [
                'key' => $key,
                'label' => $key === 'entry_date' ? 'تاریخ ورودی' : str_replace('_', ' ', $key),
                'field_key' => $key,
            ];
        }

        return $columns;
    }

    /**
     * @param Collection<int, EmploymentApplication> $applications
     * @param Collection<int, ApplicationFormField> $fields
     * @return array<string, int>
     */
    private function maxListRowsByField(Collection $applications, Collection $fields): array
    {
        $max = [];

        foreach ($fields as $field) {
            if ($field->field_type !== FormFieldType::List) {
                continue;
            }

            $max[$field->field_key] = 0;
        }

        foreach ($applications as $application) {
            $formData = EmploymentFormFields::normalizeFormData($application->form_data ?? []);

            foreach ($max as $fieldKey => $currentMax) {
                $rows = $formData[$fieldKey] ?? [];

                if (! is_array($rows)) {
                    continue;
                }

                $max[$fieldKey] = max($currentMax, count($rows));
            }
        }

        return $max;
    }

    /**
     * @param Collection<int, EmploymentApplication> $applications
     * @param Collection<int, ApplicationFormField> $fields
     * @return list<string>
     */
    private function collectExtraKeys(Collection $applications, Collection $fields): array
    {
        $knownKeys = $fields->pluck('field_key')->all();
        $extraKeys = [];

        foreach ($applications as $application) {
            $formData = EmploymentFormFields::normalizeFormData($application->form_data ?? []);

            foreach (array_keys($formData) as $key) {
                if (in_array($key, $knownKeys, true) || str_starts_with((string) $key, '_')) {
                    continue;
                }

                $extraKeys[$key] = true;
            }
        }

        return array_keys($extraKeys);
    }

    /**
     * @param list<array{key: string, label: string, field_key?: string, list_index?: int, list_col_key?: string}> $columns
     * @return list<string>
     */
    private function rowValues(EmploymentApplication $application, array $columns): array
    {
        $formData = EmploymentFormFields::normalizeFormData($application->form_data ?? []);

        return array_map(
            fn (array $column) => $this->cellValue($application, $column, $formData),
            $columns,
        );
    }

    /** @param array<string, mixed> $formData */
    private function cellValue(EmploymentApplication $application, array $column, array $formData): string
    {
        if (str_starts_with($column['key'], '_')) {
            return $this->metaValue($application, $column['key']);
        }

        if (isset($column['list_index'], $column['list_col_key'], $column['field_key'])) {
            $rows = $formData[$column['field_key']] ?? [];

            if (! is_array($rows)) {
                return '';
            }

            $row = $rows[$column['list_index']] ?? null;

            if (! is_array($row)) {
                return '';
            }

            return $this->displayService->formatForExport(null, $row[$column['list_col_key']] ?? null);
        }

        $fieldKey = $column['field_key'] ?? $column['key'];
        $field = $this->fieldsByKey->get($fieldKey);

        return $this->displayService->formatForExport(
            $field,
            $formData[$fieldKey] ?? null,
        );
    }

    private function metaValue(EmploymentApplication $application, string $key): string
    {
        return match ($key) {
            '_application_number' => (string) $application->application_number,
            '_status' => $application->status->label(),
            '_submitted_at' => $application->submitted_at
                ? JalaliHelper::toDateTimeString($application->submitted_at)
                : '',
            '_created_at' => JalaliHelper::toDateTimeString($application->created_at),
            '_contact_mobile' => (string) ($application->contact_mobile ?? ''),
            '_person_name' => (string) ($application->person?->full_name ?? ''),
            '_display_mobile' => (string) ($application->person
                ? $this->personMobileService->displayMobile($application->person)
                : ''),
            '_hr_notes' => (string) ($application->hr_notes ?? ''),
            default => '',
        };
    }
}
