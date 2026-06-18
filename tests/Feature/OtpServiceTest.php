<?php

namespace Tests\Feature;

use App\Domain\Enums\OtpPurpose;
use App\DTOs\OtpRequestData;
use App\Models\OtpCode;
use App\Services\Otp\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OtpServiceTest extends TestCase
{
    use RefreshDatabase;

    private OtpService $otpService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->otpService = app(OtpService::class);
    }

    public function test_it_generates_and_stores_otp_code(): void
    {
        $mobile = '09123456789';
        $data = new OtpRequestData($mobile, OtpPurpose::Recruitment);

        $code = $this->otpService->generate($data);

        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);

        $this->assertDatabaseHas('otp_codes', [
            'mobile' => $mobile,
            'purpose' => OtpPurpose::Recruitment->value,
        ]);

        $this->assertEquals(1, OtpCode::query()->forMobile($mobile)->count());
    }

    public function test_it_verifies_valid_otp_code(): void
    {
        $mobile = '09123456780';
        $data = new OtpRequestData($mobile, OtpPurpose::Recruitment);

        $code = $this->otpService->generate($data);

        $verified = $this->otpService->verify($mobile, $code, OtpPurpose::Recruitment);

        $this->assertTrue($verified);
        $this->assertNotNull(
            OtpCode::query()->forMobile($mobile)->first()?->verified_at,
        );
    }

    public function test_it_rejects_invalid_otp_code(): void
    {
        $mobile = '09123456781';
        $data = new OtpRequestData($mobile, OtpPurpose::Portal);

        $this->otpService->generate($data);

        $this->expectException(ValidationException::class);

        $this->otpService->verify($mobile, '000000', OtpPurpose::Portal);
    }

    public function test_it_enforces_rate_limit(): void
    {
        $mobile = '09123456782';
        $purpose = OtpPurpose::Recruitment;

        for ($i = 0; $i < 3; $i++) {
            OtpCode::query()->create([
                'mobile' => $mobile,
                'code_hash' => bcrypt('123456'),
                'purpose' => $purpose,
                'expires_at' => now()->addMinutes(2),
                'attempts' => 0,
            ]);
        }

        $this->assertTrue($this->otpService->isRateLimited($mobile, $purpose));

        $this->expectException(ValidationException::class);

        $this->otpService->generate(new OtpRequestData($mobile, $purpose));
    }
}
