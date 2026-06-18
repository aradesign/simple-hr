<?php

namespace App\Http\Requests\Recruitment;

use App\Domain\Enums\OtpPurpose;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RequestOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mobile' => ['required', 'string', 'regex:/^09\d{9}$/'],
            'purpose' => ['required', Rule::enum(OtpPurpose::class)],
        ];
    }
}
