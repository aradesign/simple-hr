@php
    $calendarConfig = [
        'year' => $jalaliYear,
        'month' => $jalaliMonth,
        'monthName' => $monthMeta['month_name'],
        'daysInMonth' => $monthMeta['days_in_month'],
        'startWeekday' => $monthMeta['first_day_of_week'],
        'events' => $eventsPayload,
        'eventColors' => $eventColors,
        'persons' => $persons->map(fn ($p) => ['id' => $p->id, 'name' => $p->full_name])->values(),
        'eventTypes' => collect($eventTypes)->map(fn ($t) => ['value' => $t->value, 'label' => $t->label()])->values(),
        'routes' => [
            'month' => route('admin.calendar.index'),
            'store' => route('admin.calendar.store'),
            'update' => route('admin.calendar.update', ['event' => '__ID__']),
            'destroy' => route('admin.calendar.destroy', ['event' => '__ID__']),
        ],
    ];
@endphp

<x-app-layout title="تقویم HR">
    <div
        class="space-y-6"
        x-data="hrCalendar(@js($calendarConfig))"
    >
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">تقویم HR</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    <span x-text="monthName"></span>
                    <span x-text="year"></span>
                    — روی هر روز کلیک کنید
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <x-button type="button" variant="secondary" size="sm" @click="goToday()">امروز</x-button>
                <x-button type="button" variant="secondary" size="sm" @click="prevMonth()">ماه قبل</x-button>
                <x-button type="button" variant="secondary" size="sm" @click="nextMonth()">ماه بعد</x-button>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            {{-- Calendar grid --}}
            <div class="xl:col-span-2">
                <x-card>
                    <div class="grid grid-cols-7 gap-1 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 mb-2">
                        <template x-for="day in weekdays" :key="day">
                            <div x-text="day"></div>
                        </template>
                    </div>

                    <div class="grid grid-cols-7 gap-1.5">
                        <template x-for="blank in blankCells()" :key="'blank-' + blank">
                            <div class="min-h-[5.5rem]"></div>
                        </template>

                        <template x-for="day in monthDays()" :key="day">
                            <button
                                type="button"
                                @click="selectDay(day)"
                                class="min-h-[5.5rem] rounded-xl border p-2 text-right transition-all flex flex-col gap-1"
                                :class="{
                                    'border-cyan-500 bg-cyan-500/10 ring-1 ring-cyan-500/40': isSelected(day),
                                    'border-slate-200 dark:border-cyan-500/15 hover:border-cyan-500/30 hover:bg-slate-50 dark:hover:bg-cyan-500/5': !isSelected(day),
                                    'shadow-[inset_0_0_0_1px_rgba(34,211,238,0.35)]': isToday(day) && !isSelected(day),
                                }"
                            >
                                <div class="flex items-center justify-between">
                                    <span
                                        class="text-sm font-bold"
                                        :class="isToday(day) ? 'text-cyan-600 dark:text-cyan-400' : 'text-slate-700 dark:text-slate-200'"
                                        x-text="day"
                                    ></span>
                                    <span
                                        x-show="dayEvents(day).length"
                                        class="text-[10px] px-1.5 py-0.5 rounded-full bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300"
                                        x-text="dayEvents(day).length"
                                    ></span>
                                </div>

                                <div class="space-y-0.5 flex-1 overflow-hidden">
                                    <template x-for="event in dayEvents(day).slice(0, 3)" :key="event.id">
                                        <div
                                            class="text-[10px] truncate text-white rounded px-1 py-0.5"
                                            :class="eventColor(event.event_type)"
                                            x-text="event.title"
                                        ></div>
                                    </template>
                                    <div
                                        x-show="dayEvents(day).length > 3"
                                        class="text-[10px] text-slate-500"
                                        x-text="'+' + (dayEvents(day).length - 3) + ' مورد دیگر'"
                                    ></div>
                                </div>
                            </button>
                        </template>
                    </div>

                    <div class="flex flex-wrap gap-3 mt-4 pt-4 border-t border-slate-200 dark:border-cyan-500/15 text-xs">
                        @foreach($eventColors as $type => $color)
                            <span class="inline-flex items-center gap-1.5 text-slate-600 dark:text-slate-400">
                                <span class="w-2.5 h-2.5 rounded-full {{ $color }}"></span>
                                {{ \App\Domain\Enums\CalendarEventType::from($type)->label() }}
                            </span>
                        @endforeach
                    </div>
                </x-card>
            </div>

            {{-- Day / event panel --}}
            <div class="xl:col-span-1">
                <x-card class="sticky top-20">
                    <div x-show="panelMode === 'day'" class="space-y-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">رویدادهای روز</h2>
                                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1" x-text="selectedLabel()"></p>
                            </div>
                            <x-button type="button" variant="primary" size="sm" @click="openCreate()">+ جدید</x-button>
                        </div>

                        <template x-if="selectedEvents().length === 0">
                            <x-empty-state title="رویدادی ثبت نشده" description="برای این روز هنوز رویدادی ندارید." />
                        </template>

                        <div class="space-y-2">
                            <template x-for="event in selectedEvents()" :key="event.id">
                                <button
                                    type="button"
                                    @click="openEdit(event)"
                                    class="w-full text-right p-3 rounded-xl border border-slate-200 dark:border-cyan-500/15 hover:border-cyan-500/35 hover:bg-slate-50 dark:hover:bg-cyan-500/5 transition-colors"
                                >
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="w-2 h-2 rounded-full shrink-0" :class="eventColor(event.event_type)"></span>
                                        <span class="font-medium text-slate-900 dark:text-white truncate" x-text="event.title"></span>
                                    </div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400 flex flex-wrap gap-2">
                                        <span x-text="event.event_type_label"></span>
                                        <span x-text="eventTimeLabel(event)"></span>
                                    </div>
                                </button>
                            </template>
                        </div>
                    </div>

                    <div x-show="panelMode === 'form'" x-cloak class="space-y-4">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-slate-900 dark:text-white" x-text="editingEvent ? 'ویرایش رویداد' : 'رویداد جدید'"></h2>
                            <button type="button" @click="cancelForm()" class="text-sm text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">بازگشت</button>
                        </div>

                        <form :action="formAction()" method="POST" class="space-y-3">
                            @csrf
                            <input type="hidden" name="_method" :value="formMethod()">

                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">عنوان <span class="text-red-500">*</span></label>
                                <input type="text" name="title" x-model="form.title" required class="cyber-input w-full rounded-lg px-3 py-2 text-sm">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">نوع رویداد</label>
                                <select name="event_type" x-model="form.event_type" class="cyber-input w-full rounded-lg px-3 py-2 text-sm">
                                    <template x-for="type in eventTypes" :key="type.value">
                                        <option :value="type.value" x-text="type.label"></option>
                                    </template>
                                </select>
                            </div>

                            <input type="hidden" name="starts_at" x-model="form.starts_at">

                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">ساعت</label>
                                <input type="time" name="starts_time" x-model="form.starts_time" class="cyber-input w-full rounded-lg px-3 py-2 text-sm">
                            </div>

                            <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400">
                                <input type="checkbox" name="all_day" value="1" x-model="form.all_day" class="rounded border-slate-400 text-cyan-600">
                                تمام‌روز
                            </label>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">پرسنل (اختیاری)</label>
                                <select name="person_id" x-model="form.person_id" class="cyber-input w-full rounded-lg px-3 py-2 text-sm">
                                    <option value="">—</option>
                                    <template x-for="person in persons" :key="person.id">
                                        <option :value="person.id" x-text="person.name"></option>
                                    </template>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">توضیحات</label>
                                <textarea name="description" x-model="form.description" rows="3" class="cyber-input w-full rounded-lg px-3 py-2 text-sm"></textarea>
                            </div>

                            <div class="flex flex-wrap gap-2 pt-2">
                                <x-button type="submit" variant="primary">ذخیره</x-button>
                                <x-button type="button" variant="ghost" @click="cancelForm()">انصراف</x-button>
                            </div>
                        </form>

                        <form x-show="editingEvent" method="POST" :action="destroyAction()" onsubmit="return confirm('حذف این رویداد؟')">
                            @csrf
                            @method('DELETE')
                            <x-button type="submit" variant="secondary" class="w-full text-red-600 dark:text-pink-400">حذف رویداد</x-button>
                        </form>
                    </div>
                </x-card>
            </div>
        </div>
    </div>
</x-app-layout>
