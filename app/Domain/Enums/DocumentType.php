<?php

namespace App\Domain\Enums;

enum DocumentType: string
{
    case Contract = 'contract';
    case Decree = 'decree';
    case Education = 'education';
    case NationalId = 'national_id';
    case BirthCertificate = 'birth_certificate';
    case Certificate = 'certificate';
    case General = 'general';

    public function label(): string
    {
        return match ($this) {
            self::Contract => 'قرارداد',
            self::Decree => 'حکم',
            self::Education => 'تحصیلی',
            self::NationalId => 'کارت ملی',
            self::BirthCertificate => 'شناسنامه',
            self::Certificate => 'گواهینامه',
            self::General => 'عمومی',
        };
    }
}
