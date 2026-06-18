<?php

namespace Tests\Feature;

use App\Models\Person;
use App\Models\User;
use App\Services\Recruitment\GravityFormsCsvImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonnelRosterTest extends TestCase
{
    use RefreshDatabase;

    public function test_personnel_index_excludes_applicants_by_default(): void
    {
        $hrUser = User::factory()->hr()->create();
        $applicant = Person::factory()->applicant()->create(['first_name' => 'متقاضی', 'last_name' => 'وارداتی']);
        $employee = Person::factory()->employee()->create(['first_name' => 'کارمند', 'last_name' => 'فعال']);

        $response = $this->actingAs($hrUser)
            ->withoutVite()
            ->get(route('admin.persons.index'));

        $response->assertOk()
            ->assertSee('کارمند فعال')
            ->assertDontSee('متقاضی وارداتی');
    }

    public function test_csv_imported_applicants_do_not_appear_in_personnel_index(): void
    {
        $hrUser = User::factory()->hr()->create();
        $path = base_path('tests/fixtures/gravity-resume-sample.csv');

        app(GravityFormsCsvImportService::class)->importFromFile($path);

        $this->actingAs($hrUser)
            ->withoutVite()
            ->get(route('admin.persons.index'))
            ->assertOk()
            ->assertDontSee('علی آزمایش')
            ->assertDontSee('سارا نمونه');
    }

    public function test_former_employees_appear_in_personnel_index(): void
    {
        $hrUser = User::factory()->hr()->create();
        Person::factory()->formerEmployee()->create(['first_name' => 'سابق', 'last_name' => 'شرکت']);

        $this->actingAs($hrUser)
            ->withoutVite()
            ->get(route('admin.persons.index'))
            ->assertOk()
            ->assertSee('سابق شرکت');
    }
}
