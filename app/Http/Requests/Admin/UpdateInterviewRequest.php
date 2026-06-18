<?php

namespace App\Http\Requests\Admin;

use App\Domain\Enums\InterviewResult;
use App\Domain\Enums\InterviewStatus;
use App\Domain\Enums\InterviewType;
use App\Http\Requests\Concerns\ConvertsJalaliDates;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInterviewRequest extends FormRequest
{
    use ConvertsJalaliDates;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->convertJalaliDateTimeFields(['scheduled_at']);
    }

    public function rules(): array
    {
        return [
            'type' => ['sometimes', Rule::enum(InterviewType::class)],
            'status' => ['sometimes', Rule::enum(InterviewStatus::class)],
            'result' => ['nullable', Rule::enum(InterviewResult::class)],
            'scheduled_at' => ['sometimes', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:15', 'max:480'],
            'location' => ['nullable', 'string', 'max:255'],
            'meeting_url' => ['nullable', 'url', 'max:500'],
            'interviewer_id' => ['sometimes', 'integer', 'exists:users,id'],
            'notes' => ['nullable', 'string'],
            'feedback' => ['nullable', 'string'],
        ];
    }
}
