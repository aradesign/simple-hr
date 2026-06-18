<?php

namespace App\Domain\Enums;

enum HrTicketStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'باز',
            self::InProgress => 'در حال بررسی',
            self::Resolved => 'پاسخ داده‌شده',
            self::Closed => 'بسته‌شده',
        };
    }
}
