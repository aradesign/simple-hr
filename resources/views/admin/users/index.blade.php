<x-app-layout title="کاربران">
    <div class="space-y-6">
        <div class="flex items-center justify-between gap-4">
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">مدیریت کاربران</h1>
            @can('create', \App\Models\User::class)
                <x-button href="{{ route('admin.users.create') }}" variant="primary">افزودن کاربر</x-button>
            @endcan
        </div>

        <x-card>
            <form method="GET" class="flex flex-col sm:flex-row gap-3 mb-6">
                <x-form.input label="جستجو" name="search" :value="request('search')" class="flex-1" />
                <x-form.select label="نقش" name="role" class="sm:w-48">
                    <option value="">همه</option>
                    @foreach (\App\Domain\Enums\UserRole::cases() as $role)
                        <option value="{{ $role->value }}" @selected(request('role') === $role->value)>{{ $role->label() }}</option>
                    @endforeach
                </x-form.select>
                <div class="flex items-end"><x-button type="submit" variant="secondary">فیلتر</x-button></div>
            </form>

            @if ($users->isEmpty())
                <x-empty-state title="کاربری یافت نشد" />
            @else
                <x-data-table>
                    <x-slot:head>
                        <th class="px-4 py-3 font-medium">نام</th>
                        <th class="px-4 py-3 font-medium">ایمیل</th>
                        <th class="px-4 py-3 font-medium">پرسنل مرتبط</th>
                        <th class="px-4 py-3 font-medium">نقش</th>
                        <th class="px-4 py-3 font-medium">دسترسی HR</th>
                        <th class="px-4 py-3 font-medium">عملیات</th>
                    </x-slot:head>
                    @foreach ($users as $user)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                            <td class="px-4 py-3">{{ $user->name }}</td>
                            <td class="px-4 py-3">{{ $user->email }}</td>
                            <td class="px-4 py-3">
                                @if ($user->person)
                                    <a href="{{ route('admin.persons.show', $user->person) }}" class="text-cyan-600 hover:underline">
                                        {{ $user->person->full_name }}
                                    </a>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $user->role?->label() }}</td>
                            <td class="px-4 py-3"><x-badge :variant="$user->hr_access ? 'success' : 'default'">{{ $user->hr_access ? 'دارد' : 'ندارد' }}</x-badge></td>
                            <td class="px-4 py-3">
                                <form method="POST" action="{{ route('admin.users.update', $user) }}" class="flex flex-wrap items-center gap-2">
                                    @csrf @method('PATCH')
                                    <select name="role" class="text-xs rounded border-slate-300 dark:border-slate-600 dark:bg-slate-700 px-2 py-1">
                                        @foreach (\App\Domain\Enums\UserRole::cases() as $role)
                                            @if ($role === \App\Domain\Enums\UserRole::SuperAdmin && ! auth()->user()->isSuperAdmin())
                                                @continue
                                            @endif
                                            <option value="{{ $role->value }}" @selected($user->role === $role)>{{ $role->label() }}</option>
                                        @endforeach
                                    </select>
                                    <label class="flex items-center gap-1 text-xs">
                                        <input type="checkbox" name="hr_access" value="1" @checked($user->hr_access) class="rounded">
                                        HR
                                    </label>
                                    <x-button type="submit" variant="ghost" size="sm">ذخیره</x-button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </x-data-table>
                <div class="mt-4">{{ $users->links() }}</div>
            @endif
        </x-card>
    </div>
</x-app-layout>
