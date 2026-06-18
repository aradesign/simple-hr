<?php

namespace App\Models;

use App\Domain\Enums\FormFieldType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ApplicationFormField extends Model
{
    protected $fillable = [
        'field_key',
        'gravity_field_id',
        'label',
        'description',
        'field_type',
        'options',
        'css_class',
        'conditional_logic',
        'list_columns',
        'layout_group_id',
        'step',
        'sort_order',
        'is_visible',
        'is_required',
    ];

    protected $casts = [
            'field_type' => FormFieldType::class,
            'options' => 'array',
            'conditional_logic' => 'array',
            'list_columns' => 'array',
            'gravity_field_id' => 'integer',
            'step' => 'integer',
            'sort_order' => 'integer',
            'is_visible' => 'boolean',
            'is_required' => 'boolean',
        ];

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('step')->orderBy('sort_order');
    }

    public function scopeForStep(Builder $query, int $step): Builder
    {
        return $query->where('step', $step);
    }

    public function scopeRequired(Builder $query): Builder
    {
        return $query->where('is_required', true);
    }
}
