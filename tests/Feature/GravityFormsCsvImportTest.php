<?php

namespace Tests\Feature;

use App\Domain\Enums\ApplicationStatus;
use App\Helpers\JalaliHelper;
use App\Models\EmploymentApplication;
use App\Models\Person;
use App\Services\Recruitment\GravityFormsCsvImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GravityFormsCsvImportTest extends TestCase
{
    use RefreshDatabase;

    private const CSV_HEADER = '"تاریخ ورودی",نام,"نام خانوادگی","نام پدر","نام مادر","تاریخ تولد",سن,"محل تولد","کد ملی","شماره شناسنامه","تصویر پروفایل",جنسیت,"قد (سانتی‌متر)","وزن (کیلوگرم)","وضعیت نظام وظیفه","وضعیت تأهل","تعداد فرزند","درصورت متارکه، سرپرستی فرزند:","تلفن همراه:","تلفن محل سکونت","آیا وسیله نقلیه دارید؟","آدرس محل سکونت",نسبت,"نام و نام خانوادگی","شماره تماس",شغل,"میزان تحصیلات","میزان تحصیلات","رشته تحصیلی","نام دانشگاه",معدل,"سال فارغ‌التخصیلی","همچنان درحال تحصیل می‌باشید؟","ترم چندم هستید","چه رشته تحصیلی","آیا سابقه کاری دارید؟","نام شرکت/موسسه","نوع فعالیت شرکت/موسسه","سمت و عنوان شغلی شما","مدت فعالیت (سال)","شماره تماس شرکت/موسسه","علت ترک‌کار","آخرین حقوق دریافتی (تومان)","اکنون مشغول به کار هستید؟","بزرگترین چالشی که با آن مواجه شدید چه بوده و چگونه با آن کنار آمدید؟","مهارت‌های فنی و تخصصی که دارید رو وارد کنید:","تمایل دارید در کدام بخش از مجموعه شیرینی لیلی فعالیت داشته باشید؟","نقاط قوت خود را نام ببرید:","نقاط ضعف خود را نام ببرید","آیا بیماری خاصی داری؟","عنوان بیماری خود را بنویسید","آیا طی ۵ سال اخیر عمل جراحی داشته‌اید؟","عنوان عمل جراحی که داشته‌اید را بنویسید","آیا سابقه بیمه تأمین اجتماعی دارید؟","چند سال سابقه بیمه دارید؟","استعمال دخانیات","از پرسنل سابق یا فعلی شیرینی لیلی کسی را می‌شناسید؟","نام و نام خانوادگی"';

    public function test_csv_import_creates_submitted_applications(): void
    {
        $path = base_path('tests/fixtures/gravity-resume-sample.csv');

        $result = app(GravityFormsCsvImportService::class)->importFromFile($path);

        $this->assertSame(2, $result['imported']);
        $this->assertSame(0, $result['skipped']);
        $this->assertSame([], $result['errors']);

        $this->assertDatabaseCount('employment_applications', 2);

        $application = EmploymentApplication::query()
            ->where('contact_mobile', '09120000011')
            ->first();

        $this->assertNotNull($application);
        $this->assertEquals(ApplicationStatus::Submitted, $application->status);
        $this->assertEquals('علی', $application->form_data['first_name']);
        $this->assertEquals('آزمایش', $application->form_data['last_name']);
        $this->assertEquals('کارشناسی', $application->form_data['education_level']);
        $this->assertEquals('1375/02/02', $application->form_data['birth_date']);
        $this->assertEquals(JalaliHelper::parseDate('1375/02/02')?->age, $application->form_data['age']);
        $this->assertEquals(
            JalaliHelper::toDateTimeString(\Carbon\Carbon::parse('2024-12-12 13:40:08')),
            $application->form_data['entry_date'],
        );
        $this->assertNotNull($application->submitted_at);
    }

    public function test_csv_import_updates_existing_rows_on_reimport(): void
    {
        $path = base_path('tests/fixtures/gravity-resume-sample.csv');
        $service = app(GravityFormsCsvImportService::class);

        $service->importFromFile($path);
        $second = $service->importFromFile($path);

        $this->assertSame(0, $second['imported']);
        $this->assertSame(2, $second['updated']);
        $this->assertSame(0, $second['skipped']);
        $this->assertDatabaseCount('employment_applications', 2);
    }

    public function test_repair_imported_applications_fixes_reversed_birth_date(): void
    {
        $path = base_path('tests/fixtures/gravity-resume-sample.csv');
        $service = app(GravityFormsCsvImportService::class);
        $service->importFromFile($path);

        $application = EmploymentApplication::query()
            ->where('contact_mobile', '09120000011')
            ->firstOrFail();

        $application->update([
            'form_data' => array_merge($application->form_data, [
                'birth_date' => '02/02/1375',
                'national_id' => '۰۰۱۹۶۸۹۴۹۷',
            ]),
        ]);

        $result = $service->repairImportedApplications();

        $this->assertSame(2, $result['fixed']);
        $application->refresh();

        $this->assertEquals('1375/02/02', $application->form_data['birth_date']);
        $this->assertEquals('0019689497', $application->form_data['national_id']);
        $this->assertEquals(JalaliHelper::parseDate('1375/02/02')?->age, $application->form_data['age']);
    }

    public function test_csv_import_allows_shared_mobile_for_different_national_ids(): void
    {
        $path = $this->writeImportCsv([
            $this->importRow('2024-12-12 13:40:08', 'علی', 'احمدی', '0019689497', '09110000001'),
            $this->importRow('2025-01-08 12:17:21', 'رضا', 'محمدی', '1234567891', '09110000001'),
        ]);

        $result = app(GravityFormsCsvImportService::class)->importFromFile($path);

        $this->assertSame(2, $result['imported']);
        $this->assertSame([], $result['errors']);
        $this->assertDatabaseCount('persons', 2);
        $this->assertDatabaseCount('employment_applications', 2);

        $persons = Person::query()->where('managed_by_mobile', '09110000001')->get();
        $this->assertCount(2, $persons);
        $this->assertCount(2, $persons->pluck('mobile')->unique()->values()->all());
        $this->assertTrue($persons->every(fn (Person $person) => $person->managed_by_mobile === '09110000001'));
    }

    public function test_csv_import_creates_multiple_applications_for_same_national_id(): void
    {
        $path = $this->writeImportCsv([
            $this->importRow('2024-12-12 13:40:08', 'علی', 'آزمایش', '0019689497', '09120000011'),
            $this->importRow('2025-06-01 10:00:00', 'علی', 'آزمایش', '0019689497', '09120000011'),
        ]);

        $result = app(GravityFormsCsvImportService::class)->importFromFile($path);

        $this->assertSame(2, $result['imported']);
        $this->assertSame([], $result['errors']);
        $this->assertDatabaseCount('persons', 1);
        $this->assertDatabaseCount('employment_applications', 2);
    }

    /** @param list<string> $rows */
    private function writeImportCsv(array $rows): string
    {
        $path = storage_path('framework/testing/gravity-import-'.uniqid().'.csv');
        $content = self::CSV_HEADER."\n".implode("\n", $rows);
        file_put_contents($path, $content);

        return $path;
    }

    private function importRow(
        string $submittedAt,
        string $firstName,
        string $lastName,
        string $nationalId,
        string $mobile,
    ): string {
        $cells = array_fill(0, 58, '');
        $cells[0] = $submittedAt;
        $cells[1] = $firstName;
        $cells[2] = $lastName;
        $cells[8] = $nationalId;
        $cells[18] = $mobile;

        return collect($cells)
            ->map(fn (string $value) => str_contains($value, ',') ? '"'.$value.'"' : $value)
            ->implode(',');
    }
}
