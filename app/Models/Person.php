<?php

namespace App\Models;

use App\Domain\Enums\Gender;
use App\Domain\Enums\MaritalStatus;
use App\Domain\Enums\PersonLifecycleStatus;
use Database\Factories\PersonFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Person extends Model
{
    /** @use HasFactory<PersonFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'persons';

    protected $fillable = [
        'first_name',
        'last_name',
        'national_id',
        'mobile',
        'managed_by_mobile',
        'birth_date',
        'gender',
        'lifecycle_status',
        'marital_status',
        'address',
        'city',
        'province',
        'postal_code',
        'profile_photo',
        'notes',
    ];

    protected $casts = [
            'birth_date' => 'date',
            'gender' => Gender::class,
            'lifecycle_status' => PersonLifecycleStatus::class,
            'marital_status' => MaritalStatus::class,
        ];

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function employmentApplications(): HasMany
    {
        return $this->hasMany(EmploymentApplication::class);
    }

    public function employmentRecords(): HasMany
    {
        return $this->hasMany(EmploymentRecord::class);
    }

    public function interviews(): HasMany
    {
        return $this->hasMany(Interview::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function educations(): HasMany
    {
        return $this->hasMany(PersonEducation::class);
    }

    public function workExperiences(): HasMany
    {
        return $this->hasMany(PersonWorkExperience::class);
    }

    public function familyMembers(): HasMany
    {
        return $this->hasMany(PersonFamilyMember::class);
    }

    public function calendarEvents(): HasMany
    {
        return $this->hasMany(CalendarEvent::class);
    }

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'department_person')
            ->withPivot(['joined_at', 'left_at', 'is_primary'])
            ->withTimestamps();
    }

    public function assignments(): MorphMany
    {
        return $this->morphMany(Assignment::class, 'assignable');
    }

    public function scopeByLifecycleStatus(Builder $query, PersonLifecycleStatus $status): Builder
    {
        return $query->where('lifecycle_status', $status);
    }

    public function scopeApplicants(Builder $query): Builder
    {
        return $query->where('lifecycle_status', PersonLifecycleStatus::Applicant);
    }

    public function scopeEmployees(Builder $query): Builder
    {
        return $query->where('lifecycle_status', PersonLifecycleStatus::Employee);
    }

    public function scopeFormerEmployees(Builder $query): Builder
    {
        return $query->where('lifecycle_status', PersonLifecycleStatus::FormerEmployee);
    }

    public function scopeInPersonnelRoster(Builder $query): Builder
    {
        return $query->whereIn('lifecycle_status', array_map(
            fn (PersonLifecycleStatus $status) => $status->value,
            PersonLifecycleStatus::personnelRosterCases(),
        ));
    }

    public function isInPersonnelRoster(): bool
    {
        return $this->lifecycle_status->isPersonnelRoster();
    }

    public function scopeActiveEmployees(Builder $query): Builder
    {
        return $query->where('lifecycle_status', PersonLifecycleStatus::Employee);
    }

    public function scopeWithoutUserAccount(Builder $query): Builder
    {
        return $query->whereDoesntHave('user');
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getDisplayMobileAttribute(): ?string
    {
        return app(\App\Services\Person\PersonMobileService::class)->displayMobile($this);
    }

    public function usesTemporaryMobile(): bool
    {
        return app(\App\Services\Person\PersonMobileService::class)->usesTemporaryMobile($this);
    }
}
