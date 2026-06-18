import { jalaaliMonthLength, toGregorian, toJalaali } from 'jalaali-js';

if (typeof window !== 'undefined') {
    window.jalaali = { toGregorian, toJalaali, jalaaliMonthLength };
}

export const WEEKDAYS = ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'];

export const MONTH_NAMES = [
    'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور',
    'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند',
];

export function jalaliKey(year, month, day) {
    return `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
}

export function todayJalali() {
    const now = new Date();
    const { jy, jm, jd } = toJalaali(now.getFullYear(), now.getMonth() + 1, now.getDate());

    return { year: jy, month: jm, day: jd, key: jalaliKey(jy, jm, jd) };
}

export function shiftMonth(year, month, delta) {
    let m = month + delta;
    let y = year;

    while (m > 12) {
        m -= 12;
        y += 1;
    }

    while (m < 1) {
        m += 12;
        y -= 1;
    }

    return { year: y, month: m };
}

export function iranianWeekday(jy, jm, jd) {
    const { gy, gm, gd } = toGregorian(jy, jm, jd);

    return (new Date(gy, gm - 1, gd).getDay() + 1) % 7;
}

export function monthGrid(year, month) {
    return {
        year,
        month,
        monthName: MONTH_NAMES[month - 1] ?? '',
        daysInMonth: jalaaliMonthLength(year, month),
        startWeekday: iranianWeekday(year, month, 1),
        weekdays: WEEKDAYS,
    };
}

export function formatJalaliFromGregorian(isoDate, withTime = false) {
    if (!isoDate) {
        return '';
    }

    const normalized = isoDate.includes('T') ? isoDate : isoDate.replace(' ', 'T');
    const date = new Date(normalized);

    if (Number.isNaN(date.getTime())) {
        return '';
    }

    const { jy, jm, jd } = toJalaali(date.getFullYear(), date.getMonth() + 1, date.getDate());
    const base = `${jy}/${String(jm).padStart(2, '0')}/${String(jd).padStart(2, '0')}`;

    if (!withTime) {
        return base;
    }

    const hh = String(date.getHours()).padStart(2, '0');
    const mm = String(date.getMinutes()).padStart(2, '0');

    return `${base} ${hh}:${mm}`;
}

export function gregorianDateFromJalali(year, month, day) {
    const { gy, gm, gd } = toGregorian(year, month, day);

    return `${gy}-${String(gm).padStart(2, '0')}-${String(gd).padStart(2, '0')}`;
}

export function gregorianFromJalali(year, month, day, hour = 0, minute = 0) {
    const { gy, gm, gd } = toGregorian(year, month, day);

    return `${gy}-${String(gm).padStart(2, '0')}-${String(gd).padStart(2, '0')} ${String(hour).padStart(2, '0')}:${String(minute).padStart(2, '0')}:00`;
}

export function parseJalaliInput(value, mode = 'date') {
    if (!value || typeof value !== 'string') {
        return '';
    }

    const trimmed = value.trim();

    if (/^\d{4}-\d{2}-\d{2}/.test(trimmed)) {
        return mode === 'datetime' ? trimmed.slice(0, 19).replace('T', ' ') : trimmed.slice(0, 10);
    }

    const [datePart, timePart = '00:00'] = trimmed.split(/\s+/);
    const parts = datePart.split(/[/-]/).map(Number);

    if (parts.length !== 3) {
        return '';
    }

    const [jy, jm, jd] = parts;

    if (jy < 1300) {
        return '';
    }

    if (mode === 'datetime') {
        const [hour, minute] = timePart.split(':').map((v) => parseInt(v, 10) || 0);

        return gregorianFromJalali(jy, jm, jd, hour, minute).slice(0, 19);
    }

    return gregorianDateFromJalali(jy, jm, jd);
}

export function formatJalali(value, withTime = false) {
    return formatJalaliFromGregorian(value, withTime);
}
