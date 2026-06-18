<?php

namespace Tests\Feature;

use App\Domain\Enums\ApplicationStatus;
use App\Domain\Enums\PersonLifecycleStatus;
use App\Models\Department;
use App\Models\EmploymentApplication;
use App\Models\Person;
use App\Models\User;
use App\Services\Person\PersonnelCsvImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonnelCsvImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_personnel_csv_import_creates_employees_with_mapped_form_data(): void
    {
        Department::factory()->create(['name' => 'کارخانه']);
        Department::factory()->create(['name' => 'اداری']);

        $path = base_path('tests/fixtures/personnel-wordpress-export.csv');
        $result = app(PersonnelCsvImportService::class)->importFromFile($path);

        $this->assertSame(3, $result['imported']);
        $this->assertSame(0, $result['updated']);
        $this->assertSame([], $result['errors']);

        $person = Person::query()->where('national_id', '0019689497')->first();

        $this->assertNotNull($person);
        $this->assertEquals(PersonLifecycleStatus::Employee, $person->lifecycle_status);
        $this->assertEquals('09120000001', $person->mobile);
        $this->assertEquals('علی', $person->first_name);
        $this->assertEquals('تستی', $person->last_name);
        $this->assertTrue($person->employmentRecords()->exists());

        $application = EmploymentApplication::query()
            ->where('person_id', $person->id)
            ->where('form_data->_import_source', 'personnel_csv')
            ->first();

        $this->assertNotNull($application);
        $this->assertEquals(ApplicationStatus::Draft, $application->status);
        $this->assertEquals('آقا', $application->form_data['gender']);
        $this->assertEquals('متأهل', $application->form_data['marital_status']);
        $this->assertEquals('کارشناسی', $application->form_data['education_level']);
        $this->assertEquals('اداری', $application->form_data['preferred_department']);
        $this->assertEquals('احمد', $application->form_data['father_name']);
        $this->assertEquals('1', $application->form_data['_import_source_user_id']);
        $this->assertArrayHasKey('_personnel_import_extra', $application->form_data);
    }

    public function test_personnel_csv_import_updates_existing_rows_on_reimport(): void
    {
        Department::factory()->create(['name' => 'کارخانه']);
        Department::factory()->create(['name' => 'اداری']);

        $path = base_path('tests/fixtures/personnel-wordpress-export.csv');
        $service = app(PersonnelCsvImportService::class);

        $service->importFromFile($path);
        $second = $service->importFromFile($path);

        $this->assertSame(0, $second['imported']);
        $this->assertSame(3, $second['updated']);
        $this->assertDatabaseCount('persons', 3);
        $this->assertDatabaseCount('employment_applications', 3);
    }

    public function test_hr_can_upload_personnel_csv_via_endpoint(): void
    {
        Department::factory()->create(['name' => 'کارخانه']);
        Department::factory()->create(['name' => 'اداری']);

        $hrUser = User::factory()->hr()->create();
        $path = base_path('tests/fixtures/personnel-wordpress-export.csv');

        $response = $this->actingAs($hrUser)
            ->post(route('admin.persons.import.store'), [
                'file' => new \Illuminate\Http\UploadedFile($path, 'personnel.csv', 'text/csv', null, true),
            ]);

        $response->assertOk();
        $response->assertJsonStructure(['import_id', 'total']);
        $this->assertSame(3, $response->json('total'));
    }
}
