<?php

namespace Tests\Feature;

use App\Domain\Enums\DocumentType;
use App\Http\Middleware\EnsurePortalAuth;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_can_upload_document_for_person(): void
    {
        Storage::fake('local');

        $hrUser = User::factory()->hr()->create();
        $person = Person::factory()->employee()->create();

        $this->actingAs($hrUser)
            ->post(route('admin.documents.store'), [
                'person_id' => $person->id,
                'type' => DocumentType::Contract->value,
                'title' => 'قرارداد همکاری',
                'file' => UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf'),
                'redirect_tab' => 'documents',
            ])
            ->assertRedirect(route('admin.persons.show', ['person' => $person, 'tab' => 'documents']));

        $this->assertDatabaseHas('documents', [
            'person_id' => $person->id,
            'title' => 'قرارداد همکاری',
            'type' => DocumentType::Contract->value,
        ]);
    }

    public function test_employee_can_download_own_document_from_portal(): void
    {
        Storage::fake('local');

        $hrUser = User::factory()->hr()->create();
        $person = Person::factory()->employee()->create();

        $this->actingAs($hrUser)
            ->post(route('admin.documents.store'), [
                'person_id' => $person->id,
                'type' => DocumentType::Decree->value,
                'title' => 'حکم کارگزینی',
                'file' => UploadedFile::fake()->create('decree.pdf', 100, 'application/pdf'),
            ]);

        $document = $person->fresh()->documents()->first();
        $version = $document->latestVersion;

        $this->withSession([EnsurePortalAuth::SESSION_KEY => $person->id])
            ->get(route('portal.documents'))
            ->assertOk()
            ->assertSee('حکم کارگزینی', false);

        $this->withSession([EnsurePortalAuth::SESSION_KEY => $person->id])
            ->get(route('portal.documents.download', [$document, $version]))
            ->assertOk();
    }
}
