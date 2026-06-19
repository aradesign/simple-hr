<?php

namespace App\Services\Sms;

use App\Services\Settings\SettingService;
use App\Services\Sms\Contracts\SmsProviderInterface;
use App\Services\Sms\Providers\IppanelProvider;
use App\Services\Sms\Providers\SmsIrProvider;
use App\Support\SmsActionCatalog;
use RuntimeException;

class SmsGatewayService
{
    public function __construct(
        private readonly SettingService $settings,
        private readonly SmsIrProvider $smsIr,
        private readonly IppanelProvider $ippanel,
    ) {}

    public function isEnabled(): bool
    {
        return (bool) $this->settings->get('sms', 'enabled', false);
    }

    public function status(): array
    {
        $provider = $this->settings->get('sms', 'provider', 'sms_ir');

        return [
            'enabled' => $this->isEnabled(),
            'provider' => $provider,
            'provider_label' => $provider === 'ippanel' ? 'ippanel.com' : 'sms.ir',
            'configured' => $this->isConfigured(),
            'actions_count' => count(SmsActionCatalog::all()),
        ];
    }

    public function isConfigured(): bool
    {
        $sms = $this->settings->group('sms');

        if (($sms['provider'] ?? 'sms_ir') === 'ippanel') {
            return ! empty($sms['ippanel_api_key']) && ! empty($sms['ippanel_originator']);
        }

        return ! empty($sms['sms_ir_api_key']);
    }

    public function send(string $mobile, string $message): array
    {
        return $this->sendForAction('application_status', $mobile, $message, []);
    }

    public function sendForAction(string $actionKey, string $mobile, string $message, array $vars = []): array
    {
        if (! $this->isEnabled()) {
            return [
                'success' => true,
                'message' => 'پیامک غیرفعال است — پیام فقط در لاگ ثبت شد.',
                'simulated' => true,
            ];
        }

        return $this->provider()->sendForAction($actionKey, $mobile, $message, $vars);
    }

    public function sendOtp(string $mobile, string $code): array
    {
        return $this->sendOtpForAction('otp_recruitment', $mobile, $code);
    }

    public function sendOtpForAction(string $actionKey, string $mobile, string $code): array
    {
        if (! $this->isEnabled()) {
            return [
                'success' => true,
                'message' => 'OTP در حالت شبیه‌سازی ثبت شد.',
                'simulated' => true,
                'code' => $code,
            ];
        }

        return $this->provider()->sendOtpForAction($actionKey, $mobile, $code);
    }

    public function testConnection(): array
    {
        if (! $this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'اطلاعات اتصال پنل پیامکی ناقص است. ابتدا تنظیمات را تکمیل کنید.',
            ];
        }

        try {
            return $this->provider()->testConnection();
        } catch (\Throwable $e) {
            report($e);

            return [
                'success' => false,
                'message' => 'خطا در اتصال به سرویس پیامک: '.$e->getMessage(),
            ];
        }
    }

    private function provider(): SmsProviderInterface
    {
        $name = $this->settings->get('sms', 'provider', 'sms_ir');

        return match ($name) {
            'ippanel' => $this->ippanel,
            'sms_ir' => $this->smsIr,
            default => throw new RuntimeException("ارائه‌دهنده پیامک نامعتبر: {$name}"),
        };
    }
}
