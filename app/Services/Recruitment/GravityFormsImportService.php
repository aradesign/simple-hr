<?php

namespace App\Services\Recruitment;

use App\Domain\Enums\FormFieldType;
use App\Models\ApplicationFormField;
use App\Support\EmploymentFormFields;
use Illuminate\Support\Facades\DB;

class GravityFormsImportService
{
    public function importFromFile(string $path): int
    {
        $payload = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $form = $payload['0'] ?? $payload;
        $fields = $form['fields'] ?? [];

        return DB::transaction(function () use ($fields) {
            ApplicationFormField::query()->delete();

            $sortOrder = 0;

            foreach ($fields as $field) {
                $sortOrder++;
                $gravityId = (int) $field['id'];
                $fieldKey = EmploymentFormFields::keyForGravityId($gravityId);
                $type = $this->mapFieldType($field);
                $isHidden = str_contains((string) ($field['cssClass'] ?? ''), 'hidden');

                ApplicationFormField::query()->create([
                    'field_key' => $fieldKey,
                    'gravity_field_id' => $gravityId,
                    'label' => (string) $field['label'],
                    'description' => $field['description'] ?: null,
                    'field_type' => $isHidden ? FormFieldType::Hidden : $type,
                    'options' => $this->mapOptions($field, $type),
                    'css_class' => $field['cssClass'] ?: null,
                    'conditional_logic' => $this->normalizeConditionalLogic($field['conditionalLogic'] ?? null),
                    'list_columns' => $this->mapListColumns($fieldKey, $type),
                    'layout_group_id' => $field['layoutGroupId'] ?? null,
                    'step' => 1,
                    'sort_order' => $sortOrder,
                    'is_visible' => ! $isHidden,
                    'is_required' => (bool) ($field['isRequired'] ?? false),
                ]);
            }

            return $sortOrder;
        });
    }

    private function mapFieldType(array $field): FormFieldType
    {
        return match ($field['type']) {
            'textarea' => FormFieldType::Textarea,
            'select' => FormFieldType::Select,
            'radio' => FormFieldType::Radio,
            'checkbox' => FormFieldType::Checkbox,
            'date' => FormFieldType::Date,
            'fileupload' => FormFieldType::File,
            'number' => FormFieldType::Number,
            'list' => FormFieldType::List,
            'ir_national_id' => FormFieldType::NationalId,
            default => FormFieldType::Text,
        };
    }

    private function mapOptions(array $field, FormFieldType $type): ?array
    {
        if ($type === FormFieldType::List) {
            return null;
        }

        $choices = $field['choices'] ?? null;

        if (! is_array($choices) || $choices === []) {
            return null;
        }

        return collect($choices)
            ->map(fn (array $choice) => $choice['value'] ?? $choice['text'] ?? '')
            ->filter()
            ->values()
            ->all();
    }

    private function mapListColumns(string $fieldKey, FormFieldType $type): ?array
    {
        if ($type !== FormFieldType::List) {
            return null;
        }

        return EmploymentFormFields::LIST_COLUMNS[$fieldKey] ?? [
            ['key' => 'item', 'label' => 'مورد'],
        ];
    }

    private function normalizeConditionalLogic(mixed $logic): ?array
    {
        if (! is_array($logic) || empty($logic['enabled'])) {
            return null;
        }

        return [
            'action' => $logic['actionType'] ?? 'show',
            'match' => $logic['logicType'] ?? 'all',
            'rules' => collect($logic['rules'] ?? [])
                ->map(fn (array $rule) => [
                    'field_key' => EmploymentFormFields::keyForGravityId((int) ($rule['fieldId'] ?? 0)),
                    'operator' => (string) ($rule['operator'] ?? 'is'),
                    'value' => (string) ($rule['value'] ?? ''),
                ])
                ->values()
                ->all(),
        ];
    }
}
