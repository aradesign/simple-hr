<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessHrPanel();
    }

    public function view(User $user, User $model): bool
    {
        return $user->canAccessHrPanel();
    }

    public function create(User $user): bool
    {
        return $user->canManageHr();
    }

    public function update(User $user, User $model): bool
    {
        if ($user->canManageHr()) {
            return true;
        }

        return $user->id === $model->id;
    }

    public function delete(User $user, User $model): bool
    {
        return $user->canManageHr() && $user->id !== $model->id;
    }

    public function grantHrAccess(User $user, User $model): bool
    {
        return $user->canManageUser($model) && $user->canManageHr();
    }

    public function updateRole(User $user, User $model): bool
    {
        if ($model->isSuperAdmin() && ! $user->isSuperAdmin()) {
            return false;
        }

        return $user->canManageUser($model) && $user->canManageHr();
    }
}
