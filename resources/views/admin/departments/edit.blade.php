@php use App\Models\User; @endphp

<x-app-layout title="ویرایش دپارتمان">
    <div class="space-y-6">
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">ویرایش {{ $department->name }}</h1>
        <x-card>
            <form method="POST" action="{{ route('admin.departments.update', $department) }}" class="space-y-4">
                @csrf @method('PUT')
                <x-form.input label="نام" name="name" :value="$department->name" required />
                <x-form.input label="کد" name="code" :value="$department->code" required />
                <x-form.textarea label="توضیحات" name="description" :value="$department->description" />
                <x-form.select label="مدیر" name="manager_id">
                    <option value="">—</option>
                    @foreach (User::query()->where('hr_access', true)->get() as $user)
                        <option value="{{ $user->id }}" @selected($department->manager_id == $user->id)>{{ $user->name }}</option>
                    @endforeach
                </x-form.select>
                <x-form.input label="ترتیب" name="sort_order" type="number" :value="$department->sort_order" />
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_active" value="1" @checked($department->is_active) class="rounded border-slate-300 text-blue-600">
                    فعال
                </label>
                <div class="flex gap-3">
                    <x-button type="submit" variant="primary">ذخیره</x-button>
                    <x-button href="{{ route('admin.departments.index') }}" variant="ghost">انصراف</x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
