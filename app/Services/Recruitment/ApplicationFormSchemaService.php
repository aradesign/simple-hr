<?php

namespace App\Services\Recruitment;

use App\Domain\Enums\FormFieldType;
use App\Models\ApplicationFormField;
use Illuminate\Support\Collection;

class ApplicationFormSchemaService
{
    public function getAllFields(): Collection
    {
        return ApplicationFormField::query()
            ->where(function ($query) {
                $query->where('is_visible', true)
                    ->orWhere('field_type', FormFieldType::Hidden);
            })
            ->orderBy('sort_order')
            ->get();
    }

    public function getVisibleFieldsByStep(int $step): Collection
    {
        return ApplicationFormField::query()
            ->visible()
            ->forStep($step)
            ->ordered()
            ->get();
    }

    public function getAllVisibleFields(): Collection
    {
        return ApplicationFormField::query()
            ->visible()
            ->ordered()
            ->get()
            ->groupBy('step');
    }

    public function getRequiredFieldKeysForStep(int $step): array
    {
        return ApplicationFormField::query()
            ->visible()
            ->required()
            ->forStep($step)
            ->pluck('field_key')
            ->all();
    }

    public function fieldsPayload(Collection $fields): array
    {
        return $fields->map(fn (ApplicationFormField $field) => [
            'key' => $field->field_key,
            'gravity_field_id' => $field->gravity_field_id,
            'label' => $field->label,
            'description' => $field->description,
            'type' => $field->field_type->value,
            'options' => $field->options ?? [],
            'list_columns' => $field->list_columns ?? [],
            'conditional_logic' => $field->conditional_logic,
            'css_class' => $field->css_class,
            'required' => $field->is_required,
        ])->values()->all();
    }
}
