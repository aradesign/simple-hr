<?php

namespace App\Services\Recruitment;

use App\Models\ApplicationFormField;
use App\Support\EmploymentFormFields;
use Illuminate\Support\Collection;

class ConditionalLogicEvaluator
{
    public function isVisible(ApplicationFormField $field, array $formData): bool
    {
        $logic = $field->conditional_logic;

        if (! $logic || empty($logic['rules'])) {
            return true;
        }

        $results = collect($logic['rules'])
            ->map(fn (array $rule) => $this->evaluateRule($rule, $formData));

        $visible = ($logic['match'] ?? 'all') === 'any'
            ? $results->contains(true)
            : $results->every(fn (bool $value) => $value);

        return ($logic['action'] ?? 'show') === 'show' ? $visible : ! $visible;
    }

    public function visibleFields(Collection $fields, array $formData): Collection
    {
        $formData = EmploymentFormFields::normalizeFormData($formData);

        return $fields->filter(fn (ApplicationFormField $field) => $this->isVisible($field, $formData));
    }

    private function evaluateRule(array $rule, array $formData): bool
    {
        $key = $rule['field_key']
            ?? (isset($rule['field_id']) ? EmploymentFormFields::keyForGravityId((int) $rule['field_id']) : '');
        $actual = $formData[$key] ?? null;
        $expected = $rule['value'] ?? '';
        $operator = $rule['operator'] ?? 'is';

        if (is_array($actual)) {
            return match ($operator) {
                'is' => in_array($expected, $actual, true),
                'isnot' => ! in_array($expected, $actual, true),
                default => false,
            };
        }

        $actualString = (string) $actual;

        return match ($operator) {
            'is' => $actualString === (string) $expected,
            'isnot' => $actualString !== (string) $expected,
            '>' => is_numeric($actualString) && (float) $actualString > (float) $expected,
            '<' => is_numeric($actualString) && (float) $actualString < (float) $expected,
            'contains' => str_contains($actualString, (string) $expected),
            default => false,
        };
    }
}
