<?php

namespace App\Services\Recruitment;

use App\Domain\Enums\ApplicationStatus;
use App\Helpers\JalaliHelper;
use App\Models\ApplicationFormField;
use App\Models\EmploymentApplication;
use App\Services\Settings\SettingService;
use Illuminate\Support\Collection;

class ApplicationFormPrintLayoutService
{
    private Collection $entries;

    public function __construct(
        private readonly ApplicationFormDisplayService $displayService,
        private readonly SettingService $settings,
    ) {}

    /** @return array<string, mixed> */
    public function build(EmploymentApplication $application): array
    {
        $this->entries = $this->displayService->entries($application)->keyBy('key');
        $branding = $this->settings->group('branding');
        $application->loadMissing(['interviews.interviewer', 'assignee', 'reviewer']);

        return [
            'company_name' => $branding['site_name'] ?? 'شیرینی لیلی',
            'submitted_at' => $application->submitted_at
                ? JalaliHelper::format($application->submitted_at, 'Y/m/d')
                : '—',
            'application_number' => $application->application_number,
            'process' => $this->buildProcessSection($application),
            'sections' => [
                $this->section('مشخصات متقاضی شغل', [
                    $this->row(['first_name', 'last_name', 'birth_place']),
                    $this->row(['national_id', 'id_card_number', 'age']),
                    $this->row(['father_name', 'mother_name', 'birth_date']),
                    $this->optionsRow('gender'),
                    $this->row(['height_cm', 'weight_kg']),
                    $this->optionsRow('marital_status'),
                    $this->row(['children_count', 'child_custody']),
                    $this->optionsRow('military_service_status'),
                    $this->fullRow('address'),
                    $this->row(['mobile', 'home_phone']),
                    $this->optionsRow('has_vehicle'),
                ]),
                $this->section('آخرین مدرک تحصیلی', [
                    $this->fullRow('education_level'),
                    $this->listTable('education_history'),
                    $this->optionsRow('currently_studying'),
                    $this->listTable('study_employment_status'),
                ]),
                $this->section('سوابق کار قبلی', [
                    $this->optionsRow('has_work_experience'),
                    $this->listTable('work_experience'),
                ]),
                $this->section(null, [
                    $this->listTable('technical_skills'),
                ]),
                $this->section('مشخصات خانواده', [
                    $this->listTable('family_members'),
                ]),
                $this->section(null, [
                    $this->optionsRow('currently_employed'),
                    $this->fullRow('biggest_career_challenge'),
                    $this->listTable('strengths'),
                    $this->listTable('weaknesses'),
                    $this->fullRow('medical_conditions'),
                    $this->fullRow('medical_condition_other'),
                    $this->optionsRow('had_surgery'),
                    $this->fullRow('surgery_details'),
                    $this->optionsRow('smoking'),
                    $this->optionsRow('has_insurance_history'),
                    $this->row(['insurance_years']),
                    $this->optionsRow('knows_company_employee'),
                    $this->fullRow('company_employee_name'),
                    $this->fullRow('preferred_department'),
                ]),
            ],
        ];
    }

    /** @param list<array<string, mixed>> $blocks */
    private function section(?string $title, array $blocks): array
    {
        $blocks = array_values(array_filter($blocks));

        return [
            'title' => $title,
            'blocks' => $blocks,
        ];
    }

    /** @param list<string> $keys */
    private function row(array $keys): ?array
    {
        $cells = [];

        foreach ($keys as $key) {
            if (! $this->has($key)) {
                continue;
            }

            $cells[] = [
                'label' => $this->label($key),
                'value' => $this->value($key),
            ];
        }

        return $cells === [] ? null : ['type' => 'row', 'cells' => $cells];
    }

    private function fullRow(string $key): ?array
    {
        if (! $this->has($key)) {
            return null;
        }

        return [
            'type' => 'full',
            'label' => $this->label($key),
            'value' => $this->value($key),
        ];
    }

    /** @param list<string>|null $fallbackOptions */
    private function optionsRow(string $key, ?array $fallbackOptions = null): ?array
    {
        if (! $this->has($key)) {
            return null;
        }

        $selected = $this->value($key);
        $fieldOptions = ApplicationFormField::query()
            ->where('field_key', $key)
            ->value('options');

        $options = is_array($fieldOptions) && $fieldOptions !== []
            ? $fieldOptions
            : ($fallbackOptions ?? []);

        if ($options === [] && $selected !== '') {
            return $this->fullRow($key);
        }

        return [
            'type' => 'options',
            'label' => $this->label($key),
            'options' => collect($options)
                ->map(fn (string $option) => [
                    'text' => $option,
                    'selected' => $selected === $option,
                ])
                ->all(),
            'value' => $selected,
        ];
    }

    private function listTable(string $key): ?array
    {
        $entry = $this->entries->get($key);

        if (! $entry || empty($entry['list_rows'])) {
            return null;
        }

        $columns = collect($entry['list_rows'][0] ?? [])
            ->map(fn (array $cell) => $cell['label'])
            ->all();

        $rows = collect($entry['list_rows'])
            ->map(fn (array $row) => collect($row)->pluck('value')->all())
            ->values()
            ->all();

        return [
            'type' => 'table',
            'label' => $entry['label'],
            'columns' => $columns,
            'rows' => $rows,
        ];
    }

    private function has(string $key): bool
    {
        if (! $this->entries->has($key)) {
            return false;
        }

        $entry = $this->entries->get($key);

        if (($entry['type']->value ?? null) === 'list') {
            return ! empty($entry['list_rows']);
        }

        return ($entry['value'] ?? '') !== '—';
    }

    private function label(string $key): string
    {
        return (string) ($this->entries->get($key)['label'] ?? $key);
    }

    private function value(string $key): string
    {
        return (string) ($this->entries->get($key)['value'] ?? '');
    }

    /** @return array<string, mixed>|null */
    private function buildProcessSection(EmploymentApplication $application): ?array
    {
        $summary = [];

        if ($application->status !== ApplicationStatus::Draft) {
            $summary[] = [
                'label' => 'وضعیت درخواست',
                'value' => $application->status->label(),
            ];
        }

        if ($application->assignee) {
            $summary[] = [
                'label' => 'مسئول پیگیری',
                'value' => $application->assignee->name,
            ];
        }

        if ($application->reviewer) {
            $summary[] = [
                'label' => 'بازبین',
                'value' => $application->reviewer->name,
            ];
        }

        if ($application->reviewed_at) {
            $summary[] = [
                'label' => 'تاریخ بررسی',
                'value' => JalaliHelper::format($application->reviewed_at, 'Y/m/d H:i'),
            ];
        }

        if (filled($application->hr_notes)) {
            $summary[] = [
                'label' => 'یادداشت منابع انسانی',
                'value' => $application->hr_notes,
            ];
        }

        $interviews = $application->interviews
            ->sortBy('scheduled_at')
            ->values()
            ->map(function ($interview, int $index) {
                $fields = [
                    ['label' => 'نوع مصاحبه', 'value' => $interview->type?->label() ?? '—'],
                    ['label' => 'زمان', 'value' => $interview->scheduled_at
                        ? JalaliHelper::format($interview->scheduled_at, 'Y/m/d H:i')
                        : '—'],
                    ['label' => 'وضعیت', 'value' => $interview->status?->label() ?? '—'],
                    ['label' => 'مصاحبه‌گر', 'value' => $interview->interviewer?->name ?? '—'],
                ];

                if (filled($interview->location)) {
                    $fields[] = ['label' => 'مکان', 'value' => $interview->location];
                }

                if (filled($interview->meeting_url)) {
                    $fields[] = ['label' => 'لینک جلسه', 'value' => $interview->meeting_url];
                }

                if ($interview->result) {
                    $fields[] = ['label' => 'نتیجه', 'value' => $interview->result->label()];
                }

                if (filled($interview->feedback)) {
                    $fields[] = ['label' => 'بازخورد', 'value' => $interview->feedback];
                }

                if (filled($interview->notes)) {
                    $fields[] = ['label' => 'یادداشت', 'value' => $interview->notes];
                }

                return [
                    'title' => 'مصاحبه '.($index + 1),
                    'fields' => $fields,
                ];
            })
            ->all();

        if ($summary === [] && $interviews === []) {
            return null;
        }

        return [
            'title' => 'فرایند گزینش و مصاحبه',
            'summary' => $summary,
            'interviews' => $interviews,
        ];
    }
}
