<?php

namespace App\Helpers;

use App\Support\PersianDigits;
use Carbon\Carbon;
use Morilog\Jalali\Jalalian;

class JalaliHelper
{
    public static function format(Carbon $date, string $format = 'Y/m/d'): string
    {
        return Jalalian::fromCarbon($date)->format($format);
    }

    public static function now(): Jalalian
    {
        return Jalalian::now();
    }

    public static function toInputDate(?Carbon $date): string
    {
        if (! $date) {
            return '';
        }

        return self::format($date, 'Y/m/d');
    }

    public static function toInputDateTime(?Carbon $date): array
    {
        if (! $date) {
            return ['date' => '', 'time' => ''];
        }

        return [
            'date' => self::format($date, 'Y/m/d'),
            'time' => $date->format('H:i'),
        ];
    }

    public static function toDateTimeString(?Carbon $date, string $format = 'Y/m/d H:i'): ?string
    {
        if (! $date) {
            return null;
        }

        return self::format($date, $format);
    }

    public static function parseGregorianDateTime(?string $value): ?Carbon
    {
        if ($value === null) {
            return null;
        }

        $value = trim(PersianDigits::toEnglish($value));

        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    public static function parseDate(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        $value = self::normalizeDateString($value);

        if (! $value) {
            return null;
        }

        if (preg_match('/^\d{4}[\/\-]\d{1,2}[\/\-]\d{1,2}$/', $value)) {
            $normalized = str_replace('-', '/', $value);
            $parts = explode('/', $normalized);
            $year = (int) $parts[0];

            if ($year < 1500) {
                return Jalalian::fromFormat('Y/m/d', $normalized)->toCarbon()->startOfDay();
            }

            return Carbon::createFromFormat('Y-m-d', str_replace('/', '-', $normalized))->startOfDay();
        }

        return Carbon::parse($value)->startOfDay();
    }

    public static function normalizeDateString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim(PersianDigits::toEnglish((string) $value));

        if ($value === '') {
            return null;
        }

        $value = str_replace('-', '/', $value);

        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $value, $matches)) {
            $first = (int) $matches[1];
            $second = (int) $matches[2];
            $third = (int) $matches[3];

            if ($third >= 1200 && $third <= 1500) {
                return sprintf('%04d/%02d/%02d', $third, $second, $first);
            }

            if ($first >= 1200 && $first <= 1500) {
                return sprintf('%04d/%02d/%02d', $first, $second, $third);
            }
        }

        if (preg_match('/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/', $value, $matches)) {
            $year = (int) $matches[1];

            if ($year >= 1200 && $year <= 1500) {
                return sprintf('%04d/%02d/%02d', $year, (int) $matches[2], (int) $matches[3]);
            }
        }

        return $value;
    }

    public static function parseDateTime(?string $date, ?string $time = null): ?Carbon
    {
        $carbon = self::parseDate($date);

        if (! $carbon) {
            return null;
        }

        if ($time) {
            [$hour, $minute] = array_pad(explode(':', $time), 2, '0');
            $carbon->setTime((int) $hour, (int) $minute, 0);
        }

        return $carbon;
    }

    /** @return array{0: Carbon, 1: Carbon} */
    public static function monthRange(int $jalaliYear, int $jalaliMonth): array
    {
        $start = (new Jalalian($jalaliYear, $jalaliMonth, 1))->toCarbon()->startOfDay();
        $days = (new Jalalian($jalaliYear, $jalaliMonth, 1))->getMonthDays();
        $end = (new Jalalian($jalaliYear, $jalaliMonth, $days))->toCarbon()->endOfDay();

        return [$start, $end];
    }

    public static function monthMeta(int $jalaliYear, int $jalaliMonth): array
    {
        $first = new Jalalian($jalaliYear, $jalaliMonth, 1);

        return [
            'year' => $jalaliYear,
            'month' => $jalaliMonth,
            'month_name' => $first->format('F'),
            'days_in_month' => $first->getMonthDays(),
            'first_day_of_week' => $first->getDayOfWeek(),
        ];
    }
}
