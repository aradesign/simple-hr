<?php

namespace App\Domain\Enums;

enum NotificationStatus: string
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'در انتظار',
            self::Sent => 'ارسال‌شده',
            self::Failed => 'ناموفق',
        };
    }
}
