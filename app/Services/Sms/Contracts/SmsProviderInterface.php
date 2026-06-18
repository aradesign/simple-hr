<?php

namespace App\Services\Sms\Contracts;

interface SmsProviderInterface
{
    public function send(string $mobile, string $message): array;

    public function sendOtp(string $mobile, string $code): array;

    public function sendForAction(string $actionKey, string $mobile, string $message, array $vars = []): array;

    public function sendOtpForAction(string $actionKey, string $mobile, string $code): array;

    public function testConnection(): array;
}
