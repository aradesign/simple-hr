<?php

namespace App\Domain\Enums;

enum Gender: string
{
    case Male = 'male';
    case Female = 'female';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Male => 'مرد',
            self::Female => 'زن',
            self::Other => 'سایر',
        };
    }
}
