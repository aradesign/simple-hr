<?php

namespace App\Domain\Enums;

enum AuditAction: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Deleted = 'deleted';
    case Downloaded = 'downloaded';
    case StatusChanged = 'status_changed';
    case MessageSent = 'message_sent';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'ایجاد',
            self::Updated => 'ویرایش',
            self::Deleted => 'حذف',
            self::Downloaded => 'دانلود',
            self::StatusChanged => 'تغییر وضعیت',
            self::MessageSent => 'ارسال پیام',
        };
    }
}
