<?php

namespace App\Support;

final class IranianNationalId
{
    public static function normalize(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', PersianDigits::toEnglish($value));

        if ($digits === null || $digits === '') {
            return null;
        }

        if (strlen($digits) > 10) {
            return null;
        }

        return str_pad($digits, 10, '0', STR_PAD_LEFT);
    }

    public static function isValid(mixed $value): bool
    {
        $code = self::normalize($value);

        if ($code === null || ! preg_match('/^\d{10}$/', $code)) {
            return false;
        }

        for ($i = 0; $i < 10; $i++) {
            if (preg_match('/^'.$i.'{10}$/', $code)) {
                return false;
            }
        }

        $sum = 0;

        for ($i = 0; $i < 9; $i++) {
            $sum += (10 - $i) * (int) substr($code, $i, 1);
        }

        $remainder = $sum % 11;
        $checkDigit = (int) substr($code, 9, 1);

        return ($remainder < 2 && $remainder === $checkDigit)
            || ($remainder >= 2 && $remainder === 11 - $checkDigit);
    }
}
