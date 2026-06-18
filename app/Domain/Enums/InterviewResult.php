<?php

namespace App\Domain\Enums;

enum InterviewResult: string
{
    case Passed = 'passed';
    case Failed = 'failed';
    case Pending = 'pending';
    case NextRound = 'next_round';

    public function label(): string
    {
        return match ($this) {
            self::Passed => 'قبول',
            self::Failed => 'رد',
            self::Pending => 'در انتظار',
            self::NextRound => 'مرحله بعد',
        };
    }
}
