<?php

namespace App\Http\Controllers\Portal;

use App\DTOs\PersonData;
use App\Http\Controllers\Concerns\ResolvesPortalPerson;
use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\UpdateProfileRequest;
use App\Services\Person\PersonService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    use ResolvesPortalPerson;

    public function __construct(
        private readonly PersonService $personService,
    ) {}

    public function edit(Request $request): View
    {
        $person = $this->portalPerson($request);
        $person->load(['educations', 'workExperiences', 'employmentRecords.department', 'departments']);

        return view('portal.profile.edit', compact('person'));
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $person = $this->portalPerson($request);

        $this->personService->update(
            $person,
            PersonData::fromArray($request->validated()),
        );

        return redirect()
            ->route('portal.profile')
            ->with('success', 'پروفایل با موفقیت به‌روزرسانی شد.');
    }
}
