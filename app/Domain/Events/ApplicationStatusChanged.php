<?php

namespace App\Domain\Events;

use App\Domain\Enums\ApplicationStatus;
use App\Models\EmploymentApplication;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ApplicationStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public EmploymentApplication $application,
        public ApplicationStatus $oldStatus,
        public ApplicationStatus $newStatus,
        public ?User $changedBy = null,
    ) {}
}
