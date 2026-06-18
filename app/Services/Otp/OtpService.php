<?php

namespace App\Services\Otp;

use App\Domain\Enums\OtpPurpose;
use App\DTOs\OtpRequestData;
use App\Models\OtpCode;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OtpService
{
    private const CODE_LENGTH = 6;

    private const TTL_MINUTES = 2;

    private const MAX_ATTEMPTS = 5;

    private const RATE_LIMIT_COUNT = 3;

    private const RATE_LIMIT_MINUTES = 10;

    public function generate(OtpRequestData $data): string
    {
        $this->enforceRateLimit($data->mobile, $data->purpose);

        $code = $this->generateCode();

        OtpCode::query()
            ->forMobile($data->mobile)
            ->forPurpose($data->purpose)
            ->unverified()
            ->delete();

        OtpCode::query()->create([
            'mobile' => $data->mobile,
            'code_hash' => Hash::make($code),
            'purpose' => $data->purpose,
            'expires_at' => now()->addMinutes(self::TTL_MINUTES),
            'attempts' => 0,
        ]);

        return $code;
    }

    public function verify(string $mobile, string $code, OtpPurpose $purpose): bool
    {
        $otp = OtpCode::query()
            ->forMobile($mobile)
            ->forPurpose($purpose)
            ->valid()
            ->latest()
            ->first();

        if (! $otp) {
            throw ValidationException::withMessages([
                'code' => 'کد تأیید نامعتبر یا منقضی شده است.',
            ]);
        }

        if ($otp->attempts >= self::MAX_ATTEMPTS) {
            throw ValidationException::withMessages([
                'code' => 'تعداد تلاش‌های مجاز به پایان رسیده است.',
            ]);
        }

        if (! Hash::check($code, $otp->code_hash)) {
            $otp->increment('attempts');

            throw ValidationException::withMessages([
                'code' => 'کد تأیید اشتباه است.',
            ]);
        }

        $otp->update(['verified_at' => now()]);

        return true;
    }

    public function isRateLimited(string $mobile, OtpPurpose $purpose): bool
    {
        $recentCount = OtpCode::query()
            ->forMobile($mobile)
            ->forPurpose($purpose)
            ->where('created_at', '>=', now()->subMinutes(self::RATE_LIMIT_MINUTES))
            ->count();

        return $recentCount >= self::RATE_LIMIT_COUNT;
    }

    private function enforceRateLimit(string $mobile, OtpPurpose $purpose): void
    {
        if ($this->isRateLimited($mobile, $purpose)) {
            throw ValidationException::withMessages([
                'mobile' => 'تعداد درخواست‌های کد تأیید بیش از حد مجاز است. لطفاً چند دقیقه دیگر تلاش کنید.',
            ]);
        }
    }

    private function generateCode(): string
    {
        return Str::padLeft((string) random_int(0, 10 ** self::CODE_LENGTH - 1), self::CODE_LENGTH, '0');
    }
}
