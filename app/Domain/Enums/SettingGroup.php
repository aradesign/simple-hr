<?php

namespace App\Domain\Enums;

enum SettingGroup: string
{
    case Appearance = 'appearance';
    case Branding = 'branding';
    case Sms = 'sms';
    case Texts = 'texts';

    public function label(): string
    {
        return match ($this) {
            self::Appearance => 'ظاهر و تم',
            self::Branding => 'برند و لوگو',
            self::Sms => 'پنل پیامکی',
            self::Texts => 'متون و ادبیات',
        };
    }
}
