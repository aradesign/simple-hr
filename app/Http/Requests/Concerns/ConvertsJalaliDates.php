<?php

namespace App\Http\Requests\Concerns;

use Morilog\Jalali\Jalalian;

trait ConvertsJalaliDates
{
    protected function convertJalaliDate(?string $jalaliDate): ?string
    {
        if (blank($jalaliDate)) {
            return null;
        }

        try {
            return Jalalian::fromFormat('Y/m/d', $jalaliDate)->toCarbon()->toDateString();
        } catch (\Exception) {
            return $jalaliDate;
        }
    }

    protected function convertJalaliDateTime(?string $jalaliDateTime): ?string
    {
        if (blank($jalaliDateTime)) {
            return null;
        }

        foreach (['Y/m/d H:i', 'Y/m/d'] as $format) {
            try {
                $carbon = Jalalian::fromFormat($format, $jalaliDateTime)->toCarbon();

                return $format === 'Y/m/d'
                    ? $carbon->startOfDay()->toDateTimeString()
                    : $carbon->toDateTimeString();
            } catch (\Exception) {
                continue;
            }
        }

        return $jalaliDateTime;
    }

    protected function convertJalaliFields(array $fields): void
    {
        $converted = [];

        foreach ($fields as $field) {
            if ($this->has($field)) {
                $converted[$field] = $this->convertJalaliDate($this->input($field));
            }
        }

        if ($converted !== []) {
            $this->merge($converted);
        }
    }

    protected function convertJalaliDateTimeFields(array $fields): void
    {
        $converted = [];

        foreach ($fields as $field) {
            if ($this->has($field)) {
                $converted[$field] = $this->convertJalaliDateTime($this->input($field));
            }
        }

        if ($converted !== []) {
            $this->merge($converted);
        }
    }
}
