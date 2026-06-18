<?php

namespace App\Domain\Enums;

enum CalendarEventType: string
{
    case Interview = 'interview';
    case Birthday = 'birthday';
    case ContractEnd = 'contract_end';
    case ContractRenewal = 'contract_renewal';
    case ProbationEnd = 'probation_end';
    case Training = 'training';
    case HrEvent = 'hr_event';

    public function label(): string
    {
        return match ($this) {
            self::Interview => 'مصاحبه',
            self::Birthday => 'تولد',
            self::ContractEnd => 'پایان قرارداد',
            self::ContractRenewal => 'تمدید قرارداد',
            self::ProbationEnd => 'پایان دوره آزمایشی',
            self::Training => 'آموزش',
            self::HrEvent => 'رویداد HR',
        };
    }
}
