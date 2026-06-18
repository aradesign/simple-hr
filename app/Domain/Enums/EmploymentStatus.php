<?php

namespace App\Domain\Enums;

enum EmploymentStatus: string
{
    case Active = 'active';
    case OnLeave = 'on_leave';
    case Terminated = 'terminated';
    case Retired = 'retired';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'فعال',
            self::OnLeave => 'مرخصی',
            self::Terminated => 'قطع همکاری',
            self::Retired => 'بازنشسته',
        };
    }
}
