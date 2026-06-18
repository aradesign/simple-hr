<?php

namespace App\Services\Notification;

use App\Domain\Enums\NotificationStatus;
use App\Models\NotificationLog;
use App\Models\Person;
use App\Models\User;
use App\Services\Settings\SettingService;
use App\Services\Sms\SmsGatewayService;
use App\Support\SmsActionCatalog;

class NotificationService
{
    public function __construct(
        private readonly SmsGatewayService $smsGateway,
        private readonly SettingService $settings,
    ) {}

    public function sendInApp(User $user, string $subject, string $body): NotificationLog
    {
        return NotificationLog::query()->create([
            'user_id' => $user->id,
            'person_id' => $user->person_id,
            'channel' => 'in_app',
            'recipient' => $user->email ?? (string) $user->id,
            'subject' => $subject,
            'body' => $body,
            'status' => NotificationStatus::Sent,
        ]);
    }

    public function sendInAppToPerson(Person $person, string $subject, string $body): NotificationLog
    {
        return NotificationLog::query()->create([
            'user_id' => $person->user?->id,
            'person_id' => $person->id,
            'channel' => 'in_app',
            'recipient' => $person->mobile ?? (string) $person->id,
            'subject' => $subject,
            'body' => $body,
            'status' => NotificationStatus::Sent,
        ]);
    }

    public function sendSms(?User $user, string $mobile, string $message): NotificationLog
    {
        return $this->sendActionSms($user, $mobile, 'application_status', [], $message);
    }

    public function sendActionSms(
        ?User $user,
        string $mobile,
        string $actionKey,
        array $vars = [],
        ?string $message = null,
    ): NotificationLog {
        $action = SmsActionCatalog::find($actionKey);
        $body = $message ?? $this->settings->renderForAction($actionKey, $vars);

        $log = NotificationLog::query()->create([
            'user_id' => $user?->id,
            'channel' => 'sms',
            'recipient' => $mobile,
            'subject' => $action['label'] ?? 'پیامک',
            'body' => $body,
            'status' => NotificationStatus::Pending,
        ]);

        try {
            $result = ($action && ($action['type'] ?? '') === SmsActionCatalog::TYPE_OTP)
                ? $this->smsGateway->sendOtpForAction($actionKey, $mobile, (string) ($vars['code'] ?? ''))
                : $this->smsGateway->sendForAction($actionKey, $mobile, $body, $vars);

            if ($result['success'] ?? false) {
                $log->update(['status' => NotificationStatus::Sent]);
            } else {
                $log->update([
                    'status' => NotificationStatus::Failed,
                    'error_message' => $result['message'] ?? 'خطای نامشخص',
                ]);
            }
        } catch (\Throwable $exception) {
            $log->update([
                'status' => NotificationStatus::Failed,
                'error_message' => $exception->getMessage(),
            ]);
        }

        return $log->fresh();
    }

    public function sendOtpSms(string $mobile, string $code, string $actionKey = 'otp_recruitment'): NotificationLog
    {
        return $this->sendActionSms(null, $mobile, $actionKey, ['code' => $code]);
    }
}
