<?php

namespace App\Support;

use App\Helpers\JalaliHelper;

final class EmploymentFormFields
{
    /** @var array<int, string> */
    public const GRAVITY_ID_TO_KEY = [
        1 => 'first_name',
        3 => 'last_name',
        4 => 'father_name',
        5 => 'mother_name',
        6 => 'birth_date',
        36 => 'age',
        7 => 'birth_place',
        8 => 'national_id',
        9 => 'id_card_number',
        10 => 'profile_photo',
        11 => 'gender',
        12 => 'height_cm',
        13 => 'weight_kg',
        14 => 'military_service_status',
        15 => 'marital_status',
        16 => 'children_count',
        17 => 'child_custody',
        18 => 'mobile',
        19 => 'home_phone',
        20 => 'has_vehicle',
        21 => 'address',
        41 => 'family_members',
        22 => 'education_level',
        23 => 'education_history',
        45 => 'currently_studying',
        46 => 'study_employment_status',
        24 => 'has_work_experience',
        25 => 'work_experience',
        39 => 'currently_employed',
        40 => 'biggest_career_challenge',
        26 => 'technical_skills',
        44 => 'preferred_department',
        37 => 'strengths',
        38 => 'weaknesses',
        27 => 'medical_conditions',
        28 => 'medical_condition_other',
        29 => 'had_surgery',
        30 => 'surgery_details',
        31 => 'has_insurance_history',
        32 => 'insurance_years',
        33 => 'smoking',
        34 => 'knows_company_employee',
        35 => 'company_employee_name',
    ];

    /** @var array<string, list<array{key: string, label: string}>> */
    public const LIST_COLUMNS = [
        'family_members' => [
            ['key' => 'relation', 'label' => 'نسبت'],
            ['key' => 'full_name', 'label' => 'نام و نام خانوادگی'],
            ['key' => 'phone', 'label' => 'شماره تماس'],
            ['key' => 'gender', 'label' => 'شکل'],
            ['key' => 'education_level', 'label' => 'میزان تحصیلات'],
        ],
        'education_history' => [
            ['key' => 'major', 'label' => 'رشته تحصیلی'],
            ['key' => 'university', 'label' => 'نام دانشگاه'],
            ['key' => 'gpa', 'label' => 'معدل'],
            ['key' => 'graduation_year', 'label' => 'سال فارغ‌التحصیلی'],
        ],
        'study_employment_status' => [
            ['key' => 'current_term', 'label' => 'ترم چندم هستید'],
            ['key' => 'major', 'label' => 'چه رشته تحصیلی'],
        ],
        'work_experience' => [
            ['key' => 'company_name', 'label' => 'نام شرکت/موسسه'],
            ['key' => 'business_type', 'label' => 'نوع فعالیت شرکت/موسسه'],
            ['key' => 'position', 'label' => 'سمت و عنوان شغلی شما'],
            ['key' => 'duration_years', 'label' => 'مدت فعالیت (سال)'],
            ['key' => 'company_phone', 'label' => 'شماره تماس شرکت/موسسه'],
            ['key' => 'leave_reason', 'label' => 'علت ترک‌کار'],
            ['key' => 'last_salary', 'label' => 'آخرین حقوق دریافتی (تومان)'],
        ],
        'technical_skills' => [
            ['key' => 'skill', 'label' => 'مهارت'],
        ],
        'strengths' => [
            ['key' => 'item', 'label' => 'نقطه قوت'],
        ],
        'weaknesses' => [
            ['key' => 'item', 'label' => 'نقطه ضعف'],
        ],
    ];

    public static function keyForGravityId(int $gravityFieldId): string
    {
        return self::GRAVITY_ID_TO_KEY[$gravityFieldId] ?? 'field_'.$gravityFieldId;
    }

    /** @return array<string, string> */
    public static function legacyKeyMap(): array
    {
        $map = [];

        foreach (self::GRAVITY_ID_TO_KEY as $id => $key) {
            $map['gf_'.$id] = $key;
        }

        return $map;
    }

    public static function normalizeFormData(array $formData): array
    {
        $normalized = [];

        foreach ($formData as $key => $value) {
            if (str_starts_with((string) $key, 'gf_')) {
                $id = (int) substr((string) $key, 3);
                $normalized[self::keyForGravityId($id)] = self::normalizeListRows($id, $value);
            } else {
                $normalized[$key] = $value;
            }
        }

        return self::normalizeIdentityFields($normalized);
    }

    /** @param array<string, mixed> $data */
    public static function normalizeIdentityFields(array $data): array
    {
        if (array_key_exists('birth_date', $data) && filled($data['birth_date'])) {
            $data['birth_date'] = JalaliHelper::normalizeDateString($data['birth_date']);
        }

        if (array_key_exists('national_id', $data) && filled($data['national_id'])) {
            $data['national_id'] = IranianNationalId::normalize($data['national_id']);
        }

        if (array_key_exists('id_card_number', $data) && filled($data['id_card_number'])) {
            $digits = preg_replace('/\D+/', '', PersianDigits::toEnglish((string) $data['id_card_number']));
            $data['id_card_number'] = $digits !== '' ? $digits : $data['id_card_number'];
        }

        if (array_key_exists('mobile', $data) && filled($data['mobile'])) {
            $data['mobile'] = preg_replace('/\D+/', '', PersianDigits::toEnglish((string) $data['mobile']));
        }

        return $data;
    }

    private static function normalizeListRows(int $gravityFieldId, mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        $fieldKey = self::keyForGravityId($gravityFieldId);
        $columns = self::LIST_COLUMNS[$fieldKey] ?? null;

        if (! $columns) {
            return $value;
        }

        return collect($value)
            ->map(function ($row) use ($columns) {
                if (! is_array($row)) {
                    return $row;
                }

                $normalizedRow = [];

                foreach ($columns as $index => $column) {
                    $legacyKey = 'col_'.$index;
                    $normalizedRow[$column['key']] = $row[$legacyKey] ?? $row[$column['key']] ?? null;
                }

                return $normalizedRow;
            })
            ->values()
            ->all();
    }
}
