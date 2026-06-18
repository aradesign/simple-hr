<?php

namespace App\Services\Sms\Providers;

use App\Services\Settings\SettingService;
use App\Services\Sms\Contracts\SmsProviderInterface;
use App\Support\SmsActionCatalog;
use Illuminate\Support\Facades\Http;

/**
 * sms.ir REST API
 *
 * @see https://sms.ir/rest-api/
 */
class SmsIrProvider implements SmsProviderInterface
{
    private const BASE_URL = 'https://api.sms.ir/v1';

    public function __construct(
        private readonly SettingService $settings,
    ) {}

    public function send(string $mobile, string $message): array
    {
        return $this->sendBulk($mobile, $message);
    }

    public function sendForAction(string $actionKey, string $mobile, string $message, array $vars = []): array
    {
        $templateId = $this->settings->smsPattern('sms_ir', $actionKey);

        if ($templateId && $action = SmsActionCatalog::find($actionKey)) {
            $parameters = $this->mapSmsIrParameters($action, $vars);

            if ($parameters !== []) {
                return $this->sendVerify($mobile, (int) $templateId, $parameters);
            }
        }

        return $this->sendBulk($mobile, $message);
    }

    public function sendOtp(string $mobile, string $code): array
    {
        return $this->sendOtpForAction('otp_recruitment', $mobile, $code);
    }

    public function sendOtpForAction(string $actionKey, string $mobile, string $code): array
    {
        $templateId = $this->settings->smsPattern('sms_ir', $actionKey);

        if ($templateId) {
            $result = $this->sendVerify($mobile, (int) $templateId, [
                ['name' => 'CODE', 'value' => $code],
            ]);

            if ($result['success'] ?? false) {
                return $result;
            }
        }

        $message = $this->settings->renderForAction($actionKey, ['code' => $code]);

        return $this->sendBulk($mobile, $message);
    }

    public function testConnection(): array
    {
        $config = $this->settings->group('sms');

        if (empty($config['sms_ir_api_key'])) {
            return ['success' => false, 'message' => 'کلید API sms.ir وارد نشده است.'];
        }

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'x-api-key' => $config['sms_ir_api_key'],
        ])->get(self::BASE_URL.'/credit');

        if ($response->successful()) {
            return [
                'success' => true,
                'message' => 'اتصال به sms.ir برقرار است.',
                'data' => $response->json(),
            ];
        }

        return [
            'success' => false,
            'message' => $response->json('message') ?? 'خطا در اتصال به sms.ir',
            'data' => $response->json(),
        ];
    }

    private function sendBulk(string $mobile, string $message): array
    {
        $config = $this->settings->group('sms');

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'x-api-key' => $config['sms_ir_api_key'] ?? '',
        ])->post(self::BASE_URL.'/send/bulk', [
            'lineNumber' => (int) ($config['sms_ir_line_number'] ?? 0),
            'messageText' => $message,
            'mobiles' => [$this->normalizeMobile($mobile)],
        ]);

        return $this->formatResponse($response->json(), $response->successful());
    }

    /** @param list<array{name: string, value: string}> $parameters */
    private function sendVerify(string $mobile, int $templateId, array $parameters): array
    {
        $config = $this->settings->group('sms');

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'x-api-key' => $config['sms_ir_api_key'] ?? '',
        ])->post(self::BASE_URL.'/send/verify', [
            'mobile' => $this->normalizeMobile($mobile),
            'templateId' => $templateId,
            'parameters' => $parameters,
        ]);

        return $this->formatResponse($response->json(), $response->successful());
    }

    /** @param array{variables: list<array{key: string, sms_ir: string}>} $action */
    private function mapSmsIrParameters(array $action, array $vars): array
    {
        $parameters = [];

        foreach ($action['variables'] as $variable) {
            if (! array_key_exists($variable['key'], $vars)) {
                continue;
            }

            $parameters[] = [
                'name' => $variable['sms_ir'],
                'value' => (string) $vars[$variable['key']],
            ];
        }

        return $parameters;
    }

    private function normalizeMobile(string $mobile): string
    {
        $mobile = preg_replace('/\D/', '', $mobile);

        if (str_starts_with($mobile, '98')) {
            return $mobile;
        }

        if (str_starts_with($mobile, '0')) {
            return '98'.substr($mobile, 1);
        }

        return $mobile;
    }

    private function formatResponse(?array $data, bool $success): array
    {
        return [
            'success' => $success,
            'message' => $data['message'] ?? ($success ? 'ارسال موفق' : 'ارسال ناموفق'),
            'data' => $data,
        ];
    }
}
