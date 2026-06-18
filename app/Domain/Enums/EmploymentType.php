<?php

namespace App\Domain\Enums;

enum EmploymentType: string
{
    case FullTime = 'full_time';
    case PartTime = 'part_time';
    case Contract = 'contract';
    case Intern = 'intern';

    public function label(): string
    {
        return match ($this) {
            self::FullTime => 'تمام‌وقت',
            self::PartTime => 'پاره‌وقت',
            self::Contract => 'قراردادی',
            self::Intern => 'کارآموز',
        };
    }
}
