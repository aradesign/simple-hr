<?php

namespace App\Domain\Enums;

enum UserRole: string
{
    case Candidate = 'candidate';
    case Employee = 'employee';
    case Hr = 'hr';
    case HrManager = 'hr_manager';
    case SuperAdmin = 'super_admin';

    public function label(): string
    {
        return match ($this) {
            self::Candidate => 'متقاضی',
            self::Employee => 'کارمند',
            self::Hr => 'منابع انسانی',
            self::HrManager => 'مدیر منابع انسانی',
            self::SuperAdmin => 'مدیر کل',
        };
    }

    public function isHr(): bool
    {
        return in_array($this, [self::Hr, self::HrManager, self::SuperAdmin], true);
    }

    public function isSuperAdmin(): bool
    {
        return $this === self::SuperAdmin;
    }

    public function isHrManager(): bool
    {
        return in_array($this, [self::HrManager, self::SuperAdmin], true);
    }

    public function level(): int
    {
        return match ($this) {
            self::Candidate => 10,
            self::Employee => 30,
            self::Hr => 50,
            self::HrManager => 70,
            self::SuperAdmin => 100,
        };
    }

    public function outranks(self $other): bool
    {
        return $this->level() > $other->level();
    }
}
