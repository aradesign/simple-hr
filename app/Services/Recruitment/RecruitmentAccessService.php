<?php

namespace App\Services\Recruitment;

use App\Models\EmploymentApplication;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;

class RecruitmentAccessService
{
    public function contactMobile(Request $request): ?string
    {
        return $request->session()->get(\App\Http\Middleware\EnsureRecruitmentAuth::CONTACT_MOBILE_KEY);
    }

    public function applicationsQuery(string $contactMobile): Builder
    {
        return EmploymentApplication::query()
            ->where('contact_mobile', $contactMobile)
            ->with('person')
            ->latest();
    }

    public function canManage(string $contactMobile, EmploymentApplication $application): bool
    {
        return $application->contact_mobile === $contactMobile;
    }

    public function authorizeApplication(Request $request, EmploymentApplication $application): EmploymentApplication
    {
        $contactMobile = $this->contactMobile($request);

        if (! $contactMobile || ! $this->canManage($contactMobile, $application)) {
            abort(403);
        }

        if ($application->status !== \App\Domain\Enums\ApplicationStatus::Draft
            && $request->isMethod('PUT')) {
            throw new HttpResponseException(
                redirect()->route('recruitment.applications.status', $application)
                    ->with('error', 'این درخواست قبلاً ارسال شده و قابل ویرایش نیست.')
            );
        }

        return $application;
    }
}
