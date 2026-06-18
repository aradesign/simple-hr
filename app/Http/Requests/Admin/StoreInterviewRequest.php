<?php

namespace App\Http\Requests\Admin;

use App\Domain\Enums\InterviewType;
use App\Http\Requests\Concerns\ConvertsJalaliDates;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInterviewRequest extends FormRequest
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
            'person_id' => ['required', 'integer', 'exists:persons,id'],
            'employment_application_id' => ['nullable', 'integer', 'exists:employment_applications,id'],
            'type' => ['required', Rule::enum(InterviewType::class)],
            'scheduled_at' => ['required', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:15', 'max:480'],
            'location' => ['nullable', 'string', 'max:255', 'required_if:type,in_person'],
            'meeting_url' => ['nullable', 'url', 'max:500', 'required_if:type,online'],
            'interviewer_id' => ['required', 'integer', 'exists:users,id'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
