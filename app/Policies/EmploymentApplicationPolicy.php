<?php

namespace App\Policies;

use App\Models\EmploymentApplication;
use App\Models\User;

class EmploymentApplicationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessHrPanel();
    }

    public function view(User $user, EmploymentApplication $application): bool
    {
        if ($user->canAccessHrPanel()) {
            return true;
        }

        return $user->person_id === $application->person_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, EmploymentApplication $application): bool
    {
        if ($user->canAccessHrPanel()) {
            return true;
        }

        return $user->person_id === $application->person_id;
    }

    public function delete(User $user, EmploymentApplication $application): bool
    {
        return $user->canManageHr();
    }

    public function updateStatus(User $user, EmploymentApplication $application): bool
    {
        return $user->canAccessHrPanel();
    }
}
