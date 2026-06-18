<?php

namespace App\Domain\Enums;

enum FormFieldType: string
{
    case Text = 'text';
    case Textarea = 'textarea';
    case Select = 'select';
    case Radio = 'radio';
    case Checkbox = 'checkbox';
    case Date = 'date';
    case File = 'file';
    case Number = 'number';
    case Email = 'email';
    case Tel = 'tel';
    case List = 'list';
    case NationalId = 'national_id';
    case Hidden = 'hidden';

    public function label(): string
    {
        return match ($this) {
            self::Text => 'متن',
            self::Textarea => 'متن چندخطی',
            self::Select => 'انتخاب',
            self::Radio => 'رادیویی',
            self::Checkbox => 'چک‌باکس',
            self::Date => 'تاریخ',
            self::File => 'فایل',
            self::Number => 'عدد',
            self::Email => 'ایمیل',
            self::Tel => 'تلفن',
            self::List => 'لیست',
            self::NationalId => 'کد ملی',
            self::Hidden => 'مخفی',
        };
    }
}
