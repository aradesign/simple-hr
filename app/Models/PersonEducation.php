<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonEducation extends Model
{
    protected $table = 'person_educations';

    protected $fillable = [
        'person_id',
        'degree',
        'field_of_study',
        'institution',
        'start_date',
        'end_date',
        'is_current',
    ];

    protected $casts = [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_current' => 'boolean',
        ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('is_current', true);
    }
}
