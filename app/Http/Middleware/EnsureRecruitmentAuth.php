<?php

namespace App\Http\Middleware;

use App\Services\Portal\PortalAccessResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRecruitmentAuth
{
    public const SESSION_KEY = 'recruitment_person_id';

    public const CONTACT_MOBILE_KEY = 'recruitment_contact_mobile';

    public const ACTIVE_PERSON_KEY = 'recruitment_active_person_id';

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->has(self::CONTACT_MOBILE_KEY)) {
            return redirect()->route('recruitment.login');
        }

        $contactMobile = (string) $request->session()->get(self::CONTACT_MOBILE_KEY);
        $employee = app(PortalAccessResolver::class)->findEmployeeByContactMobile($contactMobile);

        if ($employee) {
            return app(PortalAccessResolver::class)->redirectRecruitmentSessionToPortal($request, $employee);
        }

        return $next($request);
    }
}
