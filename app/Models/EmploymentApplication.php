<?php

namespace App\Models;

use App\Domain\Enums\ApplicationStatus;
use Database\Factories\EmploymentApplicationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmploymentApplication extends Model
{
    /** @use HasFactory<EmploymentApplicationFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'person_id',
        'contact_mobile',
        'application_number',
        'status',
        'form_data',
        'current_step',
        'assigned_to',
        'reviewer_id',
        'submitted_at',
        'reviewed_at',
        'hr_notes',
    ];

    protected $casts = [
            'status' => ApplicationStatus::class,
            'form_data' => 'array',
            'current_step' => 'integer',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function interviews(): HasMany
    {
        return $this->hasMany(Interview::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function assignments(): MorphMany
    {
        return $this->morphMany(Assignment::class, 'assignable');
    }

    public function scopeByStatus(Builder $query, ApplicationStatus $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeSubmitted(Builder $query): Builder
    {
        return $query->where('status', ApplicationStatus::Submitted);
    }

    public function scopePendingReview(Builder $query): Builder
    {
        return $query->whereIn('status', [
            ApplicationStatus::Submitted,
            ApplicationStatus::UnderReview,
            ApplicationStatus::InterviewScheduled,
            ApplicationStatus::Interviewed,
        ]);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNotIn('status', [
            ApplicationStatus::Accepted,
            ApplicationStatus::Rejected,
            ApplicationStatus::Withdrawn,
        ]);
    }

    public function formValue(string $key, mixed $default = null): mixed
    {
        $data = is_array($this->form_data) ? $this->form_data : [];

        return $data[$key] ?? $default;
    }
}
