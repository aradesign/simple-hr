<?php

namespace App\Domain\Enums;

enum InterviewType: string
{
    case InPerson = 'in_person';
    case Online = 'online';

    public function label(): string
    {
        return match ($this) {
            self::InPerson => 'حضوری',
            self::Online => 'آنلاین',
        };
    }
}
