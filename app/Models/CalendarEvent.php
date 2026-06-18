<?php

namespace App\Models;

use App\Domain\Enums\CalendarEventType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CalendarEvent extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'event_type',
        'starts_at',
        'ends_at',
        'all_day',
        'person_id',
        'interview_id',
        'employment_record_id',
        'created_by',
        'color',
    ];

    protected $casts = [
            'event_type' => CalendarEventType::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'all_day' => 'boolean',
        ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function interview(): BelongsTo
    {
        return $this->belongsTo(Interview::class);
    }

    public function employmentRecord(): BelongsTo
    {
        return $this->belongsTo(EmploymentRecord::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('starts_at', '>=', now());
    }

    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('starts_at', today());
    }

    public function scopeByType(Builder $query, CalendarEventType $type): Builder
    {
        return $query->where('event_type', $type);
    }

    public function scopeInRange(Builder $query, \DateTimeInterface $from, \DateTimeInterface $to): Builder
    {
        return $query->where('starts_at', '>=', $from)
            ->where(function (Builder $query) use ($to) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '<=', $to);
            });
    }
}
