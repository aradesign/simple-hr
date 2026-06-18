<?php

namespace App\Services\Portal;

use App\Domain\Enums\PersonLifecycleStatus;
use App\Http\Middleware\EnsurePortalAuth;
use App\Http\Middleware\EnsureRecruitmentAuth;
use App\Models\Person;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PortalAccessResolver
{
    /** @return list<PersonLifecycleStatus> */
    public static function employeePortalStatuses(): array
    {
        return [
            PersonLifecycleStatus::Employee,
            PersonLifecycleStatus::FormerEmployee,
        ];
    }

    public function findEmployeeByContactMobile(string $mobile): ?Person
    {
        $normalized = $this->normalizeMobile($mobile);

        if ($normalized === null) {
            return null;
        }

        return Person::query()
            ->whereIn('lifecycle_status', self::employeePortalStatuses())
            ->where(function ($query) use ($normalized) {
                $query->where('mobile', $normalized)
                    ->orWhere('managed_by_mobile', $normalized);
            })
            ->first();
    }

    public function redirectRecruitmentSessionToPortal(Request $request, Person $person): RedirectResponse
    {
        $request->session()->forget([
            EnsureRecruitmentAuth::CONTACT_MOBILE_KEY,
            EnsureRecruitmentAuth::ACTIVE_PERSON_KEY,
            EnsureRecruitmentAuth::SESSION_KEY,
        ]);

        $request->session()->put(EnsurePortalAuth::SESSION_KEY, $person->id);

        return redirect()
            ->route('portal.dashboard')
            ->with('success', 'به پورتال پرسنلی خوش آمدید.');
    }

    private function normalizeMobile(string $mobile): ?string
    {
        $digits = preg_replace('/\D+/', '', $mobile);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '9') && strlen($digits) === 10) {
            return '0'.$digits;
        }

        return $digits;
    }
}
