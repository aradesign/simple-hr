<?php

namespace App\Http\Requests\Portal;

use App\Domain\Enums\Gender;
use App\Domain\Enums\MaritalStatus;
use App\Http\Middleware\EnsurePortalAuth;
use App\Http\Requests\Concerns\ConvertsJalaliDates;
use App\Rules\IranianNationalIdRule;
use App\Support\IranianNationalId;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    use ConvertsJalaliDates;

    public function authorize(): bool
    {
        return $this->session()->has(EnsurePortalAuth::SESSION_KEY);
    }

    protected function prepareForValidation(): void
    {
        $this->convertJalaliFields(['birth_date']);

        if ($this->filled('national_id')) {
            $this->merge([
                'national_id' => IranianNationalId::normalize($this->input('national_id')),
            ]);
        }
    }

    public function rules(): array
    {
        $personId = $this->session()->get(EnsurePortalAuth::SESSION_KEY);

        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'national_id' => ['nullable', 'string', 'size:10', Rule::unique('persons', 'national_id')->ignore($personId), new IranianNationalIdRule],
            'birth_date' => ['nullable', 'date'],
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'marital_status' => ['nullable', Rule::enum(MaritalStatus::class)],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
        ];
    }
}
