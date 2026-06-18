<?php

namespace App\Services\Sms\Providers;

use App\Services\Settings\SettingService;
use App\Services\Sms\Contracts\SmsProviderInterface;
use App\Support\SmsActionCatalog;
use Illuminate\Support\Facades\Http;

/**
 * IPPanel Edge API
 *
 * @see https://ippanelcom.github.io/Edge-Document/docs/
 */
class IppanelProvider implements SmsProviderInterface
{
    public function __construct(
        private readonly SettingService $settings,
    ) {}

    public function send(string $mobile, string $message): array
    {
        return $this->sendWebservice($mobile, $message);
    }

    public function sendForAction(string $actionKey, string $mobile, string $message, array $vars = []): array
    {
        $patternCode = $this->settings->smsPattern('ippanel', $actionKey);

        if ($patternCode && $action = SmsActionCatalog::find($actionKey)) {
            $values = $this->mapIppanelValues($action, $vars);

            if ($values !== []) {
                $result = $this->sendPattern($patternCode, $mobile, $values);

                if ($result['success'] ?? false) {
                    return $result;
                }
            }
        }

        return $this->sendWebservice($mobile, $message);
    }

    public function sendOtp(string $mobile, string $code): array
    {
        return $this->sendOtpForAction('otp_recruitment', $mobile, $code);
    }

    public function sendOtpForAction(string $actionKey, string $mobile, string $code): array
    {
        return $this->sendForAction($actionKey, $mobile, $this->settings->renderForAction($actionKey, ['code' => $code]), [
            'code' => $code,
        ]);
    }

    public function testConnection(): array
    {
        $config = $this->settings->group('sms');
        $baseUrl = rtrim($config['ippanel_base_url'] ?? 'https://edge.ippanel.com/v1', '/');

        if (empty($config['ippanel_api_key'])) {
            return ['success' => false, 'message' => 'کلید API ippanel وارد نشده است.'];
        }

        $response = Http::withHeaders([
            'Authorization' => $config['ippanel_api_key'],
            'Accept' => 'application/json',
        ])->get("{$baseUrl}/api/user");

        if ($response->successful()) {
            return [
                'success' => true,
                'message' => 'اتصال به ippanel برقرار است.',
                'data' => $response->json(),
            ];
        }

        return [
            'success' => false,
            'message' => $response->json('message') ?? 'خطا در اتصال به ippanel',
            'data' => $response->json(),
        ];
    }

    private function sendWebservice(string $mobile, string $message): array
    {
        $config = $this->settings->group('sms');
        $baseUrl = rtrim($config['ippanel_base_url'] ?? 'https://edge.ippanel.com/v1', '/');

        $response = Http::withHeaders([
            'Authorization' => $config['ippanel_api_key'] ?? '',
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post("{$baseUrl}/api/send/webservice", [
            'originator' => $config['ippanel_originator'] ?? '',
            'recipients' => [$this->normalizeMobile($mobile)],
            'message' => $message,
        ]);

        return $this->formatResponse($response->json(), $response->successful());
    }

    /** @param array<string, string> $values */
    private function sendPattern(string $patternCode, string $mobile, array $values): array
    {
        $config = $this->settings->group('sms');
        $baseUrl = rtrim($config['ippanel_base_url'] ?? 'https://edge.ippanel.com/v1', '/');

        $response = Http::withHeaders([
            'Authorization' => $config['ippanel_api_key'] ?? '',
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post("{$baseUrl}/api/send/pattern", [
            'pattern_code' => $patternCode,
            'originator' => $config['ippanel_originator'] ?? '',
            'recipient' => $this->normalizeMobile($mobile),
            'values' => $values,
        ]);

        return $this->formatResponse($response->json(), $response->successful());
    }

    /** @param array{variables: list<array{key: string, ippanel: string}>} $action */
    private function mapIppanelValues(array $action, array $vars): array
    {
        $values = [];

        foreach ($action['variables'] as $variable) {
            if (! array_key_exists($variable['key'], $vars)) {
                continue;
            }

            $values[$variable['ippanel']] = (string) $vars[$variable['key']];
        }

        return $values;
    }

    private function normalizeMobile(string $mobile): string
    {
        $mobile = preg_replace('/\D/', '', $mobile);

        if (str_starts_with($mobile, '98')) {
            return '+'.$mobile;
        }

        if (str_starts_with($mobile, '0')) {
            return '+98'.substr($mobile, 1);
        }

        return '+'.$mobile;
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
