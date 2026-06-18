<?php

namespace App\Models;

use App\Domain\Enums\AssignmentRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Assignment extends Model
{
    protected $fillable = [
        'assignable_type',
        'assignable_id',
        'user_id',
        'role',
    ];

    protected $casts = [
            'role' => AssignmentRole::class,
        ];

    public function assignable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeByRole(Builder $query, AssignmentRole $role): Builder
    {
        return $query->where('role', $role);
    }

    public function scopeForUser(Builder $query, int|User $user): Builder
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $query->where('user_id', $userId);
    }

    public function scopeAssignees(Builder $query): Builder
    {
        return $query->where('role', AssignmentRole::Assignee);
    }

    public function scopeReviewers(Builder $query): Builder
    {
        return $query->where('role', AssignmentRole::Reviewer);
    }
}
