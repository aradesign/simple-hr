<?php

namespace App\Policies;

use App\Models\Interview;
use App\Models\User;

class InterviewPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessHrPanel();
    }

    public function view(User $user, Interview $interview): bool
    {
        if ($user->canAccessHrPanel()) {
            return true;
        }

        return $user->person_id === $interview->person_id
            || $user->id === $interview->interviewer_id;
    }

    public function create(User $user): bool
    {
        return $user->canAccessHrPanel();
    }

    public function update(User $user, Interview $interview): bool
    {
        return $user->canAccessHrPanel();
    }

    public function delete(User $user, Interview $interview): bool
    {
        return $user->canManageHr();
    }

    public function complete(User $user, Interview $interview): bool
    {
        return $user->canAccessHrPanel();
    }
}
