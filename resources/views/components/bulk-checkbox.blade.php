@props([
    'id' => null,
    'head' => false,
])

@if (auth()->user()?->canManageHr())
    @if ($head)
        <th class="px-4 py-3 w-10">
            <input
                type="checkbox"
                class="rounded border-slate-400 text-cyan-600 focus:ring-cyan-500"
                :checked="allSelected"
                :indeterminate.prop="isIndeterminate"
                @change="toggleAll($event.target.checked)"
                aria-label="انتخاب همه"
            >
        </th>
    @else
        <td class="px-4 py-3 w-10">
            <input
                type="checkbox"
                class="rounded border-slate-400 text-cyan-600 focus:ring-cyan-500"
                value="{{ $id }}"
                x-model="selected"
                aria-label="انتخاب ردیف"
            >
        </td>
    @endif
@endif
