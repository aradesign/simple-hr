<?php

namespace App\Domain\Enums;

enum InterviewStatus: string
{
    case Scheduled = 'scheduled';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'برنامه‌ریزی‌شده',
            self::Completed => 'انجام‌شده',
            self::Cancelled => 'لغو‌شده',
            self::NoShow => 'عدم حضور',
        };
    }
}
