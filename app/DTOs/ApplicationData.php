<?php

namespace App\DTOs;

use App\Domain\Enums\ApplicationStatus;
use App\Models\EmploymentApplication;

class ApplicationData
{
    public function __construct(
        public int $personId,
        public ?string $applicationNumber = null,
        public ?ApplicationStatus $status = null,
        public ?array $formData = null,
        public ?int $currentStep = null,
        public ?int $assignedTo = null,
        public ?int $reviewerId = null,
        public ?string $hrNotes = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            personId: $data['person_id'],
            applicationNumber: $data['application_number'] ?? null,
            status: isset($data['status']) ? ApplicationStatus::from($data['status']) : null,
            formData: $data['form_data'] ?? null,
            currentStep: $data['current_step'] ?? null,
            assignedTo: $data['assigned_to'] ?? null,
            reviewerId: $data['reviewer_id'] ?? null,
            hrNotes: $data['hr_notes'] ?? null,
        );
    }

    public static function fromModel(EmploymentApplication $application): self
    {
        return new self(
            personId: $application->person_id,
            applicationNumber: $application->application_number,
            status: $application->status,
            formData: $application->form_data,
            currentStep: $application->current_step,
            assignedTo: $application->assigned_to,
            reviewerId: $application->reviewer_id,
            hrNotes: $application->hr_notes,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'person_id' => $this->personId,
            'application_number' => $this->applicationNumber,
            'status' => $this->status?->value,
            'form_data' => $this->formData,
            'current_step' => $this->currentStep,
            'assigned_to' => $this->assignedTo,
            'reviewer_id' => $this->reviewerId,
            'hr_notes' => $this->hrNotes,
        ], fn ($value) => $value !== null);
    }
}
