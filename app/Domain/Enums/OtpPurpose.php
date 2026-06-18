<?php

namespace App\Domain\Enums;

enum OtpPurpose: string
{
    case Recruitment = 'recruitment';
    case Portal = 'portal';

    public function label(): string
    {
        return match ($this) {
            self::Recruitment => 'استخدام',
            self::Portal => 'پورتال',
        };
    }
}
