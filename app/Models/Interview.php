<?php

namespace App\Models;

use App\Domain\Enums\InterviewResult;
use App\Domain\Enums\InterviewStatus;
use App\Domain\Enums\InterviewType;
use Database\Factories\InterviewFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Interview extends Model
{
    /** @use HasFactory<InterviewFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'person_id',
        'employment_application_id',
        'type',
        'status',
        'result',
        'scheduled_at',
        'duration_minutes',
        'location',
        'meeting_url',
        'interviewer_id',
        'notes',
        'feedback',
    ];

    protected $casts = [
            'type' => InterviewType::class,
            'status' => InterviewStatus::class,
            'result' => InterviewResult::class,
            'scheduled_at' => 'datetime',
            'duration_minutes' => 'integer',
        ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function employmentApplication(): BelongsTo
    {
        return $this->belongsTo(EmploymentApplication::class);
    }

    public function interviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'interviewer_id');
    }

    public function calendarEvents(): HasMany
    {
        return $this->hasMany(CalendarEvent::class);
    }

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', InterviewStatus::Scheduled);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('status', InterviewStatus::Scheduled)
            ->where('scheduled_at', '>=', now());
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', InterviewStatus::Completed);
    }

    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('scheduled_at', today());
    }
}
