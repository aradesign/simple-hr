<?php

namespace App\Models;

use App\Domain\Enums\EmploymentStatus;
use App\Domain\Enums\EmploymentType;
use Database\Factories\EmploymentRecordFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmploymentRecord extends Model
{
    /** @use HasFactory<EmploymentRecordFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'person_id',
        'department_id',
        'employee_code',
        'employment_type',
        'status',
        'start_date',
        'end_date',
        'probation_end_date',
        'contract_end_date',
        'salary',
        'position_title',
        'notes',
    ];

    protected $casts = [
            'employment_type' => EmploymentType::class,
            'status' => EmploymentStatus::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'probation_end_date' => 'date',
            'contract_end_date' => 'date',
            'salary' => 'decimal:2',
        ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function calendarEvents(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CalendarEvent::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', EmploymentStatus::Active);
    }

    public function scopeEndingSoon(Builder $query, int $days = 30): Builder
    {
        return $query->where('status', EmploymentStatus::Active)
            ->whereNotNull('contract_end_date')
            ->whereBetween('contract_end_date', [now()->toDateString(), now()->addDays($days)->toDateString()]);
    }

    public function scopeByStatus(Builder $query, EmploymentStatus $status): Builder
    {
        return $query->where('status', $status);
    }
}
