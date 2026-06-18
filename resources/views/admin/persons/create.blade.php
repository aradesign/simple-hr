<x-app-layout title="افزودن پرسنل">
    <div class="space-y-6">
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">افزودن پرسنل</h1>
        <x-card>
            @include('admin.persons._form', ['action' => route('admin.persons.store')])
        </x-card>
    </div>
</x-app-layout>
