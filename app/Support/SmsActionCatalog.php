<?php

namespace App\Support;

/**
 * فهرست تمام اکشن‌های پیامکی سیستم.
 * متن پیشنهادی pattern_template را در پنل sms.ir / ippanel بسازید و کد قالب را در تنظیمات وارد کنید.
 */
class SmsActionCatalog
{
    public const TYPE_OTP = 'otp';

    public const TYPE_TRANSACTIONAL = 'transactional';

    /**
     * @return list<array{
     *     key: string,
     *     label: string,
     *     description: string,
     *     text_key: string,
     *     type: string,
     *     variables: list<array{key: string, label: string, sms_ir: string, ippanel: string}>,
     *     pattern_template: string,
     * }>
     */
    public static function all(): array
    {
        return [
            [
                'key' => 'otp_recruitment',
                'label' => 'کد تأیید — ورود استخدام',
                'description' => 'وقتی متقاضی در صفحه استخدام (/recruitment/login) درخواست کد تأیید می‌دهد.',
                'text_key' => 'otp_recruitment_sms',
                'type' => self::TYPE_OTP,
                'variables' => [
                    ['key' => 'code', 'label' => 'کد ۶ رقمی', 'sms_ir' => 'CODE', 'ippanel' => 'code'],
                ],
                'pattern_template' => 'کد تأیید درخواست استخدام: %code%',
            ],
            [
                'key' => 'otp_portal',
                'label' => 'کد تأیید — ورود پورتال پرسنل',
                'description' => 'وقتی پرسنل در پورتال (/portal/login) درخواست کد تأیید می‌دهد.',
                'text_key' => 'otp_portal_sms',
                'type' => self::TYPE_OTP,
                'variables' => [
                    ['key' => 'code', 'label' => 'کد ۶ رقمی', 'sms_ir' => 'CODE', 'ippanel' => 'code'],
                ],
                'pattern_template' => 'کد ورود پورتال پرسنلی: %code%',
            ],
            [
                'key' => 'application_submitted',
                'label' => 'ثبت درخواست استخدام',
                'description' => 'بعد از ارسال نهایی فرم درخواست توسط متقاضی (وضعیت: ارسال‌شده).',
                'text_key' => 'application_submitted_sms',
                'type' => self::TYPE_TRANSACTIONAL,
                'variables' => [
                    ['key' => 'number', 'label' => 'شماره درخواست', 'sms_ir' => 'NUMBER', 'ippanel' => 'number'],
                    ['key' => 'name', 'label' => 'نام متقاضی', 'sms_ir' => 'NAME', 'ippanel' => 'name'],
                ],
                'pattern_template' => '%name% عزیز، درخواست %number% با موفقیت ثبت شد.',
            ],
            [
                'key' => 'application_status',
                'label' => 'تغییر وضعیت درخواست',
                'description' => 'وقتی HR وضعیت درخواست را تغییر می‌دهد (به‌جز ثبت، پذیرش و رد).',
                'text_key' => 'application_status_sms',
                'type' => self::TYPE_TRANSACTIONAL,
                'variables' => [
                    ['key' => 'number', 'label' => 'شماره درخواست', 'sms_ir' => 'NUMBER', 'ippanel' => 'number'],
                    ['key' => 'status', 'label' => 'وضعیت جدید', 'sms_ir' => 'STATUS', 'ippanel' => 'status'],
                    ['key' => 'old_status', 'label' => 'وضعیت قبلی', 'sms_ir' => 'OLD_STATUS', 'ippanel' => 'old_status'],
                    ['key' => 'name', 'label' => 'نام متقاضی', 'sms_ir' => 'NAME', 'ippanel' => 'name'],
                ],
                'pattern_template' => 'وضعیت درخواست %number% از %old_status% به %status% تغییر یافت.',
            ],
            [
                'key' => 'application_accepted',
                'label' => 'پذیرش درخواست استخدام',
                'description' => 'وقتی وضعیت درخواست به «پذیرفته‌شده» تغییر کند.',
                'text_key' => 'application_accepted_sms',
                'type' => self::TYPE_TRANSACTIONAL,
                'variables' => [
                    ['key' => 'number', 'label' => 'شماره درخواست', 'sms_ir' => 'NUMBER', 'ippanel' => 'number'],
                    ['key' => 'name', 'label' => 'نام متقاضی', 'sms_ir' => 'NAME', 'ippanel' => 'name'],
                ],
                'pattern_template' => 'تبریک %name% عزیز! درخواست استخدام %number% پذیرفته شد.',
            ],
            [
                'key' => 'application_rejected',
                'label' => 'رد درخواست استخدام',
                'description' => 'وقتی وضعیت درخواست به «رد‌شده» تغییر کند.',
                'text_key' => 'application_rejected_sms',
                'type' => self::TYPE_TRANSACTIONAL,
                'variables' => [
                    ['key' => 'number', 'label' => 'شماره درخواست', 'sms_ir' => 'NUMBER', 'ippanel' => 'number'],
                    ['key' => 'name', 'label' => 'نام متقاضی', 'sms_ir' => 'NAME', 'ippanel' => 'name'],
                ],
                'pattern_template' => '%name% عزیز، متأسفانه درخواست %number% پذیرفته نشد.',
            ],
            [
                'key' => 'interview_scheduled_candidate',
                'label' => 'زمان‌بندی مصاحبه — متقاضی',
                'description' => 'وقتی برای متقاضی مصاحبه ثبت می‌شود.',
                'text_key' => 'interview_scheduled_sms',
                'type' => self::TYPE_TRANSACTIONAL,
                'variables' => [
                    ['key' => 'date', 'label' => 'تاریخ شمسی', 'sms_ir' => 'DATE', 'ippanel' => 'date'],
                    ['key' => 'time', 'label' => 'ساعت', 'sms_ir' => 'TIME', 'ippanel' => 'time'],
                    ['key' => 'name', 'label' => 'نام متقاضی', 'sms_ir' => 'NAME', 'ippanel' => 'name'],
                    ['key' => 'location', 'label' => 'محل مصاحبه', 'sms_ir' => 'LOCATION', 'ippanel' => 'location'],
                ],
                'pattern_template' => '%name% عزیز، مصاحبه شما %date% ساعت %time% — %location%',
            ],
            [
                'key' => 'interview_scheduled_interviewer',
                'label' => 'زمان‌بندی مصاحبه — مصاحبه‌کننده',
                'description' => 'وقتی برای مصاحبه‌کننده (کاربر HR) مصاحبه ثبت می‌شود.',
                'text_key' => 'interview_scheduled_interviewer_sms',
                'type' => self::TYPE_TRANSACTIONAL,
                'variables' => [
                    ['key' => 'date', 'label' => 'تاریخ شمسی', 'sms_ir' => 'DATE', 'ippanel' => 'date'],
                    ['key' => 'time', 'label' => 'ساعت', 'sms_ir' => 'TIME', 'ippanel' => 'time'],
                    ['key' => 'candidate_name', 'label' => 'نام متقاضی', 'sms_ir' => 'CANDIDATE', 'ippanel' => 'candidate_name'],
                    ['key' => 'location', 'label' => 'محل مصاحبه', 'sms_ir' => 'LOCATION', 'ippanel' => 'location'],
                ],
                'pattern_template' => 'مصاحبه با %candidate_name% — %date% ساعت %time% — %location%',
            ],
            [
                'key' => 'sms_test',
                'label' => 'پیام آزمایشی تنظیمات',
                'description' => 'ارسال از بخش «ارسال آزمایشی» در تنظیمات پیامک.',
                'text_key' => 'sms_test_sms',
                'type' => self::TYPE_TRANSACTIONAL,
                'variables' => [
                    ['key' => 'code', 'label' => 'کد نمونه', 'sms_ir' => 'CODE', 'ippanel' => 'code'],
                    ['key' => 'site_name', 'label' => 'نام سایت', 'sms_ir' => 'SITE', 'ippanel' => 'site_name'],
                ],
                'pattern_template' => 'پیام آزمایشی %site_name% — کد: %code%',
            ],
        ];
    }

    public static function find(string $key): ?array
    {
        foreach (self::all() as $action) {
            if ($action['key'] === $key) {
                return $action;
            }
        }

        return null;
    }

    public static function smsIrPatternSettingKey(string $actionKey): string
    {
        return "pattern_sms_ir_{$actionKey}";
    }

    public static function ippanelPatternSettingKey(string $actionKey): string
    {
        return "pattern_ippanel_{$actionKey}";
    }

    public static function variableLabels(string $actionKey): string
    {
        $action = self::find($actionKey);

        if (! $action) {
            return '';
        }

        return collect($action['variables'])
            ->map(fn (array $var) => '{'.$var['key'].'}')
            ->implode('، ');
    }
}
