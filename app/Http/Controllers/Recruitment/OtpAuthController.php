<?php

namespace App\Http\Controllers\Recruitment;

use App\Domain\Enums\OtpPurpose;
use App\DTOs\OtpRequestData;
use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureRecruitmentAuth;
use App\Services\Notification\NotificationService;
use App\Services\Otp\OtpService;
use App\Services\Portal\PortalAccessResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OtpAuthController extends Controller
{
    public function __construct(
        private readonly OtpService $otpService,
        private readonly NotificationService $notificationService,
        private readonly PortalAccessResolver $portalAccessResolver,
    ) {}

    public function showLogin(): View
    {
        return view('recruitment.login');
    }

    public function requestOtp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mobile' => ['required', 'string', 'regex:/^09\d{9}$/'],
        ]);

        $code = $this->otpService->generate(new OtpRequestData(
            mobile: $validated['mobile'],
            purpose: OtpPurpose::Recruitment,
        ));

        $this->notificationService->sendOtpSms($validated['mobile'], $code, 'otp_recruitment');

        return redirect()
            ->route('recruitment.verify')
            ->with('mobile', $validated['mobile'])
            ->with('success', 'کد تأیید ارسال شد.');
    }

    public function verifyOtp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mobile' => ['required', 'string', 'regex:/^09\d{9}$/'],
            'code' => ['required', 'string', 'size:6'],
        ]);

        $this->otpService->verify(
            $validated['mobile'],
            $validated['code'],
            OtpPurpose::Recruitment,
        );

        $request->session()->put(EnsureRecruitmentAuth::CONTACT_MOBILE_KEY, $validated['mobile']);

        $employee = $this->portalAccessResolver->findEmployeeByContactMobile($validated['mobile']);

        if ($employee) {
            return $this->portalAccessResolver->redirectRecruitmentSessionToPortal($request, $employee);
        }

        return redirect()->route('recruitment.dashboard');
    }

    public function showVerify(): View
    {
        return view('recruitment.verify');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget([
            EnsureRecruitmentAuth::CONTACT_MOBILE_KEY,
            EnsureRecruitmentAuth::ACTIVE_PERSON_KEY,
            EnsureRecruitmentAuth::SESSION_KEY,
        ]);

        return redirect()->route('recruitment.login');
    }
}
