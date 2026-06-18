@props(['title' => null])

<x-layouts.app :title="$title">
    @isset($hero)
        <x-slot:hero>{{ $hero }}</x-slot:hero>
    @endisset
    {{ $slot }}
</x-layouts.app>
