<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsurePortalAuth;
use App\Models\NotificationLog;
use App\Models\Person;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $person = $this->resolvePerson($request);

        $person->load([
            'employmentRecords.department',
            'departments',
        ]);

        return view('portal.dashboard', compact('person'));
    }

    public function notifications(Request $request): View
    {
        $person = $this->resolvePerson($request);

        $notifications = NotificationLog::query()
            ->where('person_id', $person->id)
            ->where('channel', 'in_app')
            ->latest()
            ->paginate(20);

        return view('portal.notifications.index', compact('person', 'notifications'));
    }

    private function resolvePerson(Request $request): Person
    {
        return Person::query()->findOrFail(
            $request->session()->get(EnsurePortalAuth::SESSION_KEY),
        );
    }
}
