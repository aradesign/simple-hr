<?php

namespace App\DTOs;

use App\Domain\Enums\InterviewResult;
use App\Domain\Enums\InterviewStatus;
use App\Domain\Enums\InterviewType;
use App\Models\Interview;

class InterviewData
{
    public function __construct(
        public int $personId,
        public ?int $employmentApplicationId = null,
        public ?InterviewType $type = null,
        public ?InterviewStatus $status = null,
        public ?InterviewResult $result = null,
        public ?string $scheduledAt = null,
        public ?int $durationMinutes = null,
        public ?string $location = null,
        public ?string $meetingUrl = null,
        public ?int $interviewerId = null,
        public ?string $notes = null,
        public ?string $feedback = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            personId: $data['person_id'],
            employmentApplicationId: $data['employment_application_id'] ?? null,
            type: isset($data['type']) ? InterviewType::from($data['type']) : null,
            status: isset($data['status']) ? InterviewStatus::from($data['status']) : null,
            result: isset($data['result']) ? InterviewResult::from($data['result']) : null,
            scheduledAt: $data['scheduled_at'] ?? null,
            durationMinutes: $data['duration_minutes'] ?? null,
            location: $data['location'] ?? null,
            meetingUrl: $data['meeting_url'] ?? null,
            interviewerId: $data['interviewer_id'] ?? null,
            notes: $data['notes'] ?? null,
            feedback: $data['feedback'] ?? null,
        );
    }

    public static function fromModel(Interview $interview): self
    {
        return new self(
            personId: $interview->person_id,
            employmentApplicationId: $interview->employment_application_id,
            type: $interview->type,
            status: $interview->status,
            result: $interview->result,
            scheduledAt: $interview->scheduled_at?->toDateTimeString(),
            durationMinutes: $interview->duration_minutes,
            location: $interview->location,
            meetingUrl: $interview->meeting_url,
            interviewerId: $interview->interviewer_id,
            notes: $interview->notes,
            feedback: $interview->feedback,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'person_id' => $this->personId,
            'employment_application_id' => $this->employmentApplicationId,
            'type' => $this->type?->value,
            'status' => $this->status?->value,
            'result' => $this->result?->value,
            'scheduled_at' => $this->scheduledAt,
            'duration_minutes' => $this->durationMinutes,
            'location' => $this->location,
            'meeting_url' => $this->meetingUrl,
            'interviewer_id' => $this->interviewerId,
            'notes' => $this->notes,
            'feedback' => $this->feedback,
        ], fn ($value) => $value !== null);
    }
}
