<?php

namespace App\Http\Controllers\Portal;

use App\Domain\Enums\OtpPurpose;
use App\Domain\Enums\PersonLifecycleStatus;
use App\DTOs\OtpRequestData;
use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsurePortalAuth;
use App\Models\Person;
use App\Services\Notification\NotificationService;
use App\Services\Otp\OtpService;
use App\Services\Person\PersonMobileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OtpAuthController extends Controller
{
    public function __construct(
        private readonly OtpService $otpService,
        private readonly NotificationService $notificationService,
        private readonly PersonMobileService $personMobileService,
    ) {}

    public function showLogin(): View
    {
        return view('portal.login');
    }

    public function requestOtp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mobile' => ['required', 'string', 'regex:/^09\d{9}$/'],
        ]);

        $person = Person::query()
            ->whereIn('lifecycle_status', [
                PersonLifecycleStatus::Employee,
                PersonLifecycleStatus::FormerEmployee,
            ])
            ->where(function ($query) use ($validated) {
                $query->where('mobile', $validated['mobile'])
                    ->orWhere('managed_by_mobile', $validated['mobile']);
            })
            ->first();

        if (! $person) {
            throw ValidationException::withMessages([
                'mobile' => 'شماره موبایل در پرونده پرسنلی یافت نشد.',
            ]);
        }

        $code = $this->otpService->generate(new OtpRequestData(
            mobile: $validated['mobile'],
            purpose: OtpPurpose::Portal,
        ));

        $this->notificationService->sendOtpSms($validated['mobile'], $code, 'otp_portal');

        return redirect()
            ->route('portal.verify')
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
            OtpPurpose::Portal,
        );

        $person = Person::query()
            ->whereIn('lifecycle_status', [
                PersonLifecycleStatus::Employee,
                PersonLifecycleStatus::FormerEmployee,
            ])
            ->where(function ($query) use ($validated) {
                $query->where('mobile', $validated['mobile'])
                    ->orWhere('managed_by_mobile', $validated['mobile']);
            })
            ->firstOrFail();

        if ($person->usesTemporaryMobile()) {
            $person = $this->personMobileService->assignRealMobile(
                $person,
                $validated['mobile'],
                $person->employmentApplications()->latest('id')->first(),
            );
        }

        $request->session()->put(EnsurePortalAuth::SESSION_KEY, $person->id);

        return redirect()->route('portal.dashboard');
    }

    public function showVerify(): View
    {
        return view('portal.verify');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget(EnsurePortalAuth::SESSION_KEY);

        return redirect()->route('portal.login');
    }
}
