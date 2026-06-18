<?php

namespace App\Domain\Enums;

enum PersonLifecycleStatus: string
{
    case Applicant = 'applicant';
    case Interviewed = 'interviewed';
    case Accepted = 'accepted';
    case Employee = 'employee';
    case FormerEmployee = 'former_employee';

    public function label(): string
    {
        return match ($this) {
            self::Applicant => 'متقاضی',
            self::Interviewed => 'مصاحبه‌شده',
            self::Accepted => 'پذیرفته‌شده',
            self::Employee => 'کارمند',
            self::FormerEmployee => 'کارمند سابق',
        };
    }

    public function isPersonnelRoster(): bool
    {
        return in_array($this, self::personnelRosterCases(), true);
    }

    /** @return list<self> */
    public static function personnelRosterCases(): array
    {
        return [
            self::Employee,
            self::FormerEmployee,
        ];
    }
}
