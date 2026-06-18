import {
    formatJalali,
    gregorianDateFromJalali,
    jalaliKey,
    shiftMonth,
    todayJalali,
} from './jalali-utils';

export function registerHrCalendar(Alpine) {
    Alpine.data('hrCalendar', (config = {}) => ({
        year: config.year,
        month: config.month,
        monthName: config.monthName,
        daysInMonth: config.daysInMonth,
        startWeekday: config.startWeekday,
        weekdays: ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'],
        events: config.events || [],
        eventColors: config.eventColors || {},
        persons: config.persons || [],
        eventTypes: config.eventTypes || [],
        routes: config.routes || {},
        today: todayJalali(),
        selectedKey: todayJalali().key,
        panelMode: 'day',
        editingEvent: null,
        form: {
            title: '',
            description: '',
            event_type: 'hr_event',
            starts_at: '',
            starts_time: '09:00',
            ends_at: '',
            all_day: false,
            person_id: '',
        },

        init() {
            if (this.year === this.today.year && this.month === this.today.month) {
                this.selectedKey = this.today.key;
            }
        },

        eventsByDate() {
            const map = {};

            this.events.forEach((event) => {
                if (!map[event.jalali_date]) {
                    map[event.jalali_date] = [];
                }

                map[event.jalali_date].push(event);
            });

            return map;
        },

        dayKey(day) {
            return jalaliKey(this.year, this.month, day);
        },

        dayEvents(day) {
            return this.eventsByDate()[this.dayKey(day)] || [];
        },

        selectedEvents() {
            return this.eventsByDate()[this.selectedKey] || [];
        },

        selectedLabel() {
            const [y, m, d] = this.selectedKey.split('-').map(Number);

            return formatJalali(gregorianDateFromJalali(y, m, d), false);
        },

        isToday(day) {
            return this.dayKey(day) === this.today.key;
        },

        isSelected(day) {
            return this.dayKey(day) === this.selectedKey;
        },

        selectDay(day) {
            this.selectedKey = this.dayKey(day);
            this.panelMode = 'day';
            this.editingEvent = null;
        },

        monthUrl(year, month) {
            const url = new URL(this.routes.month, window.location.origin);
            url.searchParams.set('year', year);
            url.searchParams.set('month', month);

            return url.pathname + url.search;
        },

        prevMonth() {
            const next = shiftMonth(this.year, this.month, -1);
            window.location.href = this.monthUrl(next.year, next.month);
        },

        nextMonth() {
            const next = shiftMonth(this.year, this.month, 1);
            window.location.href = this.monthUrl(next.year, next.month);
        },

        goToday() {
            window.location.href = this.monthUrl(this.today.year, this.today.month);
        },

        openCreate() {
            const [y, m, d] = this.selectedKey.split('-').map(Number);
            this.form = {
                title: '',
                description: '',
                event_type: 'hr_event',
                starts_at: gregorianDateFromJalali(y, m, d),
                starts_time: '09:00',
                ends_at: '',
                all_day: false,
                person_id: '',
            };
            this.panelMode = 'form';
            this.editingEvent = null;
        },

        openEdit(event) {
            this.editingEvent = event;
            this.form = {
                title: event.title,
                description: event.description || '',
                event_type: event.event_type,
                starts_at: event.starts_at.slice(0, 10),
                starts_time: event.time || '09:00',
                ends_at: event.ends_at ? event.ends_at.slice(0, 10) : '',
                all_day: !!event.all_day,
                person_id: event.person_id || '',
            };
            this.panelMode = 'form';
        },

        cancelForm() {
            this.panelMode = 'day';
            this.editingEvent = null;
        },

        formAction() {
            if (this.editingEvent) {
                return this.routes.update.replace('__ID__', this.editingEvent.id);
            }

            return this.routes.store;
        },

        formMethod() {
            return this.editingEvent ? 'PUT' : 'POST';
        },

        destroyAction() {
            if (!this.editingEvent) {
                return '#';
            }

            return this.routes.destroy.replace('__ID__', this.editingEvent.id);
        },

        blankCells() {
            return Array.from({ length: this.startWeekday }, (_, index) => index);
        },

        monthDays() {
            return Array.from({ length: this.daysInMonth }, (_, i) => i + 1);
        },

        eventColor(type) {
            return this.eventColors[type] || 'bg-slate-500';
        },

        eventTimeLabel(event) {
            return event.all_day ? 'تمام‌روز' : (event.time || '');
        },
    }));
}
