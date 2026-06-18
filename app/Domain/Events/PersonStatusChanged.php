<?php

namespace App\Domain\Events;

use App\Domain\Enums\PersonLifecycleStatus;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PersonStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Person $person,
        public PersonLifecycleStatus $oldStatus,
        public PersonLifecycleStatus $newStatus,
        public ?User $changedBy = null,
    ) {}
}
