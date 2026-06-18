<?php

namespace App\Services\Recruitment;

use App\Domain\Enums\FormFieldType;
use App\Helpers\JalaliHelper;
use App\Models\ApplicationFormField;
use App\Models\EmploymentApplication;
use App\Support\EmploymentFormFields;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class ApplicationFormDisplayService
{
    public function __construct(
        private readonly ApplicationFormSchemaService $schemaService,
    ) {}

    /** @return Collection<int, array{key: string, label: string, type: FormFieldType, value: string, file_url: ?string, list_rows: ?array<int, array<int, array{label: string, value: string}>>}> */
    public function entries(EmploymentApplication $application): Collection
    {
        $formData = EmploymentFormFields::normalizeFormData($application->form_data ?? []);
        $fields = $this->schemaService->getAllFields();

        $entries = $fields
            ->filter(fn (ApplicationFormField $field) => $field->field_key !== 'profile_photo')
            ->filter(fn (ApplicationFormField $field) => array_key_exists($field->field_key, $formData))
            ->map(function (ApplicationFormField $field) use ($formData) {
                $raw = $formData[$field->field_key];

                return [
                    'key' => $field->field_key,
                    'label' => $field->label,
                    'type' => $field->field_type,
                    'value' => $this->formatValue($field, $raw),
                    'file_url' => $field->field_type === FormFieldType::File ? $this->fileUrl($raw) : null,
                    'list_rows' => $field->field_type === FormFieldType::List
                        ? $this->formatListRows($field, $raw)
                        : null,
                ];
            })
            ->filter(function (array $entry) {
                if ($entry['type'] === FormFieldType::List) {
                    return $entry['list_rows'] !== [];
                }

                return $entry['value'] !== '—';
            })
            ->values();

        $knownKeys = $fields->pluck('field_key')->all();

        foreach ($formData as $key => $raw) {
            if (in_array($key, $knownKeys, true)) {
                continue;
            }

            if (str_starts_with((string) $key, '_')) {
                continue;
            }

            $entries->push([
                'key' => $key,
                'label' => $this->fallbackLabel((string) $key),
                'type' => FormFieldType::Text,
                'value' => $this->displayScalar($raw),
                'file_url' => null,
                'list_rows' => null,
            ]);
        }

        return $entries;
    }

    public function formatForExport(?ApplicationFormField $field, mixed $raw): string
    {
        if ($raw === null || $raw === '' || (is_array($raw) && $raw === [])) {
            return '';
        }

        $formatted = $field === null
            ? $this->displayScalar($raw)
            : $this->formatValue($field, $raw);

        return $formatted === '—' ? '' : $formatted;
    }

    public function profilePhotoUrl(EmploymentApplication $application): ?string
    {
        $path = $this->profilePhotoPath($application);

        if ($path === null) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    public function profilePhotoDataUri(EmploymentApplication $application): ?string
    {
        $path = $this->profilePhotoPath($application);

        if ($path === null) {
            return null;
        }

        $absolute = Storage::disk('public')->path($path);
        $mime = mime_content_type($absolute) ?: 'image/jpeg';

        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($absolute));
    }

    public function applicantInitials(EmploymentApplication $application): string
    {
        $formData = EmploymentFormFields::normalizeFormData($application->form_data ?? []);
        $first = mb_substr(trim((string) ($formData['first_name'] ?? $application->person?->first_name ?? '')), 0, 1);
        $last = mb_substr(trim((string) ($formData['last_name'] ?? $application->person?->last_name ?? '')), 0, 1);
        $initials = $first.$last;

        return $initials !== '' ? $initials : '؟';
    }

    public function applicantDisplayName(EmploymentApplication $application): string
    {
        $formData = EmploymentFormFields::normalizeFormData($application->form_data ?? []);
        $name = trim(($formData['first_name'] ?? '').' '.($formData['last_name'] ?? ''));

        if ($name !== '') {
            return $name;
        }

        return $application->person?->full_name ?: '—';
    }

    private function profilePhotoPath(EmploymentApplication $application): ?string
    {
        $formData = EmploymentFormFields::normalizeFormData($application->form_data ?? []);
        $path = $formData['profile_photo'] ?? $application->person?->profile_photo;

        if (! is_string($path) || $path === '') {
            return null;
        }

        return Storage::disk('public')->exists($path) ? $path : null;
    }

    private function fileUrl(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        return Storage::disk('public')->exists($value)
            ? Storage::disk('public')->url($value)
            : null;
    }

    private function formatValue(ApplicationFormField $field, mixed $value): string
    {
        return match ($field->field_type) {
            FormFieldType::Checkbox => $this->formatCheckbox($value),
            FormFieldType::Date => $this->formatDate($value),
            FormFieldType::File => $this->formatFile($value),
            FormFieldType::List => '',
            default => $this->displayScalar($value),
        };
    }

    /** @return array<int, array<int, array{label: string, value: string}>> */
    private function formatListRows(ApplicationFormField $field, mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $columns = $field->list_columns ?? [];
        $rows = [];

        foreach ($value as $row) {
            if (! is_array($row)) {
                continue;
            }

            $cells = [];
            $hasData = false;

            foreach ($columns as $column) {
                $cellValue = $row[$column['key']] ?? null;

                if ($cellValue !== null && $cellValue !== '') {
                    $hasData = true;
                }

                $cells[] = [
                    'label' => $column['label'],
                    'value' => $this->displayScalar($cellValue),
                ];
            }

            if ($hasData) {
                $rows[] = $cells;
            }
        }

        return $rows;
    }

    private function formatCheckbox(mixed $value): string
    {
        if (! is_array($value) || $value === []) {
            return '—';
        }

        return implode('، ', array_map(
            fn ($item) => $this->displayScalar($item),
            array_filter($value, fn ($item) => $item !== null && $item !== ''),
        ));
    }

    private function formatDate(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if ($value instanceof Carbon) {
            return JalaliHelper::format($value);
        }

        $string = trim((string) $value);

        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $string)) {
            return JalaliHelper::format(Carbon::parse($string)->startOfDay());
        }

        if (preg_match('/^\d{4}[\/\-]\d{1,2}[\/\-]\d{1,2}$/', $string)) {
            $parsed = JalaliHelper::parseDate($string);

            return $parsed ? JalaliHelper::format($parsed) : $string;
        }

        return $string;
    }

    private function formatFile(mixed $value): string
    {
        if (! is_string($value) || $value === '') {
            return '—';
        }

        return basename($value);
    }

    private function displayScalar(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? 'بله' : 'خیر';
        }

        if (is_array($value)) {
            return '—';
        }

        return trim((string) $value);
    }

    private function fallbackLabel(string $key): string
    {
        return match ($key) {
            'entry_date' => 'تاریخ ورودی',
            default => str_replace('_', ' ', $key),
        };
    }
}
