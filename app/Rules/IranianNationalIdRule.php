<?php

namespace App\Rules;

use App\Support\IranianNationalId;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class IranianNationalIdRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! IranianNationalId::isValid($value)) {
            $fail('کد ملی نامعتبر است.');
        }
    }
}
