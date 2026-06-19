<?php

namespace App\Http\Requests\Admin;

use App\Domain\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\User::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'person_id' => [
                'nullable',
                'integer',
                'exists:persons,id',
                Rule::unique('users', 'person_id'),
            ],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'mobile' => ['nullable', 'string', 'max:15'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', new Enum(UserRole::class)],
            'hr_access' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'person_id.unique' => 'برای این پرسنل قبلاً حساب کاربری ساخته شده است.',
            'email.unique' => 'این ایمیل قبلاً ثبت شده است.',
        ];
    }
}
