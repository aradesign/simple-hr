<?php

namespace App\Domain\Enums;

enum MaritalStatus: string
{
    case Single = 'single';
    case Married = 'married';
    case Divorced = 'divorced';
    case Widowed = 'widowed';

    public function label(): string
    {
        return match ($this) {
            self::Single => 'مجرد',
            self::Married => 'متأهل',
            self::Divorced => 'مطلقه',
            self::Widowed => 'بیوه',
        };
    }
}
