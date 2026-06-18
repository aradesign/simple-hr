<x-app-layout title="ویرایش پرسنل">
    <div class="space-y-6">
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">ویرایش {{ $person->full_name }}</h1>
        <x-card>
            @include('admin.persons._form', ['person' => $person, 'action' => route('admin.persons.update', $person), 'method' => 'PUT'])
        </x-card>
    </div>
</x-app-layout>
