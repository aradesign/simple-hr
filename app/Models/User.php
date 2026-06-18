<?php

namespace App\Models;

use App\Domain\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $fillable = [
        'name',
        'email',
        'mobile',
        'avatar',
        'job_title',
        'password',
        'role',
        'hr_access',
        'person_id',
        'email_verified_at',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'role' => UserRole::class,
        'hr_access' => 'boolean',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function managedDepartments(): HasMany
    {
        return $this->hasMany(Department::class, 'manager_id');
    }

    public function assignedApplications(): HasMany
    {
        return $this->hasMany(EmploymentApplication::class, 'assigned_to');
    }

    public function reviewedApplications(): HasMany
    {
        return $this->hasMany(EmploymentApplication::class, 'reviewer_id');
    }

    public function conductedInterviews(): HasMany
    {
        return $this->hasMany(Interview::class, 'interviewer_id');
    }

    public function uploadedDocumentVersions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class, 'uploaded_by');
    }

    public function createdCalendarEvents(): HasMany
    {
        return $this->hasMany(CalendarEvent::class, 'created_by');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function notificationLogs(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }

    public function isHr(): bool
    {
        return $this->role?->isHr() ?? false;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role?->isSuperAdmin() ?? false;
    }

    public function hasHrAccess(): bool
    {
        return $this->hr_access || $this->isHr();
    }

    public function isHrManager(): bool
    {
        return $this->role?->isHrManager() ?? false;
    }

    public function canAccessHrPanel(): bool
    {
        return $this->hasHrAccess() || $this->isSuperAdmin();
    }

    public function canManageHr(): bool
    {
        return $this->isHrManager() || $this->isSuperAdmin();
    }

    public function canManageSettings(): bool
    {
        return $this->isSuperAdmin();
    }

    public function canManageUser(User $target): bool
    {
        if ($this->id === $target->id) {
            return true;
        }

        if ($target->isSuperAdmin() && ! $this->isSuperAdmin()) {
            return false;
        }

        if ($this->isSuperAdmin()) {
            return true;
        }

        if (! $this->canManageHr()) {
            return false;
        }

        return ($this->role?->level() ?? 0) > ($target->role?->level() ?? 0);
    }

    public function roleLevel(): int
    {
        return $this->role?->level() ?? 0;
    }

    public function avatarUrl(): ?string
    {
        if (! $this->avatar) {
            return null;
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->url($this->avatar);
    }
}
