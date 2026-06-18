@php use App\Models\User; @endphp

<x-app-layout title="افزودن دپارتمان">
    <div class="space-y-6">
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">افزودن دپارتمان</h1>
        <x-card>
            <form method="POST" action="{{ route('admin.departments.store') }}" class="space-y-4">
                @csrf
                <x-form.input label="نام" name="name" :value="old('name')" required />
                <x-form.input label="کد" name="code" :value="old('code')" required />
                <x-form.textarea label="توضیحات" name="description" :value="old('description')" />
                <x-form.select label="مدیر" name="manager_id">
                    <option value="">—</option>
                    @foreach (User::query()->where('hr_access', true)->get() as $user)
                        <option value="{{ $user->id }}" @selected(old('manager_id') == $user->id)>{{ $user->name }}</option>
                    @endforeach
                </x-form.select>
                <x-form.input label="ترتیب" name="sort_order" type="number" :value="old('sort_order', 0)" />
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300 text-blue-600">
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
