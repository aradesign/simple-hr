<?php

namespace App\Policies;

use App\Models\Person;
use App\Models\User;

class PersonPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessHrPanel();
    }

    public function view(User $user, Person $person): bool
    {
        if ($user->canAccessHrPanel()) {
            return true;
        }

        return $user->person_id === $person->id;
    }

    public function create(User $user): bool
    {
        return $user->canAccessHrPanel();
    }

    public function update(User $user, Person $person): bool
    {
        if ($user->canAccessHrPanel()) {
            return true;
        }

        return $user->person_id === $person->id;
    }

    public function delete(User $user, Person $person): bool
    {
        return $user->canManageHr();
    }

    public function convertToEmployee(User $user, Person $person): bool
    {
        return $user->canAccessHrPanel();
    }
}
