<?php

namespace App\Services\Settings;

use App\Domain\Enums\SettingGroup;
use App\Models\Setting;
use App\Support\SmsActionCatalog;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class SettingService
{
    private const CACHE_KEY = 'app_settings';

    public function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            $settings = [];

            foreach (Setting::query()->get() as $setting) {
                $settings[$setting->group->value][$setting->key] = $this->castValue($setting);
            }

            return array_replace_recursive($this->defaults(), $settings);
        });
    }

    public function get(string $group, string $key, mixed $default = null): mixed
    {
        return data_get($this->all(), "{$group}.{$key}", $default);
    }

    public function group(SettingGroup|string $group): array
    {
        $groupKey = $group instanceof SettingGroup ? $group->value : $group;

        return $this->all()[$groupKey] ?? [];
    }

    public function setMany(SettingGroup|string $group, array $values): void
    {
        $groupKey = $group instanceof SettingGroup ? $group->value : $group;

        foreach ($values as $key => $value) {
            if ($value instanceof UploadedFile) {
                $value = $this->storeFile($value, $groupKey);
            }

            if (is_bool($value)) {
                $type = 'boolean';
                $stored = $value ? '1' : '0';
            } elseif (is_array($value)) {
                $type = 'json';
                $stored = json_encode($value, JSON_UNESCAPED_UNICODE);
            } else {
                $type = 'string';
                $stored = $value === null ? null : (string) $value;
            }

            Setting::query()->updateOrCreate(
                ['group' => $groupKey, 'key' => $key],
                ['value' => $stored, 'type' => $type],
            );
        }

        $this->clearCache();
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function defaults(): array
    {
        return [
            SettingGroup::Appearance->value => [
                'default_theme' => 'dark',
                'primary_color' => 'cyan',
                'show_scanlines' => true,
                'sidebar_compact' => false,
                'card_style' => 'rounded',
            ],
            SettingGroup::Branding->value => [
                'site_name' => 'سامانه منابع انسانی',
                'site_tagline' => 'مدیریت چرخه عمر پرسنل',
                'logo_path' => null,
                'logo_dark_path' => null,
                'favicon_path' => null,
            ],
            SettingGroup::Sms->value => array_merge([
                'enabled' => false,
                'provider' => 'sms_ir',
                'sms_ir_api_key' => '',
                'sms_ir_line_number' => '',
                'sms_ir_secret_key' => '',
                'ippanel_api_key' => '',
                'ippanel_originator' => '',
                'ippanel_base_url' => 'https://edge.ippanel.com/v1',
            ], self::defaultSmsPatterns()),
            SettingGroup::Texts->value => [
                'welcome_message' => 'به سامانه منابع انسانی خوش آمدید',
                'dashboard_greeting' => 'سلام',
                'login_title' => 'ورود به پنل منابع انسانی',
                'login_subtitle' => 'با حساب کاربری سازمانی وارد شوید',
                'footer_text' => '',
                'recruitment_title' => 'درخواست استخدام',
                'recruitment_subtitle' => 'با شماره موبایل وارد شوید و درخواست‌ها را مدیریت کنید',
                'portal_title' => 'پورتال پرسنلی',
                'portal_subtitle' => 'ورود پرسنل با کد تأیید موبایل',
                'otp_recruitment_sms' => 'کد تأیید درخواست استخدام: {code}',
                'otp_portal_sms' => 'کد ورود پورتال پرسنلی: {code}',
                'application_submitted_sms' => '{name} عزیز، درخواست {number} با موفقیت ثبت شد.',
                'application_status_sms' => 'وضعیت درخواست {number} از «{old_status}» به «{status}» تغییر یافت.',
                'interview_scheduled_sms' => '{name} عزیز، مصاحبه شما {date} ساعت {time} — {location}',
                'application_accepted_sms' => 'تبریک {name} عزیز! درخواست استخدام {number} پذیرفته شد.',
                'application_rejected_sms' => '{name} عزیز، متأسفانه درخواست {number} پذیرفته نشد.',
                'interview_scheduled_interviewer_sms' => 'مصاحبه با {candidate_name} — {date} ساعت {time} — {location}',
                'sms_test_sms' => 'پیام آزمایشی {site_name} — کد: {code}',
            ],
        ];
    }

    public function renderText(string $key, array $vars = [], ?string $default = null): string
    {
        $template = $this->get('texts', $key, $default ?? '');

        if ($template === '' && $key === 'otp_recruitment_sms') {
            $template = $this->get('texts', 'otp_message', 'کد تأیید: {code}');
        }

        foreach ($vars as $name => $value) {
            $template = str_replace('{'.$name.'}', (string) $value, $template);
        }

        return $template;
    }

    public function renderForAction(string $actionKey, array $vars = []): string
    {
        $action = SmsActionCatalog::find($actionKey);

        if (! $action) {
            return '';
        }

        return $this->renderText($action['text_key'], $vars);
    }

    public function smsPattern(string $provider, string $actionKey): ?string
    {
        $key = $provider === 'sms_ir'
            ? SmsActionCatalog::smsIrPatternSettingKey($actionKey)
            : SmsActionCatalog::ippanelPatternSettingKey($actionKey);

        $value = $this->get('sms', $key, '');

        return $value !== '' ? (string) $value : null;
    }

    /** @return array<string, string> */
    public static function defaultSmsPatterns(): array
    {
        $patterns = [];

        foreach (SmsActionCatalog::all() as $action) {
            $patterns[SmsActionCatalog::smsIrPatternSettingKey($action['key'])] = '';
            $patterns[SmsActionCatalog::ippanelPatternSettingKey($action['key'])] = '';
        }

        return $patterns;
    }

    /** @return array<string, string> */
    public static function defaultSmsTexts(): array
    {
        $texts = [];

        foreach (SmsActionCatalog::all() as $action) {
            $texts[$action['text_key']] = '';
        }

        return $texts;
    }

    public function deleteBrandingFile(string $key): void
    {
        $path = $this->get('branding', $key);

        if ($path) {
            Storage::disk('public')->delete($path);
            $this->setMany(SettingGroup::Branding, [$key => null]);
        }
    }

    private function castValue(Setting $setting): mixed
    {
        return match ($setting->type) {
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($setting->value ?? '[]', true),
            default => $setting->value,
        };
    }

    private function storeFile(UploadedFile $file, string $group): string
    {
        return $file->store("settings/{$group}", 'public');
    }

    public function brandingUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }
}
