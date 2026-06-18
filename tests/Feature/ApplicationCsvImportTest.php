<?php

namespace Tests\Feature;

use App\Models\EmploymentApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ApplicationCsvImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_user_can_import_applications_via_upload_api(): void
    {
        $hrUser = User::factory()->hr()->create();
        $fixture = base_path('tests/fixtures/gravity-resume-sample.csv');

        $upload = $this->actingAs($hrUser)
            ->postJson(route('admin.applications.import.store'), [
                'file' => new UploadedFile($fixture, 'sample.csv', 'text/csv', null, true),
            ]);

        $upload->assertOk()
            ->assertJsonPath('total', 2);

        $importId = $upload->json('import_id');

        $processed = 0;

        do {
            $response = $this->actingAs($hrUser)
                ->postJson(route('admin.applications.import.process', ['importId' => $importId]));

            $response->assertOk();
            $processed = $response->json('processed');
            $completed = $response->json('completed');
        } while (! $completed);

        $response->assertJsonPath('imported', 2)
            ->assertJsonPath('skipped', 0)
            ->assertJsonPath('percent', 100);

        $this->assertDatabaseCount('employment_applications', 2);

        $application = EmploymentApplication::query()
            ->where('contact_mobile', '09120000011')
            ->first();

        $this->assertNotNull($application);
        $this->assertEquals('1375/02/02', $application->form_data['birth_date']);
    }

    public function test_applications_index_shows_csv_import_button(): void
    {
        $hrUser = User::factory()->hr()->create();

        $this->actingAs($hrUser)
            ->withoutVite()
            ->get(route('admin.applications.index'))
            ->assertOk()
            ->assertSee('بارگذاری CSV');
    }

    public function test_guest_cannot_upload_csv(): void
    {
        $fixture = base_path('tests/fixtures/gravity-resume-sample.csv');

        $this->post(route('admin.applications.import.store'), [
            'file' => new UploadedFile($fixture, 'sample.csv', 'text/csv', null, true),
        ])->assertRedirect(route('admin.login'));
    }
}
