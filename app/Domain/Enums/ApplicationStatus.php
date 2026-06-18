<?php

namespace App\Domain\Enums;

enum ApplicationStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case InterviewScheduled = 'interview_scheduled';
    case Interviewed = 'interviewed';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'پیش‌نویس',
            self::Submitted => 'ارسال‌شده',
            self::UnderReview => 'در حال بررسی',
            self::InterviewScheduled => 'مصاحبه برنامه‌ریزی‌شده',
            self::Interviewed => 'مصاحبه‌شده',
            self::Accepted => 'پذیرفته‌شده',
            self::Rejected => 'رد‌شده',
            self::Withdrawn => 'انصراف‌داده‌شده',
        };
    }
}
