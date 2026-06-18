<?php

namespace Tests\Unit;

use App\Helpers\JalaliHelper;
use App\Support\EmploymentFormFields;
use App\Support\IranianNationalId;
use App\Support\PersianDigits;
use PHPUnit\Framework\TestCase;

class IdentityNormalizationTest extends TestCase
{
    public function test_jalali_date_day_month_year_is_normalized(): void
    {
        $this->assertSame('1403/09/12', JalaliHelper::normalizeDateString('12/09/1403'));
        $this->assertSame('1375/02/02', JalaliHelper::normalizeDateString('02/02/1375'));
        $this->assertSame('1403/09/12', JalaliHelper::normalizeDateString('1403/09/12'));
    }

    public function test_persian_digits_are_converted_to_english(): void
    {
        $this->assertSame('0019689497', PersianDigits::toEnglish('۰۰۱۹۶۸۹۴۹۷'));
    }

    public function test_national_id_is_normalized_and_validated(): void
    {
        $this->assertSame('0019689497', IranianNationalId::normalize('19689497'));
        $this->assertTrue(IranianNationalId::isValid('0019689497'));
        $this->assertTrue(IranianNationalId::isValid('1234567891'));
        $this->assertFalse(IranianNationalId::isValid('1234567890'));
    }

    public function test_form_data_normalizes_birth_date_and_national_id(): void
    {
        $normalized = EmploymentFormFields::normalizeFormData([
            'birth_date' => '12/09/1403',
            'national_id' => '۰۰۱۹۶۸۹۴۹۷',
            'mobile' => '۰۹۱۲۰۰۰۰۰۱۱',
        ]);

        $this->assertSame('1403/09/12', $normalized['birth_date']);
        $this->assertSame('0019689497', $normalized['national_id']);
        $this->assertSame('09120000011', $normalized['mobile']);
    }

    public function test_gregorian_entry_datetime_is_converted_to_jalali_string(): void
    {
        $parsed = JalaliHelper::parseGregorianDateTime('2024-12-12 13:40:08');

        $this->assertNotNull($parsed);
        $this->assertSame(
            JalaliHelper::toDateTimeString($parsed),
            JalaliHelper::format($parsed, 'Y/m/d H:i'),
        );
        $this->assertStringStartsWith('1403/', JalaliHelper::toDateTimeString($parsed));
    }
}
