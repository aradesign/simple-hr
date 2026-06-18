<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessHrPanel();
    }

    public function view(User $user, Document $document): bool
    {
        if ($user->canAccessHrPanel()) {
            return true;
        }

        return $user->person_id === $document->person_id;
    }

    public function create(User $user): bool
    {
        return $user->canAccessHrPanel();
    }

    public function update(User $user, Document $document): bool
    {
        return $user->canAccessHrPanel();
    }

    public function delete(User $user, Document $document): bool
    {
        return $user->canManageHr();
    }

    public function download(User $user, Document $document): bool
    {
        return $this->view($user, $document);
    }
}
