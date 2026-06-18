<?php

namespace App\Http\Requests\Admin;

use App\Domain\Enums\Gender;
use App\Domain\Enums\MaritalStatus;
use App\Domain\Enums\PersonLifecycleStatus;
use App\Http\Requests\Concerns\ConvertsJalaliDates;
use App\Rules\IranianNationalIdRule;
use App\Support\IranianNationalId;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePersonRequest extends FormRequest
{
    use ConvertsJalaliDates;

    public function authorize(): bool
    {
        return true;
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
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'national_id' => ['nullable', 'string', 'size:10', 'unique:persons,national_id', new IranianNationalIdRule],
            'mobile' => ['required', 'string', 'max:15', 'unique:persons,mobile'],
            'birth_date' => ['nullable', 'date'],
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'lifecycle_status' => ['nullable', Rule::enum(PersonLifecycleStatus::class)],
            'marital_status' => ['nullable', Rule::enum(MaritalStatus::class)],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
