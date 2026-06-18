<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorize('updateRole', $user);

        $validated = $request->validate([
            'role' => ['required', 'string'],
            'hr_access' => ['boolean'],
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
        ]);

        return back()->with('success', 'دسترسی کاربر با موفقیت به‌روزرسانی شد.');
    }
}
