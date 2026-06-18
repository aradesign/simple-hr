<?php

namespace App\Http\Controllers\Recruitment;

use App\Http\Controllers\Controller;
use App\Services\Recruitment\RecruitmentAccessService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly RecruitmentAccessService $accessService,
    ) {}

    public function index(Request $request): View
    {
        $contactMobile = $this->accessService->contactMobile($request);

        $applications = $this->accessService->applicationsQuery($contactMobile)->get();

        return view('recruitment.dashboard', [
            'contactMobile' => $contactMobile,
            'applications' => $applications,
        ]);
    }
}
