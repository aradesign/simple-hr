<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Models\Person;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()
            ->with('person')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('role'), fn ($query) => $query->where('role', $request->string('role')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        $availablePersons = Person::query()
            ->withoutUserAccount()
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $personOptions = $availablePersons->mapWithKeys(function (Person $person) {
            $suggestedRole = match ($person->lifecycle_status) {
                \App\Domain\Enums\PersonLifecycleStatus::Employee,
                \App\Domain\Enums\PersonLifecycleStatus::FormerEmployee => UserRole::Employee,
                default => UserRole::Candidate,
            };

            $slug = Str::slug($person->full_name, '.');
            if ($slug === '') {
                $slug = 'person'.$person->id;
            }

            return [
                $person->id => [
                    'name' => $person->full_name,
                    'mobile' => $person->display_mobile,
                    'suggested_email' => "{$slug}.{$person->id}@hr.local",
                    'suggested_role' => $suggestedRole->value,
                ],
            ];
        });

        return view('admin.users.create', compact('availablePersons', 'personOptions'));
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $newRole = UserRole::from($request->string('role')->toString());

        if ($newRole === UserRole::SuperAdmin && ! $request->user()->isSuperAdmin()) {
            return back()
                ->withInput()
                ->with('error', 'فقط مدیر سیستم می‌تواند نقش مدیر سیستم تعیین کند.');
        }

        $person = $request->filled('person_id')
            ? Person::query()->find($request->integer('person_id'))
            : null;

        User::query()->create([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'mobile' => $request->string('mobile')->toString() ?: $person?->display_mobile,
            'password' => $request->string('password')->toString(),
            'role' => $newRole,
            'hr_access' => $request->boolean('hr_access'),
            'person_id' => $person?->id,
            'email_verified_at' => now(),
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'کاربر جدید با موفقیت ایجاد شد.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorize('updateRole', $user);

        $validated = $request->validate([
            'role' => ['required', 'string'],
            'hr_access' => ['boolean'],
            'person_id' => [
                'nullable',
                'integer',
                'exists:persons,id',
                \Illuminate\Validation\Rule::unique('users', 'person_id')->ignore($user->id),
            ],
        ]);

        $newRole = UserRole::from($validated['role']);

        if ($newRole === UserRole::SuperAdmin && ! $request->user()->isSuperAdmin()) {
            return back()->with('error', 'فقط مدیر سیستم می‌تواند نقش مدیر سیستم تعیین کند.');
        }

        if ($user->isSuperAdmin() && ! $request->user()->isSuperAdmin()) {
            return back()->with('error', 'امکان تغییر دسترسی مدیر سیستم وجود ندارد.');
        }

        $user->update([
            'role' => UserRole::from($validated['role']),
            'hr_access' => $request->boolean('hr_access'),
            'person_id' => $validated['person_id'] ?? null,
        ]);

        return back()->with('success', 'دسترسی کاربر با موفقیت به‌روزرسانی شد.');
    }
}
