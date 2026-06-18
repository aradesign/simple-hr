<?php

namespace App\Policies;

use App\Models\CalendarEvent;
use App\Models\User;

class CalendarEventPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessHrPanel();
    }

    public function view(User $user, CalendarEvent $event): bool
    {
        return $user->canAccessHrPanel();
    }

    public function create(User $user): bool
    {
        return $user->canAccessHrPanel();
    }

    public function update(User $user, CalendarEvent $event): bool
    {
        return $user->canAccessHrPanel();
    }

    public function delete(User $user, CalendarEvent $event): bool
    {
        return $user->canManageHr();
    }
}
